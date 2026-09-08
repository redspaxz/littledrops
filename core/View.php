<?php

declare(strict_types=1);

namespace Core;

/**
 * View renderer for the modular monolith.
 *   View::render('Leases::show', $data)          -> modules/Leases/Views/show.php
 *   View::render('Leases::show', $data, 'app')   -> wrapped in layouts/app.php
 */
final class View
{
    public static function render(string $template, array $data = [], ?string $layout = 'app'): string
    {
        $php = self::resolve($template);
        extract($data, EXTR_SKIP);
        ob_start();
        require $php;
        $content = (string) ob_get_clean();

        if ($layout !== null) {
            $layoutFile = dirname(__DIR__) . "/layouts/$layout.php";
            ob_start();
            require $layoutFile;
            $content = (string) ob_get_clean();
        }
        return $content;
    }

    /** Render without a layout (documents, fragments, emails). */
    public static function partial(string $template, array $data = []): string
    {
        return self::render($template, $data, null);
    }

    private static function resolve(string $template): string
    {
        [$module, $view] = explode('::', $template, 2);
        $file = dirname(__DIR__) . "/modules/$module/Views/" . str_replace(['..', '\\'], '', $view) . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("View not found: $template");
        }
        return $file;
    }
}
