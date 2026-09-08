<?php

declare(strict_types=1);

namespace Modules\Tenants\Controllers;

use Core\Controller;
use Core\Request;
use Core\Response;
use Modules\Tenants\Models\Tenant;

final class TenantController extends Controller
{
    public function index(): void
    {
        $this->requireRole('admin', 'property_manager', 'accountant');
        $this->view('Tenants::index', ['title' => t('tenant.title'), 'tenants' => Tenant::all()]);
    }

    public function show(array $params): void
    {
        $this->requireRole('admin', 'property_manager', 'accountant');
        $tenant = Tenant::find((int) $params['id']);
        if (!$tenant) {
            Response::redirect('tenants', 'Tenant not found', 'error');
        }
        $this->view('Tenants::show', [
            'title'  => $tenant['name'],
            'tenant' => $tenant,
            'leases' => Tenant::leases((int) $tenant['id']),
        ]);
    }

    public function store(): void
    {
        $this->requireRole('admin', 'property_manager');
        csrf_check();
        try {
            $name = Request::requireStr('name', 'name');
            Tenant::create([
                'kind'            => Request::str('kind', 'individual'),
                'name'            => $name,
                'email'           => Request::str('email'),
                'phone'           => Request::str('phone'),
                'alt_phone'       => Request::str('alt_phone'),
                'emergency_name'  => Request::str('emergency_name'),
                'emergency_phone' => Request::str('emergency_phone'),
                'id_type'         => Request::str('id_type') ?: null,
                'id_number'       => Request::str('id_number'),
                'notes'           => Request::str('notes'),
            ]);
            Response::redirect('tenants', 'Tenant added: ' . $name);
        } catch (\InvalidArgumentException $e) {
            Response::redirect('tenants', $e->getMessage(), 'error');
        }
    }
}
