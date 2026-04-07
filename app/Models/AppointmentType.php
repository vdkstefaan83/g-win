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

    public function getAllActive(string $lang = 'nl', ?int $siteId = null): array
    {
        if ($siteId) {
            $types = $this->query(
                "SELECT at.* FROM appointment_types at
                 INNER JOIN appointment_type_sites ats ON ats.appointment_type_id = at.id
                 WHERE at.is_active = 1 AND ats.site_id = :site_id
                 ORDER BY at.sort_order ASC",
                ['site_id' => $siteId]
            )->fetchAll();
        } else {
            $types = $this->query(
                "SELECT * FROM appointment_types WHERE is_active = 1 ORDER BY sort_order ASC"
            )->fetchAll();
        }

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

        $type['site_ids'] = $this->getSiteIds($id);

        return $type;
    }

    public function getSiteIds(int $typeId): array
    {
        return array_column(
            $this->query("SELECT site_id FROM appointment_type_sites WHERE appointment_type_id = :id", ['id' => $typeId])->fetchAll(),
            'site_id'
        );
    }

    public function syncSites(int $typeId, array $siteIds): void
    {
        $this->query("DELETE FROM appointment_type_sites WHERE appointment_type_id = :id", ['id' => $typeId]);
        foreach ($siteIds as $siteId) {
            $this->query("INSERT INTO appointment_type_sites (appointment_type_id, site_id) VALUES (:type_id, :site_id)", [
                'type_id' => $typeId, 'site_id' => (int) $siteId,
            ]);
        }
    }
}
