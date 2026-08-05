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

$lessonId = (int) $_GET['id'];
$userId = (int) $_SESSION['user_id'];

if (!isset($_SESSION['lesson_ready_to_complete'])) {
    $_SESSION['lesson_ready_to_complete'] = [];
}

unset($_SESSION['lesson_ready_to_complete'][$lessonId]);

$learning = new TeenAnimLearning($conn);
$lesson = $learning->getLesson($lessonId, $userId);

if (!$lesson) {
    http_response_code(404);
    echo json_encode(['error' => 'Lesson not found']);
    exit;
}


$checkpointProgress = [];

$stmt = $conn->prepare("
    SELECT checkpoint_progress
    FROM lesson_progress
    WHERE user_id = ?
      AND lesson_id = ?
    LIMIT 1
");

$stmt->bind_param("ii", $userId, $lessonId);
$stmt->execute();

$result = $stmt->get_result();
$progress = $result->fetch_assoc();

if ($progress && !empty($progress['checkpoint_progress'])) {
    $decoded = json_decode($progress['checkpoint_progress'], true);

    if (is_array($decoded)) {
        $checkpointProgress = array_map('strval', $decoded);
    }
}

$stmt->close();


$lesson['checkpoint_progress'] = $checkpointProgress;

if (isset($lesson['checkpoint_progress'])) {
    if (is_string($lesson['checkpoint_progress'])) {
        $decoded = json_decode($lesson['checkpoint_progress'], true);

        $lesson['checkpoint_progress'] = is_array($decoded)
            ? $decoded
            : [];
    } elseif (!is_array($lesson['checkpoint_progress'])) {
        $lesson['checkpoint_progress'] = [];
    }
} else {
    $lesson['checkpoint_progress'] = [];
}

echo json_encode($lesson);
?>