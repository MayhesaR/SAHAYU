<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SalesAnalyticsUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public float $totalSales,
        public int $totalTransactions,
        public float $totalRevenue,
        public array $topProducts = [],
        public ?array $latestSale = null,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [new Channel('sales-analytics')];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'total_sales' => $this->totalSales,
            'total_transactions' => $this->totalTransactions,
            'total_revenue' => $this->totalRevenue,
            'top_products' => $this->topProducts,
            'latest_sale' => $this->latestSale,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
