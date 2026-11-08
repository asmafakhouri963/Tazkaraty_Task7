<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait Loggable
{
    public function logInfo(string $message, array $context = []): void
    {
        Log::info("[" . static::class . "] " . $message, $context);
    }

    public function logError(string $message, array $context = []): void
    {
        Log::error("[" . static::class . "] " . $message, $context);
    }
}