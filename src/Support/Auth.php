<?php

namespace App\Support;

class Auth
{
    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
                'use_strict_mode' => true,
            ]);
        }
    }

    public static function login(array $user): void
    {
        self::start();
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'] ?? $user['full_name'] ?? '',
            'email' => $user['email'],
            'role' => $user['role'],
        ];
    }

    public static function user(): ?array
    {
        self::start();
        return $_SESSION['user'] ?? null;
    }

    public static function requireRole(array $roles): void
    {
        $user = self::user();
        if (!$user || !in_array($user['role'], $roles, true)) {
            Response::json(['error' => 'Unauthorized'], 401);
        }
    }

    public static function logout(): void
    {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}
