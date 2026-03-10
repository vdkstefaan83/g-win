<?php

namespace App\Controllers\Front;

use Core\App;
use Core\Controller;
use Core\Session;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Models\Site;
use App\Models\Menu;

class PaymentController extends Controller
{
    public function webhook(): void
    {
        $mollieId = $this->input('id');
        if (!$mollieId) {
            http_response_code(400);
            return;
        }

        $paymentService = new PaymentService();
        $paymentService->handleWebhook($mollieId);

        http_response_code(200);
    }

    public function returnSuccess(): void
    {
        $siteModel = new Site();
        $site = $siteModel->findBySlug($this->site['slug']);
        $menuModel = new Menu();
        $lang = App::getLang();

        $this->render('front/checkout/success.twig', [
            'header_menu' => $site ? $menuModel->getByLocationAndSite('header', $site['id'], $lang) : false,
            'footer_menu' => $site ? $menuModel->getByLocationAndSite('footer', $site['id'], $lang) : false,
            'layout' => $this->site['layout'] ?? 'gwin',
        ]);
    }

    public function cancel(): void
    {
        Session::flash('warning', 'Betaling is geannuleerd.');

        $siteModel = new Site();
        $site = $siteModel->findBySlug($this->site['slug']);
        $menuModel = new Menu();
        $lang = App::getLang();

        $this->render('front/checkout/cancel.twig', [
            'header_menu' => $site ? $menuModel->getByLocationAndSite('header', $site['id'], $lang) : false,
            'footer_menu' => $site ? $menuModel->getByLocationAndSite('footer', $site['id'], $lang) : false,
            'layout' => $this->site['layout'] ?? 'gwin',
        ]);
    }
}
