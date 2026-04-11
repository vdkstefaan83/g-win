<?php

namespace App\Controllers\Front;

use Core\App;
use Core\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Site;
use App\Models\Menu;

class ShopController extends Controller
{
    private ?array $resolvedDbSite = null;

    private function getSiteMenus(): array
    {
        $siteModel = new Site();
        $site = $siteModel->findBySlug($this->site['slug']);
        $this->resolvedDbSite = $site;
        $menuModel = new Menu();
        $lang = App::getLang();

        return [
            'header_menu' => $site ? $menuModel->getByLocationAndSite('header', $site['id'], $lang) : false,
            'footer_menu' => $site ? $menuModel->getByLocationAndSite('footer', $site['id'], $lang) : false,
            'layout' => $this->site['layout'] ?? 'gwin',
        ];
    }

    /**
     * Get site ID for product filtering. G-Win (slug 'gwin') shows all products.
     */
    private function getProductSiteFilter(): ?int
    {
        if ($this->resolvedDbSite && $this->resolvedDbSite['slug'] !== 'gwin') {
            return (int) $this->resolvedDbSite['id'];
        }
        return null; // null = show all products
    }

    public function index(): void
    {
        $lang = App::getLang();
        $productModel = new Product();
        $categoryModel = new Category();
        $menus = $this->getSiteMenus();
        $siteFilter = $this->getProductSiteFilter();

        $this->render('front/shop/index.twig', array_merge($menus, [
            'products' => $productModel->getActive($lang, 'sort_order', 'ASC', $siteFilter),
            'categories' => $categoryModel->getActive($lang, $siteFilter),
        ]));
    }

    public function category(string $slug): void
    {
        $lang = App::getLang();
        $categoryModel = new Category();
        $category = $categoryModel->findBySlug($slug);

        if (!$category) {
            http_response_code(404);
            $this->render('errors/404.twig');
            return;
        }

        $productModel = new Product();
        $menus = $this->getSiteMenus();
        $siteFilter = $this->getProductSiteFilter();

        $this->render('front/shop/category.twig', array_merge($menus, [
            'category' => $category,
            'products' => $productModel->getByCategory($category['id'], $lang, $siteFilter),
            'categories' => $categoryModel->getActive($lang, $siteFilter),
        ]));
    }

    public function show(string $slug): void
    {
        $productModel = new Product();
        $product = $productModel->getBySlugWithImages($slug);

        if (!$product) {
            http_response_code(404);
            $this->render('errors/404.twig');
            return;
        }

        $this->render('front/shop/show.twig', array_merge($this->getSiteMenus(), [
            'product' => $product,
        ]));
    }
}
