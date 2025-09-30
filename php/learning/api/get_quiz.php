<?php
session_start();
require_once '../../connection.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Quiz ID is required']);
    exit();
}

$quiz_id = intval($_GET['id']);

try {
    // ✅ Fetch quiz
    $quiz_sql = "SELECT * FROM module_quizzes WHERE quiz_id = ?";
    $stmt = $conn->prepare($quiz_sql);
    $stmt->bind_param("i", $quiz_id);
    $stmt->execute();
    $quiz = $stmt->get_result()->fetch_assoc();

    if (!$quiz) {
        echo json_encode(['error' => 'Quiz not found']);
        exit();
    }

    // ✅ Fetch questions (only needed columns)
    $q_sql = "SELECT question_id, question_text, option_a, option_b, option_c, option_d, correct_option 
              FROM quiz_questions 
              WHERE quiz_id = ? 
              ORDER BY question_id ASC";
    $stmt = $conn->prepare($q_sql);
    $stmt->bind_param("i", $quiz_id);
    $stmt->execute();
    $questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // ✅ Attach questions to quiz
    $quiz['questions'] = $questions;

    echo json_encode($quiz);
} catch (Exception $e) {
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
