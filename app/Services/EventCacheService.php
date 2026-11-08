<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class EventCacheService
{
    public function getNearbyEvents()
    {
        return Cache::tags(['events'])->remember('nearby_events', 3600, function () {
            return \App\Models\Event::where('status', \App\Enums\EventStatus::PUBLISHED)
                ->orderBy('date', 'asc')
                ->take(10)
                ->get();
        });
    }

    public function getRemainingTickets(int $ticketTypeId): int
    {
        return Cache::tags(['tickets', 'ticket_' . $ticketTypeId])->remember('remaining_tickets_' . $ticketTypeId, 600, function () use ($ticketTypeId) {
            return \App\Models\TicketType::where('id', $ticketTypeId)->value('available_quantity') ?? 0;
        });
    }

    public function clearEventCaches(): void
    {
        Cache::tags(['events', 'tickets'])->flush();
    }
}