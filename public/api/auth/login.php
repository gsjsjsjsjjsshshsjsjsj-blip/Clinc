<?php
require __DIR__ . '/../_bootstrap.php';

use App\Lib\Database;
use App\Support\Response;
use App\Support\Auth;

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    Response::json(['error' => 'Invalid credentials'], 422);
}

$pdo = Database::connection();
$stmt = $pdo->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    Response::json(['error' => 'Email or password incorrect'], 401);
}

Auth::login($user);
Response::json(['message' => 'Logged in', 'user' => ['id' => $user['id'], 'name' => $user['name'], 'email' => $user['email'], 'role' => $user['role']]]);
