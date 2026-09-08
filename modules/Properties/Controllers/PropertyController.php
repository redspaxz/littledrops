<?php

declare(strict_types=1);

namespace Modules\Properties\Controllers;

use Core\Controller;
use Core\Request;
use Core\Response;
use Modules\Properties\Models\Building;
use Modules\Properties\Models\Unit;

final class PropertyController extends Controller
{
    public function index(): void
    {
        $this->requireRole('admin', 'property_manager');
        $this->view('Properties::index', [
            'title'      => t('prop.title'),
            'buildings'  => Building::all(),
            'portfolios' => Building::portfolios(),
        ]);
    }

    public function show(array $params): void
    {
        $this->requireRole('admin', 'property_manager');
        $building = Building::find((int) $params['id']);
        if (!$building) {
            Response::redirect('properties', 'Building not found', 'error');
        }
        $this->view('Properties::show', [
            'title'    => $building['name'],
            'building' => $building,
            'units'    => Unit::forBuilding((int) $building['id']),
        ]);
    }

    public function store(): void
    {
        $this->requireRole('admin', 'property_manager');
        csrf_check();
        try {
            Building::create([
                'portfolio_id' => Request::requireStr('portfolio_id', 'portfolio'),
                'name'         => Request::requireStr('name', 'name'),
                'quarter'      => Request::requireStr('quarter', 'quarter'),
                'city'         => Request::str('city', 'Bamenda'),
                'region'       => Request::str('region', 'North-West'),
                'address'      => Request::str('address'),
            ]);
            Response::redirect('properties', 'Building added: ' . Request::str('name'));
        } catch (\InvalidArgumentException $e) {
            Response::redirect('properties', $e->getMessage(), 'error');
        }
    }

    public function units(): void
    {
        $this->requireRole('admin', 'property_manager', 'accountant');
        $status = $_GET['status'] ?? null;
        $q      = $_GET['q'] ?? null;
        $this->view('Properties::units', [
            'title'     => t('prop.units'),
            'units'     => Unit::search($status, $q),
            'status'    => $status,
            'q'         => (string) $q,
            'buildings' => Building::options(),
        ]);
    }

    public function storeUnit(): void
    {
        $this->requireRole('admin', 'property_manager');
        csrf_check();
        try {
            Unit::create([
                'building_id'    => Request::requireStr('building_id', 'building'),
                'code'           => Request::requireStr('code', 'unit code'),
                'unit_type'      => Request::str('unit_type', 'apartment'),
                'sqm'            => Request::str('sqm'),
                'bedrooms'       => Request::str('bedrooms'),
                'bathrooms'      => Request::str('bathrooms'),
                'market_rent_xaf'=> Request::requireStr('market_rent_xaf', 'market rent'),
                'status'         => Request::str('status', 'vacant'),
            ]);
            Response::redirect('units', 'Unit added: ' . Request::str('code'));
        } catch (\InvalidArgumentException $e) {
            Response::redirect('units', $e->getMessage(), 'error');
        }
    }
}
