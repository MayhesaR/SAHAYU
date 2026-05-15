<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductSold implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public int $productId,
        public int $qtyDeducted,
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('stock-updates'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'product_id' => $this->productId,
            'qty_deducted' => $this->qtyDeducted,
        ];
    }
}
