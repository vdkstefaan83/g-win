<?php

namespace App\Models;

use Core\Model;

class CartItem extends Model
{
    protected string $table = 'cart_items';

    public function findByCartAndProduct(int $cartId, int $productId): array|false
    {
        return $this->query(
            "SELECT * FROM cart_items WHERE cart_id = :cart_id AND product_id = :product_id LIMIT 1",
            ['cart_id' => $cartId, 'product_id' => $productId]
        )->fetch();
    }

    public function updateQuantity(int $id, int $quantity): bool
    {
        return $this->update($id, ['quantity' => $quantity]);
    }
}
