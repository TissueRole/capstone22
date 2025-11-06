<?php
session_start();
require_once '../../connection.php';
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Quiz ID is required']);
    exit();
}

$quiz_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'] ?? 1; // fallback for testing
$forceRetake = isset($_GET['forceRetake']) && $_GET['forceRetake'] == '1';

try {
    // ✅ Fetch quiz and module
    $quiz_sql = "SELECT * FROM module_quizzes WHERE quiz_id = ?";
    $stmt = $conn->prepare($quiz_sql);
    $stmt->bind_param("i", $quiz_id);
    $stmt->execute();
    $quiz = $stmt->get_result()->fetch_assoc();

    if (!$quiz) {
        echo json_encode(['error' => 'Quiz not found']);
        exit();
    }

    $module_id = $quiz['module_id'];

    // ✅ Check if all lessons in this module are completed by the user
    $progress_sql = "
        SELECT COUNT(*) AS total_lessons,
               SUM(CASE WHEN lp.completed = 1 THEN 1 ELSE 0 END) AS completed_lessons
        FROM lessons l
        LEFT JOIN lesson_progress lp
            ON l.lesson_id = lp.lesson_id AND lp.user_id = ?
        WHERE l.module_id = ?
    ";
    $stmt = $conn->prepare($progress_sql);
    $stmt->bind_param("ii", $user_id, $module_id);
    $stmt->execute();
    $progress = $stmt->get_result()->fetch_assoc();

    $total_lessons = (int)$progress['total_lessons'];
    $completed_lessons = (int)$progress['completed_lessons'];

    // ✅ If not all lessons completed, block quiz access
    if ($total_lessons > 0 && $completed_lessons < $total_lessons) {
        echo json_encode([
            'error' => 'You must complete all lessons in this module before taking the quiz.',
            'progress' => [
                'completed' => $completed_lessons,
                'total' => $total_lessons
            ]
        ]);
        exit();
    }

    // ✅ Fetch questions
    $q_sql = "SELECT question_id, question_text, option_a, option_b, option_c, option_d, correct_option 
              FROM quiz_questions 
              WHERE quiz_id = ? 
              ORDER BY question_id ASC";
    $stmt = $conn->prepare($q_sql);
    $stmt->bind_param("i", $quiz_id);
    $stmt->execute();
    $questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $quiz['questions'] = $questions;

    // ✅ Fetch user result (only if not forcing retake)
    if (!$forceRetake) {
        $r_sql = "SELECT score, taken_at FROM quiz_results WHERE user_id = ? AND quiz_id = ?";
        $stmt = $conn->prepare($r_sql);
        $stmt->bind_param("ii", $user_id, $quiz_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result) {
            $quiz['user_result'] = [
                'score' => (float)$result['score'],
                'taken_at' => $result['taken_at']
            ];
        }
    }

    echo json_encode($quiz);

} catch (Exception $e) {
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
