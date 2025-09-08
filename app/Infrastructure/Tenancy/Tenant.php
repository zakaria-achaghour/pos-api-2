<?php
namespace Infrastructure\Tenancy;

class Tenant {

    protected static ?int $override = null;

    public static function id(): ?int {
        $user = auth('api')->user();
        // SuperAdmin may use override
        if (static::$override !== null) return static::$override;
        return $user?->restaurant_id;
    }

    public static function with(int $restaurantId, \Closure $fn) {
        $old = static::$override;
        static::$override = $restaurantId;
        try { return $fn(); } finally { static::$override = $old; }
    }
}
