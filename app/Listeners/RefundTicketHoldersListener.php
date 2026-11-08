<?php

namespace App\Listeners;

use App\Events\EventCancelledEvent;
use Illuminate\Contracts\Queue\ShouldQueue;

class RefundTicketHoldersListener implements ShouldQueue
{
    public function handle(EventCancelledEvent $event): void
    {
        // منطق تغيير حالة الحجوزات إلى "مستردة" (Refunded) ورد الأموال افتراضياً
        $event->event->bookings()->update(['status' => \App\Enums\BookingStatus::REFUNDED]);
    }
}