<?php

namespace App\Controllers\Front;

use Core\Controller;
use App\Models\Page;
use App\Models\Site;
use App\Models\Menu;

class PageController extends Controller
{
    public function show(string $slug): void
    {
        $siteModel = new Site();
        $site = $siteModel->findBySlug($this->site['slug']);

        if (!$site) {
            http_response_code(404);
            $this->render('errors/404.twig');
            return;
        }

        $pageModel = new Page();
        $page = $pageModel->findBySlugAndSite($slug, $site['id']);

        if (!$page) {
            http_response_code(404);
            $this->render('errors/404.twig');
            return;
        }

        $menuModel = new Menu();
        $headerMenu = $menuModel->getByLocationAndSite('header', $site['id']);
        $footerMenu = $menuModel->getByLocationAndSite('footer', $site['id']);

        $this->render('front/pages/show.twig', [
            'page' => $page,
            'header_menu' => $headerMenu,
            'footer_menu' => $footerMenu,
            'layout' => $this->site['layout'] ?? 'gwin',
        ]);
    }
}
