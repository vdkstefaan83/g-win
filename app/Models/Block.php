<?php

namespace App\Models;

use Core\Model;

class Block extends Model
{
    protected string $table = 'blocks';

    private function decodeOptions(array $blocks): array
    {
        foreach ($blocks as &$block) {
            if (!empty($block['options']) && is_string($block['options'])) {
                $block['options'] = json_decode($block['options'], true) ?: [];
            }
        }
        return $blocks;
    }

    public function getActiveBySite(int $siteId, string $lang = 'nl'): array
    {
        return $this->decodeOptions($this->query(
            "SELECT b.* FROM blocks b
             INNER JOIN block_sites bs ON bs.block_id = b.id
             WHERE bs.site_id = :site_id AND b.lang = :lang AND b.is_active = 1 AND b.page_id IS NULL ORDER BY b.sort_order ASC",
            ['site_id' => $siteId, 'lang' => $lang]
        )->fetchAll());
    }

    public function getAllWithSite(): array
    {
        return $this->query(
            "SELECT b.*, GROUP_CONCAT(DISTINCT s.name ORDER BY s.name SEPARATOR ', ') AS site_names,
                    p.title AS page_title,
                    (SELECT COUNT(*) FROM blocks b2 WHERE b2.translation_of = b.id) > 0 AS has_fr
             FROM blocks b
             LEFT JOIN block_sites bs ON bs.block_id = b.id
             LEFT JOIN sites s ON s.id = bs.site_id
             LEFT JOIN pages p ON b.page_id = p.id
             WHERE b.translation_of IS NULL
             GROUP BY b.id
             ORDER BY b.sort_order ASC"
        )->fetchAll();
    }

    public function getBySite(int $siteId): array
    {
        return $this->query(
            "SELECT b.*, GROUP_CONCAT(DISTINCT s2.name ORDER BY s2.name SEPARATOR ', ') AS site_names,
                    p.title AS page_title,
                    (SELECT COUNT(*) FROM blocks b2 WHERE b2.translation_of = b.id) > 0 AS has_fr
             FROM blocks b
             INNER JOIN block_sites bs ON bs.block_id = b.id
             LEFT JOIN block_sites bs2 ON bs2.block_id = b.id
             LEFT JOIN sites s2 ON s2.id = bs2.site_id
             LEFT JOIN pages p ON b.page_id = p.id
             WHERE bs.site_id = :site_id AND b.translation_of IS NULL
             GROUP BY b.id
             ORDER BY b.sort_order ASC",
            ['site_id' => $siteId]
        )->fetchAll();
    }

    public function getActiveByPage(int $pageId, string $lang = 'nl'): array
    {
        // Collect all related page IDs (current, NL master, FR translation)
        $pageIds = [$pageId];
        // Check if this is a FR page → add NL master
        $master = $this->query("SELECT translation_of FROM pages WHERE id = :id AND translation_of IS NOT NULL LIMIT 1", ['id' => $pageId])->fetch();
        if ($master) {
            $pageIds[] = (int)$master['translation_of'];
        }
        // Check if this is a NL page → add FR translation
        $frPage = $this->query("SELECT id FROM pages WHERE translation_of = :id LIMIT 1", ['id' => $pageId])->fetch();
        if ($frPage) {
            $pageIds[] = (int)$frPage['id'];
        }

        $placeholders = implode(',', array_map('intval', array_unique($pageIds)));
        // Get all blocks for any of these page IDs, prefer matching lang
        $results = $this->query(
            "SELECT b.* FROM blocks b
             WHERE b.page_id IN ({$placeholders}) AND b.is_active = 1
             ORDER BY FIELD(b.lang, :lang, 'nl', 'fr'), b.sort_order ASC",
            ['lang' => $lang]
        )->fetchAll();

        // Deduplicate by sort_order (keep first = preferred lang)
        $seen = [];
        $filtered = [];
        foreach ($results as $block) {
            $key = $block['sort_order'] . '_' . $block['type'];
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $filtered[] = $block;
            }
        }

        return $this->decodeOptions($filtered);
    }

    public function getActiveByCategory(int $categoryId, string $lang = 'nl'): array
    {
        // Collect all related category IDs
        $catIds = [$categoryId];
        $master = $this->query("SELECT translation_of FROM page_categories WHERE id = :id AND translation_of IS NOT NULL LIMIT 1", ['id' => $categoryId])->fetch();
        if ($master) {
            $catIds[] = (int)$master['translation_of'];
        }
        $frCat = $this->query("SELECT id FROM page_categories WHERE translation_of = :id LIMIT 1", ['id' => $categoryId])->fetch();
        if ($frCat) {
            $catIds[] = (int)$frCat['id'];
        }

        $placeholders = implode(',', array_map('intval', array_unique($catIds)));
        $results = $this->query(
            "SELECT b.* FROM blocks b
             WHERE b.page_category_id IN ({$placeholders}) AND b.is_active = 1
             ORDER BY FIELD(b.lang, :lang, 'nl', 'fr'), b.sort_order ASC",
            ['lang' => $lang]
        )->fetchAll();

        $seen = [];
        $filtered = [];
        foreach ($results as $block) {
            $key = $block['sort_order'] . '_' . $block['type'];
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $filtered[] = $block;
            }
        }

        return $this->decodeOptions($filtered);
    }

    public function getSiteIds(int $blockId): array
    {
        return array_column(
            $this->query("SELECT site_id FROM block_sites WHERE block_id = :id", ['id' => $blockId])->fetchAll(),
            'site_id'
        );
    }

    public function findLinkedTranslation(int $blockId): array|false
    {
        $result = $this->query(
            "SELECT * FROM blocks WHERE translation_of = :id LIMIT 1",
            ['id' => $blockId]
        )->fetch();
        if ($result) return $result;

        $block = $this->findById($blockId);
        if ($block && $block['translation_of']) {
            return $this->findById((int) $block['translation_of']);
        }
        return false;
    }

    public function syncSites(int $blockId, array $siteIds): void
    {
        $this->query("DELETE FROM block_sites WHERE block_id = :id", ['id' => $blockId]);
        foreach ($siteIds as $siteId) {
            $this->query("INSERT INTO block_sites (block_id, site_id) VALUES (:block_id, :site_id)", [
                'block_id' => $blockId, 'site_id' => (int) $siteId,
            ]);
        }
        if (!empty($siteIds)) {
            $this->update($blockId, ['site_id' => (int) $siteIds[0]]);
        }
    }
}
