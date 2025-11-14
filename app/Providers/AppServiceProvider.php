<?php

namespace App\Providers;

use App\Models\OrderItem;
use App\Observers\OrderItemObserver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Infrastructure\Tenancy\Tenant;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        OrderItem::observe(OrderItemObserver::class);

        static $registered = false;
        if (!$registered) {
            Log::getLogger()->pushProcessor(function (array $record) {
                $record['extra']['tenant_id'] = Tenant::id();
                return $record;
            });
            $registered = true;
        }
    }
}
