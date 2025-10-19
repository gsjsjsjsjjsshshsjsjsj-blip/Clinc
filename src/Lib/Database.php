<?php

namespace App\Lib;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection === null) {
            $config = require __DIR__ . '/../../config/database.php';
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset']
            );

            try {
                self::$connection = new PDO($dsn, $config['username'], $config['password'], $config['options']);
            } catch (PDOException $e) {
                if ((require __DIR__ . '/../../config/app.php')['debug']) {
                    throw $e;
                }
                http_response_code(500);
                echo json_encode(['error' => 'Database connection failed']);
                exit;
            }
        }

        return self::$connection;
    }
}
