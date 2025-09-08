<?php

namespace App\Models;

use App\Models\Traits\SetsRestaurant;
use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    use SetsRestaurantd;

    protected $fillable = ['restaurant_id','category_id','name','description','price','is_active'];

    public function restaurant() { return $this->belongsTo(Restaurant::class); }
    public function category()   { return $this->belongsTo(MenuCategory::class, 'category_id'); }
}
