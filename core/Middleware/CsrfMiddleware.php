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
                echo 'CSRF token validatie mislukt.';
                exit;
            }
        }
    }
}
