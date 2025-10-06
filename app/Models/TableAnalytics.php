<?php

namespace App\Models;

use App\Models\Traits\SetsRestaurant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TableAnalytics extends Model
{
    use HasFactory, SetsRestaurant;

    protected $fillable = [
        'restaurant_id',
        'table_id',
        'date',
        'total_seatings',
        'total_revenue',
        'total_duration_minutes',
        'average_duration_minutes',
        'occupancy_rate',
    ];

    protected $casts = [
        'date' => 'date',
        'total_revenue' => 'decimal:2',
        'occupancy_rate' => 'decimal:2',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }
}