<?php

declare(strict_types=1);

use Modules\Access\Controllers\AccessController;
use Core\Router;

return static function (Router $r): void {
    $r->get('access',              [AccessController::class, 'index']);
    $r->post('access/visitors',    [AccessController::class, 'storeVisitor']);
    $r->post('access/visitors/{id}/use', [AccessController::class, 'markUsed']);
};
