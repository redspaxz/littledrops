<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers;

use Core\Controller;
use Core\Request;
use Core\Response;

final class AuthController extends Controller
{
    public function loginForm(): void
    {
        if (\Core\Auth::check()) {
            Response::redirect('dashboard');
        }
        $this->view('Auth::login', ['title' => t('auth.login')], 'auth');
    }

    public function login(): void
    {
        csrf_check();
        $user = \Core\Auth::login(Request::str('email'), (string) (Request::input()['password'] ?? ''));
        if (!$user) {
            Response::redirect('login', t('auth.failed'), 'error');
        }
        Response::redirect('dashboard', t('auth.welcome') . ', ' . $user['full_name']);
    }

    public function logout(): void
    {
        csrf_check();
        \Core\Auth::logout();
        Response::redirect('login');
    }

    public function locale(): void
    {
        csrf_check();
        \Core\Auth::setLocale(Request::str('locale', 'en'));
        Response::redirect(Request::str('back', 'dashboard'));
    }

    /**
     * Public health probe (no auth, no secrets): confirms the app runs and
     * whether MySQL answers. Use it right after deploying to a new host.
     */
    public function health(): void
    {
        $db = ['ok' => false, 'error' => 'connection_failed'];
        try {
            $version = \Core\Database::scalar('SELECT VERSION()');
            $db = ['ok' => true, 'server' => (string) $version];
        } catch (\PDOException $e) {
            $db['error'] = str_contains($e->getMessage(), 'Access denied') ? 'access_denied' : 'connection_failed';
        }
        Response::json([
            'ok'  => $db['ok'],
            'app' => \Core\Config::get('app.name'),
            'php' => PHP_VERSION,
            'db'  => $db,
        ]);
    }
}
