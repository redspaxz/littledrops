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
}
