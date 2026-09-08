<?php

declare(strict_types=1);

namespace Core;

/**
 * Dot-notation config access with optional config/config.ini overrides.
 * Defaults target a stock XAMPP install (MariaDB root, no password).
 */
final class Config
{
    private static ?array $data = null;

    public static function load(): array
    {
        if (self::$data !== null) {
            return self::$data;
        }

        self::$data = [
            'app' => [
                'name'      => 'LittleDrops PBMS',
                'tagline'   => 'Property & Building Management · Bamenda, Cameroon',
                'locale'    => 'en',          // Bamenda is anglophone: EN default, FR available
                'timezone'  => 'Africa/Douala',
            ],
            'db' => [
                'driver'    => 'mysql',
                'host'      => '127.0.0.1',
                'port'      => 3306,
                'database'  => 'pbms',
                'username'  => 'root',
                'password'  => '',
                'charset'   => 'utf8mb4',
            ],
            // Cameroon is bijural; Bamenda (North-West Region) is a Common Law
            // jurisdiction. These defaults flow into lease creation.
            'legal' => [
                'default_legal_system' => 'common_law',
                'default_jurisdiction' => 'Bamenda, North-West Region, Cameroon',
                'default_notice_days'  => 30,
                'late_fee_penalty_pct' => 5.00,
                'late_fee_grace_days'  => 7,
            ],
            // Modules loaded by the front controller, in nav order.
            // Each module owns its MVC triad + routes (modular monolith).
            'modules' => ['Auth', 'Dashboard', 'Properties', 'Tenants', 'Leases', 'Billing', 'Maintenance', 'Utilities', 'Access'],
        ];

        $ini = dirname(__DIR__) . '/config/config.ini';
        if (is_file($ini)) {
            $parsed = parse_ini_file($ini, true, INI_SCANNER_TYPED);
            if (is_array($parsed)) {
                foreach ($parsed as $section => $values) {
                    if (is_array($values)) {
                        self::$data[$section] = array_merge(self::$data[$section] ?? [], $values);
                    }
                }
            }
        }

        return self::$data;
    }

    /** Config::get('db.host'), Config::get('legal.default_notice_days') */
    public static function get(string $path, $default = null)
    {
        $node = self::load();
        foreach (explode('.', $path) as $key) {
            if (!is_array($node) || !array_key_exists($key, $node)) {
                return $default;
            }
            $node = $node[$key];
        }
        return $node;
    }
}
