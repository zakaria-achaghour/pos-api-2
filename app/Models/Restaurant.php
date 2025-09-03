<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
     use HasFactory;

    protected $fillable = ['name','address','phone'];

    public function users()        { return $this->hasMany(User::class); }
    // public function tables()       { return $this->hasMany(Table::class); }
    // public function menuCategories(){ return $this->hasMany(MenuCategory::class); }
    // public function menuItems()    { return $this->hasMany(MenuItem::class); }
    // public function orders()       { return $this->hasMany(Order::class); }
}
