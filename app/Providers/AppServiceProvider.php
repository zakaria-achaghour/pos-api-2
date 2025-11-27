<?php

namespace App\Providers;

use App\Models\OrderItem;
use App\Observers\OrderItemObserver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Infrastructure\Tenancy\Tenant;
use Monolog\LogRecord;

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
            Log::getLogger()->pushProcessor(function ($record) {
                $tenantId = Tenant::id();
                if ($record instanceof LogRecord) {
                    $record->extra['tenant_id'] = $tenantId;
                    return $record;
                }
                $record['extra']['tenant_id'] = $tenantId;
                return $record;
            });
            $registered = true;
        }
    }
}
