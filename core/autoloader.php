<?php

declare(strict_types=1);

/**
 * Minimal PSR-4 autoloader (no Composer dependency).
 *   Core\...            -> core/...
 *   Modules\Leases\...  -> modules/Leases/...
 */

defined('PBMS') || exit('No direct access');

spl_autoload_register(static function (string $class): void {
    $prefixes = [
        'Core\\'    => __DIR__ . '/',
        'Modules\\' => dirname(__DIR__) . '/modules/',
    ];
    foreach ($prefixes as $prefix => $baseDir) {
        if (str_starts_with($class, $prefix)) {
            $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
            $file = $baseDir . $relative . '.php';
            if (is_file($file)) {
                require $file;
            }
            return;
        }
    }
});
