<?php

namespace App\Services;

use App\Models\Appointment;
use Core\Model;

class AppointmentNotificationService
{
    private SmsService $sms;

    public function __construct()
    {
        $this->sms = new SmsService();
    }

    public function sendPaymentRequest(array $appointment, string $paymentUrl): void
    {
        $lang = $appointment['lang'] ?? 'nl';
        $name = $appointment['first_name'];
        $date = date('d/m/Y', strtotime($appointment['date']));
        $time = substr($appointment['start_time'], 0, 5);
        $amount = number_format((float)$appointment['deposit_amount'], 2, ',', '.');
        $deadline = date('d/m/Y', strtotime($appointment['payment_deadline']));

        if ($lang === 'fr') {
            $typeLabel = $appointment['type'] === 'pregnancy' ? 'Sculpture de grossesse' : 'Sculpture avec enfant';
            $subject = "Demande de paiement - Rendez-vous G-Win";
            $body = "<h2>Demande de paiement</h2>"
                . "<p>Cher(e) {$name},</p>"
                . "<p>Merci pour votre rendez-vous pour <strong>{$typeLabel}</strong> le <strong>{$date}</strong>"
                . ($time !== '00:00' ? " à <strong>{$time}</strong>" : '') . ".</p>"
                . "<p>Pour confirmer votre rendez-vous, veuillez payer un acompte de <strong>€{$amount}</strong> avant le <strong>{$deadline}</strong>.</p>"
                . "<p style='margin:20px 0;'><a href='{$paymentUrl}' style='display:inline-block;padding:12px 30px;background:#e8be2e;color:#2d2118;text-decoration:none;font-weight:bold;border-radius:8px;'>Payer maintenant</a></p>"
                . "<p>Si le paiement n'est pas effectué à temps, votre rendez-vous sera annulé.</p>"
                . "<p>Cordialement,<br>G-Win</p>";
            $smsText = "G-Win: Payez votre acompte de €{$amount} avant le {$deadline} pour confirmer votre rendez-vous. {$paymentUrl}";
        } else {
            $typeLabel = $appointment['type'] === 'pregnancy' ? 'Zwangerschapsbeeldje' : 'Beeldje met kind';
            $subject = "Betaalverzoek - Afspraak G-Win";
            $body = "<h2>Betaalverzoek</h2>"
                . "<p>Beste {$name},</p>"
                . "<p>Bedankt voor uw afspraak voor <strong>{$typeLabel}</strong> op <strong>{$date}</strong>"
                . ($time !== '00:00' ? " om <strong>{$time}</strong>" : '') . ".</p>"
                . "<p>Om uw afspraak te bevestigen, gelieve een voorschot van <strong>€{$amount}</strong> te betalen vóór <strong>{$deadline}</strong>.</p>"
                . "<p style='margin:20px 0;'><a href='{$paymentUrl}' style='display:inline-block;padding:12px 30px;background:#e8be2e;color:#2d2118;text-decoration:none;font-weight:bold;border-radius:8px;'>Nu betalen</a></p>"
                . "<p>Indien de betaling niet op tijd gebeurt, wordt uw afspraak geannuleerd.</p>"
                . "<p>Met vriendelijke groet,<br>G-Win</p>";
            $smsText = "G-Win: Betaal uw voorschot van €{$amount} vóór {$deadline} om uw afspraak te bevestigen. {$paymentUrl}";
        }

        $emailSent = MailService::send($appointment['email'], $subject, $body);
        $this->logNotification($appointment['id'], 'payment_request', 'email', $emailSent);

        if (!empty($appointment['phone'])) {
            $smsSent = $this->sms->send($appointment['phone'], $smsText);
            $this->logNotification($appointment['id'], 'payment_request', 'sms', $smsSent);
        }
    }

