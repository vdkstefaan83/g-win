<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\MailTemplate;
use Core\Model;

class AppointmentNotificationService
{
    private SmsService $sms;

    public function __construct()
    {
        $this->sms = new SmsService();
    }

    /**
     * Build common template variables from an appointment array.
     */
    private function buildVariables(array $appointment, array $extra = []): array
    {
        $lang = $appointment['lang'] ?? 'nl';
        $time = substr($appointment['start_time'] ?? '00:00', 0, 5);

        // Type label — try to resolve from appointment_types table, fallback to hardcoded
        $typeLabel = $appointment['type_name'] ?? null;
        if (!$typeLabel) {
            if ($lang === 'fr') {
                $typeLabel = $appointment['type'] === 'pregnancy' ? 'Sculpture de grossesse' : 'Sculpture avec enfant';
            } else {
                $typeLabel = $appointment['type'] === 'pregnancy' ? 'Zwangerschapsbeeldje' : 'Beeldje met kind';
            }
        }

        $tijdstipZin = '';
        if ($time !== '00:00') {
            $tijdstipZin = $lang === 'fr' ? " à <strong>{$time}</strong>" : " om <strong>{$time}</strong>";
        }

        return array_merge([
            'voornaam' => $appointment['first_name'] ?? '',
            'achternaam' => $appointment['last_name'] ?? '',
            'datum' => date('d/m/Y', strtotime($appointment['date'])),
            'tijdstip' => $time !== '00:00' ? $time : '',
            'tijdstip_zin' => $tijdstipZin,
            'type' => $typeLabel,
        ], $extra);
    }

    /**
     * Send an email using a DB template with fallback to hardcoded content.
     */
    private function sendTemplatedEmail(string $slug, string $lang, array $variables, string $toEmail, string $fallbackSubject, string $fallbackBody): bool
    {
        $rendered = MailTemplate::renderTemplate($slug, $lang, $variables);
        if ($rendered) {
            return MailService::send($toEmail, $rendered['subject'], $rendered['body']);
        }
        // Fallback: use hardcoded
        return MailService::send($toEmail, $fallbackSubject, $fallbackBody);
    }

    public function sendPaymentRequest(array $appointment, string $paymentUrl): void
    {
        $lang = $appointment['lang'] ?? 'nl';
        $amount = number_format((float)$appointment['deposit_amount'], 2, ',', '.');
        $deadline = date('d/m/Y', strtotime($appointment['payment_deadline']));

        $vars = $this->buildVariables($appointment, [
            'bedrag' => $amount,
            'deadline' => $deadline,
            'betaallink' => $paymentUrl,
        ]);

        // Fallback subject/body (in case template not in DB)
        $fallbackSubject = $lang === 'fr' ? "Demande de paiement - Rendez-vous G-Win" : "Betaalverzoek - Afspraak G-Win";
        $fallbackBody = $lang === 'fr'
            ? "<h2>Demande de paiement</h2><p>Cher(e) {$vars['voornaam']},</p><p>Veuillez payer €{$amount} avant le {$deadline}.</p><p><a href='{$paymentUrl}'>Payer maintenant</a></p>"
            : "<h2>Betaalverzoek</h2><p>Beste {$vars['voornaam']},</p><p>Gelieve €{$amount} te betalen vóór {$deadline}.</p><p><a href='{$paymentUrl}'>Nu betalen</a></p>";

        $emailSent = $this->sendTemplatedEmail('payment_request', $lang, $vars, $appointment['email'], $fallbackSubject, $fallbackBody);
        $this->logNotification($appointment['id'], 'payment_request', 'email', $emailSent);

        if (!empty($appointment['phone'])) {
            $smsText = $lang === 'fr'
                ? "G-Win: Payez votre acompte de €{$amount} avant le {$deadline}. {$paymentUrl}"
                : "G-Win: Betaal uw voorschot van €{$amount} vóór {$deadline}. {$paymentUrl}";
            $smsSent = $this->sms->send($appointment['phone'], $smsText);
            $this->logNotification($appointment['id'], 'payment_request', 'sms', $smsSent);
        }
    }

    public function sendPaymentReminder(array $appointment, string $paymentUrl, string $newDeadline): void
    {
        $lang = $appointment['lang'] ?? 'nl';
        $deadline = date('d/m/Y', strtotime($newDeadline));

        $vars = $this->buildVariables($appointment, [
            'deadline' => $deadline,
            'betaallink' => $paymentUrl,
        ]);

        $fallbackSubject = $lang === 'fr' ? "Rappel de paiement - Rendez-vous G-Win" : "Herinnering betaling - Afspraak G-Win";
        $fallbackBody = $lang === 'fr'
            ? "<h2>Rappel</h2><p>Cher(e) {$vars['voornaam']},</p><p>Payez avant le {$deadline}.</p><p><a href='{$paymentUrl}'>Payer</a></p>"
            : "<h2>Herinnering</h2><p>Beste {$vars['voornaam']},</p><p>Betaal vóór {$deadline}.</p><p><a href='{$paymentUrl}'>Betalen</a></p>";

        $emailSent = $this->sendTemplatedEmail('payment_reminder', $lang, $vars, $appointment['email'], $fallbackSubject, $fallbackBody);
        $this->logNotification($appointment['id'], 'payment_reminder', 'email', $emailSent);

        if (!empty($appointment['phone'])) {
            $smsText = $lang === 'fr'
                ? "G-Win: Rappel! Payez avant le {$deadline} ou votre rendez-vous sera annulé. {$paymentUrl}"
                : "G-Win: Herinnering! Betaal vóór {$deadline} of uw afspraak wordt geannuleerd. {$paymentUrl}";
            $smsSent = $this->sms->send($appointment['phone'], $smsText);
            $this->logNotification($appointment['id'], 'payment_reminder', 'sms', $smsSent);
        }
    }

