<?php
session_start();
require_once '../../connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Quiz ID is required']);
    exit;
}

$user_id = $_SESSION['user_id'];
$quiz_id = (int)$_GET['id'];

// Fetch quiz
$stmt = $conn->prepare("SELECT * FROM module_quizzes WHERE quiz_id = ?");
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$quiz = $stmt->get_result()->fetch_assoc();

if (!$quiz) {
    echo json_encode(['error' => 'Quiz not found']);
    exit;
}

// Fetch attempt info
$stmt = $conn->prepare("
    SELECT attempt_count, score 
    FROM quiz_results 
    WHERE user_id = ? AND quiz_id = ?
");
$stmt->bind_param("ii", $user_id, $quiz_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

$attempts = $result ? (int)$result['attempt_count'] : 0;
$passed   = $result && (float)$result['score'] >= 70;

// 🔒 LOCKED STATE
if ($passed || $attempts >= 3) {
    echo json_encode([
        'locked' => true,
        'passed' => $passed,
        'attempts' => $attempts,
        'questions' => [], // 🔑 IMPORTANT
        'message' => $passed
            ? '✅ You already passed this quiz.'
            : '❌ You have reached the maximum of 3 attempts.'
    ]);
    exit;
}

// Fetch questions
$stmt = $conn->prepare("
    SELECT question_id, question_text, option_a, option_b, option_c, option_d
    FROM quiz_questions
    WHERE quiz_id = ?
    ORDER BY question_id ASC
");
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'locked' => false,
    'attempts' => $attempts,
    'questions' => $questions
]);
