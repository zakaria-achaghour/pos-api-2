<?php

namespace App\Models;

use App\Models\Traits\SetsRestaurant;
use Illuminate\Database\Eloquent\Model;

class MenuCategory extends Model
{
    use SetsRestaurant;

    protected $fillable = ['restaurant_id','name','description'];

    public function restaurant() { return $this->belongsTo(Restaurant::class); }
    public function items()      { return $this->hasMany(MenuItem::class, 'category_id'); }
}
