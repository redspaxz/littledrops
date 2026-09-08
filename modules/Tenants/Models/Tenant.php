<?php

declare(strict_types=1);

namespace Modules\Tenants\Models;

use Core\Database;

/** Tenant model — Module 2 (profiles, ID docs, emergency contacts). */
final class Tenant
{
    public static function all(): array
    {
        return Database::rows(
            "SELECT tn.*,
                    (SELECT COUNT(*) FROM leases l WHERE l.tenant_id = tn.id AND l.status IN ('active','in_recovery')) AS active_leases
               FROM tenants tn ORDER BY tn.name"
        );
    }

    public static function find(int $id): ?array
    {
        return Database::row('SELECT * FROM tenants WHERE id = ?', [$id]);
    }

    public static function options(): array
    {
        return Database::rows("SELECT id, name, kind FROM tenants ORDER BY name");
    }

    public static function create(array $d): int
    {
        Database::run(
            'INSERT INTO tenants (kind, name, email, phone, alt_phone, emergency_name, emergency_phone, id_type, id_number, notes)
             VALUES (?,?,?,?,?,?,?,?,?,?)',
            [
                ($d['kind'] ?? 'individual') === 'corporate' ? 'corporate' : 'individual',
                $d['name'], $d['email'] ?? null, $d['phone'] ?? null, $d['alt_phone'] ?? null,
                $d['emergency_name'] ?? null, $d['emergency_phone'] ?? null,
                $d['id_type'] ?? null, $d['id_number'] ?? null, $d['notes'] ?? null,
            ]
        );
        return Database::lastId();
    }

    public static function leases(int $tenantId): array
    {
        return Database::rows(
            'SELECT l.*, u.code AS unit_code, u.unit_type, b.name AS building_name
               FROM leases l
               JOIN units u ON u.id = l.unit_id
               JOIN buildings b ON b.id = u.building_id
              WHERE l.tenant_id = ? ORDER BY l.start_date DESC',
            [$tenantId]
        );
    }
}
