<?php

namespace App\Models;

use Core\Model;

class Menu extends Model
{
    protected string $table = 'menus';

    public function getBySite(int $siteId): array
    {
        return $this->query(
            "SELECT m.*,
                    GROUP_CONCAT(DISTINCT s.name ORDER BY s.name SEPARATOR ', ') AS site_names,
                    COUNT(DISTINCT mi.id) AS item_count,
                    EXISTS(SELECT 1 FROM menus m2
                           INNER JOIN menu_sites ms2 ON ms2.menu_id = m2.id
                           WHERE m2.lang = 'fr' AND m2.location = m.location AND ms2.site_id = :site_id
                    ) AS has_fr
             FROM menus m
             INNER JOIN menu_sites ms ON ms.menu_id = m.id
             LEFT JOIN menu_sites ms4 ON ms4.menu_id = m.id
             LEFT JOIN sites s ON s.id = ms4.site_id
             LEFT JOIN menu_items mi ON mi.menu_id = m.id
             WHERE ms.site_id = :site_id2 AND m.lang = 'nl'
             GROUP BY m.id
             ORDER BY m.name ASC",
            ['site_id' => $siteId, 'site_id2' => $siteId]
        )->fetchAll();
    }

    public function getAllWithSite(): array
    {
        return $this->query(
            "SELECT m.*,
                    GROUP_CONCAT(DISTINCT s.name ORDER BY s.name SEPARATOR ', ') AS site_names,
                    COUNT(DISTINCT mi.id) AS item_count,
                    EXISTS(SELECT 1 FROM menus m2
                           INNER JOIN menu_sites ms2 ON ms2.menu_id = m2.id
                           WHERE m2.lang = 'fr' AND m2.location = m.location
                           AND ms2.site_id IN (SELECT ms3.site_id FROM menu_sites ms3 WHERE ms3.menu_id = m.id)
                    ) AS has_fr
             FROM menus m
             LEFT JOIN menu_sites ms ON ms.menu_id = m.id
             LEFT JOIN sites s ON s.id = ms.site_id
             LEFT JOIN menu_items mi ON mi.menu_id = m.id
             WHERE m.lang = 'nl'
             GROUP BY m.id
             ORDER BY m.name ASC"
        )->fetchAll();
    }

    public function getWithItems(int $menuId): array|false
    {
        $menu = $this->findById($menuId);
        if (!$menu) return false;

        $menu['items'] = $this->query(
            "SELECT mi.*, p.slug as page_slug FROM menu_items mi
             LEFT JOIN pages p ON mi.page_id = p.id
             WHERE mi.menu_id = :menu_id ORDER BY mi.sort_order ASC",
            ['menu_id' => $menuId]
        )->fetchAll();

        return $menu;
    }

