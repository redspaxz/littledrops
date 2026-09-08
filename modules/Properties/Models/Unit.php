<?php

declare(strict_types=1);

namespace Modules\Properties\Models;

use Core\Database;

/** Unit model — occupancy & status engine lives on this table. */
final class Unit
{
    public const STATUSES = ['vacant', 'leased', 'under_maintenance', 'reserved', 'out_of_service'];

    /** Search with building + current lease/tenant. */
    public static function search(?string $status = null, ?string $q = null): array
    {
        $sql = "SELECT u.*, b.name AS building_name, b.quarter,
                       l.id AS lease_id, l.code AS lease_code, l.status AS lease_status,
                       tn.name AS tenant_name
                  FROM units u
                  JOIN buildings b ON b.id = u.building_id
                  LEFT JOIN leases l ON l.unit_id = u.id AND l.status IN ('active','in_recovery')
                  LEFT JOIN tenants tn ON tn.id = l.tenant_id
                 WHERE 1=1";
        $params = [];
        if ($status && in_array($status, self::STATUSES, true)) {
            $sql .= ' AND u.status = ?';
            $params[] = $status;
        }
        if ($q !== null && $q !== '') {
            $sql .= ' AND (u.code LIKE ? OR b.name LIKE ? OR b.quarter LIKE ?)';
            $like = "%$q%";
            array_push($params, $like, $like, $like);
        }
        $sql .= ' ORDER BY b.name, u.code';
        return Database::rows($sql, $params);
    }

    public static function find(int $id): ?array
    {
        return Database::row(
            'SELECT u.*, b.name AS building_name, b.quarter
               FROM units u JOIN buildings b ON b.id = u.building_id WHERE u.id = ?',
            [$id]
        );
    }

    public static function forBuilding(int $buildingId): array
    {
        return Database::rows(
            "SELECT u.*, l.id AS lease_id, l.code AS lease_code, l.status AS lease_status,
                    tn.name AS tenant_name
               FROM units u
               LEFT JOIN leases l ON l.unit_id = u.id AND l.status IN ('active','in_recovery')
               LEFT JOIN tenants tn ON tn.id = l.tenant_id
              WHERE u.building_id = ?
              ORDER BY u.code",
            [$buildingId]
        );
    }

    /** Vacant or reserved units — candidates for lease creation. */
    public static function leasable(): array
    {
        return Database::rows(
            "SELECT u.id, u.code, u.unit_type, u.market_rent_xaf, b.name AS building_name
               FROM units u JOIN buildings b ON b.id = u.building_id
              WHERE u.status IN ('vacant','reserved')
              ORDER BY b.name, u.code"
        );
    }

    public static function create(array $d): int
    {
        Database::run(
            'INSERT INTO units (building_id, floor_id, code, unit_type, sqm, bedrooms, bathrooms, market_rent_xaf, status)
             VALUES (?,?,?,?,?,?,?,?,?)',
            [
                (int) $d['building_id'],
                isset($d['floor_id']) && $d['floor_id'] !== '' ? (int) $d['floor_id'] : null,
                $d['code'], $d['unit_type'],
                isset($d['sqm']) && $d['sqm'] !== '' ? (float) $d['sqm'] : null,
                isset($d['bedrooms']) && $d['bedrooms'] !== '' ? (int) $d['bedrooms'] : null,
                isset($d['bathrooms']) && $d['bathrooms'] !== '' ? (int) $d['bathrooms'] : null,
                (int) $d['market_rent_xaf'],
                in_array($d['status'] ?? 'vacant', self::STATUSES, true) ? $d['status'] : 'vacant',
            ]
        );
        return Database::lastId();
    }

    public static function setStatus(int $id, string $status): void
    {
        Database::run('UPDATE units SET status = ? WHERE id = ?', [$status, $id]);
    }
}
