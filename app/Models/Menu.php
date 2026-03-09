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

        $menu['items'] = $this->query(
            "SELECT mi.*, p.slug as page_slug FROM menu_items mi
             LEFT JOIN pages p ON mi.page_id = p.id
             WHERE mi.menu_id = :menu_id AND mi.parent_id IS NULL
             ORDER BY mi.sort_order ASC",
            ['menu_id' => $menu['id']]
        )->fetchAll();

        // Get children for each item
        foreach ($menu['items'] as &$item) {
            $item['children'] = $this->query(
                "SELECT mi.*, p.slug as page_slug FROM menu_items mi
                 LEFT JOIN pages p ON mi.page_id = p.id
                 WHERE mi.parent_id = :parent_id ORDER BY mi.sort_order ASC",
                ['parent_id' => $item['id']]
            )->fetchAll();
        }

        return $menu;
    }
}
