<?php

namespace App\Services;

php
namespace App\Services;

use App\Enums\BookingStatus;
use App\Events\TicketPurchasedEvent;
use App\Models\Booking;
use App\Models\TicketType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function createTemporaryBooking(array $data, int $userId): Booking
    {
        return DB::transaction(function () use ($data, $userId) {
            $ticketType = TicketType::lockForUpdate()->findOrFail($data['ticket_type_id']);

            // التحقق من توفر المقاعد
            if ($ticketType->available_quantity < $data['quantity']) {
                throw ValidationException::withMessages([
                    'quantity' => ['المقاعد المطلوبة غير متوفرة في الوقت الحالي.'],
                ]);
            }

            // خصم مؤقت للمقاعد
            $ticketType->decrement('available_quantity', $data['quantity']);

            // إنشاء الحجز بحالة pending (مؤقت)
            $booking = Booking::create([
                'user_id' => $userId,
                'event_id' => $ticketType->event_id,
                'ticket_type_id' => $ticketType->id,
                'quantity' => $data['quantity'],
                'total_price' => $ticketType->price * $data['quantity'],
                'status' => BookingStatus::PENDING,
            ]);

            // حفظ مؤشر في Redis ينتهي خلال 10 دقائق لتتبع انتهاء المهلة بدون Cron Job
            Cache::put('temporary_booking_' . $booking->id, true, now()->addMinutes(10));

            return $booking;
        });
    }

    public function confirmPayment(int $bookingId): Booking
    {
        $booking = Booking::findOrFail($bookingId);

        if ($booking->status !== BookingStatus::PENDING) {
            throw ValidationException::withMessages([
                'booking' => ['هذا الحجز غير صالح أو تم إمراؤه مسبقاً.'],
            ]);
        }

        // التحقق إن انتهت مدة Redis المؤقتة
        if (!Cache::has('temporary_booking_' . $booking->id)) {
            $booking->update(['status' => BookingStatus::EXPIRED]);
            // إرجاع المقاعد المخصومة
            $booking->ticketType()->increment('available_quantity', $booking->quantity);
            
            throw ValidationException::withMessages([
                'booking' => ['انتهت مهلة الدفع وتم إلغاء الحجز المؤقت.'],
            ]);
        }

        // تحديث الحجز إلى مدفوع
        $booking->update(['status' => BookingStatus::PAID]);
        Cache::forget('temporary_booking_' . $booking->id);

        // إطلاق الحدث (Event) لتوزيع المهام على الطوابير بالخلفية
        event(new TicketPurchasedEvent($booking));

        return $booking;
    }
}