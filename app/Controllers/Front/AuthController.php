<?php

namespace App\Controllers\Front;

use Core\Controller;
use Core\Session;
use App\Models\Customer;
use App\Models\Site;
use App\Models\Menu;

class AuthController extends Controller
{
    private function getSiteMenus(): array
    {
        $siteModel = new Site();
        $site = $siteModel->findBySlug($this->site['slug']);
        $menuModel = new Menu();

        return [
            'header_menu' => $site ? $menuModel->getByLocationAndSite('header', $site['id']) : false,
            'footer_menu' => $site ? $menuModel->getByLocationAndSite('footer', $site['id']) : false,
            'layout' => $this->site['layout'] ?? 'gwin',
        ];
    }

    public function loginForm(): void
    {
        $this->render('front/auth/login.twig', $this->getSiteMenus());
    }

    public function login(): void
    {
        $email = $this->input('email');
        $password = $this->input('password');

        $customerModel = new Customer();
        $customer = $customerModel->findByEmail($email);

        if ($customer && $customer['password_hash'] && password_verify($password, $customer['password_hash'])) {
            $_SESSION['customer'] = [
                'id' => $customer['id'],
                'name' => $customer['first_name'] . ' ' . $customer['last_name'],
                'email' => $customer['email'],
            ];
            Session::flash('success', 'Welkom terug!');
            $this->redirect('/');
        }

        Session::flash('error', 'Ongeldige inloggegevens.');
        $this->redirect('/login');
    }

    public function registerForm(): void
    {
        $this->render('front/auth/register.twig', $this->getSiteMenus());
    }

    public function register(): void
    {
        $validation = $this->validate([
            'first_name' => 'required|max:100',
            'last_name' => 'required|max:100',
            'email' => 'required|email',
            'password' => 'required|min:8',
        ]);

        if (!empty($validation['errors'])) {
            Session::flash('error', implode(' ', $validation['errors']));
            $this->redirect('/register');
        }

        $customerModel = new Customer();
        $existing = $customerModel->findByEmail($validation['data']['email']);

        if ($existing) {
            if ($existing['password_hash']) {
                Session::flash('error', 'Dit e-mailadres is al geregistreerd.');
                $this->redirect('/register');
            }
            // Customer exists from appointment but no password yet
            $customerModel->update($existing['id'], [
                'password_hash' => password_hash($validation['data']['password'], PASSWORD_BCRYPT),
            ]);
            $customerId = $existing['id'];
        } else {
            $customerId = $customerModel->create([
                'first_name' => $validation['data']['first_name'],
                'last_name' => $validation['data']['last_name'],
                'email' => $validation['data']['email'],
                'password_hash' => password_hash($validation['data']['password'], PASSWORD_BCRYPT),
            ]);
        }

        $customer = $customerModel->findById($customerId);
        $_SESSION['customer'] = [
            'id' => $customer['id'],
            'name' => $customer['first_name'] . ' ' . $customer['last_name'],
            'email' => $customer['email'],
        ];

        Session::flash('success', 'Account aangemaakt!');
        $this->redirect('/');
    }

    public function logout(): void
    {
        unset($_SESSION['customer']);
        Session::flash('success', 'U bent uitgelogd.');
        $this->redirect('/');
    }
}
