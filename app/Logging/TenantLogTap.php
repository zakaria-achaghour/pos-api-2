<?php

namespace App\Logging;

use Infrastructure\Tenancy\Tenant;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class TenantLogTap
{
    public function __invoke(Logger $logger): void
    {
        $tenantId = Tenant::id();
        $suffix = $tenantId ? "tenant-{$tenantId}" : 'tenant-global';
        $path = storage_path("logs/{$suffix}.log");

        $handlers = $logger->getHandlers();
        $level = $handlers[0]->getLevel() ?? Logger::DEBUG;
        $formatter = $handlers[0]->getFormatter() ?? null;

        $handler = new StreamHandler($path, $level);
        if ($formatter) {
            $handler->setFormatter($formatter);
        }

        $logger->setHandlers([$handler]);
    }
}
