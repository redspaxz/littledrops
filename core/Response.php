<?php

declare(strict_types=1);

namespace Core;

/** Response helpers: JSON for AJAX, redirects + flash for classic MVC flow. */
final class Response
{
    public static function json($data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function ok($data = null): never
    {
        self::json(['ok' => true, 'data' => $data]);
    }

    public static function fail(string $message, int $status = 400): never
    {
        self::json(['ok' => false, 'error' => $message], $status);
    }

    /** Post/Redirect/Get with a flash message rendered by the layout. */
    public static function redirect(string $path, ?string $flash = null, string $type = 'success'): never
    {
        if ($flash !== null) {
            $_SESSION['flash'] = ['msg' => $flash, 'type' => $type];
        }
        header('Location: ' . url($path));
        exit;
    }

    /** HTML document download (generated lease agreement / notice to quit). */
    public static function downloadHtml(string $filename, string $html): never
    {
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $html;
        exit;
    }
}
