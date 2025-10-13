<?php
// filepath: app/Events/StaffClockedIn.php

namespace App\Events;

use App\Models\Attendance;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StaffClockedIn implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Attendance $attendance) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("restaurant.{$this->attendance->restaurant_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'staff.clocked.in';
    }

    public function broadcastWith(): array
    {
        return [
            'staff_id' => $this->attendance->staff_id,
            'staff_name' => $this->attendance->staff->full_name,
            'clock_in' => $this->attendance->clock_in->toISOString(),
            'timestamp' => now()->toISOString(),
        ];
    }
}