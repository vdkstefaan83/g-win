<?php

namespace App\Services;

use Mollie\Api\MollieApiClient;
use App\Models\Payment;
use App\Models\Order;

class PaymentService
{
    private MollieApiClient $mollie;
    private Payment $paymentModel;
    private Order $orderModel;

    public function __construct()
    {
        $this->mollie = new MollieApiClient();
        $this->mollie->setApiKey($_ENV['MOLLIE_API_KEY'] ?? '');
        $this->paymentModel = new Payment();
        $this->orderModel = new Order();
    }

    public function createPayment(array $order, string $method = 'bancontact'): ?string
    {
        try {
            $payment = $this->mollie->payments->create([
                'amount' => [
                    'currency' => 'EUR',
                    'value' => number_format((float)$order['total'], 2, '.', ''),
                ],
                'description' => 'Bestelling ' . $order['order_number'],
                'redirectUrl' => ($_ENV['APP_URL'] ?? '') . '/betaling/succes?order=' . $order['id'],
                'cancelUrl' => ($_ENV['APP_URL'] ?? '') . '/betaling/annulatie?order=' . $order['id'],
                'webhookUrl' => $_ENV['MOLLIE_WEBHOOK_URL'] ?? (($_ENV['APP_URL'] ?? '') . '/webhook/mollie'),
                'method' => $method === 'paypal' ? 'paypal' : 'bancontact',
                'metadata' => [
                    'order_id' => $order['id'],
                    'order_number' => $order['order_number'],
                ],
            ]);

            $this->paymentModel->create([
                'order_id' => $order['id'],
                'mollie_id' => $payment->id,
                'method' => $method,
                'amount' => $order['total'],
                'status' => 'open',
            ]);

            return $payment->getCheckoutUrl();
        } catch (\Exception $e) {
            error_log('Payment creation failed: ' . $e->getMessage());
            return null;
        }
    }

    public function handleWebhook(string $mollieId): void
    {
        try {
            $payment = $this->paymentModel->findByMollieId($mollieId);
            if (!$payment) return;

            // Route to appointment payment handler if this is an appointment payment
            if (($payment['payment_type'] ?? 'order') === 'appointment') {
                $appointmentPaymentService = new AppointmentPaymentService();
                $appointmentPaymentService->handleWebhook($payment);
                return;
            }

            // Handle order payment
            $molliePayment = $this->mollie->payments->get($mollieId);
            $status = $molliePayment->status;
            $updateData = ['status' => $status];

            if ($molliePayment->isPaid()) {
                $updateData['paid_at'] = date('Y-m-d H:i:s');
                $this->orderModel->update($payment['order_id'], ['status' => 'paid']);
            } elseif ($molliePayment->isFailed() || $molliePayment->isCanceled()) {
                $this->orderModel->update($payment['order_id'], ['status' => 'cancelled']);
            }

            $this->paymentModel->update($payment['id'], $updateData);
        } catch (\Exception $e) {
            error_log('Webhook handling failed: ' . $e->getMessage());
        }
    }
}
