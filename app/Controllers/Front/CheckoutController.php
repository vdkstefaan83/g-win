<?php

namespace App\Controllers\Front;

use Core\App;
use Core\Controller;
use Core\Session;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Site;
use App\Models\Menu;
use App\Services\PaymentService;

class CheckoutController extends Controller
{
    public function index(): void
    {
        $cartModel = new Cart();
        $cart = $cartModel->getOrCreate(session_id());
        $items = $cartModel->getItems($cart['id']);

        if (empty($items)) {
            Session::flash('warning', 'Uw winkelwagen is leeg.');
            $this->redirect(App::langUrl('/shop'));
        }

        $siteModel = new Site();
        $site = $siteModel->findBySlug($this->site['slug']);
        $menuModel = new Menu();
        $lang = App::getLang();

        $this->render('front/checkout/index.twig', [
            'cart_items' => $items,
            'cart_total' => $cartModel->getTotal($cart['id']),
            'header_menu' => $site ? $menuModel->getByLocationAndSite('header', $site['id'], $lang) : false,
            'footer_menu' => $site ? $menuModel->getByLocationAndSite('footer', $site['id'], $lang) : false,
            'layout' => $this->site['layout'] ?? 'gwin',
        ]);
    }

    public function process(): void
    {
        $validation = $this->validate([
            'first_name' => 'required|max:100',
            'last_name' => 'required|max:100',
            'email' => 'required|email',
            'phone' => 'required',
            'address' => 'required',
            'city' => 'required',
            'postal_code' => 'required',
        ]);

        if (!empty($validation['errors'])) {
            Session::flash('error', implode(' ', $validation['errors']));
            $this->redirect(App::langUrl('/afrekenen'));
        }

        $cartModel = new Cart();
        $cart = $cartModel->getOrCreate(session_id());
        $items = $cartModel->getItems($cart['id']);

        if (empty($items)) {
            Session::flash('error', 'Uw winkelwagen is leeg.');
            $this->redirect(App::langUrl('/shop'));
        }

        // Find or create customer
        $customerModel = new Customer();
        $customer = $customerModel->findByEmail($validation['data']['email']);

        if (!$customer) {
            $customerId = $customerModel->create([
                'first_name' => $validation['data']['first_name'],
                'last_name' => $validation['data']['last_name'],
                'email' => $validation['data']['email'],
                'phone' => $validation['data']['phone'],
                'address' => $validation['data']['address'],
                'city' => $validation['data']['city'],
                'postal_code' => $validation['data']['postal_code'],
            ]);
        } else {
            $customerId = $customer['id'];
        }

        // Create order
        $total = $cartModel->getTotal($cart['id']);
        $orderModel = new Order();
        $orderItemModel = new OrderItem();

        $shippingAddress = $validation['data']['address'] . ', '
            . $validation['data']['postal_code'] . ' '
            . $validation['data']['city'];

        // Billing address: use separate address if provided, otherwise same as shipping
        $billingAddr = trim($this->input('billing_address', ''));
        if (!empty($billingAddr)) {
            $billingAddress = $billingAddr . ', '
                . trim($this->input('billing_postal_code', '')) . ' '
                . trim($this->input('billing_city', ''));
        } else {
            $billingAddress = $shippingAddress;
        }

        // Company info
        $companyName = trim($this->input('company_name', ''));
        $vatNumber = trim($this->input('vat_number', ''));

        $orderId = $orderModel->create([
            'customer_id' => $customerId,
            'order_number' => $orderModel->generateOrderNumber(),
            'status' => 'pending',
            'subtotal' => $total,
            'total' => $total,
            'shipping_address' => $shippingAddress,
            'billing_address' => $billingAddress,
            'company_name' => $companyName ?: null,
            'vat_number' => $vatNumber ?: null,
            'notes' => $this->input('notes', ''),
        ]);

        // Create order items
        foreach ($items as $item) {
            $orderItemModel->create([
                'order_id' => $orderId,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'product_name' => $item['name'],
            ]);
        }

        // Clear cart
        $cartModel->clearItems($cart['id']);

        // Initiate payment
        $paymentMethod = $this->input('payment_method', 'bancontact');
        $paymentService = new PaymentService();

        $order = $orderModel->findById($orderId);
        $redirectUrl = $paymentService->createPayment($order, $paymentMethod);

        if ($redirectUrl) {
            $this->redirect($redirectUrl);
        } else {
            Session::flash('error', 'Er is een fout opgetreden bij het aanmaken van de betaling.');
            $this->redirect(App::langUrl('/afrekenen'));
        }
    }
}
