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
use App\Models\AppointmentType;
use App\Models\AppointmentFlowStep;
use App\Models\AppointmentDateProposal;
use App\Services\AppointmentPaymentService;

class AppointmentController extends Controller
{
    private function getBlockedDates(): array
    {
        $settingModel = new Setting();
        $blockedDates = json_decode($settingModel->get('blocked_dates', null, '[]'), true);
        return array_map(function ($entry) {
            if (is_string($entry)) {
                return ['date' => $entry, 'period' => 'hele_dag', 'reason' => ''];
            }
            return $entry;
        }, $blockedDates);
    }

    public function index(): void
    {
        $lang = App::getLang();
        $typeModel = new AppointmentType();
        $types = $typeModel->getAllActive($lang);

        $settingModel = new Setting();
        $maxBookingMonths = (int) $settingModel->get('appointment_max_months', null, '24');

        $this->render('front/appointments/index.twig', [
            'appointment_types' => $types,
            'blocked_dates' => $this->getBlockedDates(),
            'max_booking_months' => $maxBookingMonths,
            'layout' => $this->site['layout'] ?? 'gwin',
        ]);
    }

    /**
     * Dynamic flow for a specific appointment type.
     */
    public function flow(string $slug): void
    {
        $lang = App::getLang();
        $typeModel = new AppointmentType();
        $type = $typeModel->findBySlug($slug);

        if (!$type || !$type['is_active']) {
            http_response_code(404);
            $this->render('errors/404.twig');
            return;
        }

        // Localize
        $type['name'] = ($lang === 'fr' && !empty($type['name_fr'])) ? $type['name_fr'] : $type['name_nl'];
        $type['description'] = ($lang === 'fr' && !empty($type['description_fr'])) ? $type['description_fr'] : $type['description_nl'];

        $flowStepModel = new AppointmentFlowStep();
        $flowSteps = $flowStepModel->getByTypeId($type['id']);

        $settingModel = new Setting();
        $maxBookingMonths = (int) $settingModel->get('appointment_max_months', null, '24');

        $this->render('front/appointments/flow.twig', [
            'appointment_type' => $type,
            'flow_steps' => $flowSteps,
            'blocked_dates' => $this->getBlockedDates(),
            'max_booking_months' => $maxBookingMonths,
            'layout' => $this->site['layout'] ?? 'gwin',
        ]);
    }

