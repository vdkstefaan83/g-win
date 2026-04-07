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
            "SELECT * FROM product_images WHERE product_id = :id ORDER BY sort_order DESC",
            ['id' => $id]
        )->fetchAll();

        return $product;
    }

    public function getBySlugWithImages(string $slug): array|false
    {
        $product = $this->findBySlug($slug);
        if (!$product) return false;

        $product['images'] = $this->query(
            "SELECT * FROM product_images WHERE product_id = :id ORDER BY sort_order DESC",
            ['id' => $product['id']]
        )->fetchAll();

        $product['category'] = $this->query(
            "SELECT * FROM categories WHERE id = :id LIMIT 1",
            ['id' => $product['category_id']]
        )->fetch();

        return $product;
    }

    public function getActive(string $lang = 'nl', string $orderBy = 'name', string $direction = 'ASC', ?int $siteId = null): array
    {
        $sql = "SELECT p.*, pi.filename as primary_image
                FROM products p
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1";
        $params = ['lang' => $lang];

        if ($siteId) {
            $sql .= " INNER JOIN product_sites ps ON ps.product_id = p.id AND ps.site_id = :site_id";
            $params['site_id'] = $siteId;
        }

        $sql .= " WHERE p.is_active = 1 AND p.lang = :lang ORDER BY p.{$orderBy} {$direction}";
        return $this->query($sql, $params)->fetchAll();
    }

    public function getByCategory(int $categoryId, string $lang = 'nl', ?int $siteId = null): array
    {
        $sql = "SELECT p.*, pi.filename as primary_image
                FROM products p
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1";
        $params = ['category_id' => $categoryId, 'lang' => $lang];

        if ($siteId) {
            $sql .= " INNER JOIN product_sites ps ON ps.product_id = p.id AND ps.site_id = :site_id";
            $params['site_id'] = $siteId;
        }

        $sql .= " WHERE p.category_id = :category_id AND p.is_active = 1 AND p.lang = :lang ORDER BY p.sort_order ASC, p.name ASC";
        return $this->query($sql, $params)->fetchAll();
    }

    public function getFeatured(int $limit = 8, string $lang = 'nl', ?int $siteId = null): array
    {
        $sql = "SELECT p.*, pi.filename as primary_image
                FROM products p
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1";
        $params = ['lang' => $lang];

        if ($siteId) {
            $sql .= " INNER JOIN product_sites ps ON ps.product_id = p.id AND ps.site_id = :site_id";
            $params['site_id'] = $siteId;
        }

        $sql .= " WHERE p.is_active = 1 AND p.is_featured = 1 AND p.lang = :lang ORDER BY p.created_at DESC LIMIT {$limit}";
        return $this->query($sql, $params)->fetchAll();
    }

    public function getSiteIds(int $productId): array
    {
        return array_column(
            $this->query("SELECT site_id FROM product_sites WHERE product_id = :id", ['id' => $productId])->fetchAll(),
            'site_id'
        );
    }

    public function syncSites(int $productId, array $siteIds): void
    {
        $this->query("DELETE FROM product_sites WHERE product_id = :id", ['id' => $productId]);
        foreach ($siteIds as $siteId) {
            $this->query("INSERT INTO product_sites (product_id, site_id) VALUES (:product_id, :site_id)", [
                'product_id' => $productId, 'site_id' => (int) $siteId,
            ]);
        }
    }

    public function findLinkedTranslation(int $productId): array|false
    {
        $result = $this->query(
            "SELECT * FROM products WHERE translation_of = :id LIMIT 1",
            ['id' => $productId]
        )->fetch();
        if ($result) return $result;

        $product = $this->findById($productId);
        if ($product && $product['translation_of']) {
            return $this->findById((int) $product['translation_of']);
        }
        return false;
    }

    public function getAllWithCategory(): array
    {
        return $this->query(
            "SELECT p.*, c.name as category_name,
                    (SELECT pi.filename FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) as image,
                    (SELECT COUNT(*) FROM products p2 WHERE p2.translation_of = p.id) > 0 AS has_fr
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.translation_of IS NULL
             ORDER BY p.sort_order ASC, p.name ASC"
        )->fetchAll();
    }

    public function getByLang(string $lang): array
    {
        return $this->query(
            "SELECT id, name FROM products WHERE lang = :lang ORDER BY name ASC",
            ['lang' => $lang]
        )->fetchAll();
    }
}
