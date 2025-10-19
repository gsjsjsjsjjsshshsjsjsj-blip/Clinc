<?php
require __DIR__ . '/../_bootstrap.php';

use App\Lib\Database;
use App\Support\Response;

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$role = $input['role'] ?? 'patient';

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
    Response::json(['error' => 'Invalid input'], 422);
}

$pdo = Database::connection();

// Check duplicate
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    Response::json(['error' => 'Email already exists'], 409);
}

$hash = password_hash($password, PASSWORD_BCRYPT);
$stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');
$stmt->execute([$name, $email, $hash, $role]);

Response::json(['message' => 'Registered successfully']);
