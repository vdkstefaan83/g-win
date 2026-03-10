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
    private function getSiteMenus(): array
    {
        $siteModel = new Site();
        $site = $siteModel->findBySlug($this->site['slug']);
        $menuModel = new Menu();
        $lang = App::getLang();

        return [
            'header_menu' => $site ? $menuModel->getByLocationAndSite('header', $site['id'], $lang) : false,
            'footer_menu' => $site ? $menuModel->getByLocationAndSite('footer', $site['id'], $lang) : false,
            'layout' => $this->site['layout'] ?? 'gwin',
        ];
    }

    public function index(): void
    {
        $lang = App::getLang();
        $productModel = new Product();
        $categoryModel = new Category();

        $this->render('front/shop/index.twig', array_merge($this->getSiteMenus(), [
            'products' => $productModel->getActive($lang),
            'categories' => $categoryModel->getActive($lang),
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

        $this->render('front/shop/category.twig', array_merge($this->getSiteMenus(), [
            'category' => $category,
            'products' => $productModel->getByCategory($category['id'], $lang),
            'categories' => $categoryModel->getActive($lang),
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