    /**
     * Handle form submission from the dynamic flow.
     */
    public function storeFlow(string $slug): void
    {
        $lang = App::getLang();
        $aptUrl = $lang === 'fr' ? "/fr/rendez-vous/{$slug}" : "/afspraken/{$slug}";

        $typeModel = new AppointmentType();
        $type = $typeModel->findBySlug($slug);
        if (!$type) {
            Session::flash('error', 'Ongeldig afspraaktype.');
            $this->redirect($lang === 'fr' ? '/fr/rendez-vous' : '/afspraken');
            return;
        }

        // Validate required fields
        $firstName = trim($this->input('first_name', ''));
        $lastName = trim($this->input('last_name', ''));
        $email = trim($this->input('email', ''));
        $phone = trim($this->input('phone', ''));

        if (empty($firstName) || empty($lastName) || empty($email) || empty($phone)) {
            Session::flash('error', $lang === 'fr' ? 'Tous les champs obligatoires doivent être remplis.' : 'Alle verplichte velden moeten ingevuld zijn.');
            $this->redirect($aptUrl);
            return;
        }

        $date = $this->input('date', '');
        $slotId = $this->input('slot_id', '');

        // Find or create customer
        $customerModel = new Customer();
        $customer = $customerModel->findBy('email', $email);
        if (!$customer) {
            $customerId = $customerModel->create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $phone,
            ]);
        } else {
            $customerId = $customer['id'];
            if ($phone && $phone !== ($customer['phone'] ?? '')) {
                $customerModel->update($customerId, ['phone' => $phone]);
            }
        }

        // Build appointment data
        $appointmentData = [
            'customer_id' => $customerId,
            'type' => $slug,
            'appointment_type_id' => $type['id'],
            'date' => $date ?: null,
            'slot_id' => !empty($slotId) ? (int)$slotId : 0,
            'start_time' => '00:00:00',
            'end_time' => '00:00:00',
            'status' => 'pending',
            'payment_status' => 'none',
            'lang' => $lang,
            'notes' => $this->input('notes', ''),
        ];

        // If slot selected, get times from slot
        if (!empty($slotId)) {
            $slotModel = new AppointmentSlot();
            $slot = $slotModel->findById((int)$slotId);
            if ($slot) {
                $appointmentData['start_time'] = $slot['start_time'];
                $appointmentData['end_time'] = $slot['end_time'];
            }
        }

        $appointmentModel = new Appointment();
        $appointmentId = $appointmentModel->create($appointmentData);

        // Store date proposals if any
        $proposals = $_POST['proposals'] ?? [];
        if (!empty($proposals) && is_array($proposals)) {
            $proposalModel = new AppointmentDateProposal();
            foreach ($proposals as $index => $proposal) {
                if (!empty($proposal['date']) && !empty($proposal['time'])) {
                    $proposalModel->create([
                        'appointment_id' => $appointmentId,
                        'proposed_date' => $proposal['date'],
                        'proposed_time' => $proposal['time'],
                        'sort_order' => (int)$index,
                    ]);
                }
            }
        }

        $confirmUrl = $lang === 'fr' ? "/fr/rendez-vous/confirmation/{$appointmentId}" : "/afspraken/bevestiging/{$appointmentId}";
        $this->redirect($confirmUrl);
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

        $isFullyBlocked = false;
        $blockedPeriods = [];
        foreach ($blockedDates as $bd) {
            $bdDate = is_string($bd) ? $bd : ($bd['date'] ?? '');
            $bdPeriod = is_string($bd) ? 'hele_dag' : ($bd['period'] ?? 'hele_dag');
            if ($bdDate === $date) {
                if ($bdPeriod === 'hele_dag') {
                    $isFullyBlocked = true;
                    break;
                }
                $blockedPeriods[] = $bdPeriod;
            }
        }

        if ($isFullyBlocked) {
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

        // Filter out slots in blocked periods
        if (!empty($blockedPeriods)) {
            $slots = array_filter($slots, function ($slot) use ($blockedPeriods) {
                $hour = (int) date('H', strtotime($slot['start_time']));
                if (in_array('voormiddag', $blockedPeriods) && $hour < 13) return false;
                if (in_array('namiddag', $blockedPeriods) && $hour >= 13) return false;
                return true;
            });
            $slots = array_values($slots);
        }

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
        $dateBlocked = false;
        foreach ($blockedDates as $bd) {
            $bdDate = is_string($bd) ? $bd : ($bd['date'] ?? '');
            $bdPeriod = is_string($bd) ? 'hele_dag' : ($bd['period'] ?? 'hele_dag');
            if ($bdDate === $date) {
                if ($bdPeriod === 'hele_dag') { $dateBlocked = true; break; }
                if ($slotId) {
                    $slotModel = new AppointmentSlot();
                    $slot = $slotModel->findById((int)$slotId);
                    if ($slot) {
                        $hour = (int) date('H', strtotime($slot['start_time']));
                        if ($bdPeriod === 'voormiddag' && $hour < 13) { $dateBlocked = true; break; }
                        if ($bdPeriod === 'namiddag' && $hour >= 13) { $dateBlocked = true; break; }
                    }
                }
            }
        }
        if ($dateBlocked) {
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

        if (App::getLang() === 'fr') {
            Session::flash('success', 'Votre rendez-vous est planifié ! Vous recevrez un e-mail de confirmation dès qu\'il sera approuvé.');
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
