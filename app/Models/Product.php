<?php

namespace App\Models;

use Core\Model;

class Product extends Model
{
    protected string $table = 'products';

    public function findBySlug(string $slug): array|false
    {
        return $this->findBy('slug', $slug);
    }

    public function getWithImages(int $id): array|false
    {
        $product = $this->findById($id);
        if (!$product) return false;

        $product['images'] = $this->query(
            "SELECT * FROM product_images WHERE product_id = :id ORDER BY sort_order ASC",
            ['id' => $id]
        )->fetchAll();

        return $product;
    }

    public function getBySlugWithImages(string $slug): array|false
    {
        $product = $this->findBySlug($slug);
        if (!$product) return false;

        $product['images'] = $this->query(
            "SELECT * FROM product_images WHERE product_id = :id ORDER BY sort_order ASC",
            ['id' => $product['id']]
        )->fetchAll();

        $product['category'] = $this->query(
            "SELECT * FROM categories WHERE id = :id LIMIT 1",
            ['id' => $product['category_id']]
        )->fetch();

        return $product;
    }

    public function getActive(string $orderBy = 'created_at', string $direction = 'DESC'): array
    {
        return $this->query(
            "SELECT p.*, pi.filename as primary_image
             FROM products p
             LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
             WHERE p.is_active = 1
             ORDER BY p.{$orderBy} {$direction}"
        )->fetchAll();
    }

    public function getByCategory(int $categoryId): array
    {
        return $this->query(
            "SELECT p.*, pi.filename as primary_image
             FROM products p
             LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
             WHERE p.category_id = :category_id AND p.is_active = 1
             ORDER BY p.sort_order ASC, p.name ASC",
            ['category_id' => $categoryId]
        )->fetchAll();
    }

    public function getFeatured(int $limit = 8): array
    {
        return $this->query(
            "SELECT p.*, pi.filename as primary_image
             FROM products p
             LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
             WHERE p.is_active = 1 AND p.is_featured = 1
             ORDER BY p.created_at DESC
             LIMIT {$limit}"
        )->fetchAll();
    }
}
