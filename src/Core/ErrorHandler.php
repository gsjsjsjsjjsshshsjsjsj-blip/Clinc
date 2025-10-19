<?php
declare(strict_types=1);

namespace App\Core;

use Throwable;

final class ErrorHandler
{
    public static function register(): void
    {
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
    }

    public static function handleError(int $severity, string $message, string $file, int $line): void
    {
        $error = sprintf('[PHP ERROR] %s in %s:%d', $message, $file, $line);
        self::log($error);
        if (Config::isProduction()) {
            http_response_code(500);
            echo 'حدث خطأ غير متوقع. يرجى المحاولة لاحقاً.';
        } else {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        }
    }

    public static function handleException(Throwable $e): void
    {
        $error = sprintf('[EXCEPTION] %s in %s:%d', $e->getMessage(), $e->getFile(), $e->getLine());
        self::log($error . "\n" . $e->getTraceAsString());
        http_response_code(500);
        if (Config::isProduction()) {
            echo 'حدث خطأ غير متوقع. يرجى المحاولة لاحقاً.';
        } else {
            echo '<pre style="padding:16px">' . htmlspecialchars($error . "\n\n" . $e) . '</pre>';
        }
    }

    public static function log(string $message): void
    {
        $logDir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }
        $logFile = $logDir . '/app.log';
        $date = date('Y-m-d H:i:s');
        error_log("[{$date}] {$message}\n", 3, $logFile);
    }
}
