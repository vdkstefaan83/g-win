<?php

namespace App\Models;

use Core\Model;

class PageImage extends Model
{
    protected string $table = 'page_images';

    public function getByPage(int $pageId): array
    {
        $images = $this->query(
            "SELECT * FROM page_images WHERE page_id = :page_id ORDER BY sort_order DESC",
            ['page_id' => $pageId]
        )->fetchAll();

        // If no images found, check NL master page
        if (empty($images)) {
            $master = $this->query(
                "SELECT translation_of FROM pages WHERE id = :id AND translation_of IS NOT NULL LIMIT 1",
                ['id' => $pageId]
            )->fetch();
            if ($master) {
                $images = $this->query(
                    "SELECT * FROM page_images WHERE page_id = :page_id ORDER BY sort_order DESC",
                    ['page_id' => (int)$master['translation_of']]
                )->fetchAll();
            }
        }

        return $images;
    }
}
