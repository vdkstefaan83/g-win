<?php

namespace App\Models;

use Core\Model;

class PageCategory extends Model
{
    protected string $table = 'page_categories';

    public function findBySlugAndSite(string $slug, int $siteId): array|false
    {
        return $this->query(
            "SELECT * FROM page_categories WHERE slug = :slug AND site_id = :site_id AND is_active = 1 LIMIT 1",
            ['slug' => $slug, 'site_id' => $siteId]
        )->fetch();
    }

    public function getBySite(int $siteId): array
    {
        return $this->query(
            "SELECT * FROM page_categories WHERE site_id = :site_id AND is_active = 1 ORDER BY sort_order ASC",
            ['site_id' => $siteId]
        )->fetchAll();
    }

    public function getAllWithSite(): array
    {
        return $this->query(
            "SELECT pc.*, s.name AS site_name, COUNT(p.id) AS page_count
             FROM page_categories pc
             LEFT JOIN sites s ON pc.site_id = s.id
             LEFT JOIN pages p ON pc.id = p.page_category_id AND p.is_published = 1
             GROUP BY pc.id
             ORDER BY pc.sort_order ASC"
        )->fetchAll();
    }
}
