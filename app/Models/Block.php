<?php

namespace App\Models;

use Core\Model;

class Block extends Model
{
    protected string $table = 'blocks';

    public function getActiveBySite(int $siteId, string $lang = 'nl'): array
    {
        return $this->query(
            "SELECT b.* FROM blocks b
             INNER JOIN block_sites bs ON bs.block_id = b.id
             WHERE bs.site_id = :site_id AND b.lang = :lang AND b.is_active = 1 ORDER BY b.sort_order ASC",
            ['site_id' => $siteId, 'lang' => $lang]
        )->fetchAll();
    }

    public function getAllWithSite(): array
    {
        return $this->query(
            "SELECT b.*, GROUP_CONCAT(s.name ORDER BY s.name SEPARATOR ', ') AS site_names
             FROM blocks b
             LEFT JOIN block_sites bs ON bs.block_id = b.id
             LEFT JOIN sites s ON s.id = bs.site_id
             GROUP BY b.id
             ORDER BY b.lang ASC, b.sort_order ASC"
        )->fetchAll();
    }

    public function getBySite(int $siteId): array
    {
        return $this->query(
            "SELECT b.*, GROUP_CONCAT(s2.name ORDER BY s2.name SEPARATOR ', ') AS site_names
             FROM blocks b
             INNER JOIN block_sites bs ON bs.block_id = b.id
             LEFT JOIN block_sites bs2 ON bs2.block_id = b.id
             LEFT JOIN sites s2 ON s2.id = bs2.site_id
             WHERE bs.site_id = :site_id
             GROUP BY b.id
             ORDER BY b.lang ASC, b.sort_order ASC",
            ['site_id' => $siteId]
        )->fetchAll();
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
