<?php

declare(strict_types=1);

namespace Modules\Properties\Models;

use Core\Database;

/** Building model — Module 1 (Property & Asset Directory). */
final class Building
{
    /** All buildings with portfolio + unit roll-up. */
    public static function all(): array
    {
        return Database::rows(
            'SELECT b.*, p.name AS portfolio_name,
                    COUNT(u.id) AS unit_count,
                    SUM(u.status = \'leased\') AS leased_count,
                    COALESCE(SUM(CASE u.status WHEN \'leased\' THEN u.market_rent_xaf ELSE 0 END), 0) AS leased_rent_xaf
               FROM buildings b
               JOIN portfolios p ON p.id = b.portfolio_id
               LEFT JOIN units u ON u.building_id = b.id
              GROUP BY b.id
              ORDER BY p.name, b.name'
        );
    }

    public static function find(int $id): ?array
    {
        return Database::row(
            'SELECT b.*, p.name AS portfolio_name, p.owner_id
               FROM buildings b JOIN portfolios p ON p.id = b.portfolio_id
              WHERE b.id = ?',
            [$id]
        );
    }

    public static function options(): array
    {
        return Database::rows('SELECT id, name, quarter FROM buildings ORDER BY name');
    }

    public static function create(array $d): int
    {
        Database::run(
            'INSERT INTO buildings (portfolio_id, name, quarter, city, region, address) VALUES (?,?,?,?,?,?)',
            [
                (int) $d['portfolio_id'], $d['name'], $d['quarter'],
                $d['city'] ?: 'Bamenda', $d['region'] ?: 'North-West', $d['address'] ?? null,
            ]
        );
        return Database::lastId();
    }

    public static function portfolios(): array
    {
        return Database::rows('SELECT id, name FROM portfolios ORDER BY name');
    }
}
