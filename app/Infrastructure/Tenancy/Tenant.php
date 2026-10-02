<?php

namespace Infrastructure\Tenancy;

use Illuminate\Contracts\Auth\Authenticatable;

class Tenant
{
    protected static ?int $override = null;

    public static function id(): ?int
    {
        // SuperAdmin may use override
        if (static::$override !== null) {
            return static::$override;
        }

        return static::user()?->restaurant_id;
    }

    public static function actorId(): ?int
    {
        return static::user()?->getAuthIdentifier();
    }

    private static function user(): ?Authenticatable
    {
        // Artisan and queue jobs have no request actor. Do not construct the JWT
        // guard just to scope a query, enrich a log, or populate audit fields.
        // An explicitly resolved guard still supports actingAs() and CLI actors.
        if (app()->runningInConsole() && ! request()->bearerToken() && ! auth()->hasResolvedGuards()) {
            return null;
        }

        return auth('api')->user();
    }

    public static function with(int $restaurantId, \Closure $fn)
    {
        $old = static::$override;
        static::$override = $restaurantId;
        try {
            return $fn();
        } finally {
            static::$override = $old;
        }
    }
}
