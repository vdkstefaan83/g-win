<?php

namespace App\Models;

use Core\Model;

class Order extends Model
{
    protected string $table = 'orders';

    public function getWithItems(int $id): array|false
    {
        $order = $this->findById($id);
        if (!$order) return false;

        $order['items'] = $this->query(
            "SELECT * FROM order_items WHERE order_id = :order_id",
            ['order_id' => $id]
        )->fetchAll();

        $order['customer'] = $this->query(
            "SELECT * FROM customers WHERE id = :id LIMIT 1",
            ['id' => $order['customer_id']]
        )->fetch();

        $order['payment'] = $this->query(
            "SELECT * FROM payments WHERE order_id = :order_id LIMIT 1",
            ['order_id' => $id]
        )->fetch();

        return $order;
    }

    public function getAllWithCustomer(string $orderBy = 'created_at', string $direction = 'DESC'): array
    {
        return $this->query(
            "SELECT o.*, c.first_name, c.last_name, c.email,
                    p.method as payment_method, p.status as payment_status
             FROM orders o
             JOIN customers c ON o.customer_id = c.id
             LEFT JOIN payments p ON o.id = p.order_id
             ORDER BY o.{$orderBy} {$direction}"
        )->fetchAll();
    }

    public function generateOrderNumber(): string
    {
        return 'GW-' . date('Ymd') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    public function getRecent(int $limit = 10): array
    {
        return $this->query(
            "SELECT o.*, c.first_name, c.last_name
             FROM orders o
             JOIN customers c ON o.customer_id = c.id
             ORDER BY o.created_at DESC
             LIMIT {$limit}"
        )->fetchAll();
    }

    public function filterByStatus(?string $status = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $sql = "SELECT o.*, c.first_name, c.last_name, c.email,
                       p.method as payment_method, p.status as payment_status
                FROM orders o
                JOIN customers c ON o.customer_id = c.id
                LEFT JOIN payments p ON o.id = p.order_id
                WHERE 1=1";
        $params = [];

        if ($status) {
            $sql .= " AND o.status = :status";
            $params['status'] = $status;
        }
        if ($dateFrom) {
            $sql .= " AND DATE(o.created_at) >= :date_from";
            $params['date_from'] = $dateFrom;
        }
        if ($dateTo) {
            $sql .= " AND DATE(o.created_at) <= :date_to";
            $params['date_to'] = $dateTo;
        }

        $sql .= " ORDER BY o.created_at DESC";
        return $this->query($sql, $params)->fetchAll();
    }
}
