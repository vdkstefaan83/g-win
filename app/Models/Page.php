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
            "SELECT * FROM pages WHERE site_id = :site_id ORDER BY sort_order ASC",
            ['site_id' => $siteId]
        )->fetchAll();
    }

    public function getPublishedBySite(int $siteId): array
    {
        return $this->query(
            "SELECT * FROM pages WHERE site_id = :site_id AND is_published = 1 ORDER BY sort_order ASC",
            ['site_id' => $siteId]
        )->fetchAll();
    }
}
