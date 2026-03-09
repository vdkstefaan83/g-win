<?php

namespace App\Models;

use Core\Model;

class AppointmentSlot extends Model
{
    protected string $table = 'appointment_slots';

    public function getByType(string $type): array
    {
        return $this->query(
            "SELECT * FROM appointment_slots WHERE type = :type AND is_active = 1 ORDER BY start_time ASC",
            ['type' => $type]
        )->fetchAll();
    }

    public function getAvailableForDate(string $date, string $type): array
    {
        $dayOfWeek = (int) date('w', strtotime($date));

        return $this->query(
            "SELECT aps.* FROM appointment_slots aps
             WHERE aps.type = :type
             AND aps.day_of_week = :day
             AND aps.is_active = 1
             AND aps.id NOT IN (
                 SELECT a.slot_id FROM appointments a
                 WHERE a.date = :date AND a.status != 'cancelled'
             )
             ORDER BY aps.start_time ASC",
            ['type' => $type, 'day' => $dayOfWeek, 'date' => $date]
        )->fetchAll();
    }

    public function isSlotBooked(string $date, int $slotId): bool
    {
        $result = $this->query(
            "SELECT COUNT(*) as count FROM appointments
             WHERE date = :date AND slot_id = :slot_id AND status != 'cancelled'",
            ['date' => $date, 'slot_id' => $slotId]
        )->fetch();

        return $result['count'] > 0;
    }
}
