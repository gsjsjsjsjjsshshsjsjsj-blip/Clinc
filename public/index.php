<?php
declare(strict_types=1);

use App\Core\ErrorHandler;
use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Csrf;

require __DIR__ . '/../src/Core/Autoloader.php';

ErrorHandler::register();

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = Request::method();

// Simple router switch (can be upgraded to a Router class later)
switch ([$method, $path]) {
    case ['GET', '/']:
        require __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/dashboard/home.php';
        require __DIR__ . '/../views/layout/footer.php';
        break;

    case ['GET', '/login']:
        require __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/auth/login.php';
        require __DIR__ . '/../views/layout/footer.php';
        break;

    case ['GET', '/register']:
        require __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/auth/register.php';
        require __DIR__ . '/../views/layout/footer.php';
        break;

    default:
        http_response_code(404);
        echo 'الصفحة غير موجودة';
}
