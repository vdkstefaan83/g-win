<?php

namespace Core\Middleware;

use Core\Auth;
use Core\Helpers\Redirect;

class AdminMiddleware
{
    public static function handle(): void
    {
        // Skip for login routes
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (str_starts_with($uri, '/admin/login')) {
            return;
        }

        if (!Auth::isAdmin()) {
            Redirect::to('/admin/login');
        }
    }
}
