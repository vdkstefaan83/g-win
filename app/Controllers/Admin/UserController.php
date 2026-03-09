<?php

namespace App\Controllers\Admin;

use Core\Controller;
use Core\Auth;
use Core\Session;
use App\Models\User;

class UserController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
    }

    public function index(): void
    {
        $this->render('admin/users/index.twig', [
            'users' => $this->userModel->findAll('name', 'ASC'),
        ]);
    }

    public function create(): void
    {
        $this->render('admin/users/create.twig');
    }

    public function store(): void
    {
        $validation = $this->validate([
            'name' => 'required|max:100',
            'email' => 'required|email',
            'password' => 'required|min:8',
        ]);

        if (!empty($validation['errors'])) {
            Session::flash('error', implode(' ', $validation['errors']));
            $this->redirect('/admin/users/create');
        }

        $data = [
            'name' => $validation['data']['name'],
            'email' => $validation['data']['email'],
            'password_hash' => password_hash($validation['data']['password'], PASSWORD_BCRYPT),
            'role' => $this->input('role', 'admin'),
            'is_active' => 1,
        ];

        $this->userModel->create($data);
        Session::flash('success', 'Gebruiker aangemaakt.');
        $this->redirect('/admin/users');
    }

    public function edit(int $id): void
    {
        $user = $this->userModel->findById($id);
        if (!$user) {
            Session::flash('error', 'Gebruiker niet gevonden.');
            $this->redirect('/admin/users');
        }

        $this->render('admin/users/edit.twig', ['edit_user' => $user]);
    }

    public function update(int $id): void
    {
        $validation = $this->validate([
            'name' => 'required|max:100',
            'email' => 'required|email',
        ]);

        if (!empty($validation['errors'])) {
            Session::flash('error', implode(' ', $validation['errors']));
            $this->redirect("/admin/users/{$id}/edit");
        }

        $data = [
            'name' => $validation['data']['name'],
            'email' => $validation['data']['email'],
            'role' => $this->input('role', 'admin'),
            'is_active' => $this->input('is_active') ? 1 : 0,
        ];

        $password = $this->input('password');
        if (!empty($password)) {
            $data['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
        }

        $this->userModel->update($id, $data);
        Session::flash('success', 'Gebruiker bijgewerkt.');
        $this->redirect('/admin/users');
    }

    public function destroy(int $id): void
    {
        if ($id === Auth::id()) {
            Session::flash('error', 'U kunt uzelf niet verwijderen.');
            $this->redirect('/admin/users');
        }

        $this->userModel->delete($id);
        Session::flash('success', 'Gebruiker verwijderd.');
        $this->redirect('/admin/users');
    }
}
