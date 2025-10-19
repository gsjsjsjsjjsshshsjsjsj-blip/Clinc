<?php
declare(strict_types=1);

namespace App\Core;

final class Config
{
    private static ?array $config = null;

    public static function get(string $path, $default = null)
    {
        if (self::$config === null) {
            $configFile = __DIR__ . '/../Config/config.php';
            if (!is_file($configFile)) {
                throw new \RuntimeException('Missing configuration file at ' . $configFile);
            }
            self::$config = require $configFile;
        }

        $segments = explode('.', $path);
        $value = self::$config;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }

    public static function env(): string
    {
        return (string) self::get('app.env', 'development');
    }

    public static function isProduction(): bool
    {
        return self::env() === 'production';
    }
}
