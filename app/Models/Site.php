<?php

namespace App\Models;

use Core\Model;

class Site extends Model
{
    protected string $table = 'sites';

    public function getPages(int $siteId): array
    {
        return $this->query(
            "SELECT * FROM pages WHERE site_id = :site_id ORDER BY sort_order ASC",
            ['site_id' => $siteId]
        )->fetchAll();
    }

    public function getMenus(int $siteId): array
    {
        return $this->query(
            "SELECT * FROM menus WHERE site_id = :site_id",
            ['site_id' => $siteId]
        )->fetchAll();
    }

    public function findBySlug(string $slug): array|false
    {
        return $this->findBy('slug', $slug);
    }

    public function findByDomain(string $domain): array|false
    {
        return $this->findBy('domain', $domain);
    }
}
