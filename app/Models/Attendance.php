<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasUserTracking;
use App\Models\Traits\SetsRestaurant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory, BelongsToTenant, SetsRestaurant, HasUserTracking;

    protected $fillable = [
        'restaurant_id',
        'staff_id',
        'clock_in',
        'clock_out',
        'break_minutes',
        'hours_worked',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'hours_worked' => 'decimal:2',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function calculateHoursWorked(): void
    {
        if ($this->clock_in && $this->clock_out) {
            $totalMinutes = $this->clock_in->diffInMinutes($this->clock_out);
            $workMinutes = $totalMinutes - $this->break_minutes;
            $this->hours_worked = round($workMinutes / 60, 2);
            $this->save();
        }
    }

    public function isActive(): bool
    {
        return $this->clock_in && !$this->clock_out;
    }
}