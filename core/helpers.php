<?php

declare(strict_types=1);

/**
 * Global view/controller helpers (kept deliberately small).
 */

defined('PBMS') || exit('No direct access');

/** Build an app URL that works with or without mod_rewrite: index.php?r=... */
function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/\\');
    return $path === '' ? $base . '/' : $base . "/index.php?r=" . urlencode($path);
}

/** URL for static files under assets/ (never routed through index.php). */
function asset(string $path): string
{
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/\\');
    return $base . '/' . ltrim($path, '/');
}

/** HTML-escape. */
function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Translate via core/translations.php using the session locale. */
function t(string $key, array $replace = []): string
{
    static $dict = null;
    $dict ??= require __DIR__ . '/translations.php';
    $locale = \Core\Auth::locale();
    $text = $dict[$locale][$key] ?? $dict['en'][$key] ?? $key;
    foreach ($replace as $k => $v) {
        $text = str_replace(':' . $k, (string) $v, $text);
    }
    return $text;
}

/** XAF has no minor units — integer amounts, thousands-separated. */
function fmt_xaf($amount): string
{
    return number_format((float) $amount, 0, ',', ' ') . ' FCFA';
}

function fmt_date(?string $date): string
{
    if (!$date) {
        return '—';
    }
    $ts = strtotime($date);
    return $ts ? date('d M Y', $ts) : $date;
}

/** Tailwind-free CSS hook: map a domain status to a badge colour class. */
function badge(string $status): string
{
    $s = strtolower(str_replace(' ', '-', $status));
    $green  = ['leased', 'active', 'paid', 'resolved', 'closed', 'confirmed', 'approved'];
    $amber  = ['reserved', 'partial', 'pending', 'assigned', 'in_progress', 'notice_to_quit_issued', 'demand_letter'];
    $red    = ['under-maintenance', 'out-of-service', 'unpaid', 'in_recovery', 'urgent', 'failed', 'notice_expired', 'enforcement'];
    $blue   = ['vacant', 'draft', 'open', 'expired'];
    if (in_array($s, $green, true)) {
        return 'badge badge-green';
    }
    if (in_array($s, $amber, true)) {
        return 'badge badge-amber';
    }
    if (in_array($s, $red, true)) {
        return 'badge badge-red';
    }
    return 'badge badge-blue';
}

/** CSRF token for POST forms. */
function csrf_token(): string
{
    $_SESSION['csrf'] ??= bin2hex(random_bytes(20));
    return $_SESSION['csrf'];
}

function csrf_check(): void
{
    $sent = $_POST['csrf'] ?? (\Core\Request::input()['csrf'] ?? '');
    if (!hash_equals($_SESSION['csrf'] ?? '', (string) $sent)) {
        if (\Core\Request::wantsJson()) {
            \Core\Response::fail('Invalid CSRF token', 419);
        }
        http_response_code(419);
        exit('Invalid CSRF token');
    }
}

function current_user(): ?array
{
    return \Core\Auth::user();
}
