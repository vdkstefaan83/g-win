<?php

namespace Core;

use Twig\Environment;
use App\Models\Menu;
use App\Models\Site;

abstract class Controller
{
    protected Environment $twig;
    protected \PDO $db;
    protected array $site;

    public function __construct()
    {
        $this->twig = App::getTwig();
        $this->db = Database::getInstance();
        $this->site = App::getSite();
    }

    private static array|false|null $resolvedSite = null;

    private function getResolvedSite(): array|false
    {
        if (self::$resolvedSite === null) {
            $siteModel = new Site();
            self::$resolvedSite = $siteModel->findBySlug($this->site['slug'])
                ?: $siteModel->findByLinkedDomain($_SERVER['HTTP_HOST'] ?? '')
                ?: $siteModel->findByDomain($_SERVER['HTTP_HOST'] ?? '')
                ?: $siteModel->findFirst()
                ?: false;
        }
        return self::$resolvedSite;
    }

    protected function render(string $template, array $data = []): void
    {
        $lang = App::getLang();

        // Auto-inject menus and contact info for front-end templates only (skip for admin)
        if (!str_starts_with($template, 'admin/')) {
            $dbSite = $this->getResolvedSite();
            if ($dbSite) {
                $menuModel = new Menu();
                if (!isset($data['header_menu'])) {
                    $data['header_menu'] = $menuModel->getByLocationAndSite('header', $dbSite['id'], $lang);
                }
                if (!isset($data['footer_menu'])) {
                    $data['footer_menu'] = $menuModel->getByLocationAndSite('footer', $dbSite['id'], $lang);
                }
                if (!isset($data['services_menu'])) {
                    $data['services_menu'] = $menuModel->getByLocationAndSite('services', $dbSite['id'], $lang);
                }
            }

            // Auto-inject contact info from settings (global, shared across all sites)
            if (!isset($data['contact_address'])) {
                $settingModel = new \App\Models\Setting();
                $data['contact_address'] = $settingModel->get('contact_address', null, '');
                $data['contact_phone'] = $settingModel->get('contact_phone', null, '');
                $data['contact_email'] = $settingModel->get('contact_email', null, '');
                $data['site_description'] = $settingModel->get('site_description', null, '');
                $data['sketchfab_premium'] = (bool) $settingModel->get('sketchfab_premium', null, '');
            }
        }

        // Check if shop is enabled (any menu item links to shop-related URLs)
        $data['shop_enabled'] = false;
        $shopKeywords = ['shop', 'winkelwagen', 'boutique', 'panier'];
        if (!empty($data['header_menu']['items'])) {
            foreach ($data['header_menu']['items'] as $item) {
                $url = trim($item['url'] ?? '', '/');
                $slug = $item['page_slug'] ?? '';
                $label = strtolower($item['label'] ?? '');
                foreach ($shopKeywords as $kw) {
                    if ($url === $kw || str_starts_with($url, $kw . '/') || $slug === $kw || str_contains($label, $kw)) {
                        $data['shop_enabled'] = true;
                        break 2;
                    }
                }
            }
        }

        // Resolve layout: database site layout takes priority, then config, then 'gwin' default
        $dbSiteForLayout = $this->getResolvedSite();
        $layout = $dbSiteForLayout['layout'] ?? $this->site['layout'] ?? 'gwin';

        $baseUrl = preg_replace('#^/fr(/|$)#', '/', $_SERVER['REQUEST_URI'] ?? '/');

        // Build language switch URLs
        if (!isset($data['alternate_url'])) {
            // Try to auto-translate the current URL slug
            $data['alternate_url'] = $this->resolveAlternateUrl($baseUrl, $lang);
        }

        $altLang = $lang === 'nl' ? 'fr' : 'nl';
        if ($data['alternate_url']) {
            $nlUrl = $lang === 'nl' ? $baseUrl : $data['alternate_url'];
            $frUrl = $lang === 'fr' ? $baseUrl : $data['alternate_url'];
        } else {
            $nlUrl = $baseUrl;
            $frUrl = '/fr' . ($baseUrl === '/' ? '' : $baseUrl);
        }

        $data = array_merge($data, [
            'site' => $this->site,
            'layout' => $layout,
            'lang' => $lang,
            'csrf_token' => Csrf::token(),
            'csrf_field' => Csrf::field(),
            'flash' => Session::getFlash(),
            'current_user' => Auth::user(),
            'current_url' => $_SERVER['REQUEST_URI'] ?? '/',
            'base_url' => $baseUrl,
            'nl_url' => $nlUrl,
            'fr_url' => $frUrl,
        ]);

        $html = $this->twig->render($template, $data);

        // Replace G-Win with non-breaking hyphen in visible text (not in HTML tags/attributes)
        $html = preg_replace_callback(
            '/(<[^>]*>)|G-Win/',
            function ($m) {
                // If it's an HTML tag, leave it untouched
                if (!empty($m[1])) return $m[0];
                // Replace the hyphen with non-breaking hyphen
                return "G\u{2011}Win";
            },
            $html
        );

        echo $html;
    }

