<?php

namespace App\Models;

use Core\Model;

class Block extends Model
{
    protected string $table = 'blocks';

    public function getActiveBySite(int $siteId): array
    {
        return $this->query(
            "SELECT * FROM blocks WHERE site_id = :site_id AND is_active = 1 ORDER BY sort_order ASC",
            ['site_id' => $siteId]
        )->fetchAll();
    }

    public function getBySite(int $siteId): array
    {
        return $this->query(
            "SELECT * FROM blocks WHERE site_id = :site_id ORDER BY sort_order ASC",
            ['site_id' => $siteId]
        )->fetchAll();
    }
}
