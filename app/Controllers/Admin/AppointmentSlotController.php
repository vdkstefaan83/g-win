<?php

namespace App\Controllers\Admin;

use Core\Controller;
use Core\Session;
use App\Models\AppointmentSlot;
use App\Models\AppointmentType;

class AppointmentSlotController extends Controller
{
    private AppointmentSlot $slotModel;

    public function __construct()
    {
        parent::__construct();
        $this->slotModel = new AppointmentSlot();
    }

    public function index(): void
    {
        $typeModel = new AppointmentType();
        $types = $typeModel->findAll('sort_order', 'ASC');

        $selectedType = $this->input('type_id');

        $slots = $this->slotModel->query(
            "SELECT s.*, at.name_nl as type_name FROM appointment_slots s
             LEFT JOIN appointment_types at ON at.id = s.appointment_type_id
             " . ($selectedType ? "WHERE s.appointment_type_id = :type_id" : "") . "
             ORDER BY at.sort_order ASC, s.day_of_week ASC, s.start_time ASC",
            $selectedType ? ['type_id' => (int)$selectedType] : []
        )->fetchAll();

        $this->render('admin/appointment-slots/index.twig', [
            'slots' => $slots,
            'types' => $types,
            'selected_type_id' => $selectedType,
        ]);
    }

    public function store(): void
    {
        $typeId = (int) $this->input('appointment_type_id');
        $dayOfWeek = (int) $this->input('day_of_week');
        $startTime = $this->input('start_time');
        $endTime = $this->input('end_time');

        if (!$startTime || !$endTime || !$typeId) {
            Session::flash('error', 'Alle velden zijn verplicht.');
            $this->redirect('/admin/appointment-slots');
            return;
        }

        // Get type slug for backward compat
        $typeModel = new AppointmentType();
        $type = $typeModel->findById($typeId);

        $this->slotModel->create([
            'appointment_type_id' => $typeId,
            'type' => $type ? $type['slug'] : '',
            'day_of_week' => $dayOfWeek,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'max_bookings' => (int) $this->input('max_bookings', 1),
            'is_active' => 1,
        ]);

        Session::flash('success', 'Tijdslot aangemaakt.');
        $this->redirect('/admin/appointment-slots?type_id=' . $typeId);
    }

    public function update(int $id): void
    {
        $data = [
            'start_time' => $this->input('start_time'),
            'end_time' => $this->input('end_time'),
            'day_of_week' => (int) $this->input('day_of_week'),
            'max_bookings' => (int) $this->input('max_bookings', 1),
            'is_active' => $this->input('is_active') ? 1 : 0,
        ];

        $this->slotModel->update($id, $data);
        Session::flash('success', 'Tijdslot bijgewerkt.');
        $this->redirect('/admin/appointment-slots');
    }

    public function destroy(int $id): void
    {
        $this->slotModel->delete($id);
        Session::flash('success', 'Tijdslot verwijderd.');
        $this->redirect('/admin/appointment-slots');
    }
}
