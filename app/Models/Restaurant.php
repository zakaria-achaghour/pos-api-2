<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
     use HasFactory;

    protected $fillable = [
        'name',
        'slug', 
        'address',
        'city',
        'postal_code',
        'country',
        'phone',
        'email',
        'website',
        'cuisine_type',
        'currency',
        'timezone', 
        'tax_rate',
        'service_charge',
        'status',
        'subscription_type',
        'subscription_start',
        'subscription_end',
        'subdomain',
        'is_active',
        'settings'
    ];

    protected $casts = [
        'tax_rate' => 'decimal:2',
        'service_charge' => 'decimal:2',
        'is_active' => 'boolean',
        'settings' => 'array',
        'subscription_start' => 'date',
        'subscription_end' => 'date',
    ];

    public function users()        { return $this->hasMany(User::class); }
    public function tables()       { return $this->hasMany(Table::class); }
    public function menuCategories(){ return $this->hasMany(MenuCategory::class); }
    public function menuItems()    { return $this->hasMany(MenuItem::class); }
    public function orders()       { return $this->hasMany(Order::class); }
}
