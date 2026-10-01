<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Infrastructure\Tenancy\Tenant;

/**
 * Scopes every query (including route model binding) to the current tenant.
 *
 * When there is no tenant context (SuperAdmin without Tenant::with(), console,
 * seeders, queue workers) the scope is not applied.
 */
trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if ($tenantId = Tenant::id()) {
                $builder->where($builder->qualifyColumn('restaurant_id'), $tenantId);
            }
        });
    }
}
