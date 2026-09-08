<?php

declare(strict_types=1);

namespace Core;

/**
 * Base controller: view rendering + auth gates that behave correctly for
 * both classic HTML requests and AJAX/JSON endpoints.
 */
abstract class Controller
{
    protected function view(string $template, array $data = [], ?string $layout = 'app'): never
    {
        header('Content-Type: text/html; charset=utf-8');
        echo View::render($template, $data, $layout);
        exit;
    }

    protected function requireAuth(): array
    {
        $user = Auth::user();
        if (!$user) {
            if (Request::wantsJson()) {
                Response::fail('Not signed in', 401);
            }
            Response::redirect('login');
        }
        return $user;
    }

    protected function requireRole(string ...$roles): array
    {
        $user = $this->requireAuth();
        if ($roles !== [] && !in_array($user['role'], $roles, true)) {
            if (Request::wantsJson()) {
                Response::fail('Your role does not permit this action', 403);
            }
            Response::redirect('dashboard', t('common.forbidden'), 'error');
        }
        return $user;
    }

    /** Validate CSRF on any POST unless the route opts out (login form has no session yet). */
    protected function checkCsrf(): void
    {
        if (Request::isPost()) {
            csrf_check();
        }
    }
}
