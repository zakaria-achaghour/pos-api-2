<?php

namespace App\Models;

use App\Models\Traits\SetsRestaurant;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use SetsRestaurant;

    protected $fillable = [
        'restaurant_id','table_id','user_id','order_number','status','total','placed_at','closed_at'
    ];
    protected $casts = [
        'placed_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function restaurant(){ return $this->belongsTo(Restaurant::class); }
    public function table()     { return $this->belongsTo(Table::class); }
    public function user()      { return $this->belongsTo(User::class); }
    public function items()     { return $this->hasMany(OrderItem::class); }
    public function payments()  { return $this->hasMany(Payment::class); }
}
