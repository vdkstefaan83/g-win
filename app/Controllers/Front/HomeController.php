<?php

namespace App\Controllers\Front;

use Core\App;
use Core\Controller;
use App\Models\Block;
use App\Models\Site;
use App\Models\Menu;
use App\Models\Product;

class HomeController extends Controller
{
    public function index(): void
    {
        $siteModel = new Site();
        $site = $siteModel->findBySlug($this->site['slug']);
        if (!$site) {
            $host = $_SERVER['HTTP_HOST'] ?? '';
            $site = $siteModel->findByLinkedDomain($host);
        }
        if (!$site) {
            $host = $_SERVER['HTTP_HOST'] ?? '';
            $site = $siteModel->findByDomain($host);
        }
        if (!$site) {
            $site = $siteModel->findFirst();
        }

        $lang = App::getLang();
        $blocks = [];
        $headerMenu = false;
        $footerMenu = false;
        $featuredProducts = [];

        if ($site) {
            $blockModel = new Block();
            $blocks = $blockModel->getActiveBySite($site['id'], $lang);

            // Decode JSON options for each block
            foreach ($blocks as &$block) {
                if (!empty($block['options']) && is_string($block['options'])) {
                    $block['options'] = json_decode($block['options'], true) ?: [];
                }
            }
            unset($block);

            $menuModel = new Menu();
            $headerMenu = $menuModel->getByLocationAndSite('header', $site['id'], $lang);
            $footerMenu = $menuModel->getByLocationAndSite('footer', $site['id'], $lang);

            $productModel = new Product();
            $featuredProducts = $productModel->getFeatured(4, $lang);
        }

        $layout = $this->site['layout'] ?? 'gwin';

        $this->render("front/home/index.twig", [
            'blocks' => $blocks,
            'header_menu' => $headerMenu,
            'footer_menu' => $footerMenu,
            'featured_products' => $featuredProducts,
            'layout' => $layout,
        ]);
    }
}