    /**
     * Try to find the alternate language URL for a given base URL.
     */
    private function resolveAlternateUrl(string $baseUrl, string $currentLang): ?string
    {
        $targetLang = $currentLang === 'nl' ? 'fr' : 'nl';
        $prefix = $targetLang === 'nl' ? '' : '/fr';
        $db = Database::getInstance();

        // Parse path: /slug or /cat-slug/page-slug
        $path = trim($baseUrl, '/');
        if (empty($path)) return null;

        $parts = explode('/', $path);

        if (count($parts) === 1) {
            $slug = $parts[0];

            // Try page_categories
            $cat = $db->prepare("SELECT id FROM page_categories WHERE slug = :slug AND lang = :lang LIMIT 1");
            $cat->execute(['slug' => $slug, 'lang' => $currentLang]);
            $catRow = $cat->fetch();
            if ($catRow) {
                $trans = $db->prepare(
                    $currentLang === 'nl'
                        ? "SELECT slug FROM page_categories WHERE translation_of = :id AND lang = :target LIMIT 1"
                        : "SELECT slug FROM page_categories WHERE id = (SELECT translation_of FROM page_categories WHERE id = :id) LIMIT 1"
                );
                $trans->execute(['id' => $catRow['id']]);
                $row = $trans->fetch();
                if ($row) return $prefix . '/' . $row['slug'];
            }

            // Try pages
            $page = $db->prepare("SELECT id FROM pages WHERE slug = :slug AND lang = :lang LIMIT 1");
            $page->execute(['slug' => $slug, 'lang' => $currentLang]);
            $pageRow = $page->fetch();
            if ($pageRow) {
                $trans = $db->prepare(
                    $currentLang === 'nl'
                        ? "SELECT slug FROM pages WHERE translation_of = :id AND lang = :target LIMIT 1"
                        : "SELECT slug FROM pages WHERE id = (SELECT translation_of FROM pages WHERE id = :id) LIMIT 1"
                );
                $trans->execute(['id' => $pageRow['id'], 'target' => $targetLang]);
                $row = $trans->fetch();
                if ($row) return $prefix . '/' . $row['slug'];
            }
        } elseif (count($parts) === 2) {
            $catSlug = $parts[0];
            $pageSlug = $parts[1];
            $newCat = $catSlug;
            $newPage = $pageSlug;

            // Translate category
            $cat = $db->prepare("SELECT id FROM page_categories WHERE slug = :slug AND lang = :lang LIMIT 1");
            $cat->execute(['slug' => $catSlug, 'lang' => $currentLang]);
            $catRow = $cat->fetch();
            if ($catRow) {
                $trans = $db->prepare(
                    $currentLang === 'nl'
                        ? "SELECT slug FROM page_categories WHERE translation_of = :id AND lang = :target LIMIT 1"
                        : "SELECT slug FROM page_categories WHERE id = (SELECT translation_of FROM page_categories WHERE id = :id) LIMIT 1"
                );
                $trans->execute(['id' => $catRow['id'], 'target' => $targetLang]);
                $row = $trans->fetch();
                if ($row) $newCat = $row['slug'];
            }

            // Translate page
            $page = $db->prepare("SELECT id FROM pages WHERE slug = :slug AND lang = :lang LIMIT 1");
            $page->execute(['slug' => $pageSlug, 'lang' => $currentLang]);
            $pageRow = $page->fetch();
            if ($pageRow) {
                $trans = $db->prepare(
                    $currentLang === 'nl'
                        ? "SELECT slug FROM pages WHERE translation_of = :id AND lang = :target LIMIT 1"
                        : "SELECT slug FROM pages WHERE id = (SELECT translation_of FROM pages WHERE id = :id) LIMIT 1"
                );
                $trans->execute(['id' => $pageRow['id'], 'target' => $targetLang]);
                $row = $trans->fetch();
                if ($row) $newPage = $row['slug'];
            }

            if ($newCat !== $catSlug || $newPage !== $pageSlug) {
                return $prefix . '/' . $newCat . '/' . $newPage;
            }
        }

        return null;
    }

    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    protected function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }

    protected function back(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        $this->redirect($referer);
    }

    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    protected function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function validate(array $rules): array
    {
        $errors = [];
        $data = [];

        foreach ($rules as $field => $rule) {
            $value = $this->input($field);
            $ruleList = explode('|', $rule);

            foreach ($ruleList as $r) {
                if ($r === 'required' && (is_null($value) || trim((string)$value) === '')) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is verplicht.';
                }
                if ($r === 'email' && $value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = 'Ongeldig e-mailadres.';
                }
                if (str_starts_with($r, 'min:')) {
                    $min = (int) substr($r, 4);
                    if ($value && strlen((string)$value) < $min) {
                        $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " moet minimaal {$min} tekens zijn.";
                    }
                }
                if (str_starts_with($r, 'max:')) {
                    $max = (int) substr($r, 4);
                    if ($value && strlen((string)$value) > $max) {
                        $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " mag maximaal {$max} tekens zijn.";
                    }
                }
                if ($r === 'numeric' && $value && !is_numeric($value)) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' moet een getal zijn.';
                }
            }

            $data[$field] = $value;
        }

        return ['data' => $data, 'errors' => $errors];
    }

}
