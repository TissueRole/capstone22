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

$user_id = (int)$_SESSION['user_id'];
$quiz_id = (int)$_GET['id'];

/* ===========================
   FETCH QUIZ
=========================== */
$stmt = $conn->prepare("SELECT * FROM module_quizzes WHERE quiz_id = ?");
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$quiz = $stmt->get_result()->fetch_assoc();

if (!$quiz) {
    echo json_encode(['error' => 'Quiz not found']);
    exit;
}

/* ===========================
   FETCH USER RESULT
=========================== */
$stmt = $conn->prepare("
    SELECT attempt_count, score 
    FROM quiz_results 
    WHERE user_id = ? AND quiz_id = ?
");
$stmt->bind_param("ii", $user_id, $quiz_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

$attempts = $result ? (int)$result['attempt_count'] : 0;
$score    = $result ? (float)$result['score'] : null;
$passed   = $score !== null && $score >= 70;

/* ===========================
   FETCH QUESTIONS
=========================== */
$stmt = $conn->prepare("
    SELECT question_id, question_text, option_a, option_b, option_c, option_d
    FROM quiz_questions
    WHERE quiz_id = ?
    ORDER BY question_id ASC
");
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* ===========================
   RESPONSE (IMPORTANT)
=========================== */
echo json_encode([
    'quiz_id'     => $quiz_id,
    'locked'      => ($attempts >= 3 || $passed), // locked means no retake
    'attempts'    => $attempts,

    // ✅ ALWAYS SEND RESULT IF EXISTS
    'user_result' => $result ? [
        'score'    => $score,
        'attempts' => $attempts
    ] : null,

    // ✅ QUESTIONS ONLY WHEN RETAKE IS ALLOWED
    'questions'   => ($passed || $attempts >= 3) ? [] : $questions,

    'message' => $passed
        ? '✅ You already passed this quiz.'
        : ($attempts >= 3 ? '❌ You have reached the maximum of 3 attempts.' : null)
]);
