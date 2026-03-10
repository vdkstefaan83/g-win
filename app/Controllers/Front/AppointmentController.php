<?php

namespace App\Controllers\Front;

use Core\App;
use Core\Controller;
use Core\Session;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\Customer;
use App\Models\Setting;
use App\Models\Site;
use App\Models\Menu;
use App\Services\AppointmentPaymentService;
use App\Services\AppointmentNotificationService;

class AppointmentController extends Controller
{
    public function index(): void
    {
        $siteModel = new Site();
        $site = $siteModel->findBySlug($this->site['slug']);

        $lang = App::getLang();
        $menuModel = new Menu();
        $headerMenu = $site ? $menuModel->getByLocationAndSite('header', $site['id'], $lang) : false;
        $footerMenu = $site ? $menuModel->getByLocationAndSite('footer', $site['id'], $lang) : false;

        $settingModel = new Setting();
        $blockedDates = json_decode($settingModel->get('blocked_dates', null, '[]'), true);
        $maxBookingMonths = (int) $settingModel->get('appointment_max_months', null, '24');

        $this->render('front/appointments/index.twig', [
            'blocked_dates' => $blockedDates,
            'max_booking_months' => $maxBookingMonths,
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
            $this->json(['error' => 'Missing date/type.'], 400);
            return;
        }

        // Check if date is blocked
        $settingModel = new Setting();
        $blockedDates = json_decode($settingModel->get('blocked_dates', null, '[]'), true);

        if (in_array($date, $blockedDates)) {
            $this->json(['slots' => [], 'message' => 'Date not available.']);
            return;
        }

        // Validate day of week
        $dayOfWeek = (int) date('w', strtotime($date));
        if ($type === 'pregnancy' && $dayOfWeek !== 6) {
            $this->json(['slots' => [], 'message' => 'Pregnancy scans are only on Saturdays.']);
            return;
        }
        if ($type === 'child' && $dayOfWeek !== 0) {
            $this->json(['slots' => [], 'message' => 'Child scans are only on Sundays.']);
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

        $aptUrl = App::getLang() === 'fr' ? '/fr/rendez-vous' : '/afspraken';

        if (!empty($validation['errors'])) {
            Session::flash('error', implode(' ', $validation['errors']));
            $this->redirect($aptUrl);
        }

        $type = $validation['data']['type'];
        $date = $this->input('date');
        $slotId = $this->input('slot_id');

        // Validate date
        if (!$date) {
            Session::flash('error', App::getLang() === 'fr' ? 'Sélectionnez une date.' : 'Selecteer een datum.');
            $this->redirect($aptUrl);
        }

        // Check blocked dates
        $settingModel = new Setting();
        $blockedDates = json_decode($settingModel->get('blocked_dates', null, '[]'), true);
        if (in_array($date, $blockedDates)) {
            Session::flash('error', App::getLang() === 'fr' ? 'Cette date n\'est pas disponible.' : 'Deze datum is niet beschikbaar.');
            $this->redirect($aptUrl);
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
                Session::flash('error', App::getLang() === 'fr' ? 'Sélectionnez un créneau.' : 'Selecteer een tijdslot.');
                $this->redirect($aptUrl);
            }

            $slotModel = new AppointmentSlot();
            if ($slotModel->isSlotBooked($date, (int)$slotId)) {
                Session::flash('error', App::getLang() === 'fr' ? 'Ce créneau n\'est plus disponible.' : 'Dit tijdslot is niet meer beschikbaar.');
                $this->redirect($aptUrl);
            }

            $slot = $slotModel->findById((int)$slotId);
            $lang = App::getLang();
            $appointmentId = $appointmentModel->create([
                'customer_id' => $customerId,
                'slot_id' => (int)$slotId,
                'type' => 'pregnancy',
                'date' => $date,
                'start_time' => $slot['start_time'],
                'end_time' => $slot['end_time'],
                'status' => 'pending',
                'notes' => $this->input('notes', ''),
                'lang' => $lang,
            ]);
        } else {
            // Child: just the date, admin will confirm time
            $lang = App::getLang();
            $appointmentId = $appointmentModel->create([
                'customer_id' => $customerId,
                'slot_id' => 0,
                'type' => 'child',
                'date' => $date,
                'start_time' => '00:00:00',
                'end_time' => '00:00:00',
                'status' => 'pending',
                'notes' => $this->input('notes', ''),
                'lang' => $lang,
            ]);
        }

        // Create payment request and send email
        $paymentService = new AppointmentPaymentService();
        $paymentUrl = $paymentService->createPaymentRequest($appointmentId);

        if ($paymentUrl) {
            $appointment = $appointmentModel->getWithCustomer($appointmentId);
            $notificationService = new AppointmentNotificationService();
            $notificationService->sendPaymentRequest($appointment, $paymentUrl);
        }

        if (App::getLang() === 'fr') {
            Session::flash('success', 'Votre rendez-vous est planifié ! Vous recevrez un e-mail avec un lien de paiement.');
            $this->redirect("/fr/rendez-vous/confirmation/{$appointmentId}");
        } else {
            Session::flash('success', 'Uw afspraak is ingepland! U ontvangt een e-mail met een betaallink.');
            $this->redirect("/afspraken/bevestiging/{$appointmentId}");
        }
    }

    public function confirm(int $id): void
    {
        $appointmentModel = new Appointment();
        $appointment = $appointmentModel->getWithCustomer($id);

        $siteModel = new Site();
        $site = $siteModel->findBySlug($this->site['slug']);
        $menuModel = new Menu();
        $lang = App::getLang();
        $headerMenu = $site ? $menuModel->getByLocationAndSite('header', $site['id'], $lang) : false;
        $footerMenu = $site ? $menuModel->getByLocationAndSite('footer', $site['id'], $lang) : false;

        // Build the payment URL if payment is pending
        $paymentUrl = null;
        if ($appointment && $appointment['payment_token'] && $appointment['payment_status'] === 'pending') {
            $paymentUrl = $lang === 'fr'
                ? "/fr/rendez-vous/betalen/{$appointment['payment_token']}"
                : "/afspraken/betalen/{$appointment['payment_token']}";
        }

        $this->render('front/appointments/confirm.twig', [
            'appointment' => $appointment,
            'payment_url' => $paymentUrl,
            'header_menu' => $headerMenu,
            'footer_menu' => $footerMenu,
            'layout' => $this->site['layout'] ?? 'gwin',
        ]);
    }

    public function pay(string $token): void
    {
        $appointmentModel = new Appointment();
        $appointment = $appointmentModel->findByPaymentToken($token);

        if (!$appointment || $appointment['payment_status'] === 'paid') {
            $aptUrl = App::getLang() === 'fr' ? '/fr/rendez-vous' : '/afspraken';
            Session::flash('error', App::getLang() === 'fr'
                ? 'Lien de paiement invalide ou déjà payé.'
                : 'Ongeldige betaallink of al betaald.');
            $this->redirect($aptUrl);
            return;
        }

        if ($appointment['payment_status'] === 'cancelled' || $appointment['status'] === 'cancelled') {
            $aptUrl = App::getLang() === 'fr' ? '/fr/rendez-vous' : '/afspraken';
            Session::flash('error', App::getLang() === 'fr'
                ? 'Ce rendez-vous a été annulé.'
                : 'Deze afspraak is geannuleerd.');
            $this->redirect($aptUrl);
            return;
        }

        // Redirect to Mollie checkout
        $paymentService = new AppointmentPaymentService();
        $checkoutUrl = $paymentService->initiatePayment($appointment);

        if ($checkoutUrl) {
            $this->redirect($checkoutUrl);
        } else {
            $aptUrl = App::getLang() === 'fr' ? '/fr/rendez-vous' : '/afspraken';
            Session::flash('error', App::getLang() === 'fr'
                ? 'Erreur lors de la création du paiement.'
                : 'Er is een fout opgetreden bij het aanmaken van de betaling.');
            $this->redirect($aptUrl);
        }
    }

    public function paymentSuccess(int $id): void
    {
        $appointmentModel = new Appointment();
        $appointment = $appointmentModel->getWithCustomer($id);

        $siteModel = new Site();
        $site = $siteModel->findBySlug($this->site['slug']);
        $menuModel = new Menu();
        $lang = App::getLang();

        $this->render('front/appointments/payment-success.twig', [
            'appointment' => $appointment,
            'header_menu' => $site ? $menuModel->getByLocationAndSite('header', $site['id'], $lang) : false,
            'footer_menu' => $site ? $menuModel->getByLocationAndSite('footer', $site['id'], $lang) : false,
            'layout' => $this->site['layout'] ?? 'gwin',
        ]);
    }
}
