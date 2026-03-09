<?php

namespace App\Models;

use Core\Model;

class Cart extends Model
{
    protected string $table = 'carts';

    public function findBySession(string $sessionId): array|false
    {
        return $this->findBy('session_id', $sessionId);
    }

    public function getOrCreate(string $sessionId, ?int $customerId = null): array
    {
        $cart = $this->findBySession($sessionId);
        if (!$cart) {
            $id = $this->create([
                'session_id' => $sessionId,
                'customer_id' => $customerId,
            ]);
            $cart = $this->findById($id);
        }
        return $cart;
    }

    public function getItems(int $cartId): array
    {
        return $this->query(
            "SELECT ci.*, p.name, p.slug, p.price as current_price, pi.filename as image
             FROM cart_items ci
             JOIN products p ON ci.product_id = p.id
             LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
             WHERE ci.cart_id = :cart_id",
            ['cart_id' => $cartId]
        )->fetchAll();
    }

    public function getTotal(int $cartId): float
    {
        $result = $this->query(
            "SELECT SUM(ci.price * ci.quantity) as total FROM cart_items ci WHERE ci.cart_id = :cart_id",
            ['cart_id' => $cartId]
        )->fetch();

        return (float) ($result['total'] ?? 0);
    }

    public function getItemCount(int $cartId): int
    {
        $result = $this->query(
            "SELECT SUM(quantity) as count FROM cart_items WHERE cart_id = :cart_id",
            ['cart_id' => $cartId]
        )->fetch();

        return (int) ($result['count'] ?? 0);
    }

    public function clearItems(int $cartId): bool
    {
        return $this->query(
            "DELETE FROM cart_items WHERE cart_id = :cart_id",
            ['cart_id' => $cartId]
        ) !== false;
    }
}
