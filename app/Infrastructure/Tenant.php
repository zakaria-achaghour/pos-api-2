<?php
namespace Infrastructure\Tenancy;

class Tenant {

    public static function id(): ?int {
        return auth('api')->user()->restaurant_id ?? null;
    }
}
