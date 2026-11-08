<?php

namespace App\Listeners;

use App\Events\TicketPurchasedEvent;
use App\Jobs\GenerateTicketPdfJob;
use App\Jobs\SendTicketEmailJob;
use App\Jobs\SendTicketSmsJob;
use App\Jobs\NotifyOrganizerJob;
use App\Jobs\UpdateSalesAnalyticsJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class DispatchTicketJobsListener implements ShouldQueue
{
    public function handle(TicketPurchasedEvent $event): void
    {
        $booking = $event->booking;

        // توزيع المهام على الطوابير (Queues) حسب الأولوية المبرمجة
        GenerateTicketPdfJob::dispatch($booking)->onQueue('high');
        SendTicketEmailJob::dispatch($booking)->onQueue('high');
        SendTicketSmsJob::dispatch($booking)->onQueue('default');
        NotifyOrganizerJob::dispatch($booking)->onQueue('low');
        UpdateSalesAnalyticsJob::dispatch($booking)->onQueue('low');
    }
}