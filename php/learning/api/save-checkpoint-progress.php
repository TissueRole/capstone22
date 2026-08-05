<?php

session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Not logged in.'
    ]);
    exit();
}

require_once '../../connection.php';

$user_id = intval($_SESSION['user_id']);

$data = json_decode(file_get_contents('php://input'), true);

$lesson_id = intval($data['lesson_id'] ?? 0);
$checkpoints = $data['checkpoints'] ?? [];

if ($lesson_id <= 0 || !is_array($checkpoints)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid data.'
    ]);
    exit();
}

/*
 * Only store checkpoint indexes.
 * Example: ["0", "1", "2"]
 */
$checkpoints = array_values(array_unique(
    array_map('strval', $checkpoints)
));

$checkpoint_json = json_encode($checkpoints);

$stmt = $conn->prepare("
    INSERT INTO lesson_progress
        (user_id, lesson_id, checkpoint_progress)
    VALUES
        (?, ?, ?)
    ON DUPLICATE KEY UPDATE
        checkpoint_progress = VALUES(checkpoint_progress)
");

$stmt->bind_param(
    "iis",
    $user_id,
    $lesson_id,
    $checkpoint_json
);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => $stmt->error
    ]);
}