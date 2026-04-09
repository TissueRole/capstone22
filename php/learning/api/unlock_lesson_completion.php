<?php
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$lessonId = isset($input['lesson_id']) ? (int) $input['lesson_id'] : 0;

if ($lessonId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Lesson ID required']);
    exit;
}

if (!isset($_SESSION['lesson_ready_to_complete'])) {
    $_SESSION['lesson_ready_to_complete'] = [];
}

$_SESSION['lesson_ready_to_complete'][$lessonId] = true;

echo json_encode(['success' => true]);
?>
