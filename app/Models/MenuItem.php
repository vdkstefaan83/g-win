<?php

namespace App\Models;

use Core\Model;

class MenuItem extends Model
{
    protected string $table = 'menu_items';

    public function getByMenu(int $menuId): array
    {
        return $this->query(
            "SELECT * FROM menu_items WHERE menu_id = :menu_id ORDER BY sort_order ASC",
            ['menu_id' => $menuId]
        )->fetchAll();
    }

    public function updateSortOrder(int $id, int $sortOrder, ?int $parentId = null): bool
    {
        return $this->query(
            "UPDATE menu_items SET sort_order = :sort_order, parent_id = :parent_id WHERE id = :id",
            ['sort_order' => $sortOrder, 'parent_id' => $parentId, 'id' => $id]
        ) !== false;
    }
}
