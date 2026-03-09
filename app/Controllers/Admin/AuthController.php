<?php

namespace App\Controllers\Admin;

use Core\Controller;
use Core\Auth;
use Core\Session;
use App\Models\User;

class AuthController extends Controller
{
    public function loginForm(): void
    {
        if (Auth::isAdmin()) {
            $this->redirect('/admin');
        }
        $this->render('admin/auth/login.twig');
    }

    public function login(): void
    {
        $email = $this->input('email');
        $password = $this->input('password');

        $userModel = new User();
        $user = $userModel->findByEmail($email);

        // TEMP DEBUG
        $debug = [
            'email' => $email,
            'user_found' => $user ? 'yes' : 'no',
            'is_active' => $user['is_active'] ?? 'n/a',
            'pw_ok' => $user ? password_verify($password, $user['password_hash']) : false,
        ];
        echo '<pre>' . print_r($debug, true) . '</pre>';

        if ($user && $user['is_active'] && password_verify($password, $user['password_hash'])) {
            Auth::login($user);
            Session::flash('success', 'Welkom terug, ' . $user['name'] . '!');
            echo '<p style="color:green">LOGIN OK - <a href="/admin">Ga naar admin</a></p>';
            return;
        }

        echo '<p style="color:red">LOGIN MISLUKT</p>';
        echo '<p><a href="/admin/login">Terug</a></p>';
    }

    public function logout(): void
    {
        Auth::logout();
        Session::flash('success', 'U bent uitgelogd.');
        $this->redirect('/admin/login');
    }
}
