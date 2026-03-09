<?php

namespace App\Controllers\Front;

use Core\Controller;
use Core\Session;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\Customer;
use App\Models\Setting;
use App\Models\Site;
use App\Models\Menu;

class AppointmentController extends Controller
{
    public function index(): void
    {
        $siteModel = new Site();
        $site = $siteModel->findBySlug($this->site['slug']);

        $menuModel = new Menu();
        $headerMenu = $site ? $menuModel->getByLocationAndSite('header', $site['id']) : false;
        $footerMenu = $site ? $menuModel->getByLocationAndSite('footer', $site['id']) : false;

        $settingModel = new Setting();
        $blockedDates = json_decode($settingModel->get('blocked_dates', null, '[]'), true);

        $this->render('front/appointments/index.twig', [
            'blocked_dates' => $blockedDates,
            'header_menu' => $headerMenu,
            'footer_menu' => $footerMenu,
            'layout' => $this->site['layout'] ?? 'gwin',
        ]);
    }

    public function getAvailableSlots(): void
    {
        $date = $this->input('date');
        $type = $this->input('type');

        if (!$date || !$type) {
            $this->json(['error' => 'Datum en type zijn verplicht.'], 400);
            return;
        }

        // Check if date is blocked
        $settingModel = new Setting();
        $blockedDates = json_decode($settingModel->get('blocked_dates', null, '[]'), true);

        if (in_array($date, $blockedDates)) {
            $this->json(['slots' => [], 'message' => 'Deze datum is niet beschikbaar.']);
            return;
        }

        // Validate day of week
        $dayOfWeek = (int) date('w', strtotime($date));
        if ($type === 'pregnancy' && $dayOfWeek !== 6) {
            $this->json(['slots' => [], 'message' => 'Zwangerschapsbeeldjes zijn enkel op zaterdag.']);
            return;
        }
        if ($type === 'child' && $dayOfWeek !== 0) {
            $this->json(['slots' => [], 'message' => 'Beeldjes met kind zijn enkel op zondag.']);
            return;
        }

        $slotModel = new AppointmentSlot();
        $slots = $slotModel->getAvailableForDate($date, $type);

        // For existing appointments on this date, mark as pending (orange)
        $appointmentModel = new Appointment();
        $existingAppointments = $appointmentModel->getByDate($date);

        $slotsWithStatus = [];
        foreach ($slots as $slot) {
            $slot['status'] = 'available';
            $slotsWithStatus[] = $slot;
        }

        // Add booked slots with their status
        foreach ($existingAppointments as $appt) {
            $slotsWithStatus[] = [
                'id' => $appt['slot_id'],
                'start_time' => $appt['start_time'],
                'end_time' => $appt['end_time'],
                'status' => $appt['status'], // pending = orange, confirmed = red
            ];
        }

        $this->json(['slots' => $slotsWithStatus]);
    }

    public function store(): void
    {
        $validation = $this->validate([
            'first_name' => 'required|max:100',
            'last_name' => 'required|max:100',
            'email' => 'required|email',
            'phone' => 'required',
            'type' => 'required',
        ]);

        if (!empty($validation['errors'])) {
            Session::flash('error', implode(' ', $validation['errors']));
            $this->redirect('/afspraken');
        }

        $type = $validation['data']['type'];
        $date = $this->input('date');
        $slotId = $this->input('slot_id');

        // Validate date
        if (!$date) {
            Session::flash('error', 'Selecteer een datum.');
            $this->redirect('/afspraken');
        }

        // Check blocked dates
        $settingModel = new Setting();
        $blockedDates = json_decode($settingModel->get('blocked_dates', null, '[]'), true);
        if (in_array($date, $blockedDates)) {
            Session::flash('error', 'Deze datum is niet beschikbaar.');
            $this->redirect('/afspraken');
        }

        // Find or create customer
        $customerModel = new Customer();
        $customer = $customerModel->findByEmail($validation['data']['email']);

        if (!$customer) {
            $customerId = $customerModel->create([
                'first_name' => $validation['data']['first_name'],
                'last_name' => $validation['data']['last_name'],
                'email' => $validation['data']['email'],
                'phone' => $validation['data']['phone'],
            ]);
        } else {
            $customerId = $customer['id'];
            // Update phone if changed
            $customerModel->update($customerId, ['phone' => $validation['data']['phone']]);
        }

        $appointmentModel = new Appointment();

        if ($type === 'pregnancy') {
            // Must have a slot selected
            if (!$slotId) {
                Session::flash('error', 'Selecteer een tijdslot.');
                $this->redirect('/afspraken');
            }

            $slotModel = new AppointmentSlot();
            if ($slotModel->isSlotBooked($date, (int)$slotId)) {
                Session::flash('error', 'Dit tijdslot is niet meer beschikbaar.');
                $this->redirect('/afspraken');
            }

            $slot = $slotModel->findById((int)$slotId);
            $appointmentId = $appointmentModel->create([
                'customer_id' => $customerId,
                'slot_id' => (int)$slotId,
                'type' => 'pregnancy',
                'date' => $date,
                'start_time' => $slot['start_time'],
                'end_time' => $slot['end_time'],
                'status' => 'pending',
                'notes' => $this->input('notes', ''),
            ]);
        } else {
            // Child: just the date, admin will confirm time
            $appointmentId = $appointmentModel->create([
                'customer_id' => $customerId,
                'slot_id' => 0,
                'type' => 'child',
                'date' => $date,
                'start_time' => '00:00:00',
                'end_time' => '00:00:00',
                'status' => 'pending',
                'notes' => $this->input('notes', ''),
            ]);
        }

        Session::flash('success', 'Uw afspraak is ingepland! U ontvangt een bevestiging zodra deze is goedgekeurd.');
        $this->redirect("/afspraken/bevestiging/{$appointmentId}");
    }

    public function confirm(int $id): void
    {
        $appointmentModel = new Appointment();
        $appointment = $appointmentModel->getWithCustomer($id);

        $siteModel = new Site();
        $site = $siteModel->findBySlug($this->site['slug']);
        $menuModel = new Menu();
        $headerMenu = $site ? $menuModel->getByLocationAndSite('header', $site['id']) : false;
        $footerMenu = $site ? $menuModel->getByLocationAndSite('footer', $site['id']) : false;

        $this->render('front/appointments/confirm.twig', [
            'appointment' => $appointment,
            'header_menu' => $headerMenu,
            'footer_menu' => $footerMenu,
            'layout' => $this->site['layout'] ?? 'gwin',
        ]);
    }
}
