<?php
session_start();
include '../../connection.php'; // adjust path if needed
include '../../notifications/notifications_helper.php';
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

$ownerStmt = $conn->prepare("SELECT user_id, title FROM questions WHERE question_id = ?");
$ownerStmt->bind_param("i", $questionId);
$ownerStmt->execute();
$questionRow = $ownerStmt->get_result()->fetch_assoc();
$ownerStmt->close();

$stmt = $conn->prepare("UPDATE questions SET status = ? WHERE question_id = ?");
$stmt->bind_param('si', $status, $questionId);
$success = $stmt->execute();

if ($success && $questionRow && $status === 'approved') {
    create_notification(
        $conn,
        (int) $questionRow['user_id'],
        'thread_approved',
        'Your thread "' . $questionRow['title'] . '" has been approved.',
        '../Forum/community.php?filter=my_posts'
    );
}

echo json_encode(['success' => $success]);
?>
