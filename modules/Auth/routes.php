<?php

declare(strict_types=1);

/** Auth module routes (self-registration). */

use Modules\Auth\Controllers\AuthController;
use Core\Router;

return static function (Router $r): void {
    $r->get('login', [AuthController::class, 'loginForm']);
    $r->post('login', [AuthController::class, 'login']);
    $r->post('logout', [AuthController::class, 'logout']);
    $r->post('locale', [AuthController::class, 'locale']);
    $r->get('health', [AuthController::class, 'health']);
};
