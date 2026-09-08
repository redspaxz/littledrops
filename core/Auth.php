<?php

declare(strict_types=1);

namespace Core;

/**
 * Session auth + RBAC. Roles mirror the PBMS spec:
 * System Admin, Property Manager, Accounting, Technician, Owner/Landlord, Tenant.
 */
final class Auth
{
    public const ROLES = [
        'admin'            => 'System Admin',
        'property_manager' => 'Property Manager',
        'accountant'       => 'Accounting',
        'technician'       => 'Technician',
        'owner'            => 'Owner / Landlord',
        'tenant'           => 'Tenant',
    ];

    public static function boot(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'httponly' => true,
                'samesite' => 'Lax',
                'secure'   => (($_SERVER['HTTPS'] ?? '') === 'on'),
            ]);
            session_name('pbms_session');
            session_start();
        }
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function login(string $email, string $password): ?array
    {
        $user = Database::row(
            'SELECT id, full_name, email, phone, role, locale, active, password_hash
               FROM users WHERE email = ? LIMIT 1',
            [$email]
        );
        if (!$user || (int) $user['active'] !== 1 || !password_verify($password, $user['password_hash'])) {
            return null;
        }
        session_regenerate_id(true);
        unset($user['password_hash']);
        $_SESSION['user']   = $user;
        $_SESSION['locale'] = $user['locale'] ?: 'en';
        return $user;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public static function locale(): string
    {
        return $_SESSION['locale'] ?? Config::get('app.locale', 'en');
    }

    public static function setLocale(string $locale): void
    {
        $_SESSION['locale'] = in_array($locale, ['en', 'fr'], true) ? $locale : 'en';
    }
}
