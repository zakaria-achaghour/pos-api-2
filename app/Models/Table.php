<?php

namespace App\Models;

use App\Models\Traits\SetsRestaurant;
use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    use SetsRestaurant;

    protected $fillable = ['restaurant_id','name','capacity','status','section','shape','grid_x','grid_y','qr_code'];

    public function restaurant() { return $this->belongsTo(Restaurant::class); }
    public function orders()     { return $this->hasMany(Order::class); } // later
}
