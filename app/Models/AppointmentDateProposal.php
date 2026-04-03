<?php

namespace App\Models;

use Core\Model;

class AppointmentDateProposal extends Model
{
    protected string $table = 'appointment_date_proposals';

    public function getByAppointmentId(int $appointmentId): array
    {
        return $this->query(
            "SELECT * FROM appointment_date_proposals WHERE appointment_id = :id ORDER BY sort_order ASC",
            ['id' => $appointmentId]
        )->fetchAll();
    }

    public function selectProposal(int $proposalId, int $appointmentId): bool
    {
        // Reset all
        $this->query(
            "UPDATE appointment_date_proposals SET is_selected = 0 WHERE appointment_id = :id",
            ['id' => $appointmentId]
        );
        // Set selected
        return $this->update($proposalId, ['is_selected' => 1]);
    }
}
