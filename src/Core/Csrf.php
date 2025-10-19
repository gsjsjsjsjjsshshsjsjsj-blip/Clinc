<?php
declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return (string) $_SESSION['csrf_token'];
    }

    public static function field(): string
    {
        $key = Config::get('security.csrf_token_key', '_csrf');
        return '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars(self::token()) . '">';
    }

    public static function validateFromArray(array $data): bool
    {
        $key = Config::get('security.csrf_token_key', '_csrf');
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $expected = $_SESSION['csrf_token'] ?? '';
        $provided = $data[$key] ?? '';
        return is_string($provided) && hash_equals((string)$expected, (string)$provided);
    }
}
