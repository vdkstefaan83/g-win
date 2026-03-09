<?php

namespace App\Controllers\Admin;

use Core\Controller;
use Core\Session;
use App\Models\Order;

class OrderController extends Controller
{
    private Order $orderModel;

    public function __construct()
    {
        parent::__construct();
        $this->orderModel = new Order();
    }

    public function index(): void
    {
        $this->render('admin/orders/index.twig', [
            'orders' => $this->orderModel->getAllWithCustomer(),
        ]);
    }

    public function show(int $id): void
    {
        $order = $this->orderModel->getWithItems($id);
        if (!$order) {
            Session::flash('error', 'Bestelling niet gevonden.');
            $this->redirect('/admin/orders');
        }

        $this->render('admin/orders/show.twig', [
            'order' => $order,
        ]);
    }

    public function updateStatus(int $id): void
    {
        $status = $this->input('status');
        if (!in_array($status, ['pending', 'paid', 'shipped', 'completed', 'cancelled'])) {
            Session::flash('error', 'Ongeldige status.');
            $this->redirect("/admin/orders/{$id}");
        }

        $this->orderModel->update($id, ['status' => $status]);
        Session::flash('success', 'Bestelstatus bijgewerkt.');
        $this->redirect("/admin/orders/{$id}");
    }

    public function filter(): void
    {
        $orders = $this->orderModel->filterByStatus(
            $this->input('status'),
            $this->input('date_from'),
            $this->input('date_to')
        );

        $this->json(['orders' => $orders]);
    }
}
