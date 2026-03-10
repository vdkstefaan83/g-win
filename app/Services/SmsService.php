<?php

namespace App\Services;

class SmsService
{
    private string $username;
    private string $apiKey;
    private string $senderName;

    public function __construct()
    {
        $this->username = $_ENV['CLICKSEND_API_USERNAME'] ?? '';
        $this->apiKey = $_ENV['CLICKSEND_API_KEY'] ?? '';
        $this->senderName = $_ENV['CLICKSEND_SENDER_NAME'] ?? 'G-Win';
    }

    public function send(string $to, string $message): bool
    {
        if (empty($this->username) || empty($this->apiKey)) {
            error_log('ClickSend: API credentials not configured');
            return false;
        }

        $to = $this->normalizePhone($to);
        if (!$to) {
            error_log('ClickSend: Invalid phone number');
            return false;
        }

        $payload = [
            'messages' => [
                [
                    'from' => $this->senderName,
                    'to' => $to,
                    'body' => $message,
                ],
            ],
        ];

        $ch = curl_init('https://rest.clicksend.com/v3/sms/send');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_USERPWD => $this->username . ':' . $this->apiKey,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        }

        error_log("ClickSend SMS failed (HTTP {$httpCode}): {$response}");
        return false;
    }

    private function normalizePhone(string $phone): ?string
    {
        // Strip spaces, dashes, dots
        $phone = preg_replace('/[\s\-\.]/', '', $phone);

        // Belgian number starting with 0 → +32
        if (preg_match('/^0[1-9]/', $phone)) {
            $phone = '+32' . substr($phone, 1);
        }
        // 0032 prefix
        if (str_starts_with($phone, '0032')) {
            $phone = '+32' . substr($phone, 4);
        }
        // Already has + prefix
        if (str_starts_with($phone, '+')) {
            return $phone;
        }

        return null;
    }
}