    public function sendPaymentReminder(array $appointment, string $paymentUrl, string $newDeadline): void
    {
        $lang = $appointment['lang'] ?? 'nl';
        $name = $appointment['first_name'];
        $deadline = date('d/m/Y', strtotime($newDeadline));

        if ($lang === 'fr') {
            $subject = "Rappel de paiement - Rendez-vous G-Win";
            $body = "<h2>Rappel de paiement</h2>"
                . "<p>Cher(e) {$name},</p>"
                . "<p>Nous n'avons pas encore reçu votre paiement pour votre rendez-vous chez G-Win.</p>"
                . "<p>Veuillez effectuer le paiement avant le <strong>{$deadline}</strong>, sinon votre rendez-vous sera automatiquement annulé.</p>"
                . "<p style='margin:20px 0;'><a href='{$paymentUrl}' style='display:inline-block;padding:12px 30px;background:#e8be2e;color:#2d2118;text-decoration:none;font-weight:bold;border-radius:8px;'>Payer maintenant</a></p>"
                . "<p>Cordialement,<br>G-Win</p>";
            $smsText = "G-Win: Rappel! Payez avant le {$deadline} ou votre rendez-vous sera annulé. {$paymentUrl}";
        } else {
            $subject = "Herinnering betaling - Afspraak G-Win";
            $body = "<h2>Herinnering betaling</h2>"
                . "<p>Beste {$name},</p>"
                . "<p>We hebben uw betaling voor uw afspraak bij G-Win nog niet ontvangen.</p>"
                . "<p>Gelieve de betaling uit te voeren vóór <strong>{$deadline}</strong>, anders wordt uw afspraak automatisch geannuleerd.</p>"
                . "<p style='margin:20px 0;'><a href='{$paymentUrl}' style='display:inline-block;padding:12px 30px;background:#e8be2e;color:#2d2118;text-decoration:none;font-weight:bold;border-radius:8px;'>Nu betalen</a></p>"
                . "<p>Met vriendelijke groet,<br>G-Win</p>";
            $smsText = "G-Win: Herinnering! Betaal vóór {$deadline} of uw afspraak wordt geannuleerd. {$paymentUrl}";
        }

        $emailSent = MailService::send($appointment['email'], $subject, $body);
        $this->logNotification($appointment['id'], 'payment_reminder', 'email', $emailSent);

        if (!empty($appointment['phone'])) {
            $smsSent = $this->sms->send($appointment['phone'], $smsText);
            $this->logNotification($appointment['id'], 'payment_reminder', 'sms', $smsSent);
        }
    }

    public function sendPaymentConfirmation(array $appointment): void
    {
        $lang = $appointment['lang'] ?? 'nl';
        $name = $appointment['first_name'];
        $date = date('d/m/Y', strtotime($appointment['date']));
        $time = substr($appointment['start_time'], 0, 5);

        if ($lang === 'fr') {
            $typeLabel = $appointment['type'] === 'pregnancy' ? 'Sculpture de grossesse' : 'Sculpture avec enfant';
            $subject = "Rendez-vous confirmé - G-Win";
            $body = "<h2>Rendez-vous confirmé</h2>"
                . "<p>Cher(e) {$name},</p>"
                . "<p>Votre paiement a été reçu. Votre rendez-vous pour <strong>{$typeLabel}</strong> le <strong>{$date}</strong>"
                . ($time !== '00:00' ? " à <strong>{$time}</strong>" : '') . " est confirmé.</p>"
                . "<div style='background:#f5f5f5;padding:15px;margin:15px 0;border-left:4px solid #d4a843;'>"
                . "<strong>Informations pratiques:</strong><br>"
                . "• Chaque rendez-vous dure environ 60-90 minutes<br>"
                . "• Arrivez 10 minutes avant votre rendez-vous<br>"
                . "• Adresse: Duivenstuk 4, 8531 Bavikhove"
                . "</div>"
                . "<p>Cordialement,<br>G-Win</p>";
        } else {
            $typeLabel = $appointment['type'] === 'pregnancy' ? 'Zwangerschapsbeeldje' : 'Beeldje met kind';
            $subject = "Afspraak bevestigd - G-Win";
            $body = "<h2>Afspraak bevestigd</h2>"
                . "<p>Beste {$name},</p>"
                . "<p>Uw betaling is ontvangen. Uw afspraak voor <strong>{$typeLabel}</strong> op <strong>{$date}</strong>"
                . ($time !== '00:00' ? " om <strong>{$time}</strong>" : '') . " is bevestigd.</p>"
                . "<div style='background:#f5f5f5;padding:15px;margin:15px 0;border-left:4px solid #d4a843;'>"
                . "<strong>Praktische info:</strong><br>"
                . "• Elke afspraak duurt ongeveer 60-90 minuten<br>"
                . "• Kom 10 minuten voor uw afspraak<br>"
                . "• Adres: Duivenstuk 4, 8531 Bavikhove"
                . "</div>"
                . "<p>Met vriendelijke groet,<br>G-Win</p>";
        }

        $emailSent = MailService::send($appointment['email'], $subject, $body);
        $this->logNotification($appointment['id'], 'payment_confirmed', 'email', $emailSent);
    }

