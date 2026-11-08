<?php

namespace App\Jobs;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendBulkAnnouncementJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $backoff = 120; // انتظار دقيقتين بين المحاولات الفاشلة

    protected Event $event;
    protected string $announcementMessage;

    public function __construct(Event $event, string $announcementMessage)
    {
        $this->event = $event;
        $this->announcementMessage = $announcementMessage;
    }

    public function handle(): void
    {
        // استخدام chunk لمعالجة حاملي التذاكر (مثلاً 500 حجز دفعة واحدة) بدون تحميل الذاكرة مرة واحدة
        $this->event->bookings()->with('user')->chunk(500, function ($bookings) {
            foreach ($bookings as $booking) {
                if ($booking->user && $booking->user->email) {
                    // محاكاة إرسال الإشعار أو الإيميل الجماعي للمستخدم
                    // Mail::to($booking->user->email)->send(new BulkMail($this->announcementMessage));
                }
            }
        });
    }
}