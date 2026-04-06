<?php
/**
 * ClickSend SMS Test Script
 * Usage: php test-sms.php OR visit /test-sms.php in browser
 * DELETE THIS FILE AFTER TESTING
 */

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

header('Content-Type: text/plain; charset=utf-8');

echo "=== ClickSend SMS Test ===\n\n";

// Check credentials
$username = $_ENV['CLICKSEND_API_USERNAME'] ?? '';
$apiKey = $_ENV['CLICKSEND_API_KEY'] ?? '';
$senderName = $_ENV['CLICKSEND_SENDER_NAME'] ?? 'G-Win';

echo "Username: " . ($username ? substr($username, 0, 3) . '***' : 'NOT SET') . "\n";
echo "API Key: " . ($apiKey ? substr($apiKey, 0, 5) . '***' : 'NOT SET') . "\n";
echo "Sender: {$senderName}\n\n";

if (empty($username) || empty($apiKey)) {
    echo "ERROR: ClickSend credentials not configured in .env\n";
    echo "Required:\n";
    echo "  CLICKSEND_API_USERNAME=your_username\n";
    echo "  CLICKSEND_API_KEY=your_api_key\n";
    echo "  CLICKSEND_SENDER_NAME=G-Win\n";
    exit;
}

// Test phone number - CHANGE THIS
$testPhone = '+32477723423'; // <-- Change to your test number
$testMessage = 'G-Win SMS test: dit is een testbericht. Als je dit ontvangt werkt de SMS service correct.';

echo "Sending to: {$testPhone}\n";
echo "Message: {$testMessage}\n\n";

// Build payload
$payload = [
    'messages' => [
        [
            'from' => $senderName,
            'to' => $testPhone,
            'body' => $testMessage,
        ],
    ],
];

echo "Request payload:\n" . json_encode($payload, JSON_PRETTY_PRINT) . "\n\n";

// Send
$ch = curl_init('https://rest.clicksend.com/v3/sms/send');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_USERPWD => $username . ':' . $apiKey,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_VERBOSE => false,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "--- Response ---\n";
echo "HTTP Code: {$httpCode}\n";

if ($curlError) {
    echo "CURL Error: {$curlError}\n";
}

echo "Response body:\n";
$decoded = json_decode($response, true);
if ($decoded) {
    echo json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

    // Parse result
    if ($httpCode >= 200 && $httpCode < 300) {
        echo "\n✓ API call successful (HTTP {$httpCode})\n";

        if (isset($decoded['data']['messages'])) {
            foreach ($decoded['data']['messages'] as $msg) {
                echo "\nMessage status:\n";
                echo "  To: " . ($msg['to'] ?? '?') . "\n";
                echo "  Status: " . ($msg['status'] ?? '?') . "\n";
                echo "  Status code: " . ($msg['status_code'] ?? '?') . "\n";
                echo "  Status text: " . ($msg['status_text'] ?? '?') . "\n";
                echo "  Message ID: " . ($msg['message_id'] ?? '?') . "\n";
                echo "  Cost: " . ($msg['message_price'] ?? '?') . "\n";

                if (($msg['status'] ?? '') === 'SUCCESS') {
                    echo "\n✓ SMS sent successfully!\n";
                } else {
                    echo "\n✗ SMS not sent. Status: " . ($msg['status_text'] ?? $msg['status'] ?? 'unknown') . "\n";
                }
            }
        }
    } else {
        echo "\n✗ API call failed (HTTP {$httpCode})\n";
        if (isset($decoded['response_code'])) {
            echo "Error code: " . $decoded['response_code'] . "\n";
        }
        if (isset($decoded['response_msg'])) {
            echo "Error message: " . $decoded['response_msg'] . "\n";
        }
    }
} else {
    echo $response . "\n";
}

echo "\n=== DELETE THIS FILE AFTER TESTING ===\n";
