<?php

namespace App\Models;

use App\Models\Traits\SetsRestaurant;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use SetsRestaurant;

    protected $fillable = [
        'restaurant_id','table_id','waiter_id','user_id','order_number','type','status','priority','subtotal','tax_amount','discount_amount','total','payment_method','notes','placed_at','paid_at'
    ];
    protected $casts = [
        'placed_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function restaurant(){ return $this->belongsTo(Restaurant::class); }
    public function table()     { return $this->belongsTo(Table::class); }
    public function waiter()    { return $this->belongsTo(Staff::class, 'waiter_id'); }
    public function orderItems(){ return $this->hasMany(OrderItem::class); }
    public function payments()  { return $this->hasMany(Payment::class); }
    public function kitchenTicket(){ return $this->hasOne(KitchenTicket::class); }
}
