<?php

namespace Core\Middleware;

use Core\Csrf;

class CsrfMiddleware
{
    public static function handle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            if (!Csrf::validate($token)) {
                http_response_code(403);
                // TEMP DEBUG
                echo '<h2>CSRF Debug</h2>';
                echo '<p>Token uit form: <code>' . htmlspecialchars($token ?? 'NULL') . '</code></p>';
                echo '<p>Token in session: <code>' . htmlspecialchars($_SESSION['csrf_token'] ?? 'NIET GEZET') . '</code></p>';
                echo '<p>Session ID: <code>' . session_id() . '</code></p>';
                echo '<p>Session save path: <code>' . session_save_path() . '</code></p>';
                echo '<pre>POST data: ' . print_r($_POST, true) . '</pre>';
                exit;
            }
        }
    }
}