    public function sendCancellationNotice(array $appointment): void
    {
        $lang = $appointment['lang'] ?? 'nl';
        $name = $appointment['first_name'];
        $date = date('d/m/Y', strtotime($appointment['date']));

        if ($lang === 'fr') {
            $subject = "Rendez-vous annulé - G-Win";
            $body = "<h2>Rendez-vous annulé</h2>"
                . "<p>Cher(e) {$name},</p>"
                . "<p>Votre rendez-vous du <strong>{$date}</strong> a été annulé car le paiement n'a pas été effectué à temps.</p>"
                . "<p>Si vous souhaitez prendre un nouveau rendez-vous, visitez notre site web.</p>"
                . "<p>Cordialement,<br>G-Win</p>";
            $smsText = "G-Win: Votre rendez-vous du {$date} a été annulé (paiement non reçu). Contactez-nous pour un nouveau rendez-vous.";
        } else {
            $subject = "Afspraak geannuleerd - G-Win";
            $body = "<h2>Afspraak geannuleerd</h2>"
                . "<p>Beste {$name},</p>"
                . "<p>Uw afspraak op <strong>{$date}</strong> is geannuleerd omdat de betaling niet op tijd is ontvangen.</p>"
                . "<p>Wilt u een nieuwe afspraak maken? Bezoek dan onze website.</p>"
                . "<p>Met vriendelijke groet,<br>G-Win</p>";
            $smsText = "G-Win: Uw afspraak van {$date} is geannuleerd (betaling niet ontvangen). Neem contact op voor een nieuwe afspraak.";
        }

        $emailSent = MailService::send($appointment['email'], $subject, $body);
        $this->logNotification($appointment['id'], 'cancellation', 'email', $emailSent);

        if (!empty($appointment['phone'])) {
            $smsSent = $this->sms->send($appointment['phone'], $smsText);
            $this->logNotification($appointment['id'], 'cancellation', 'sms', $smsSent);
        }
    }

    public function sendPreAppointmentReminder(array $appointment): void
    {
        $lang = $appointment['lang'] ?? 'nl';
        $name = $appointment['first_name'];
        $date = date('d/m/Y', strtotime($appointment['date']));
        $time = substr($appointment['start_time'], 0, 5);

        if ($lang === 'fr') {
            $typeLabel = $appointment['type'] === 'pregnancy' ? 'Sculpture de grossesse' : 'Sculpture avec enfant';
            $subject = "Rappel rendez-vous - G-Win";
            $body = "<h2>Rappel de votre rendez-vous</h2>"
                . "<p>Cher(e) {$name},</p>"
                . "<p>Nous vous rappelons votre rendez-vous pour <strong>{$typeLabel}</strong> le <strong>{$date}</strong>"
                . ($time !== '00:00' ? " à <strong>{$time}</strong>" : '') . ".</p>"
                . "<div style='background:#f5f5f5;padding:15px;margin:15px 0;border-left:4px solid #d4a843;'>"
                . "<strong>Informations pratiques:</strong><br>"
                . "• Arrivez 10 minutes avant votre rendez-vous<br>"
                . "• Adresse: Duivenstuk 4, 8531 Bavikhove<br>"
                . "• Téléphone: +32 (0)56 499 284"
                . "</div>"
                . "<p>Cordialement,<br>G-Win</p>";
            $smsText = "G-Win: Rappel! Votre rendez-vous est prévu le {$date}" . ($time !== '00:00' ? " à {$time}" : '') . ". Adresse: Duivenstuk 4, Bavikhove.";
        } else {
            $typeLabel = $appointment['type'] === 'pregnancy' ? 'Zwangerschapsbeeldje' : 'Beeldje met kind';
            $subject = "Herinnering afspraak - G-Win";
            $body = "<h2>Herinnering aan uw afspraak</h2>"
                . "<p>Beste {$name},</p>"
                . "<p>We herinneren u aan uw afspraak voor <strong>{$typeLabel}</strong> op <strong>{$date}</strong>"
                . ($time !== '00:00' ? " om <strong>{$time}</strong>" : '') . ".</p>"
                . "<div style='background:#f5f5f5;padding:15px;margin:15px 0;border-left:4px solid #d4a843;'>"
                . "<strong>Praktische info:</strong><br>"
                . "• Kom 10 minuten voor uw afspraak<br>"
                . "• Adres: Duivenstuk 4, 8531 Bavikhove<br>"
                . "• Telefoon: +32 (0)56 499 284"
                . "</div>"
                . "<p>Met vriendelijke groet,<br>G-Win</p>";
            $smsText = "G-Win: Herinnering! Uw afspraak is op {$date}" . ($time !== '00:00' ? " om {$time}" : '') . ". Adres: Duivenstuk 4, Bavikhove.";
        }

        $emailSent = MailService::send($appointment['email'], $subject, $body);
        $this->logNotification($appointment['id'], 'pre_appointment_reminder', 'email', $emailSent);

        if (!empty($appointment['phone'])) {
            $smsSent = $this->sms->send($appointment['phone'], $smsText);
            $this->logNotification($appointment['id'], 'pre_appointment_reminder', 'sms', $smsSent);
        }
    }

    private function logNotification(int $appointmentId, string $type, string $channel, bool $success): void
    {
        try {
            $db = \Core\Database::getInstance();
            $stmt = $db->prepare(
                "INSERT INTO appointment_notifications (appointment_id, type, channel, status)
                 VALUES (:appointment_id, :type, :channel, :status)"
            );
            $stmt->execute([
                'appointment_id' => $appointmentId,
                'type' => $type,
                'channel' => $channel,
                'status' => $success ? 'sent' : 'failed',
            ]);
        } catch (\Exception $e) {
            error_log("Failed to log notification: " . $e->getMessage());
        }
    }
}
