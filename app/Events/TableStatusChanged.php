<?php
// filepath: app/Events/TableStatusChanged.php

namespace App\Events;

use App\Models\Table;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TableStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Table $table,
        public string $oldStatus,
        public string $newStatus
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("restaurant.{$this->table->restaurant_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'table.status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'table_id' => $this->table->id,
            'table_number' => $this->table->number,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'timestamp' => now()->toISOString(),
        ];
    }
}