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

    public function getActive(): array
    {
        return $this->query(
            "SELECT c.*, COUNT(p.id) as product_count
             FROM categories c
             LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
             WHERE c.is_active = 1
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
}
