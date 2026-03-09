<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;

class CheckoutService
{
    public function validateStock(array $cartItems): array
    {
        $errors = [];
        $productModel = new Product();

        foreach ($cartItems as $item) {
            $product = $productModel->findById($item['product_id']);
            if (!$product || !$product['is_active']) {
                $errors[] = "Product '{$item['name']}' is niet meer beschikbaar.";
            } elseif ($product['stock'] < $item['quantity']) {
                $errors[] = "Onvoldoende voorraad voor '{$item['name']}'. Beschikbaar: {$product['stock']}.";
            }
        }

        return $errors;
    }

    public function updateStock(array $orderItems): void
    {
        $productModel = new Product();

        foreach ($orderItems as $item) {
            $product = $productModel->findById($item['product_id']);
            if ($product) {
                $newStock = max(0, $product['stock'] - $item['quantity']);
                $productModel->update($product['id'], ['stock' => $newStock]);
            }
        }
    }
}