    public function sendPaymentConfirmation(array $appointment): void
    {
        $lang = $appointment['lang'] ?? 'nl';
        $vars = $this->buildVariables($appointment);

        $fallbackSubject = $lang === 'fr' ? "Rendez-vous confirmé - G-Win" : "Afspraak bevestigd - G-Win";
        $fallbackBody = $lang === 'fr'
            ? "<h2>Confirmé</h2><p>Cher(e) {$vars['voornaam']},</p><p>Votre rendez-vous est confirmé.</p>"
            : "<h2>Bevestigd</h2><p>Beste {$vars['voornaam']},</p><p>Uw afspraak is bevestigd.</p>";

        // Send with ICS calendar attachment
        $rendered = \App\Models\MailTemplate::renderTemplate('payment_confirmed', $lang, $vars);
        $subject = $rendered ? $rendered['subject'] : $fallbackSubject;
        $body = $rendered ? $rendered['body'] : $fallbackBody;

        $ics = MailService::generateIcs($appointment);
        $emailSent = MailService::sendWithAttachment(
            $appointment['email'],
            $subject,
            $body,
            $ics,
            'afspraak-gwin.ics',
            'text/calendar'
        );
        $this->logNotification($appointment['id'], 'payment_confirmed', 'email', $emailSent);
    }

    public function sendCancellationNotice(array $appointment): void
    {
        $lang = $appointment['lang'] ?? 'nl';
        $vars = $this->buildVariables($appointment);

        $fallbackSubject = $lang === 'fr' ? "Rendez-vous annulé - G-Win" : "Afspraak geannuleerd - G-Win";
        $fallbackBody = $lang === 'fr'
            ? "<h2>Annulé</h2><p>Cher(e) {$vars['voornaam']},</p><p>Votre rendez-vous a été annulé.</p>"
            : "<h2>Geannuleerd</h2><p>Beste {$vars['voornaam']},</p><p>Uw afspraak is geannuleerd.</p>";

        $emailSent = $this->sendTemplatedEmail('cancellation', $lang, $vars, $appointment['email'], $fallbackSubject, $fallbackBody);
        $this->logNotification($appointment['id'], 'cancellation', 'email', $emailSent);

        if (!empty($appointment['phone'])) {
            $date = date('d/m/Y', strtotime($appointment['date']));
            $smsText = $lang === 'fr'
                ? "G-Win: Votre rendez-vous du {$date} a été annulé (paiement non reçu)."
                : "G-Win: Uw afspraak van {$date} is geannuleerd (betaling niet ontvangen).";
            $smsSent = $this->sms->send($appointment['phone'], $smsText);
            $this->logNotification($appointment['id'], 'cancellation', 'sms', $smsSent);
        }
    }

    public function sendPreAppointmentReminder(array $appointment): void
    {
        $lang = $appointment['lang'] ?? 'nl';
        $vars = $this->buildVariables($appointment);

        $fallbackSubject = $lang === 'fr' ? "Rappel rendez-vous - G-Win" : "Herinnering afspraak - G-Win";
        $fallbackBody = $lang === 'fr'
            ? "<h2>Rappel</h2><p>Cher(e) {$vars['voornaam']},</p><p>Votre rendez-vous approche.</p>"
            : "<h2>Herinnering</h2><p>Beste {$vars['voornaam']},</p><p>Uw afspraak nadert.</p>";

        $emailSent = $this->sendTemplatedEmail('pre_appointment_reminder', $lang, $vars, $appointment['email'], $fallbackSubject, $fallbackBody);
        $this->logNotification($appointment['id'], 'pre_appointment_reminder', 'email', $emailSent);

        if (!empty($appointment['phone'])) {
            $date = date('d/m/Y', strtotime($appointment['date']));
            $time = substr($appointment['start_time'] ?? '00:00', 0, 5);
            $smsText = $lang === 'fr'
                ? "G-Win: Rappel! Votre rendez-vous est prévu le {$date}" . ($time !== '00:00' ? " à {$time}" : '') . ". Adresse: Duivenstuk 4, Bavikhove."
                : "G-Win: Herinnering! Uw afspraak is op {$date}" . ($time !== '00:00' ? " om {$time}" : '') . ". Adres: Duivenstuk 4, Bavikhove.";
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
