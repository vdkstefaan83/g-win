<?php

namespace Core\Middleware;

use Core\Auth;
use Core\Helpers\Redirect;

class AuthMiddleware
{
    public static function handle(): void
    {
        if (!Auth::check()) {
            Redirect::to('/login');
        }
    }
}
