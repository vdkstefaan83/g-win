<?php

namespace Core;

class Auth
{
    public static function login(array $user): void
    {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];
    }

    public static function logout(): void
    {
        unset($_SESSION['user']);
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int
    {
        return isset($_SESSION['user']) ? (int)$_SESSION['user']['id'] : null;
    }

    public static function isAdmin(): bool
    {
        return self::check() && in_array($_SESSION['user']['role'] ?? '', ['admin', 'superadmin']);
    }

    public static function isSuperAdmin(): bool
    {
        return self::check() && ($_SESSION['user']['role'] ?? '') === 'superadmin';
    }
}
