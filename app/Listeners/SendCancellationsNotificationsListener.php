<?php

namespace App\Listeners;

use App\Events\EventCancelledEvent;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendCancellationsNotificationsListener implements ShouldQueue
{
    public function handle(EventCancelledEvent $event): void
    {
        // إرسال إيميلات و SMS لحاملي التذاكر بأن الفعالية قد ألغيت
    }
}