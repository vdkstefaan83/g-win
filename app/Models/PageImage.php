<?php

namespace App\Models;

use Core\Model;

class PageImage extends Model
{
    protected string $table = 'page_images';

    public function getByPage(int $pageId): array
    {
        return $this->query(
            "SELECT * FROM page_images WHERE page_id = :page_id ORDER BY sort_order DESC",
            ['page_id' => $pageId]
        )->fetchAll();
    }
}
