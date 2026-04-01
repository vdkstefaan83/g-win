<?php

namespace App\Controllers\Admin;

use Core\Controller;
use Core\Session;
use App\Models\Appointment;
use App\Models\Setting;
use App\Services\AppointmentPaymentService;
use App\Services\AppointmentNotificationService;

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

        // Migrate old format (plain strings) to new format (objects)
        $blockedDates = array_map(function ($entry) {
            if (is_string($entry)) {
                return ['date' => $entry, 'period' => 'hele_dag', 'reason' => ''];
            }
            return $entry;
        }, $blockedDates);

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
        $data = [];

        if ($appointment['type'] === 'child') {
            $date = $this->input('date');
            $startTime = $this->input('start_time');
            $endTime = $this->input('end_time');

            if ($date) $data['date'] = $date;
            if ($startTime) $data['start_time'] = $startTime;
            if ($endTime) $data['end_time'] = $endTime;
        }

        if (!empty($data)) {
            $this->appointmentModel->update($id, $data);
        }

        // Create payment request and send email with payment link
        $paymentService = new AppointmentPaymentService();
        $paymentUrl = $paymentService->createPaymentRequest($id);

        if ($paymentUrl) {
            $appointment = $this->appointmentModel->getWithCustomer($id);
            $notificationService = new AppointmentNotificationService();
            $notificationService->sendPaymentRequest($appointment, $paymentUrl);

            Session::flash('success', 'Betaalverzoek verstuurd naar ' . $appointment['email']);
        } else {
            Session::flash('error', 'Fout bij aanmaken betaalverzoek.');
        }

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
        $dateStart = $this->input('blocked_date');
        if (!$dateStart) {
            Session::flash('error', 'Selecteer een datum.');
            $this->redirect('/admin/appointments');
            return;
        }

        $dateEnd = $this->input('blocked_date_end') ?: $dateStart;
        $period = $this->input('period', 'hele_dag');
        $reason = $this->input('reason', '');

        // Build list of dates to block
        $datesToBlock = [];
        $current = new \DateTime($dateStart);
        $end = new \DateTime($dateEnd);
        while ($current <= $end) {
            $datesToBlock[] = $current->format('Y-m-d');
            $current->modify('+1 day');
        }

        $settingModel = new Setting();
        $blockedDates = json_decode($settingModel->get('blocked_dates', null, '[]'), true);

        // Migrate old format (plain strings) to new format (objects)
        $blockedDates = array_map(function ($entry) {
            if (is_string($entry)) {
                return ['date' => $entry, 'period' => 'hele_dag', 'reason' => ''];
            }
            return $entry;
        }, $blockedDates);

        $added = 0;
        foreach ($datesToBlock as $date) {
            // Check if already blocked with same period
            $exists = false;
            foreach ($blockedDates as $bd) {
                if ($bd['date'] === $date && $bd['period'] === $period) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $blockedDates[] = ['date' => $date, 'period' => $period, 'reason' => $reason];
                $added++;
            }
        }

        // Sort by date
        usort($blockedDates, fn($a, $b) => strcmp($a['date'], $b['date']));
        $settingModel->set('blocked_dates', json_encode($blockedDates));

        $periodLabels = ['hele_dag' => 'hele dag', 'voormiddag' => 'voormiddag', 'namiddag' => 'namiddag'];
        if ($dateStart === $dateEnd) {
            Session::flash('success', "Datum {$dateStart} ({$periodLabels[$period]}) is geblokkeerd.");
        } else {
            Session::flash('success', "{$added} dagen geblokkeerd van {$dateStart} tot {$dateEnd} ({$periodLabels[$period]}).");
        }
        $this->redirect('/admin/appointments');
    }

    public function unblockDate(): void
    {
        $index = (int) $this->input('index', -1);
        $settingModel = new Setting();
        $blockedDates = json_decode($settingModel->get('blocked_dates', null, '[]'), true);

        if (isset($blockedDates[$index])) {
            array_splice($blockedDates, $index, 1);
            $settingModel->set('blocked_dates', json_encode($blockedDates));
            Session::flash('success', 'Blokkering verwijderd.');
        }

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
