<?php

namespace App\Controllers\Admin;

use Core\Controller;
use Core\Session;
use App\Models\AppointmentType;
use App\Models\MailTemplate;
use App\Models\Site;

class AppointmentTypeController extends Controller
{
    private AppointmentType $typeModel;

    public function __construct()
    {
        parent::__construct();
        $this->typeModel = new AppointmentType();
    }

    public function index(): void
    {
        $this->render('admin/appointment-types/index.twig', [
            'types' => $this->typeModel->findAll('sort_order', 'ASC'),
        ]);
    }

    public function create(): void
    {
        $this->render('admin/appointment-types/create.twig');
    }

    public function store(): void
    {
        $name = trim($this->input('name_nl', ''));
        $slug = trim($this->input('slug', ''));

        if (empty($name) || empty($slug)) {
            Session::flash('error', 'Naam (NL) en slug zijn verplicht.');
            $this->redirect('/admin/appointment-types/create');
            return;
        }

        $data = [
            'slug' => $slug,
            'name_nl' => $name,
            'name_fr' => $this->input('name_fr', ''),
            'description_nl' => $this->input('description_nl', ''),
            'description_fr' => $this->input('description_fr', ''),
            'icon' => $this->input('icon', ''),
            'is_active' => $this->input('is_active') ? 1 : 0,
            'sort_order' => (int) $this->input('sort_order', 0),
        ];

        $id = $this->typeModel->create($data);
        Session::flash('success', 'Afspraaktype aangemaakt.');
        $this->redirect("/admin/appointment-types/{$id}/edit");
    }

    public function edit(int $id): void
    {
        $type = $this->typeModel->getWithFlowSteps($id);
        if (!$type) {
            Session::flash('error', 'Type niet gevonden.');
            $this->redirect('/admin/appointment-types');
            return;
        }

        $templateModel = new MailTemplate();
        $siteModel = new Site();

        $this->render('admin/appointment-types/edit.twig', [
            'type' => $type,
            'mail_templates' => $templateModel->findAll('name', 'ASC'),
            'sites' => $siteModel->findAll('name', 'ASC'),
        ]);
    }

    public function update(int $id): void
    {
        $name = trim($this->input('name_nl', ''));
        if (empty($name)) {
            Session::flash('error', 'Naam (NL) is verplicht.');
            $this->redirect("/admin/appointment-types/{$id}/edit");
            return;
        }

        $data = [
            'name_nl' => $name,
            'name_fr' => $this->input('name_fr', ''),
            'description_nl' => $this->input('description_nl', ''),
            'description_fr' => $this->input('description_fr', ''),
            'icon' => $this->input('icon', ''),
            'is_active' => $this->input('is_active') ? 1 : 0,
            'sort_order' => (int) $this->input('sort_order', 0),
        ];

        $this->typeModel->update($id, $data);

        // Sync sites
        $siteIds = $_POST['site_ids'] ?? [];
        if (!empty($siteIds)) {
            $this->typeModel->syncSites($id, $siteIds);
        }

        // Handle flow steps if submitted
        $flowSteps = $this->input('flow_steps');
        if ($flowSteps) {
            $steps = json_decode($flowSteps, true);
            if (is_array($steps)) {
                $this->syncFlowSteps($id, $steps);
            }
        }

        Session::flash('success', 'Afspraaktype bijgewerkt.');
        $this->redirect("/admin/appointment-types/{$id}/edit");
    }

    public function destroy(int $id): void
    {
        $this->typeModel->delete($id);
        Session::flash('success', 'Afspraaktype verwijderd.');
        $this->redirect('/admin/appointment-types');
    }

    private function syncFlowSteps(int $typeId, array $steps): void
    {
        $db = \Core\Database::getInstance();

        // Get existing step IDs
        $existing = $db->prepare("SELECT id FROM appointment_flow_steps WHERE appointment_type_id = :id");
        $existing->execute(['id' => $typeId]);
        $existingIds = array_column($existing->fetchAll(), 'id');

        $newIds = [];
        foreach ($steps as $index => $step) {
            $stepData = [
                'appointment_type_id' => $typeId,
                'step_type' => $step['step_type'],
                'label_nl' => $step['label_nl'] ?? '',
                'label_fr' => $step['label_fr'] ?? '',
                'config' => json_encode($step['config'] ?? []),
                'sort_order' => $index,
                'is_active' => 1,
            ];

            if (!empty($step['id']) && in_array($step['id'], $existingIds)) {
                // Update existing
                $sets = [];
                $params = ['id' => (int)$step['id']];
                foreach ($stepData as $key => $value) {
                    $sets[] = "{$key} = :{$key}";
                    $params[$key] = $value;
                }
                $db->prepare("UPDATE appointment_flow_steps SET " . implode(', ', $sets) . " WHERE id = :id")->execute($params);
                $newIds[] = (int)$step['id'];
            } else {
                // Create new
                $cols = implode(', ', array_keys($stepData));
                $placeholders = ':' . implode(', :', array_keys($stepData));
                $db->prepare("INSERT INTO appointment_flow_steps ({$cols}) VALUES ({$placeholders})")->execute($stepData);
                $newIds[] = (int)$db->lastInsertId();
            }
        }

        // Delete removed steps
        $toDelete = array_diff($existingIds, $newIds);
        if (!empty($toDelete)) {
            $ids = implode(',', array_map('intval', $toDelete));
            $db->exec("DELETE FROM appointment_flow_steps WHERE id IN ({$ids})");
        }
    }
}
