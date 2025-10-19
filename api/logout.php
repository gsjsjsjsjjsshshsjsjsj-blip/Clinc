<?php
/**
 * معالج تسجيل الخروج
 * Logout Handler
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

$auth = new Auth();
$result = $auth->logout();

redirect(SITE_URL . '/index.html');
?>
