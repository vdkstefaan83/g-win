<?php

namespace App\Models;

use Core\Model;

class AppointmentFlowStep extends Model
{
    protected string $table = 'appointment_flow_steps';

    public function getByTypeId(int $typeId): array
    {
        return $this->query(
            "SELECT * FROM appointment_flow_steps WHERE appointment_type_id = :id AND is_active = 1 ORDER BY sort_order ASC",
            ['id' => $typeId]
        )->fetchAll();
    }
}
