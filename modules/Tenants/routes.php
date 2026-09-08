<?php

declare(strict_types=1);

use Modules\Tenants\Controllers\TenantController;
use Core\Router;

return static function (Router $r): void {
    $r->get('tenants',          [TenantController::class, 'index']);
    $r->post('tenants',         [TenantController::class, 'store']);
    $r->get('tenants/{id}',     [TenantController::class, 'show']);
};
