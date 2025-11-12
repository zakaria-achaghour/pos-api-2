<?php

namespace App\Models;

use App\Models\Traits\HasUserTracking;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Restaurant extends Model
{
    use HasFactory, HasUserTracking;

    protected $fillable = [
        'name',
        'subdomain',
        'address',
        'city',
        'postal_code',
        'country',
        'phone',
        'email',
        'website',
        'license_number',
        'tax_number',
        'cuisine_type',
        'timezone',
        'currency',
        'tax_rate',
        'service_charge',
        'service_charge_rate',
        'opening_time',
        'closing_time',
        'logo_url',
        'description',
        'status',
        'subscription_type',
        'subscription_start',
        'subscription_end',
        'is_active',
        'settings',
        'payment_settings',
        'created_by',
        'updated_by',
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
