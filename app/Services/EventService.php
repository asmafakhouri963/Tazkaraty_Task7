<?php

namespace App\Services;

php
namespace App\Services;

use App\Enums\EventStatus;
use App\Events\EventCancelledEvent;
use App\Models\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EventService
{
    public function cancelEvent(int $eventId, int $userId): Event
    {
        return DB::transaction(function () use ($eventId, $userId) {
            $event = Event::lockForUpdate()->findOrFail($eventId);

            // التحقق من الصلاحيات (المنظم يدير فعالياته فقط)
            if ($event->organizer_id !== $userId) {
                throw ValidationException::withMessages([
                    'authorization' => ['غير مصرح لك بإلغاء هذه الفعالية.'],
                ]);
            }

            if ($event->status === EventStatus::CANCELLED) {
                throw ValidationException::withMessages([
                    'status' => ['الفعالية ملغاة مسبقاً.'],
                ]);
            }

            // تحديث حالة الفعالية
            $event->update(['status' => EventStatus::CANCELLED]);

            // إطلاق حدث إلغاء الفعالية (الحدث سيقوم بتشغيل كل الـ Listeners الخاصة برد الأموال والإشعارات بالخلفية)
            event(new EventCancelledEvent($event));

            return $event;
        });
    }
}