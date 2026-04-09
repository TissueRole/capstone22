<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

include "../connection.php";

$userId = (int) $_SESSION['user_id'];
$action = $_POST['action'] ?? '';
$questionId = (int) ($_POST['question_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $questionId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$ownershipStmt = $conn->prepare("SELECT question_id FROM questions WHERE question_id = ? AND user_id = ? AND status = 'pending'");
$ownershipStmt->bind_param("ii", $questionId, $userId);
$ownershipStmt->execute();
$ownedQuestion = $ownershipStmt->get_result()->fetch_assoc();
$ownershipStmt->close();

if (!$ownedQuestion) {
    echo json_encode(['success' => false, 'message' => 'You can only manage your own pending threads.']);
    exit;
}

if ($action === 'delete') {
    $deleteReplies = $conn->prepare("DELETE FROM reply WHERE question_id = ?");
    $deleteReplies->bind_param("i", $questionId);
    $deleteReplies->execute();
    $deleteReplies->close();

    $deleteQuestion = $conn->prepare("DELETE FROM questions WHERE question_id = ? AND user_id = ? AND status = 'pending'");
    $deleteQuestion->bind_param("ii", $questionId, $userId);
    $success = $deleteQuestion->execute();
    $deleteQuestion->close();

    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Pending thread deleted.' : 'Failed to delete thread.'
    ]);
    exit;
}

if ($action === 'update') {
    $title = trim($_POST['title'] ?? '');
    $body = trim($_POST['body'] ?? '');

    if ($title === '' || $body === '') {
        echo json_encode(['success' => false, 'message' => 'Title and message are required.']);
        exit;
    }

    $updateStmt = $conn->prepare("
        UPDATE questions
        SET title = ?, body = ?, created_at = created_at
        WHERE question_id = ? AND user_id = ? AND status = 'pending'
    ");
    $updateStmt->bind_param("ssii", $title, $body, $questionId, $userId);
    $success = $updateStmt->execute();
    $updateStmt->close();

    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Pending thread updated.' : 'Failed to update thread.'
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unsupported action']);
