<?php

declare(strict_types=1);

namespace Core;

/** Request helpers: route, method, input (JSON or form), typed accessors. */
final class Request
{
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /** Route comes from ?r=... so the app runs on any Apache config without mod_rewrite. */
    public static function route(): string
    {
        $r = trim((string) ($_GET['r'] ?? ''), "/ \t\n\r");
        return $r === '' ? 'dashboard' : $r;
    }

    public static function isPost(): bool
    {
        return self::method() === 'POST';
    }

    public static function wantsJson(): bool
    {
        return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
            || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }

    /** Decoded JSON body, falling back to form fields. */
    public static function input(): array
    {
        static $cached = null;
        if ($cached === null) {
            $raw = file_get_contents('php://input');
            $parsed = $raw !== false && $raw !== '' ? json_decode($raw, true) : null;
            $cached = is_array($parsed) ? $parsed : $_POST;
        }
        return $cached;
    }

    public static function str(string $key, string $default = ''): string
    {
        return trim((string) (self::input()[$key] ?? $default));
    }

    public static function int(string $key, ?int $default = null): ?int
    {
        $in = self::input();
        return isset($in[$key]) && $in[$key] !== '' ? (int) $in[$key] : $default;
    }

    public static function requireStr(string $key, string $label): string
    {
        $v = self::str($key);
        if ($v === '') {
            throw new \InvalidArgumentException("Missing required field: $label");
        }
        return $v;
    }
}
