<?php

namespace Core\Helpers;

use Core\Session;

class FlashMessage
{
    public static function success(string $message): void
    {
        Session::flash('success', $message);
    }

    public static function error(string $message): void
    {
        Session::flash('error', $message);
    }

    public static function warning(string $message): void
    {
        Session::flash('warning', $message);
    }

    public static function info(string $message): void
    {
        Session::flash('info', $message);
    }
}
