<?php

namespace App\Models;

use Core\Model;

class Page extends Model
{
    protected string $table = 'pages';

    public function findBySlugAndSite(string $slug, int $siteId, string $lang = 'nl'): array|false
    {
        return $this->query(
            "SELECT p.* FROM pages p
             INNER JOIN page_sites ps ON ps.page_id = p.id
             WHERE p.slug = :slug AND ps.site_id = :site_id AND p.lang = :lang AND p.is_published = 1 LIMIT 1",
            ['slug' => $slug, 'site_id' => $siteId, 'lang' => $lang]
        )->fetch();
    }

    public function getBySite(int $siteId): array
    {
        return $this->query(
            "SELECT p.*, pc.name AS category_name,
                    GROUP_CONCAT(s2.name ORDER BY s2.name SEPARATOR ', ') AS site_names,
                    (SELECT COUNT(*) FROM pages p2 WHERE p2.translation_of = p.id) > 0 AS has_fr
             FROM pages p
             INNER JOIN page_sites ps ON ps.page_id = p.id
             LEFT JOIN page_categories pc ON p.page_category_id = pc.id
             LEFT JOIN page_sites ps2 ON ps2.page_id = p.id
             LEFT JOIN sites s2 ON s2.id = ps2.site_id
             WHERE ps.site_id = :site_id AND p.translation_of IS NULL
             GROUP BY p.id
             ORDER BY pc.name ASC, p.page_category_id IS NULL, p.sort_order ASC",
            ['site_id' => $siteId]
        )->fetchAll();
    }

    public function getAllWithSite(): array
    {
        return $this->query(
            "SELECT p.*, pc.name AS category_name,
                    GROUP_CONCAT(s.name ORDER BY s.name SEPARATOR ', ') AS site_names,
                    (SELECT COUNT(*) FROM pages p2 WHERE p2.translation_of = p.id) > 0 AS has_fr
             FROM pages p
             LEFT JOIN page_sites ps ON ps.page_id = p.id
             LEFT JOIN sites s ON s.id = ps.site_id
             LEFT JOIN page_categories pc ON p.page_category_id = pc.id
             WHERE p.translation_of IS NULL
             GROUP BY p.id
             ORDER BY pc.name ASC, p.page_category_id IS NULL, p.sort_order ASC"
        )->fetchAll();
    }

    public function getPublishedBySite(int $siteId, string $lang = 'nl'): array
    {
        return $this->query(
            "SELECT p.* FROM pages p
             INNER JOIN page_sites ps ON ps.page_id = p.id
             WHERE ps.site_id = :site_id AND p.lang = :lang AND p.is_published = 1 ORDER BY p.sort_order ASC",
            ['site_id' => $siteId, 'lang' => $lang]
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

    public function findTranslation(int $pageId, string $lang): array|false
    {
        // Check if this page has a translation in the target language
        $result = $this->query(
            "SELECT * FROM pages WHERE translation_of = :id AND lang = :lang AND is_published = 1 LIMIT 1",
            ['id' => $pageId, 'lang' => $lang]
        )->fetch();

        if ($result) return $result;

        // Check reverse: if this page IS a translation, find the original
        $page = $this->findById($pageId);
        if ($page && $page['translation_of']) {
            if ($lang === 'nl') {
                // Original is the translation_of target
                return $this->query(
                    "SELECT * FROM pages WHERE id = :id AND lang = :lang AND is_published = 1 LIMIT 1",
                    ['id' => $page['translation_of'], 'lang' => $lang]
                )->fetch() ?: false;
            }
            // Find sibling translation
            return $this->query(
                "SELECT * FROM pages WHERE translation_of = :id AND lang = :lang AND is_published = 1 LIMIT 1",
                ['id' => $page['translation_of'], 'lang' => $lang]
            )->fetch() ?: false;
        }

        return false;
    }

    /**
     * Find the linked translation record (any status, for admin editing).
     */
    public function findLinkedTranslation(int $pageId): array|false
    {
        // Check if another page points to this one
        $result = $this->query(
            "SELECT * FROM pages WHERE translation_of = :id LIMIT 1",
            ['id' => $pageId]
        )->fetch();

        if ($result) return $result;

        // Check if this page points to another
        $page = $this->findById($pageId);
        if ($page && $page['translation_of']) {
            return $this->findById((int) $page['translation_of']);
        }

        return false;
    }

    public function getSiteIds(int $pageId): array
    {
        return array_column(
            $this->query("SELECT site_id FROM page_sites WHERE page_id = :id", ['id' => $pageId])->fetchAll(),
            'site_id'
        );
    }

    public function syncSites(int $pageId, array $siteIds): void
    {
        $this->query("DELETE FROM page_sites WHERE page_id = :id", ['id' => $pageId]);
        foreach ($siteIds as $siteId) {
            $this->query("INSERT INTO page_sites (page_id, site_id) VALUES (:page_id, :site_id)", [
                'page_id' => $pageId, 'site_id' => (int) $siteId,
            ]);
        }
        // Keep site_id column in sync (first selected site)
        if (!empty($siteIds)) {
            $this->update($pageId, ['site_id' => (int) $siteIds[0]]);
        }
    }
}
