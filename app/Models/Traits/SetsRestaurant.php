<?php

namespace App\Models\Traits;

use Infrastructure\Tenancy\Tenant;

trait SetsRestaurant
{
    protected static function bootSetsRestaurant(): void
    {
        static::creating(function ($model) {
            if (isset($model->restaurant_id) && empty($model->restaurant_id)) {
                $model->restaurant_id = Tenant::id();
            }
        });
    }
}
