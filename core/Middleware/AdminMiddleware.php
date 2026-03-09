<?php

namespace Core\Middleware;

use Core\Auth;
use Core\Helpers\Redirect;

class AdminMiddleware
{
    public static function handle(): void
    {
        if (!Auth::isAdmin()) {
            Redirect::to('/admin/login');
        }
    }
}
