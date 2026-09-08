<?php

declare(strict_types=1);

use Modules\Billing\Controllers\InvoiceController;
use Core\Router;

return static function (Router $r): void {
    $r->get('billing',            [InvoiceController::class, 'index']);
    $r->post('billing/invoices',  [InvoiceController::class, 'store']);
    $r->post('billing/payments',  [InvoiceController::class, 'pay']);
    $r->post('billing/late-fees', [InvoiceController::class, 'lateFees']);
};
