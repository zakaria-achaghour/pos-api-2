<?php

namespace App\Models;

use App\Models\Traits\SetsRestaurant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Staff extends Model
{
    use HasFactory, SetsRestaurant;

    protected $fillable = [
        'restaurant_id',
        'user_id',
        'employee_id',
        'first_name',
        'last_name',
        'phone',
        'position',
        'hourly_rate',
        'photo_path',
        'status',
        'hire_date',
        'termination_date',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'termination_date' => 'date',
        'hourly_rate' => 'decimal:2',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function assignedOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'waiter_id');
    }

    public function kitchenTickets(): HasMany
    {
        return $this->hasMany(KitchenTicket::class, 'assigned_chef_id');
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getTotalHoursWorkedAttribute(): float
    {
        return $this->attendances()
            ->whereNotNull('clock_out')
            ->sum('hours_worked') ?? 0;
    }
}