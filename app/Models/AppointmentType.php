<?php

namespace App\Models;

use Core\Model;

class AppointmentType extends Model
{
    protected string $table = 'appointment_types';

    public function findBySlug(string $slug): array|false
    {
        return $this->findBy('slug', $slug);
    }

    public function getAllActive(string $lang = 'nl'): array
    {
        $types = $this->query(
            "SELECT * FROM appointment_types WHERE is_active = 1 ORDER BY sort_order ASC"
        )->fetchAll();

        // Add localized name/description
        foreach ($types as &$type) {
            $type['name'] = ($lang === 'fr' && !empty($type['name_fr'])) ? $type['name_fr'] : $type['name_nl'];
            $type['description'] = ($lang === 'fr' && !empty($type['description_fr'])) ? $type['description_fr'] : $type['description_nl'];
        }

        return $types;
    }

    public function getWithFlowSteps(int $id): array|false
    {
        $type = $this->findById($id);
        if (!$type) return false;

        $type['flow_steps'] = $this->query(
            "SELECT * FROM appointment_flow_steps WHERE appointment_type_id = :id ORDER BY sort_order ASC",
            ['id' => $id]
        )->fetchAll();

        return $type;
    }
}
