<?php

namespace App\Events;

use App\Models\Campaign;
use App\Models\Shop;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShopOpened implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Shop $shop) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $channels = $this->shop->campaigns->map(
            fn (Campaign $campaign) => new PrivateChannel("campaign.{$campaign->id}.shops")
        )->all();

        $channels[] = new PrivateChannel("shop.{$this->shop->id}");

        return $channels;
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'shopId' => $this->shop->id,
            'status' => $this->shop->status,
        ];
    }
}
