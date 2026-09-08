<?php

declare(strict_types=1);

use Modules\Properties\Controllers\PropertyController;
use Core\Router;

return static function (Router $r): void {
    $r->get('properties',                 [PropertyController::class, 'index']);
    $r->post('properties',                [PropertyController::class, 'store']);
    $r->get('properties/{id}',            [PropertyController::class, 'show']);
    $r->get('units',                      [PropertyController::class, 'units']);
    $r->post('units',                     [PropertyController::class, 'storeUnit']);
};
