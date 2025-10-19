<?php
require __DIR__ . '/../_bootstrap.php';

use App\Lib\Database;
use App\Support\Response;
use App\Support\Auth;

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // List appointments for current user (patient or doctor)
        $user = Auth::user();
        if (!$user) Response::json(['error' => 'Unauthorized'], 401);
        $pdo = Database::connection();
        if ($user['role'] === 'doctor') {
            $stmt = $pdo->prepare('SELECT a.* FROM appointments a WHERE a.doctor_id = ? ORDER BY a.start_time DESC');
            $stmt->execute([$user['id']]);
        } else {
            $stmt = $pdo->prepare('SELECT a.* FROM appointments a WHERE a.patient_id = ? ORDER BY a.start_time DESC');
            $stmt->execute([$user['id']]);
        }
        Response::json(['data' => $stmt->fetchAll()]);
        break;
    case 'POST':
        // Create appointment (patient)
        Auth::requireRole(['patient']);
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $doctorId = (int) ($input['doctor_id'] ?? 0);
        $startTime = $input['start_time'] ?? '';
        $endTime = $input['end_time'] ?? '';
        if ($doctorId <= 0 || !$startTime || !$endTime) {
            Response::json(['error' => 'Invalid input'], 422);
        }
        $pdo = Database::connection();
        // Prevent overlap for same doctor
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND ((start_time < ? AND end_time > ?) OR (start_time < ? AND end_time > ?) OR (start_time >= ? AND start_time < ?))');
        $stmt->execute([$doctorId, $endTime, $endTime, $startTime, $startTime, $startTime, $endTime]);
        if ($stmt->fetchColumn() > 0) {
            Response::json(['error' => 'Time slot not available'], 409);
        }
        $user = Auth::user();
        $stmt = $pdo->prepare('INSERT INTO appointments (patient_id, doctor_id, start_time, end_time, status) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$user['id'], $doctorId, $startTime, $endTime, 'pending']);
        Response::json(['message' => 'Appointment created']);
        break;
    case 'PUT':
    case 'PATCH':
        // Update status (doctor can approve/cancel; patient can cancel own)
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $appointmentId = (int) ($input['id'] ?? 0);
        $status = $input['status'] ?? '';
        if ($appointmentId <= 0 || !in_array($status, ['approved', 'cancelled'], true)) {
            Response::json(['error' => 'Invalid input'], 422);
        }
        $user = Auth::user();
        if (!$user) Response::json(['error' => 'Unauthorized'], 401);
        $pdo = Database::connection();
        if ($user['role'] === 'doctor') {
            $stmt = $pdo->prepare('UPDATE appointments SET status = ? WHERE id = ? AND doctor_id = ?');
            $stmt->execute([$status, $appointmentId, $user['id']]);
        } else {
            if ($status !== 'cancelled') Response::json(['error' => 'Patients can only cancel'], 403);
            $stmt = $pdo->prepare('UPDATE appointments SET status = ? WHERE id = ? AND patient_id = ?');
            $stmt->execute([$status, $appointmentId, $user['id']]);
        }
        Response::json(['message' => 'Appointment updated']);
        break;
    case 'DELETE':
        // Delete by admin only
        Auth::requireRole(['admin']);
        parse_str(file_get_contents('php://input'), $input);
        $id = (int) ($input['id'] ?? 0);
        if ($id <= 0) Response::json(['error' => 'Invalid input'], 422);
        $pdo = Database::connection();
        $stmt = $pdo->prepare('DELETE FROM appointments WHERE id = ?');
        $stmt->execute([$id]);
        Response::json(['message' => 'Appointment deleted']);
        break;
    default:
        Response::json(['error' => 'Method not allowed'], 405);
}
