<?php

namespace App\Models;

use Core\Model;

class Category extends Model
{
    protected string $table = 'categories';

    public function findBySlug(string $slug): array|false
    {
        return $this->findBy('slug', $slug);
    }

    public function getActive(string $lang = 'nl', ?int $siteId = null): array
    {
        $params = ['lang' => $lang, 'lang2' => $lang];

        $sql = "SELECT c.*, COUNT(p.id) as product_count
                FROM categories c
                LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1 AND p.lang = :lang";
        if ($siteId) {
            $sql .= " INNER JOIN product_sites ps ON ps.product_id = p.id AND ps.site_id = :site_id";
            $params['site_id'] = $siteId;
        }
        $sql .= " WHERE c.is_active = 1 AND c.lang = :lang2
                   GROUP BY c.id
                   HAVING product_count > 0
                   ORDER BY c.sort_order ASC";

        return $this->query($sql, $params)->fetchAll();
    }

    public function getAllForAdmin(): array
    {
        return $this->query(
            "SELECT c.*, p.name AS parent_name, COUNT(pr.id) AS product_count,
                    (SELECT COUNT(*) FROM categories c2 WHERE c2.translation_of = c.id) > 0 AS has_fr
             FROM categories c
             LEFT JOIN categories p ON c.parent_id = p.id
             LEFT JOIN products pr ON c.id = pr.category_id
             WHERE c.translation_of IS NULL
             GROUP BY c.id
             ORDER BY c.sort_order ASC"
        )->fetchAll();
    }

    public function getParentCategories(): array
    {
        return $this->query(
            "SELECT * FROM categories WHERE parent_id IS NULL ORDER BY sort_order ASC"
        )->fetchAll();
    }

    public function findLinkedTranslation(int $catId): array|false
    {
        $result = $this->query(
            "SELECT * FROM categories WHERE translation_of = :id LIMIT 1",
            ['id' => $catId]
        )->fetch();
        if ($result) return $result;

        $cat = $this->findById($catId);
        if ($cat && !empty($cat['translation_of'])) {
            return $this->findById((int) $cat['translation_of']);
        }
        return false;
    }
}
