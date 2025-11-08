<?php
// filepath: app/Models/OrderItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'menu_item_id',
        'quantity',
        'unit_price',
        'special_instructions',
        'removed_ingredients',
        'added_extras',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'removed_ingredients' => 'array',
        'added_extras' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function getTotalAttribute(): float
    {
        return $this->quantity * $this->unit_price;
    }
}