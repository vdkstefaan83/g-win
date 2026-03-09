<?php

namespace App\Models;

use Core\Model;

class Menu extends Model
{
    protected string $table = 'menus';

    public function getBySite(int $siteId): array
    {
        return $this->query(
            "SELECT * FROM menus WHERE site_id = :site_id",
            ['site_id' => $siteId]
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

    public function getByLocationAndSite(string $location, int $siteId): array|false
    {
        $menu = $this->query(
            "SELECT * FROM menus WHERE site_id = :site_id AND location = :location LIMIT 1",
            ['site_id' => $siteId, 'location' => $location]
        )->fetch();

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
}
