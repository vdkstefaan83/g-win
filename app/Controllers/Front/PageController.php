<?php

namespace App\Controllers\Front;

use Core\Controller;
use App\Models\Page;
use App\Models\PageCategory;
use App\Models\PageImage;
use App\Models\Site;

class PageController extends Controller
{
    private function resolveSite(): array|false
    {
        $siteModel = new Site();
        $site = $siteModel->findBySlug($this->site['slug']);
        if (!$site) {
            $site = $siteModel->findByDomain($_SERVER['HTTP_HOST'] ?? '');
        }
        if (!$site) {
            $site = $siteModel->findFirst();
        }
        return $site;
    }

    /**
     * Smart slug handler: checks if slug is a page category or standalone page.
     * If category with only 1 page -> show detail directly.
     * If category with multiple pages -> show overview.
     * If standalone page -> show page.
     */
    public function showOrCategory(string $slug): void
    {
        $site = $this->resolveSite();
        if (!$site) {
            http_response_code(404);
            $this->render('errors/404.twig');
            return;
        }

        // Check if it's a page category
        $catModel = new PageCategory();
        $category = $catModel->findBySlugAndSite($slug, $site['id']);

        if ($category) {
            $pageModel = new Page();
            $pages = $pageModel->getByCategory($category['id']);

            // If only 1 page in category, show it directly
            if (count($pages) === 1) {
                $page = $pages[0];
                $imageModel = new PageImage();
                $page['images'] = $imageModel->getByPage($page['id']);
                $this->render('front/pages/detail.twig', [
                    'page' => $page,
                    'category' => $category,
                    'layout' => $this->site['layout'] ?? 'gwin',
                ]);
                return;
            }

            // Multiple pages: show overview
            $this->render('front/pages/category.twig', [
                'category' => $category,
                'pages' => $pages,
                'layout' => $this->site['layout'] ?? 'gwin',
            ]);
            return;
        }

        // Fallback: standalone page
        $pageModel = new Page();
        $page = $pageModel->findBySlugAndSite($slug, $site['id']);

        if (!$page) {
            http_response_code(404);
            $this->render('errors/404.twig');
            return;
        }

        $imageModel = new PageImage();
        $page['images'] = $imageModel->getByPage($page['id']);

        $this->render('front/pages/show.twig', [
            'page' => $page,
            'layout' => $this->site['layout'] ?? 'gwin',
        ]);
    }

    /**
     * Show a page within a category: /{category-slug}/{page-slug}
     */
    public function showCategoryPage(string $categorySlug, string $pageSlug): void
    {
        $site = $this->resolveSite();
        if (!$site) {
            http_response_code(404);
            $this->render('errors/404.twig');
            return;
        }

        $catModel = new PageCategory();
        $category = $catModel->findBySlugAndSite($categorySlug, $site['id']);

        if (!$category) {
            http_response_code(404);
            $this->render('errors/404.twig');
            return;
        }

        $pageModel = new Page();
        $page = $pageModel->findBySlugAndCategory($pageSlug, $category['id']);

        if (!$page) {
            http_response_code(404);
            $this->render('errors/404.twig');
            return;
        }

        $imageModel = new PageImage();
        $page['images'] = $imageModel->getByPage($page['id']);

        $this->render('front/pages/detail.twig', [
            'page' => $page,
            'category' => $category,
            'layout' => $this->site['layout'] ?? 'gwin',
        ]);
    }

    // Keep old method for backwards compat
    public function show(string $slug): void
    {
        $this->showOrCategory($slug);
    }
}
