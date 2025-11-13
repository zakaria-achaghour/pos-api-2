<?php

namespace App\Models;

use App\Models\Traits\HasUserTracking;
use App\Models\Traits\SetsRestaurant;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use SetsRestaurant, HasUserTracking;

    protected $fillable = [
        'restaurant_id','table_id','waiter_id','user_id','order_number','type','status','priority','subtotal','tax_amount','discount_amount','total','payment_method','notes','placed_at','paid_at','created_by','updated_by'
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
    
    /**
     * Update order status based on order items state
     */
    public function updateStatusFromItems(): void
    {
        $items = $this->orderItems;
        
        if ($items->isEmpty()) {
            return;
        }
        
        $allStates = $items->pluck('state')->unique();
        
        // If all items are ready, set order to ready
        if ($allStates->count() === 1 && $allStates->first() === 'ready') {
            if (!in_array($this->status, ['ready', 'served', 'completed', 'cancelled'])) {
                $this->update(['status' => 'ready']);
            }
        }
        // If all items are preparing, set order to preparing
        elseif ($allStates->count() === 1 && $allStates->first() === 'preparing') {
            if (!in_array($this->status, ['preparing', 'ready', 'served', 'completed', 'cancelled'])) {
                $this->update(['status' => 'preparing']);
            }
        }
        // If all items are served, set order to served
        elseif ($allStates->count() === 1 && $allStates->first() === 'served') {
            if (!in_array($this->status, ['served', 'completed', 'cancelled'])) {
                $this->update(['status' => 'served']);
            }
        }
        // If items are in mixed states (some preparing, some ready), set to preparing
        elseif ($allStates->contains('preparing') || $allStates->contains('ready')) {
            if (!in_array($this->status, ['preparing', 'ready', 'served', 'completed', 'cancelled'])) {
                $this->update(['status' => 'preparing']);
            }
        }
    }
}
