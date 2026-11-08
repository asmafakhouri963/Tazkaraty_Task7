<?php

namespace App\Listeners;

use App\Events\TicketPurchasedEvent;
use App\Events\EventCancelledEvent;
use App\Services\EventCacheService;

class ClearEventCacheListener
{
    protected EventCacheService $cacheService;

    public function __construct(EventCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    public function handle($event): void
    {
        // تفريغ الكاش تلقائياً بغض النظر عن الحدث (شراء أو إلغاء)
        $this->cacheService->clearEventCaches();
    }
}