    public function getByLocationAndSite(string $location, int $siteId, string $lang = 'nl'): array|false
    {
        $menu = $this->query(
            "SELECT m.* FROM menus m
             INNER JOIN menu_sites ms ON ms.menu_id = m.id
             WHERE ms.site_id = :site_id AND m.location = :location AND m.lang = :lang LIMIT 1",
            ['site_id' => $siteId, 'location' => $location, 'lang' => $lang]
        )->fetch();

        // Fallback to Dutch if no menu found for this language
        if (!$menu && $lang !== 'nl') {
            $menu = $this->query(
                "SELECT m.* FROM menus m
                 INNER JOIN menu_sites ms ON ms.menu_id = m.id
                 WHERE ms.site_id = :site_id AND m.location = :location AND m.lang = 'nl' LIMIT 1",
                ['site_id' => $siteId, 'location' => $location]
            )->fetch();
        }

        if (!$menu) return false;

        // Determine if we need to translate slugs (NL menu used as FR fallback)
        $needsTranslation = ($lang !== 'nl' && $menu['lang'] === 'nl');

        // Single query for all items (parents + children)
        $allItems = $this->query(
            "SELECT mi.*, p.slug as page_slug FROM menu_items mi
             LEFT JOIN pages p ON mi.page_id = p.id
             WHERE mi.menu_id = :menu_id
             ORDER BY mi.sort_order ASC",
            ['menu_id' => $menu['id']]
        )->fetchAll();

        // Translate slugs if using NL menu for FR
        if ($needsTranslation) {
            foreach ($allItems as &$item) {
                // Translate page slug
                if ($item['page_id'] && $item['page_slug']) {
                    $translated = $this->query(
                        "SELECT slug FROM pages WHERE translation_of = :id AND lang = :lang LIMIT 1",
                        ['id' => $item['page_id'], 'lang' => $lang]
                    )->fetch();
                    if ($translated) {
                        $item['page_slug'] = $translated['slug'];
                    }
                }
                // Translate URL slugs (categories and standalone pages)
                if ($item['url'] && preg_match('#^/([^/]+)$#', $item['url'], $m)) {
                    $slug = $m[1];
                    // Try page_categories first
                    $translated = $this->query(
                        "SELECT pc2.slug FROM page_categories pc1
                         JOIN page_categories pc2 ON pc2.translation_of = pc1.id AND pc2.lang = :lang
                         WHERE pc1.slug = :slug AND pc1.lang = 'nl'
                         LIMIT 1",
                        ['slug' => $slug, 'lang' => $lang]
                    )->fetch();
                    if (!$translated) {
                        // Try pages
                        $translated = $this->query(
                            "SELECT p2.slug FROM pages p1
                             JOIN pages p2 ON p2.translation_of = p1.id AND p2.lang = :lang
                             WHERE p1.slug = :slug AND p1.lang = 'nl'
                             LIMIT 1",
                            ['slug' => $slug, 'lang' => $lang]
                        )->fetch();
                    }
                    if ($translated) {
                        $item['url'] = '/' . $translated['slug'];
                    }
                }
                // Translate nested URLs (e.g. /category/page)
                if ($item['url'] && preg_match('#^/([^/]+)/([^/]+)$#', $item['url'], $m)) {
                    $catSlug = $m[1];
                    $pageSlug = $m[2];
                    // Translate category part
                    $translatedCat = $this->query(
                        "SELECT pc2.slug FROM page_categories pc1
                         JOIN page_categories pc2 ON pc2.translation_of = pc1.id AND pc2.lang = :lang
                         WHERE pc1.slug = :slug AND pc1.lang = 'nl'
                         LIMIT 1",
                        ['slug' => $catSlug, 'lang' => $lang]
                    )->fetch();
                    // Translate page part
                    $translatedPage = $this->query(
                        "SELECT p2.slug FROM pages p1
                         JOIN pages p2 ON p2.translation_of = p1.id AND p2.lang = :lang
                         WHERE p1.slug = :slug AND p1.lang = 'nl'
                         LIMIT 1",
                        ['slug' => $pageSlug, 'lang' => $lang]
                    )->fetch();
                    $newCat = $translatedCat ? $translatedCat['slug'] : $catSlug;
                    $newPage = $translatedPage ? $translatedPage['slug'] : $pageSlug;
                    if ($translatedCat || $translatedPage) {
                        $item['url'] = '/' . $newCat . '/' . $newPage;
                    }
                }
            }
            unset($item);
        }

        // Build tree in PHP instead of N+1 queries
        $parents = [];
        $children = [];
        foreach ($allItems as $item) {
            $item['children'] = [];
            if ($item['parent_id']) {
                $children[$item['parent_id']][] = $item;
            } else {
                $parents[$item['id']] = $item;
            }
        }
        foreach ($parents as &$parent) {
            $parent['children'] = $children[$parent['id']] ?? [];
        }
        $menu['items'] = array_values($parents);

        return $menu;
    }

    public function findLinkedTranslation(int $menuId): array|false
    {
        $menu = $this->findById($menuId);
        if (!$menu) return false;

        $otherLang = $menu['lang'] === 'nl' ? 'fr' : 'nl';

        // Find menu with same location linked to same sites
        $result = $this->query(
            "SELECT m.* FROM menus m
             INNER JOIN menu_sites ms ON ms.menu_id = m.id
             WHERE m.lang = :lang AND m.location = :location
             AND ms.site_id IN (SELECT site_id FROM menu_sites WHERE menu_id = :menu_id)
             LIMIT 1",
            ['lang' => $otherLang, 'location' => $menu['location'], 'menu_id' => $menuId]
        )->fetch();

        return $result ?: false;
    }

    public function getSiteIds(int $menuId): array
    {
        return array_column(
            $this->query("SELECT site_id FROM menu_sites WHERE menu_id = :id", ['id' => $menuId])->fetchAll(),
            'site_id'
        );
    }

    public function syncSites(int $menuId, array $siteIds): void
    {
        $this->query("DELETE FROM menu_sites WHERE menu_id = :id", ['id' => $menuId]);
        foreach ($siteIds as $siteId) {
            $this->query("INSERT INTO menu_sites (menu_id, site_id) VALUES (:menu_id, :site_id)", [
                'menu_id' => $menuId, 'site_id' => (int) $siteId,
            ]);
        }
        if (!empty($siteIds)) {
            $this->update($menuId, ['site_id' => (int) $siteIds[0]]);
        }
    }
}
