<?php

namespace App\Controllers\Front;

use Core\App;
use Core\Controller;
use App\Models\Block;
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
            $site = $siteModel->findByLinkedDomain($_SERVER['HTTP_HOST'] ?? '');
        }
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

        $lang = App::getLang();

        // Check if it's a page category
        $catModel = new PageCategory();
        $category = $catModel->findBySlugAndSite($slug, $site['id'], $lang);

        // If not found, try the other language and redirect to the translated slug
        if (!$category) {
            $otherLang = $lang === 'nl' ? 'fr' : 'nl';
            $otherCategory = $catModel->findBySlugAndSite($slug, $site['id'], $otherLang);
            if ($otherCategory) {
                $translated = $catModel->findLinkedTranslation($otherCategory['id']);
                if ($translated && $translated['lang'] === $lang) {
                    $prefix = $lang === 'nl' ? '' : '/fr';
                    $this->redirect($prefix . '/' . $translated['slug']);
                    return;
                }
            }
        }

        if ($category) {
            $pageModel = new Page();
            $pages = $pageModel->getByCategory($category['id'], $lang);

            // Build alternate URL for language switcher
            $alternateLang = $lang === 'nl' ? 'fr' : 'nl';
            $alternateCatUrl = null;
            $linkedCat = $catModel->findLinkedTranslation($category['id']);
            if ($linkedCat) {
                $prefix = $alternateLang === 'nl' ? '' : '/fr';
                $alternateCatUrl = $prefix . '/' . $linkedCat['slug'];
            }

            // If only 1 page in category, show it directly
            if (count($pages) === 1) {
                $page = $pages[0];
                $imageModel = new PageImage();
                $page['images'] = $imageModel->getByPage($page['id']);

                $alternateUrl = $this->getAlternateUrl($page, $pageModel, $lang);

                $blockModel = new Block();
                $pageBlocks = $blockModel->getActiveByPage($page['id'], $lang);

                $this->render('front/pages/detail.twig', [
                    'page' => $page,
                    'category' => $category,
                    'blocks' => $pageBlocks,
                    'layout' => $this->site['layout'] ?? 'gwin',
                    'alternate_url' => $alternateUrl ?? $alternateCatUrl,
                    'alternate_lang' => $alternateLang,
                ]);
                return;
            }

            // Multiple pages: show overview
            $this->render('front/pages/category.twig', [
                'category' => $category,
                'pages' => $pages,
                'layout' => $this->site['layout'] ?? 'gwin',
                'alternate_url' => $alternateCatUrl,
                'alternate_lang' => $alternateLang,
            ]);
            return;
        }

        // Fallback: standalone page
        $pageModel = new Page();
        $page = $pageModel->findBySlugAndSite($slug, $site['id'], $lang);

        if (!$page) {
            http_response_code(404);
            $this->render('errors/404.twig');
            return;
        }

        $imageModel = new PageImage();
        $page['images'] = $imageModel->getByPage($page['id']);

        $alternateUrl = $this->getAlternateUrl($page, $pageModel, $lang);

        // Load page blocks
        $blockModel = new Block();
        $pageBlocks = $blockModel->getActiveByPage($page['id'], $lang);

        $this->render('front/pages/show.twig', [
            'page' => $page,
            'blocks' => $pageBlocks,
            'layout' => $this->site['layout'] ?? 'gwin',
            'alternate_url' => $alternateUrl,
            'alternate_lang' => $lang === 'nl' ? 'fr' : 'nl',
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

        $lang = App::getLang();

        $catModel = new PageCategory();
        $category = $catModel->findBySlugAndSite($categorySlug, $site['id'], $lang);

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

        $alternateUrl = $this->getAlternateUrl($page, $pageModel, $lang);

        $blockModel = new Block();
        $pageBlocks = $blockModel->getActiveByPage($page['id'], $lang);

        $this->render('front/pages/detail.twig', [
            'page' => $page,
            'category' => $category,
            'blocks' => $pageBlocks,
            'layout' => $this->site['layout'] ?? 'gwin',
            'alternate_url' => $alternateUrl,
            'alternate_lang' => $lang === 'nl' ? 'fr' : 'nl',
        ]);
    }

    // Keep old method for backwards compat
    public function show(string $slug): void
    {
        $this->showOrCategory($slug);
    }

    private function getAlternateUrl(array $page, Page $pageModel, string $currentLang): ?string
    {
        $targetLang = $currentLang === 'nl' ? 'fr' : 'nl';
        $translation = $pageModel->findTranslation($page['id'], $targetLang);

        if (!$translation) return null;

        $prefix = $targetLang === 'nl' ? '' : '/fr';
        return $prefix . '/' . $translation['slug'];
    }
}
