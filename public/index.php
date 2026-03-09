<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Core\App;

// Load environment variables
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

// Error reporting based on environment
if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Start session
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'cookie_secure' => isset($_SERVER['HTTPS']),
]);

// Boot and run application
try {
    $app = new App();
    $app->run();
} catch (\Throwable $e) {
    if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
        echo '<h1>Error</h1>';
        echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        http_response_code(500);
        if (file_exists(__DIR__ . '/../views/errors/500.twig')) {
            include __DIR__ . '/../views/errors/500.twig';
        } else {
            echo 'Er is een fout opgetreden.';
        }
    }
}
