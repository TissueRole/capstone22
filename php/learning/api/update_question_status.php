<?php
session_start();
include '../../connection.php'; // adjust path if needed
header('Content-Type: application/json');

// Optional: Admin-only protection
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['id']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$questionId = intval($data['id']);
$status = $data['status'];

$allowed = ['approved', 'rejected', 'pending'];
if (!in_array($status, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

$stmt = $conn->prepare("UPDATE questions SET status = ? WHERE question_id = ?");
$stmt->bind_param('si', $status, $questionId);
$success = $stmt->execute();

echo json_encode(['success' => $success]);
?>
