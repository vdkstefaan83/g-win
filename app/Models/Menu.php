<?php

namespace App\Models;

use Core\Model;

class Menu extends Model
{
    protected string $table = 'menus';

    public function getBySite(int $siteId): array
    {
        return $this->query(
            "SELECT DISTINCT m.* FROM menus m
             INNER JOIN menu_sites ms ON ms.menu_id = m.id
             WHERE ms.site_id = :site_id",
            ['site_id' => $siteId]
        )->fetchAll();
    }

    public function getAllWithSite(): array
    {
        return $this->query(
            "SELECT m.*, GROUP_CONCAT(s.name ORDER BY s.name SEPARATOR ', ') AS site_names
             FROM menus m
             LEFT JOIN menu_sites ms ON ms.menu_id = m.id
             LEFT JOIN sites s ON s.id = ms.site_id
             GROUP BY m.id
             ORDER BY m.lang ASC, m.name ASC"
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

        // Single query for all items (parents + children)
        $allItems = $this->query(
            "SELECT mi.*, p.slug as page_slug FROM menu_items mi
             LEFT JOIN pages p ON mi.page_id = p.id
             WHERE mi.menu_id = :menu_id
             ORDER BY mi.sort_order ASC",
            ['menu_id' => $menu['id']]
        )->fetchAll();

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
