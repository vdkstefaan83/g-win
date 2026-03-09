<?php

namespace Core\Helpers;

class Redirect
{
    public static function to(string $url): never
    {
        header("Location: {$url}");
        exit;
    }

    public static function back(): never
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        self::to($referer);
    }
}
