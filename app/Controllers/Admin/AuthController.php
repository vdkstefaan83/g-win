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

        if ($user && $user['is_active'] && password_verify($password, $user['password_hash'])) {
            Auth::login($user);
            Session::flash('success', 'Welkom terug, ' . $user['name'] . '!');
            $this->redirect('/admin');
        }

        Session::flash('error', 'Ongeldige inloggegevens.');
        $this->redirect('/admin/login');
    }

    public function logout(): void
    {
        Auth::logout();
        Session::flash('success', 'U bent uitgelogd.');
        $this->redirect('/admin/login');
    }
}
