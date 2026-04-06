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
        // Always start from NL master blocks for consistent sort_order
        $nlBlocks = $this->query(
            "SELECT b.* FROM blocks b
             INNER JOIN block_sites bs ON bs.block_id = b.id
             WHERE bs.site_id = :site_id AND b.lang = 'nl' AND b.translation_of IS NULL AND b.is_active = 1 AND b.page_id IS NULL
             ORDER BY b.sort_order ASC",
            ['site_id' => $siteId]
        )->fetchAll();

        if ($lang === 'nl') {
            return $this->decodeOptions($nlBlocks);
        }

        // For FR: find translations, inherit image/link_url from NL
        $results = [];
        foreach ($nlBlocks as $nlBlock) {
            $frBlock = $this->query(
                "SELECT * FROM blocks WHERE translation_of = :id AND lang = :lang LIMIT 1",
                ['id' => $nlBlock['id'], 'lang' => $lang]
            )->fetch();

            if ($frBlock) {
                if (empty($frBlock['image']) && !empty($nlBlock['image'])) {
                    $frBlock['image'] = $nlBlock['image'];
                }
                if (empty($frBlock['link_url']) && !empty($nlBlock['link_url'])) {
                    $frBlock['link_url'] = $nlBlock['link_url'];
                }
                if (empty($frBlock['options']) && !empty($nlBlock['options'])) {
                    $frBlock['options'] = $nlBlock['options'];
                }
                // Use NL sort_order for consistent ordering
                $frBlock['sort_order'] = $nlBlock['sort_order'];
                $results[] = $frBlock;
            } else {
                $results[] = $nlBlock;
            }
        }

        return $this->decodeOptions($results);
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
        // Collect related page IDs (current + NL master)
        $pageIds = [$pageId];
        $master = $this->query("SELECT translation_of FROM pages WHERE id = :id AND translation_of IS NOT NULL LIMIT 1", ['id' => $pageId])->fetch();
        if ($master) {
            $pageIds[] = (int)$master['translation_of'];
        }

        $placeholders = implode(',', array_map('intval', array_unique($pageIds)));

        // Get NL blocks for these pages
        $nlBlocks = $this->query(
            "SELECT b.* FROM blocks b
             WHERE b.page_id IN ({$placeholders}) AND b.lang = 'nl' AND b.translation_of IS NULL AND b.is_active = 1
             ORDER BY b.sort_order ASC"
        )->fetchAll();

        if ($lang === 'nl') {
            return $this->decodeOptions($nlBlocks);
        }

        // For FR: try to find FR translation of each NL block, fallback to NL block with image
        $results = [];
        foreach ($nlBlocks as $nlBlock) {
            $frBlock = $this->query(
                "SELECT * FROM blocks WHERE translation_of = :id AND lang = :lang LIMIT 1",
                ['id' => $nlBlock['id'], 'lang' => $lang]
            )->fetch();

            if ($frBlock) {
                // Use FR block but inherit image from NL if FR has none
                if (empty($frBlock['image']) && !empty($nlBlock['image'])) {
                    $frBlock['image'] = $nlBlock['image'];
                }
                if (empty($frBlock['link_url']) && !empty($nlBlock['link_url'])) {
                    $frBlock['link_url'] = $nlBlock['link_url'];
                }
                $results[] = $frBlock;
            } else {
                // No FR translation — use NL block (images/embeds still useful)
                $results[] = $nlBlock;
            }
        }

        return $this->decodeOptions($results);
    }

    public function getActiveByCategory(int $categoryId, string $lang = 'nl'): array
    {
        // Collect related category IDs (current + NL master)
        $catIds = [$categoryId];
        $master = $this->query("SELECT translation_of FROM page_categories WHERE id = :id AND translation_of IS NOT NULL LIMIT 1", ['id' => $categoryId])->fetch();
        if ($master) {
            $catIds[] = (int)$master['translation_of'];
        }

        $placeholders = implode(',', array_map('intval', array_unique($catIds)));

        // Get NL blocks
        $nlBlocks = $this->query(
            "SELECT b.* FROM blocks b
             WHERE b.page_category_id IN ({$placeholders}) AND b.lang = 'nl' AND b.translation_of IS NULL AND b.is_active = 1
             ORDER BY b.sort_order ASC"
        )->fetchAll();

        if ($lang === 'nl') {
            return $this->decodeOptions($nlBlocks);
        }

        // For FR: find FR translation or fallback to NL
        $results = [];
        foreach ($nlBlocks as $nlBlock) {
            $frBlock = $this->query(
                "SELECT * FROM blocks WHERE translation_of = :id AND lang = :lang LIMIT 1",
                ['id' => $nlBlock['id'], 'lang' => $lang]
            )->fetch();

            if ($frBlock) {
                if (empty($frBlock['image']) && !empty($nlBlock['image'])) {
                    $frBlock['image'] = $nlBlock['image'];
                }
                if (empty($frBlock['link_url']) && !empty($nlBlock['link_url'])) {
                    $frBlock['link_url'] = $nlBlock['link_url'];
                }
                $results[] = $frBlock;
            } else {
                $results[] = $nlBlock;
            }
        }

        return $this->decodeOptions($results);
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
