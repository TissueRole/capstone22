<?php
session_start();
require_once '../../connection.php';
require_once '../../learning-functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Lesson ID required']);
    exit;
}

$learning = new TeenAnimLearning($conn);
$lesson = $learning->getLesson($_GET['id'], $_SESSION['user_id']);

if (!$lesson) {
    http_response_code(404);
    echo json_encode(['error' => 'Lesson not found']);
    exit;
}

echo json_encode($lesson);
?>