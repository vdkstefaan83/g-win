<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    public static function send(string $to, string $subject, string $body): bool
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $_ENV['MAIL_HOST'] ?? 'localhost';
            $mail->Port = (int) ($_ENV['MAIL_PORT'] ?? 587);
            $mail->SMTPSecure = $mail->Port === 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;

            $username = $_ENV['MAIL_USER'] ?? '';
            if (!empty($username)) {
                $mail->SMTPAuth = true;
                $mail->Username = $username;
                $mail->Password = $_ENV['MAIL_PASS'] ?? '';
            }

            $from = $_ENV['MAIL_FROM'] ?? 'noreply@g-win.be';
            $mail->setFrom($from, 'G-Win');
            $mail->addAddress($to);

            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $subject;
            $mail->Body = $body;

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('Mail send failed: ' . $mail->ErrorInfo);
            return false;
        }
    }

    /**
     * Send email with a string attachment (e.g. ICS file).
     */
    public static function sendWithAttachment(string $to, string $subject, string $body, string $attachmentContent, string $attachmentName, string $attachmentType = 'text/calendar'): bool
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $_ENV['MAIL_HOST'] ?? 'localhost';
            $mail->Port = (int) ($_ENV['MAIL_PORT'] ?? 587);
            $mail->SMTPSecure = $mail->Port === 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;

            $username = $_ENV['MAIL_USER'] ?? '';
            if (!empty($username)) {
                $mail->SMTPAuth = true;
                $mail->Username = $username;
                $mail->Password = $_ENV['MAIL_PASS'] ?? '';
            }

            $from = $_ENV['MAIL_FROM'] ?? 'noreply@g-win.be';
            $mail->setFrom($from, 'G-Win');
            $mail->addAddress($to);

            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $subject;
            $mail->Body = $body;

            $mail->addStringAttachment($attachmentContent, $attachmentName, 'base64', $attachmentType);

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('Mail send failed: ' . $mail->ErrorInfo);
            return false;
        }
    }

    /**
     * Generate an ICS calendar event.
     */
    public static function generateIcs(array $appointment): string
    {
        $date = $appointment['date'];
        $startTime = $appointment['start_time'] ?? '00:00:00';
        $endTime = $appointment['end_time'] ?? '00:00:00';

        // Build datetime strings (format: 20260404T100000)
        $dtStart = date('Ymd\THis', strtotime("{$date} {$startTime}"));
        if ($endTime && $endTime !== '00:00:00') {
            $dtEnd = date('Ymd\THis', strtotime("{$date} {$endTime}"));
        } else {
            // Default 1.5 hour duration
            $dtEnd = date('Ymd\THis', strtotime("{$date} {$startTime}") + 5400);
        }

        $now = gmdate('Ymd\THis\Z');
        $uid = uniqid('gwin-apt-') . '@g-win.be';

        $typeName = $appointment['type_name'] ?? $appointment['type'] ?? 'Afspraak';
        $summary = "G-Win - {$typeName}";
        $description = "Afspraak bij G-Win\\nType: {$typeName}";
        $location = 'Duivenstuk 4, 8531 Bavikhove, België';

        $ics = "BEGIN:VCALENDAR\r\n";
        $ics .= "VERSION:2.0\r\n";
        $ics .= "PRODID:-//G-Win//Appointment//NL\r\n";
        $ics .= "CALSCALE:GREGORIAN\r\n";
        $ics .= "METHOD:REQUEST\r\n";
        $ics .= "BEGIN:VEVENT\r\n";
        $ics .= "UID:{$uid}\r\n";
        $ics .= "DTSTAMP:{$now}\r\n";
        $ics .= "DTSTART:{$dtStart}\r\n";
        $ics .= "DTEND:{$dtEnd}\r\n";
        $ics .= "SUMMARY:{$summary}\r\n";
        $ics .= "DESCRIPTION:{$description}\r\n";
        $ics .= "LOCATION:{$location}\r\n";
        $ics .= "STATUS:CONFIRMED\r\n";
        $ics .= "END:VEVENT\r\n";
        $ics .= "END:VCALENDAR\r\n";

        return $ics;
    }

    public static function sendAppointmentConfirmation(array $appointment, array $customer): bool
    {
        $typeLabel = $appointment['type'] === 'pregnancy' ? 'Zwangerschapsbeeldje' : 'Beeldje met kind';
        $date = date('d/m/Y', strtotime($appointment['date']));

        $body = "<h2>Bevestiging afspraak</h2>";
        $body .= "<p>Beste {$customer['first_name']},</p>";
        $body .= "<p>Uw afspraak voor <strong>{$typeLabel}</strong> op <strong>{$date}</strong>";

        if ($appointment['start_time'] !== '00:00:00') {
            $body .= " om <strong>" . substr($appointment['start_time'], 0, 5) . "</strong>";
        }

        $body .= " is bevestigd.</p>";

        if ($appointment['type'] === 'pregnancy') {
            $body .= "<div style='background:#f5f5f5;padding:15px;margin:15px 0;border-left:4px solid #d4a843;'>";
            $body .= "<strong>Tijdslot informatie:</strong><br>";
            $body .= "• Elke afspraak duurt ongeveer 60-90 minuten<br>";
            $body .= "• Kom 10 minuten voor uw afspraak<br>";
            $body .= "• Bij te laat komen kan uw tijdslot worden ingekort";
            $body .= "</div>";
        }

        $body .= "<p>Met vriendelijke groet,<br>G-Win</p>";

        return self::send($customer['email'], "Bevestiging afspraak - {$typeLabel}", $body);
    }

    public static function sendOrderConfirmation(array $order, array $customer): bool
    {
        $body = "<h2>Bestelbevestiging</h2>";
        $body .= "<p>Beste {$customer['first_name']},</p>";
        $body .= "<p>Bedankt voor uw bestelling <strong>#{$order['order_number']}</strong>.</p>";

        // Order items
        if (!empty($order['items'])) {
            $body .= "<table style='width:100%;border-collapse:collapse;margin:16px 0;'>";
            $body .= "<tr style='border-bottom:2px solid #eee;'><th style='text-align:left;padding:8px;'>Product</th><th style='text-align:center;padding:8px;'>Aantal</th><th style='text-align:right;padding:8px;'>Prijs</th></tr>";
            foreach ($order['items'] as $item) {
                $lineTotal = number_format((float)$item['price'] * (int)$item['quantity'], 2, ',', '.');
                $body .= "<tr style='border-bottom:1px solid #f0f0f0;'>";
                $body .= "<td style='padding:8px;'>{$item['product_name']}</td>";
                $body .= "<td style='text-align:center;padding:8px;'>{$item['quantity']}</td>";
                $body .= "<td style='text-align:right;padding:8px;'>€{$lineTotal}</td>";
                $body .= "</tr>";
            }
            $body .= "<tr><td colspan='2' style='padding:8px;text-align:right;font-weight:bold;'>Totaal:</td>";
            $body .= "<td style='padding:8px;text-align:right;font-weight:bold;'>€" . number_format((float)$order['total'], 2, ',', '.') . "</td></tr>";
            $body .= "</table>";
        } else {
            $body .= "<p>Totaalbedrag: <strong>€" . number_format((float)$order['total'], 2, ',', '.') . "</strong></p>";
        }

        // Shipping address
        if (!empty($customer['address'])) {
            $body .= "<p><strong>Verzendadres:</strong><br>";
            $body .= "{$customer['first_name']} {$customer['last_name']}<br>";
            $body .= "{$customer['address']}<br>";
            $body .= "{$customer['postal_code']} {$customer['city']}</p>";
        }

        $body .= "<p>Met vriendelijke groet,<br>G-Win</p>";

        return self::send($customer['email'], "Bestelbevestiging #{$order['order_number']}", $body);
    }
}
