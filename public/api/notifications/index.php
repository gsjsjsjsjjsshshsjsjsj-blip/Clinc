<?php
require __DIR__ . '/../_bootstrap.php';

use App\Lib\Database;
use App\Support\Response;
use App\Support\Auth;

$user = Auth::user();
if (!$user) Response::json(['error' => 'Unauthorized'], 401);

$pdo = Database::connection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare('SELECT id, type, title, body, created_at, read_at FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 50');
    $stmt->execute([$user['id']]);
    Response::json(['data' => $stmt->fetchAll()]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $notificationIds = $input['ids'] ?? [];
    if (!is_array($notificationIds) || empty($notificationIds)) {
        Response::json(['error' => 'Invalid input'], 422);
    }
    $in = implode(',', array_fill(0, count($notificationIds), '?'));
    $stmt = $pdo->prepare("UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND id IN ($in)");
    $stmt->execute(array_merge([$user['id']], $notificationIds));
    Response::json(['message' => 'Marked as read']);
}

Response::json(['error' => 'Method not allowed'], 405);
