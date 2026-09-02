<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Dispatch an event safely, catching any exceptions (e.g., broadcast connection errors).
     */
    protected function dispatchEvent(mixed $event): void
    {
        try {
            event($event);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to broadcast event ' . get_class($event) . ': ' . $e->getMessage());
        }
    }
}
