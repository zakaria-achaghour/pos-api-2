<?php

namespace App\Models;

use App\Models\Traits\SetsRestaurant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KitchenTicket extends Model
{
    use HasFactory, SetsRestaurant;

    protected $fillable = [
        'restaurant_id',
        'order_id',
        'ticket_number',
        'priority',
        'status',
        'assigned_chef_id',
        'cooking_station',
        'started_at',
        'completed_at',
        'bumped_at',
        'preparation_time',
        'special_instructions',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'bumped_at' => 'datetime',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function assignedChef(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'assigned_chef_id');
    }

    public function markAsStarted(): void
    {
        $this->update([
            'status' => 'preparing',
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'ready',
            'completed_at' => now(),
            'preparation_time' => $this->started_at ? 
                (int) $this->started_at->diffInMinutes(now()) : null,
        ]);
    }

    public function markAsServed(): void
    {
        $this->update([
            'status' => 'served',
            'bumped_at' => now(),
        ]);
    }
}
