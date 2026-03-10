<?php

/**
 * CLI cron entry point.
 *
 * Usage:
 *   php cron.php appointment:check-payments
 *   php cron.php appointment:check-reminders
 *   php cron.php appointment:pre-reminders
 */

// Only allow CLI access
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Access denied.');
}

require_once __DIR__ . '/../vendor/autoload.php';

// Load .env
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Start session for any code that might need it
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$command = $argv[1] ?? null;

if (!$command) {
    echo "Usage: php cron.php <command>\n";
    echo "Commands:\n";
    echo "  appointment:check-payments   - Send reminders for overdue payments\n";
    echo "  appointment:check-reminders  - Cancel appointments past reminder deadline\n";
    echo "  appointment:pre-reminders    - Send pre-appointment reminders\n";
    exit(1);
}

$handler = new \App\Commands\AppointmentCronHandler();

switch ($command) {
    case 'appointment:check-payments':
        $handler->checkPaymentDeadlines();
        break;
    case 'appointment:check-reminders':
        $handler->checkReminderDeadlines();
        break;
    case 'appointment:pre-reminders':
        $handler->sendPreAppointmentReminders();
        break;
    default:
        echo "Unknown command: {$command}\n";
        exit(1);
}
