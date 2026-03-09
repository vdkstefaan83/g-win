<?php

namespace App\Models;

use Core\Model;

class Page extends Model
{
    protected string $table = 'pages';

    public function findBySlugAndSite(string $slug, int $siteId): array|false
    {
        return $this->query(
            "SELECT * FROM pages WHERE slug = :slug AND site_id = :site_id AND is_published = 1 LIMIT 1",
            ['slug' => $slug, 'site_id' => $siteId]
        )->fetch();
    }

    public function getBySite(int $siteId): array
    {
        return $this->query(
            "SELECT p.*, s.name AS site_name, pc.name AS category_name
             FROM pages p
             LEFT JOIN sites s ON p.site_id = s.id
             LEFT JOIN page_categories pc ON p.page_category_id = pc.id
             WHERE p.site_id = :site_id
             ORDER BY pc.name ASC, p.page_category_id IS NULL, p.sort_order ASC",
            ['site_id' => $siteId]
        )->fetchAll();
    }

    public function getAllWithSite(): array
    {
        return $this->query(
            "SELECT p.*, s.name AS site_name, pc.name AS category_name
             FROM pages p
             LEFT JOIN sites s ON p.site_id = s.id
             LEFT JOIN page_categories pc ON p.page_category_id = pc.id
             ORDER BY pc.name ASC, p.page_category_id IS NULL, p.sort_order ASC"
        )->fetchAll();
    }

    public function getPublishedBySite(int $siteId): array
    {
        return $this->query(
            "SELECT * FROM pages WHERE site_id = :site_id AND is_published = 1 ORDER BY sort_order ASC",
            ['site_id' => $siteId]
        )->fetchAll();
    }

    public function getByCategory(int $categoryId): array
    {
        return $this->query(
            "SELECT * FROM pages WHERE page_category_id = :cat_id AND is_published = 1 ORDER BY sort_order ASC",
            ['cat_id' => $categoryId]
        )->fetchAll();
    }

    public function findBySlugAndCategory(string $slug, int $categoryId): array|false
    {
        return $this->query(
            "SELECT * FROM pages WHERE slug = :slug AND page_category_id = :cat_id AND is_published = 1 LIMIT 1",
            ['slug' => $slug, 'cat_id' => $categoryId]
        )->fetch();
    }
}
