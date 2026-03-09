<?php

namespace App\Models;

use Core\Model;

class Appointment extends Model
{
    protected string $table = 'appointments';

    public function getWithCustomer(int $id): array|false
    {
        return $this->query(
            "SELECT a.*, c.first_name, c.last_name, c.email, c.phone
             FROM appointments a
             JOIN customers c ON a.customer_id = c.id
             WHERE a.id = :id LIMIT 1",
            ['id' => $id]
        )->fetch();
    }

    public function getAllWithCustomers(string $orderBy = 'date', string $direction = 'DESC'): array
    {
        return $this->query(
            "SELECT a.*, c.first_name, c.last_name, c.email, c.phone
             FROM appointments a
             JOIN customers c ON a.customer_id = c.id
             ORDER BY a.{$orderBy} {$direction}"
        )->fetchAll();
    }

    public function getByDate(string $date): array
    {
        return $this->query(
            "SELECT a.*, c.first_name, c.last_name, c.email
             FROM appointments a
             JOIN customers c ON a.customer_id = c.id
             WHERE a.date = :date AND a.status != 'cancelled'
             ORDER BY a.start_time ASC",
            ['date' => $date]
        )->fetchAll();
    }

    public function getByDateAndSlot(string $date, int $slotId): array|false
    {
        return $this->query(
            "SELECT * FROM appointments WHERE date = :date AND slot_id = :slot_id AND status != 'cancelled' LIMIT 1",
            ['date' => $date, 'slot_id' => $slotId]
        )->fetch();
    }

    public function getUpcoming(int $limit = 10): array
    {
        return $this->query(
            "SELECT a.*, c.first_name, c.last_name, c.email, c.phone
             FROM appointments a
             JOIN customers c ON a.customer_id = c.id
             WHERE a.date >= CURDATE() AND a.status != 'cancelled'
             ORDER BY a.date ASC, a.start_time ASC
             LIMIT {$limit}"
        )->fetchAll();
    }

    public function filterByStatus(?string $status = null, ?string $type = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $sql = "SELECT a.*, c.first_name, c.last_name, c.email, c.phone
                FROM appointments a
                JOIN customers c ON a.customer_id = c.id WHERE 1=1";
        $params = [];

        if ($status) {
            $sql .= " AND a.status = :status";
            $params['status'] = $status;
        }
        if ($type) {
            $sql .= " AND a.type = :type";
            $params['type'] = $type;
        }
        if ($dateFrom) {
            $sql .= " AND a.date >= :date_from";
            $params['date_from'] = $dateFrom;
        }
        if ($dateTo) {
            $sql .= " AND a.date <= :date_to";
            $params['date_to'] = $dateTo;
        }

        $sql .= " ORDER BY a.date DESC, a.start_time ASC";
        return $this->query($sql, $params)->fetchAll();
    }
}
