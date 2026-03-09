<?php

namespace App\Services;

class MailService
{
    public static function send(string $to, string $subject, string $body): bool
    {
        $from = $_ENV['MAIL_FROM'] ?? 'noreply@g-win.be';

        $headers = [
            'From' => $from,
            'Reply-To' => $from,
            'Content-Type' => 'text/html; charset=UTF-8',
            'MIME-Version' => '1.0',
        ];

        $headerString = '';
        foreach ($headers as $key => $value) {
            $headerString .= "{$key}: {$value}\r\n";
        }

        return mail($to, $subject, $body, $headerString);
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
        $body .= "<p>Totaalbedrag: <strong>€" . number_format((float)$order['total'], 2, ',', '.') . "</strong></p>";
        $body .= "<p>Met vriendelijke groet,<br>G-Win</p>";

        return self::send($customer['email'], "Bestelbevestiging #{$order['order_number']}", $body);
    }
}
