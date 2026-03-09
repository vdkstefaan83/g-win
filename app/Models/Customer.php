<?php

namespace App\Models;

use Core\Model;

class Customer extends Model
{
    protected string $table = 'customers';

    public function findByEmail(string $email): array|false
    {
        return $this->findBy('email', $email);
    }

    public function getAppointments(int $customerId): array
    {
        return $this->query(
            "SELECT a.*, aps.start_time as slot_start, aps.end_time as slot_end
             FROM appointments a
             LEFT JOIN appointment_slots aps ON a.slot_id = aps.id
             WHERE a.customer_id = :customer_id
             ORDER BY a.date DESC",
            ['customer_id' => $customerId]
        )->fetchAll();
    }

    public function getOrders(int $customerId): array
    {
        return $this->query(
            "SELECT * FROM orders WHERE customer_id = :customer_id ORDER BY created_at DESC",
            ['customer_id' => $customerId]
        )->fetchAll();
    }

    public function search(string $term): array
    {
        $like = '%' . $term . '%';
        return $this->query(
            "SELECT * FROM customers WHERE first_name LIKE :term1 OR last_name LIKE :term2 OR email LIKE :term3 ORDER BY last_name ASC",
            ['term1' => $like, 'term2' => $like, 'term3' => $like]
        )->fetchAll();
    }
}
