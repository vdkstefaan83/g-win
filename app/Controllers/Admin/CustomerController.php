<?php

namespace App\Controllers\Admin;

use Core\Controller;
use Core\Session;
use App\Models\Customer;

class CustomerController extends Controller
{
    private Customer $customerModel;

    public function __construct()
    {
        parent::__construct();
        $this->customerModel = new Customer();
    }

    public function index(): void
    {
        $this->render('admin/customers/index.twig', [
            'customers' => $this->customerModel->findAll('last_name', 'ASC'),
        ]);
    }

    public function show(int $id): void
    {
        $customer = $this->customerModel->findById($id);
        if (!$customer) {
            Session::flash('error', 'Klant niet gevonden.');
            $this->redirect('/admin/customers');
        }

        $this->render('admin/customers/show.twig', [
            'customer' => $customer,
            'appointments' => $this->customerModel->getAppointments($id),
            'orders' => $this->customerModel->getOrders($id),
        ]);
    }

    public function edit(int $id): void
    {
        $customer = $this->customerModel->findById($id);
        if (!$customer) {
            Session::flash('error', 'Klant niet gevonden.');
            $this->redirect('/admin/customers');
        }

        $this->render('admin/customers/edit.twig', [
            'customer' => $customer,
        ]);
    }

    public function update(int $id): void
    {
        $validation = $this->validate([
            'first_name' => 'required|max:100',
            'last_name' => 'required|max:100',
            'email' => 'required|email',
        ]);

        if (!empty($validation['errors'])) {
            Session::flash('error', implode(' ', $validation['errors']));
            $this->redirect("/admin/customers/{$id}/edit");
        }

        $data = $validation['data'];
        $data['phone'] = $this->input('phone', '');
        $data['address'] = $this->input('address', '');
        $data['city'] = $this->input('city', '');
        $data['postal_code'] = $this->input('postal_code', '');

        $this->customerModel->update($id, $data);
        Session::flash('success', 'Klant bijgewerkt.');
        $this->redirect("/admin/customers/{$id}");
    }

    public function filter(): void
    {
        $search = $this->input('search', '');
        $customers = $search
            ? $this->customerModel->search($search)
            : $this->customerModel->findAll('last_name', 'ASC');

        $this->json(['customers' => $customers]);
    }
}
