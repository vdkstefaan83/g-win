<?php
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

echo "<pre>";
echo "USERNAME: " . ($_ENV['CLICKSEND_API_USERNAME'] ?? 'LEEG') . "\n";
echo "API KEY: " . (empty($_ENV['CLICKSEND_API_KEY']) ? 'LEEG' : '***SET***') . "\n";
echo "SENDER: " . ($_ENV['CLICKSEND_SENDER_NAME'] ?? 'LEEG') . "\n\n";

use App\Services\SmsService;

$sms = new SmsService();
$result = $sms->send('+32XXXXXXXXX', 'G-Win test SMS - als je dit leest werkt ClickSend!');

echo $result ? "✅ SMS verstuurd!" : "❌ SMS MISLUKT - check error log";
echo "</pre>";
