<?php

namespace App\Controllers\Admin;

use Core\Controller;
use Core\Session;
use App\Models\Appointment;
use App\Models\Setting;

class AppointmentController extends Controller
{
    private Appointment $appointmentModel;

    public function __construct()
    {
        parent::__construct();
        $this->appointmentModel = new Appointment();
    }

    public function index(): void
    {
        $settingModel = new Setting();
        $blockedDates = json_decode($settingModel->get('blocked_dates', null, '[]'), true);

        $this->render('admin/appointments/index.twig', [
            'appointments' => $this->appointmentModel->getAllWithCustomers(),
            'blocked_dates' => $blockedDates,
        ]);
    }

    public function show(int $id): void
    {
        $appointment = $this->appointmentModel->getWithCustomer($id);
        if (!$appointment) {
            Session::flash('error', 'Afspraak niet gevonden.');
            $this->redirect('/admin/appointments');
        }

        $this->render('admin/appointments/show.twig', [
            'appointment' => $appointment,
        ]);
    }

    public function edit(int $id): void
    {
        $appointment = $this->appointmentModel->getWithCustomer($id);
        if (!$appointment) {
            Session::flash('error', 'Afspraak niet gevonden.');
            $this->redirect('/admin/appointments');
        }

        $this->render('admin/appointments/edit.twig', [
            'appointment' => $appointment,
        ]);
    }

    public function update(int $id): void
    {
        $data = [
            'date' => $this->input('date'),
            'start_time' => $this->input('start_time'),
            'end_time' => $this->input('end_time'),
            'status' => $this->input('status'),
            'notes' => $this->input('notes', ''),
        ];

        $this->appointmentModel->update($id, $data);
        Session::flash('success', 'Afspraak bijgewerkt.');
        $this->redirect('/admin/appointments');
    }

    public function confirm(int $id): void
    {
        $appointment = $this->appointmentModel->findById($id);
        if (!$appointment) {
            Session::flash('error', 'Afspraak niet gevonden.');
            $this->redirect('/admin/appointments');
        }

        // For child appointments, admin sets the date and time during confirmation
        $data = ['status' => 'confirmed'];

        if ($appointment['type'] === 'child') {
            $date = $this->input('date');
            $startTime = $this->input('start_time');
            $endTime = $this->input('end_time');

            if ($date) $data['date'] = $date;
            if ($startTime) $data['start_time'] = $startTime;
            if ($endTime) $data['end_time'] = $endTime;
        }

        $this->appointmentModel->update($id, $data);
        Session::flash('success', 'Afspraak bevestigd.');
        $this->redirect('/admin/appointments');
    }

    public function cancelAppointment(int $id): void
    {
        $this->appointmentModel->update($id, ['status' => 'cancelled']);
        Session::flash('success', 'Afspraak geannuleerd.');
        $this->redirect('/admin/appointments');
    }

    public function blockDate(): void
    {
        $date = $this->input('date');
        if (!$date) {
            Session::flash('error', 'Selecteer een datum.');
            $this->redirect('/admin/appointments');
        }

        $settingModel = new Setting();
        $blockedDates = json_decode($settingModel->get('blocked_dates', null, '[]'), true);

        if (!in_array($date, $blockedDates)) {
            $blockedDates[] = $date;
            $settingModel->set('blocked_dates', json_encode($blockedDates));
        }

        Session::flash('success', "Datum {$date} is geblokkeerd.");
        $this->redirect('/admin/appointments');
    }

    public function unblockDate(): void
    {
        $date = $this->input('date');
        $settingModel = new Setting();
        $blockedDates = json_decode($settingModel->get('blocked_dates', null, '[]'), true);
        $blockedDates = array_values(array_filter($blockedDates, fn($d) => $d !== $date));
        $settingModel->set('blocked_dates', json_encode($blockedDates));

        Session::flash('success', "Datum {$date} is gedeblokkeerd.");
        $this->redirect('/admin/appointments');
    }

    public function filter(): void
    {
        $appointments = $this->appointmentModel->filterByStatus(
            $this->input('status'),
            $this->input('type'),
            $this->input('date_from'),
            $this->input('date_to')
        );

        $this->json(['appointments' => $appointments]);
    }
}
