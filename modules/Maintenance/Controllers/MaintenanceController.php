<?php

declare(strict_types=1);

namespace Modules\Maintenance\Controllers;

use Core\Controller;
use Core\Database;
use Core\Request;
use Core\Response;
use Modules\Maintenance\Models\Ticket;

final class MaintenanceController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $severity = $_GET['severity'] ?? null;
        $this->view('Maintenance::index', [
            'title'     => t('maint.title'),
            'tickets'   => Ticket::all($severity),
            'severity'  => (string) $severity,
            'buildings' => Database::rows('SELECT id, name FROM buildings ORDER BY name'),
            'units'     => Database::rows('SELECT u.id, u.code, b.name AS building_name FROM units u JOIN buildings b ON b.id = u.building_id ORDER BY b.name, u.code'),
            'tenants'   => Database::rows('SELECT id, name FROM tenants ORDER BY name'),
            'technicians' => Database::rows("SELECT id, full_name FROM users WHERE role = 'technician' AND active = 1"),
            'vendors'   => Database::rows('SELECT id, name, service_type FROM vendors ORDER BY name'),
        ]);
    }

    public function store(): void
    {
        $this->requireRole('admin', 'property_manager', 'technician');
        csrf_check();
        try {
            Ticket::create([
                'unit_id'     => Request::str('unit_id'),
                'building_id' => Request::str('building_id'),
                'tenant_id'   => Request::str('tenant_id'),
                'title'       => Request::requireStr('title', 'title'),
                'description' => Request::str('description'),
                'severity'    => Request::str('severity', 'high'),
            ]);
            Response::redirect('maintenance', 'Ticket created: ' . Request::str('title'));
        } catch (\InvalidArgumentException $e) {
            Response::redirect('maintenance', $e->getMessage(), 'error');
        }
    }

    public function assign(array $params): void
    {
        $this->requireRole('admin', 'property_manager');
        csrf_check();
        $kind    = Request::str('assignee_kind', 'internal') === 'vendor' ? 'vendor' : 'internal';
        $userId  = $kind === 'internal' ? Request::int('user_id') : null;
        $vendorId = $kind === 'vendor' ? Request::int('vendor_id') : null;
        $sla      = max(1, (int) Request::int('sla_hours', 48));
        if ($kind === 'internal' && !$userId) {
            Response::redirect('maintenance', 'Pick a technician to assign', 'error');
        }
        if ($kind === 'vendor' && !$vendorId) {
            Response::redirect('maintenance', 'Pick a vendor to assign', 'error');
        }
        Ticket::assign((int) $params['id'], $kind, $userId, $vendorId, $sla);
        Response::redirect('maintenance', 'Work order created and ticket assigned');
    }

    public function updateStatus(array $params): void
    {
        $this->requireRole('admin', 'property_manager', 'technician');
        csrf_check();
        try {
            Ticket::setStatus((int) $params['id'], Request::requireStr('status', 'status'));
            Response::redirect('maintenance', 'Ticket updated');
        } catch (\DomainException $e) {
            Response::redirect('maintenance', $e->getMessage(), 'error');
        } catch (\InvalidArgumentException $e) {
            Response::redirect('maintenance', $e->getMessage(), 'error');
        }
    }
}
