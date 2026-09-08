<?php

declare(strict_types=1);

namespace Modules\Utilities\Controllers;

use Core\Controller;
use Core\Database;

final class UtilitiesController extends Controller
{
    public function index(): void
    {
        $this->requireRole('admin', 'property_manager', 'accountant');

        $meters = Database::rows(
            'SELECT m.*,
                    COALESCE(u.code, b.name) AS location,
                    u.id AS unit_id, b.id AS building_id,
                    r.value AS last_value, r.read_date AS last_read
               FROM meters m
               LEFT JOIN units u ON u.id = m.unit_id
               LEFT JOIN buildings b ON b.id = m.building_id
               LEFT JOIN meter_readings r ON r.meter_id = m.id
                AND r.read_date = (SELECT MAX(read_date) FROM meter_readings WHERE meter_id = m.id)
              ORDER BY m.utility, location'
        );

        $bills = Database::rows(
            'SELECT ub.*, b.name AS building_name,
                    (SELECT COUNT(*) FROM utility_allocations a WHERE a.bill_id = ub.id) AS allocations
               FROM utility_bills ub
               JOIN buildings b ON b.id = ub.building_id
              ORDER BY ub.period_start DESC'
        );

        $allocations = Database::rows(
            'SELECT a.*, u.code AS unit_code, ub.total_xaf AS bill_total, b.name AS building_name, ub.utility, ub.split_method
               FROM utility_allocations a
               JOIN utility_bills ub ON ub.id = a.bill_id
               JOIN buildings b ON b.id = ub.building_id
               JOIN units u ON u.id = a.unit_id
              ORDER BY a.bill_id DESC, u.code'
        );

        $this->view('Utilities::index', [
            'title'       => t('util.title'),
            'meters'      => $meters,
            'bills'       => $bills,
            'allocations' => $allocations,
        ]);
    }
}
