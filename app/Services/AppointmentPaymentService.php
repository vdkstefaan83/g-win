<?php

namespace App\Services;

use Mollie\Api\MollieApiClient;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Setting;
use App\Helpers\DateHelper;

class AppointmentPaymentService
{
    private MollieApiClient $mollie;
    private Appointment $appointmentModel;
    private Payment $paymentModel;
    private Setting $settingModel;

    public function __construct()
    {
        $this->mollie = new MollieApiClient();
        $this->mollie->setApiKey($_ENV['MOLLIE_API_KEY'] ?? '');
        $this->appointmentModel = new Appointment();
        $this->paymentModel = new Payment();
        $this->settingModel = new Setting();
    }

    /**
     * Create a payment request for an appointment deposit.
     * Returns the payment URL, or null on failure.
     */
    public function createPaymentRequest(int $appointmentId): ?string
    {
        $appointment = $this->appointmentModel->getWithCustomer($appointmentId);
        if (!$appointment) return null;

        $depositAmount = (float) $this->settingModel->get('appointment_deposit_amount', null, '50.00');
        $deadlineDays = (int) $this->settingModel->get('appointment_payment_deadline_days', null, '3');

        // Generate unique payment token
        $token = bin2hex(random_bytes(32));
        $paymentDeadline = DateHelper::addWorkingDays(date('Y-m-d H:i:s'), $deadlineDays);

        // Update appointment with payment info
        $this->appointmentModel->update($appointmentId, [
            'payment_status' => 'pending',
            'payment_deadline' => $paymentDeadline,
            'deposit_amount' => $depositAmount,
            'payment_token' => $token,
        ]);

        // Build the payment URL based on language
        $lang = $appointment['lang'] ?? 'nl';
        $baseUrl = $_ENV['APP_URL'] ?? '';
        $payUrl = $lang === 'fr'
            ? "{$baseUrl}/fr/rendez-vous/betalen/{$token}"
            : "{$baseUrl}/afspraken/betalen/{$token}";

        return $payUrl;
    }

    /**
     * Redirect the customer to Mollie checkout for the deposit.
     */
    public function initiatePayment(array $appointment): ?string
    {
        $token = $appointment['payment_token'];
        $lang = $appointment['lang'] ?? 'nl';
        $baseUrl = $_ENV['APP_URL'] ?? '';

        $redirectUrl = $lang === 'fr'
            ? "{$baseUrl}/fr/rendez-vous/betaling/succes/{$appointment['id']}"
            : "{$baseUrl}/afspraken/betaling/succes/{$appointment['id']}";

        $cancelUrl = $lang === 'fr'
            ? "{$baseUrl}/fr/rendez-vous/betalen/{$token}"
            : "{$baseUrl}/afspraken/betalen/{$token}";

        $description = $lang === 'fr'
            ? "Acompte rendez-vous G-Win"
            : "Voorschot afspraak G-Win";

        try {
            $molliePayment = $this->mollie->payments->create([
                'amount' => [
                    'currency' => 'EUR',
                    'value' => number_format((float)$appointment['deposit_amount'], 2, '.', ''),
                ],
                'description' => $description,
                'redirectUrl' => $redirectUrl,
                'cancelUrl' => $cancelUrl,
                'webhookUrl' => $_ENV['MOLLIE_WEBHOOK_URL'] ?? ($baseUrl . '/webhook/mollie'),
                'metadata' => [
                    'appointment_id' => $appointment['id'],
                    'payment_token' => $token,
                    'type' => 'appointment',
                ],
            ]);

            // Store payment record
            $this->paymentModel->create([
                'order_id' => null,
                'appointment_id' => $appointment['id'],
                'payment_type' => 'appointment',
                'mollie_id' => $molliePayment->id,
                'method' => 'bancontact',
                'amount' => $appointment['deposit_amount'],
                'status' => 'open',
            ]);

            return $molliePayment->getCheckoutUrl();
        } catch (\Exception $e) {
            error_log('Appointment payment creation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Handle webhook callback for appointment payments.
     */
    public function handleWebhook(array $payment): void
    {
        try {
            $molliePayment = $this->mollie->payments->get($payment['mollie_id']);

            $updateData = ['status' => $molliePayment->status];

            if ($molliePayment->isPaid()) {
                $updateData['paid_at'] = date('Y-m-d H:i:s');
                $this->paymentModel->update($payment['id'], $updateData);

                // Confirm the appointment
                $this->appointmentModel->update($payment['appointment_id'], [
                    'status' => 'confirmed',
                    'payment_status' => 'paid',
                ]);

                // Send confirmation email
                $appointment = $this->appointmentModel->getWithCustomer($payment['appointment_id']);
                if ($appointment) {
                    $notificationService = new AppointmentNotificationService();
                    $notificationService->sendPaymentConfirmation($appointment);
                }
            } elseif ($molliePayment->isFailed() || $molliePayment->isCanceled()) {
                $this->paymentModel->update($payment['id'], $updateData);
            } else {
                $this->paymentModel->update($payment['id'], $updateData);
            }
        } catch (\Exception $e) {
            error_log('Appointment webhook handling failed: ' . $e->getMessage());
        }
    }
}
