<?php

declare(strict_types=1);

use Modules\Utilities\Controllers\UtilitiesController;
use Core\Router;

return static function (Router $r): void {
    $r->get('utilities', [UtilitiesController::class, 'index']);
};
