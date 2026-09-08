<?php

declare(strict_types=1);

use Modules\Dashboard\Controllers\DashboardController;
use Core\Router;

return static function (Router $r): void {
    $r->get('dashboard', [DashboardController::class, 'index']);
};
