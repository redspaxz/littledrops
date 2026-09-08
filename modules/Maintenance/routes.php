<?php

declare(strict_types=1);

use Modules\Maintenance\Controllers\MaintenanceController;
use Core\Router;

return static function (Router $r): void {
    $r->get('maintenance',              [MaintenanceController::class, 'index']);
    $r->post('maintenance',             [MaintenanceController::class, 'store']);
    $r->post('maintenance/{id}/assign', [MaintenanceController::class, 'assign']);
    $r->post('maintenance/{id}/status', [MaintenanceController::class, 'updateStatus']);
};
