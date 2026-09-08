<?php

declare(strict_types=1);

namespace Modules\Access\Controllers;

use Core\Controller;
use Core\Database;
use Core\Request;
use Core\Response;

final class AccessController extends Controller
{
    public function index(): void
    {
        $this->requireRole('admin', 'property_manager', 'technician');

        $this->view('Access::index', [
            'title'     => t('access.title'),
            'visitors'  => Database::rows(
                'SELECT vp.*, u.code AS unit_code, b.name AS building_name
                   FROM visitor_passes vp
                   LEFT JOIN units u ON u.id = vp.unit_id
                   LEFT JOIN buildings b ON b.id = u.building_id
                  ORDER BY vp.valid_on DESC, vp.id DESC LIMIT 100'
            ),
            'amenities' => Database::rows(
                'SELECT a.*, b.name AS building_name,
                        (SELECT COUNT(*) FROM amenity_bookings ab WHERE ab.amenity_id = a.id) AS bookings
                   FROM amenities a JOIN buildings b ON b.id = a.building_id
                  WHERE a.active = 1 ORDER BY b.name, a.name'
            ),
            'bookings'  => Database::rows(
                'SELECT ab.*, am.name AS amenity_name, tn.name AS tenant_name
                   FROM amenity_bookings ab
                   JOIN amenities am ON am.id = ab.amenity_id
                   JOIN tenants tn ON tn.id = ab.tenant_id
                  ORDER BY ab.start_at DESC LIMIT 50'
            ),
            'units'     => Database::rows(
                'SELECT u.id, u.code, b.name AS building_name FROM units u JOIN buildings b ON b.id = u.building_id ORDER BY b.name, u.code'
            ),
        ]);
    }

    public function storeVisitor(): void
    {
        $this->requireRole('admin', 'property_manager');
        csrf_check();
        try {
            $unitId = Request::str('unit_id');
            Database::run(
                'INSERT INTO visitor_passes (unit_id, visitor_name, visitor_phone, qr_token, valid_on, status) VALUES (?,?,?,?,?,"approved")',
                [
                    $unitId !== '' ? (int) $unitId : null,
                    Request::requireStr('visitor_name', 'visitor name'),
                    Request::str('visitor_phone'),
                    bin2hex(random_bytes(12)),
                    Request::str('valid_on', date('Y-m-d')),
                ]
            );
            Response::redirect('access', 'Gate pass issued for ' . Request::str('visitor_name'));
        } catch (\InvalidArgumentException $e) {
            Response::redirect('access', $e->getMessage(), 'error');
        }
    }

    public function markUsed(array $params): void
    {
        $this->requireRole('admin', 'property_manager', 'technician');
        csrf_check();
        Database::run("UPDATE visitor_passes SET status = 'used' WHERE id = ?", [(int) $params['id']]);
        Response::redirect('access', 'Visitor checked in — entry logged');
    }
}
