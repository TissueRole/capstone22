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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['lesson_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Lesson ID required']);
    exit;
}

$lessonId = (int) $input['lesson_id'];

if (!isset($_SESSION['lesson_ready_to_complete'])) {
    $_SESSION['lesson_ready_to_complete'] = [];
}

if (empty($_SESSION['lesson_ready_to_complete'][$lessonId])) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Reach the end of the lesson before marking it complete.'
    ]);
    exit;
}

$learning = new TeenAnimLearning($conn);
$result = $learning->markLessonComplete($lessonId, $_SESSION['user_id']);

if (!empty($result['success'])) {
    unset($_SESSION['lesson_ready_to_complete'][$lessonId]);
}

echo json_encode($result);
?>
