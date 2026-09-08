<?php

declare(strict_types=1);

use Modules\Leases\Controllers\LeaseController;
use Core\Router;

return static function (Router $r): void {
    $r->get('leases',                       [LeaseController::class, 'index']);
    $r->post('leases',                      [LeaseController::class, 'store']);
    $r->get('leases/{id}',                  [LeaseController::class, 'show']);
    $r->post('leases/{id}/activate',        [LeaseController::class, 'activate']);
    $r->post('leases/{id}/terminate',       [LeaseController::class, 'terminate']);
    $r->get('leases/{id}/agreement',        [LeaseController::class, 'agreement']);
    $r->post('leases/{id}/notice-to-quit',  [LeaseController::class, 'noticeToQuit']);
    $r->post('leases/{id}/recovery',        [LeaseController::class, 'advanceRecovery']);
};
