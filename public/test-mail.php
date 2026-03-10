<?php
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

echo "<pre>";
echo "HOST: " . ($_ENV['MAIL_HOST'] ?? 'LEEG') . "\n";
echo "PORT: " . ($_ENV['MAIL_PORT'] ?? 'LEEG') . "\n";
echo "USER: " . ($_ENV['MAIL_USER'] ?? 'LEEG') . "\n";
echo "FROM: " . ($_ENV['MAIL_FROM'] ?? 'LEEG') . "\n\n";

$mail = new PHPMailer\PHPMailer\PHPMailer(true);
$mail->SMTPDebug = 2;
$mail->Debugoutput = function($str, $level) { echo htmlspecialchars($str); };
$mail->isSMTP();
$mail->Host = $_ENV['MAIL_HOST'] ?? '';
$mail->Port = (int) ($_ENV['MAIL_PORT'] ?? 587);
$mail->SMTPAuth = true;
$mail->Username = $_ENV['MAIL_USER'] ?? '';
$mail->Password = $_ENV['MAIL_PASS'] ?? '';
$mail->SMTPSecure = $mail->Port === 465 ? 'ssl' : 'tls';
$mail->setFrom($_ENV['MAIL_FROM'] ?? 'noreply@g-win.be', 'G-Win');
$mail->addAddress('stefaan@vanderkerken.com');
$mail->isHTML(true);
$mail->CharSet = 'UTF-8';
$mail->Subject = 'G-Win Test Mail';
$mail->Body = '<h2>Test</h2><p>Als je dit leest werkt de SMTP mail configuratie!</p>';

try {
    $mail->send();
    echo "\n\n✅ MAIL VERSTUURD!";
} catch (Exception $e) {
    echo "\n\n❌ FOUT: " . $mail->ErrorInfo;
}
echo "</pre>";
