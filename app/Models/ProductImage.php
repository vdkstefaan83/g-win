<?php

namespace App\Models;

use Core\Model;

class ProductImage extends Model
{
    protected string $table = 'product_images';

    public function getByProduct(int $productId): array
    {
        return $this->query(
            "SELECT * FROM product_images WHERE product_id = :product_id ORDER BY sort_order DESC",
            ['product_id' => $productId]
        )->fetchAll();
    }

    public function setPrimary(int $productId, int $imageId): bool
    {
        // Reset all
        $this->query(
            "UPDATE product_images SET is_primary = 0 WHERE product_id = :product_id",
            ['product_id' => $productId]
        );
        // Set new primary
        return $this->update($imageId, ['is_primary' => 1]);
    }
}
