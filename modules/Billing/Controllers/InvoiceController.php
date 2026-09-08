<?php

declare(strict_types=1);

namespace Modules\Billing\Controllers;

use Core\Controller;
use Core\Database;
use Core\Request;
use Core\Response;
use Modules\Billing\Models\Invoice;
use Modules\Tenants\Models\Tenant;

final class InvoiceController extends Controller
{
    public function index(): void
    {
        $this->requireRole('admin', 'accountant', 'property_manager');
        $status = $_GET['status'] ?? null;
        $this->view('Billing::index', [
            'title'    => t('bill.title'),
            'invoices' => Invoice::all($status),
            'status'   => (string) $status,
            'leases'   => Database::rows(
                "SELECT l.id, l.code, l.tenant_id, l.rent_xaf, tn.name AS tenant_name
                   FROM leases l JOIN tenants tn ON tn.id = l.tenant_id
                  WHERE l.status IN ('active','in_recovery') ORDER BY l.code"
            ),
            'tenants'  => Tenant::options(),
        ]);
    }

    public function store(): void
    {
        $this->requireRole('admin', 'accountant');
        csrf_check();
        try {
            $leaseId  = Request::str('lease_id');
            $tenantId = Request::str('tenant_id');
            if ($tenantId === '' && $leaseId !== '') {
                $tenantId = (string) Database::scalar('SELECT tenant_id FROM leases WHERE id = ?', [(int) $leaseId]);
            }
            if ($tenantId === '') {
                throw new \InvalidArgumentException('A tenant or lease is required');
            }
            Invoice::create([
                'lease_id'     => $leaseId,
                'tenant_id'    => $tenantId,
                'kind'         => Request::str('kind', 'rent'),
                'issue_date'   => Request::str('issue_date', date('Y-m-d')),
                'due_date'     => Request::str('due_date', date('Y-m-d', strtotime('+5 days'))),
                'period_start' => Request::str('period_start') ?: null,
                'period_end'   => Request::str('period_end') ?: null,
                'amount_xaf'   => Request::requireStr('amount_xaf', 'amount'),
                'description'  => Request::str('description'),
                'notes'        => Request::str('notes'),
            ], (int) current_user()['id']);
            Response::redirect('billing', 'Invoice created');
        } catch (\InvalidArgumentException $e) {
            Response::redirect('billing', $e->getMessage(), 'error');
        }
    }

    public function pay(): void
    {
        $user = $this->requireRole('admin', 'accountant');
        csrf_check();
        $invoiceId = Request::int('invoice_id', 0);
        try {
            Invoice::pay(
                (int) $invoiceId,
                (int) Request::requireStr('amount_xaf', 'amount'),
                Request::requireStr('channel', 'channel'),
                Request::str('reference'),
                (int) $user['id']
            );
            Response::redirect('billing', 'Payment recorded');
        } catch (\DomainException $e) {
            Response::redirect('billing', $e->getMessage(), 'error');
        } catch (\InvalidArgumentException $e) {
            Response::redirect('billing', $e->getMessage(), 'error');
        }
    }

    public function lateFees(): void
    {
        $this->requireRole('admin', 'accountant');
        csrf_check();
        $result = Invoice::runLateFees();
        Response::redirect(
            'billing',
            $result['raised'] === 0
                ? 'Late-fee check: nothing overdue past the grace period'
                : "Late fees raised: {$result['raised']} invoice(s), total " . fmt_xaf($result['total'])
        );
    }
}
