<?php

namespace App\Models;

use App\Models\Traits\SetsRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Table extends Model
{
    use SetsRestaurant, SoftDeletes;

    protected $fillable = [
        'restaurant_id', 
        'number', 
        'capacity', 
        'status', 
        'section', 
        'shape', 
        'grid_x', 
        'grid_y', 
        'qr_code',
        'location',
        'features'
    ];

    protected $casts = [
        'location' => 'array',
        'features' => 'array',
        'capacity' => 'integer',
        'grid_x' => 'integer',
        'grid_y' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        // Generate QR code automatically when creating a new table
        static::creating(function ($table) {
            if (empty($table->qr_code)) {
                $table->qr_code = 'TBL-' . strtoupper(Str::random(8));
            }
        });

        // Sync location.section with section field
        static::saving(function ($table) {
            if (is_array($table->location) && isset($table->location['section'])) {
                $table->section = $table->location['section'];
            }
        });
    }

    public function restaurant() 
    { 
        return $this->belongsTo(Restaurant::class); 
    }
    
    public function orders() 
    { 
        return $this->hasMany(Order::class); 
    }
}
