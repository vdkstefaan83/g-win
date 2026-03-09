<?php

namespace App\Controllers\Admin;

use Core\Controller;
use App\Models\Appointment;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Product;

class DashboardController extends Controller
{
    public function index(): void
    {
        $appointmentModel = new Appointment();
        $orderModel = new Order();
        $customerModel = new Customer();
        $productModel = new Product();

        $this->render('admin/dashboard/index.twig', [
            'upcoming_appointments' => $appointmentModel->getUpcoming(5),
            'recent_orders' => $orderModel->getRecent(5),
            'total_customers' => $customerModel->count(),
            'total_products' => $productModel->count(),
            'total_orders' => $orderModel->count(),
            'pending_appointments' => $appointmentModel->count('*', 'status = :status', ['status' => 'pending']),
        ]);
    }
}
