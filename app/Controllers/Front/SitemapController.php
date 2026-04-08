<?php

namespace App\Controllers\Front;

use Core\Controller;
use Core\Database;

class SitemapController extends Controller
{
    public function index(): void
    {
        $db = Database::getInstance();
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'gwin.vanderkerken.com';
        $baseUrl = $scheme . '://' . $host;

        header('Content-Type: application/xml; charset=utf-8');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
        $xml .= '        xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

        // Homepage
        $xml .= $this->url($baseUrl . '/', '1.0', 'weekly');

        // Page categories (NL)
        $categories = $db->query("SELECT pc.slug, pc.id, pc.updated_at FROM page_categories pc WHERE pc.lang = 'nl' AND pc.is_active = 1 ORDER BY pc.sort_order")->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($categories as $cat) {
            // Find FR translation
            $frCat = $db->prepare("SELECT slug FROM page_categories WHERE translation_of = :id AND lang = 'fr' LIMIT 1");
            $frCat->execute(['id' => $cat['id']]);
            $fr = $frCat->fetch(\PDO::FETCH_ASSOC);

            $nlUrl = $baseUrl . '/' . $cat['slug'];
            $frUrl = $fr ? $baseUrl . '/fr/' . $fr['slug'] : $baseUrl . '/fr/' . $cat['slug'];

            $xml .= $this->url($nlUrl, '0.8', 'weekly', $cat['updated_at'], $nlUrl, $frUrl);
        }

        // Pages (NL)
        $pages = $db->query("SELECT p.id, p.slug, p.page_category_id, p.updated_at, pc.slug as cat_slug
            FROM pages p
            LEFT JOIN page_categories pc ON p.page_category_id = pc.id
            WHERE p.lang = 'nl' AND p.translation_of IS NULL AND p.is_published = 1
            ORDER BY p.sort_order")->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($pages as $page) {
            $path = $page['cat_slug'] ? '/' . $page['cat_slug'] . '/' . $page['slug'] : '/' . $page['slug'];
            $nlUrl = $baseUrl . $path;

            // Find FR translation
            $frPage = $db->prepare("SELECT p2.slug, pc2.slug as cat_slug FROM pages p2
                LEFT JOIN page_categories pc2 ON p2.page_category_id = pc2.id
                WHERE p2.translation_of = :id AND p2.lang = 'fr' LIMIT 1");
            $frPage->execute(['id' => $page['id']]);
            $fr = $frPage->fetch(\PDO::FETCH_ASSOC);

            if ($fr) {
                $frCatSlug = $fr['cat_slug'] ?? $page['cat_slug'];
                // Try FR category slug
                if ($page['page_category_id']) {
                    $frCat = $db->prepare("SELECT slug FROM page_categories WHERE translation_of = :id AND lang = 'fr' LIMIT 1");
                    $frCat->execute(['id' => $page['page_category_id']]);
                    $frCatRow = $frCat->fetch(\PDO::FETCH_ASSOC);
                    if ($frCatRow) $frCatSlug = $frCatRow['slug'];
                }
                $frPath = $frCatSlug ? '/fr/' . $frCatSlug . '/' . $fr['slug'] : '/fr/' . $fr['slug'];
            } else {
                $frPath = '/fr' . $path;
            }
            $frUrl = $baseUrl . $frPath;

            $xml .= $this->url($nlUrl, '0.7', 'monthly', $page['updated_at'], $nlUrl, $frUrl);
        }

        // Shop
        $xml .= $this->url($baseUrl . '/shop', '0.7', 'weekly');

        // Products (NL)
        $products = $db->query("SELECT id, slug, updated_at FROM products WHERE lang = 'nl' AND translation_of IS NULL AND is_active = 1 ORDER BY sort_order")->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($products as $product) {
            $xml .= $this->url($baseUrl . '/shop/product/' . rawurlencode($product['slug']), '0.6', 'monthly', $product['updated_at']);
        }

        // Appointments
        $xml .= $this->url($baseUrl . '/afspraken', '0.8', 'monthly');

        $xml .= '</urlset>';

        echo $xml;
        exit;
    }

    private function url(string $loc, string $priority = '0.5', string $changefreq = 'monthly', ?string $lastmod = null, ?string $nlUrl = null, ?string $frUrl = null): string
    {
        $xml = "  <url>\n";
        $xml .= "    <loc>" . htmlspecialchars($loc) . "</loc>\n";
        if ($lastmod) {
            $xml .= "    <lastmod>" . date('Y-m-d', strtotime($lastmod)) . "</lastmod>\n";
        }
        $xml .= "    <changefreq>{$changefreq}</changefreq>\n";
        $xml .= "    <priority>{$priority}</priority>\n";
        if ($nlUrl && $frUrl) {
            $xml .= '    <xhtml:link rel="alternate" hreflang="nl" href="' . htmlspecialchars($nlUrl) . '"/>' . "\n";
            $xml .= '    <xhtml:link rel="alternate" hreflang="fr" href="' . htmlspecialchars($frUrl) . '"/>' . "\n";
        }
        $xml .= "  </url>\n";
        return $xml;
    }
}
