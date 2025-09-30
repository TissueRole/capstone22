<?php
// File: php/learning/api/submit-quiz.php
session_start();
require_once '../../connection.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['quiz_id'], $input['user_id'], $input['answers'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

$quiz_id = intval($input['quiz_id']);
$user_id = intval($input['user_id']);
$answers = $input['answers']; // format: { question_id: "A", question_id2: "C", ... }

try {
    // ✅ Get all questions + correct answers
    $sql = "SELECT question_id, correct_option FROM quiz_questions WHERE quiz_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $quiz_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $total = $result->num_rows;
    $correct = 0;

    while ($row = $result->fetch_assoc()) {
        $qid = $row['question_id'];
        $correctOpt = strtoupper(trim($row['correct_option']));

        if (isset($answers[$qid]) && strtoupper(trim($answers[$qid])) === $correctOpt) {
            $correct++;
        }
    }

    $score = $total > 0 ? round(($correct / $total) * 100) : 0;

    // ✅ Save results (make a results table if you haven’t yet)
    $save_sql = "INSERT INTO quiz_results (user_id, quiz_id, score, taken_at)
                 VALUES (?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE score = VALUES(score), taken_at = NOW()";
    $stmt = $conn->prepare($save_sql);
    $stmt->bind_param("iii", $user_id, $quiz_id, $score);
    $stmt->execute();

    echo json_encode(['success' => true, 'score' => $score]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
