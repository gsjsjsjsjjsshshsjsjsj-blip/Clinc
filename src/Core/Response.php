<?php
declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}
