<?php
session_start();
require_once '../../connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$quiz_id = intval($data['quiz_id'] ?? 0);
$answers = $data['answers'] ?? [];

if ($quiz_id <= 0 || empty($answers)) {
    echo json_encode(['success' => false, 'message' => 'Invalid submission']);
    exit;
}

$user_id = $_SESSION['user_id'];

/* 🔢 Evaluate answers */
$correct = 0;
$total = count($answers);

foreach ($answers as $a) {
    $stmt = $conn->prepare("
        SELECT correct_option 
        FROM quiz_questions 
        WHERE question_id = ?
    ");
    $stmt->bind_param("i", $a['question_id']);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    if ($res && $res['correct_option'] === $a['selected_option']) {
        $correct++;
    }
}

$score = round(($correct / $total) * 100);

/* 🔁 Check existing attempts */
$stmt = $conn->prepare("
    SELECT attempt_count 
    FROM quiz_results 
    WHERE user_id = ? AND quiz_id = ?
");
$stmt->bind_param("ii", $user_id, $quiz_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if ($result) {
    // User has taken this quiz before - update the record
    $attempts = $result['attempt_count'] + 1;
    
    $stmt = $conn->prepare("
        UPDATE quiz_results 
        SET score = ?, attempt_count = ?, taken_at = NOW() 
        WHERE user_id = ? AND quiz_id = ?
    ");
    $stmt->bind_param("iiii", $score, $attempts, $user_id, $quiz_id);
    $stmt->execute();
} else {
    // First attempt - insert new record
    $attempts = 1;
    
    $stmt = $conn->prepare("
        INSERT INTO quiz_results (user_id, quiz_id, score, attempt_count, taken_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("iiii", $user_id, $quiz_id, $score, $attempts);
    $stmt->execute();
}

echo json_encode([
    'success' => true,
    'score' => $score,
    'attempts' => $attempts
]);
exit;