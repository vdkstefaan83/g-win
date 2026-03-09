<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;

class CartService
{
    private Cart $cartModel;
    private CartItem $cartItemModel;

    public function __construct()
    {
        $this->cartModel = new Cart();
        $this->cartItemModel = new CartItem();
    }

    public function getCart(): array
    {
        $cart = $this->cartModel->getOrCreate(session_id());
        return [
            'cart' => $cart,
            'items' => $this->cartModel->getItems($cart['id']),
            'total' => $this->cartModel->getTotal($cart['id']),
            'count' => $this->cartModel->getItemCount($cart['id']),
        ];
    }

    public function mergeGuestCart(int $customerId): void
    {
        $cart = $this->cartModel->findBySession(session_id());
        if ($cart) {
            $this->cartModel->update($cart['id'], ['customer_id' => $customerId]);
        }
    }
}
