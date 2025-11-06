<?php
require_once '../../connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$module_id = intval($_GET['module_id']);

// ✅ Get total lessons in the module
$total_sql = $conn->prepare("SELECT COUNT(*) AS total FROM lessons WHERE module_id = ?");
$total_sql->bind_param("i", $module_id);
$total_sql->execute();
$total_result = $total_sql->get_result()->fetch_assoc();
$total_lessons = $total_result['total'] ?? 0;

// ✅ Get completed lessons
$completed_sql = $conn->prepare("
    SELECT COUNT(*) AS completed 
    FROM lesson_progress lp
    JOIN lessons l ON lp.lesson_id = l.lesson_id
    WHERE lp.user_id = ? AND lp.completed = 1 AND l.module_id = ?
");
$completed_sql->bind_param("ii", $user_id, $module_id);
$completed_sql->execute();
$completed_result = $completed_sql->get_result()->fetch_assoc();
$completed_lessons = $completed_result['completed'] ?? 0;

// ✅ Compare results
if ($completed_lessons < $total_lessons) {
    echo json_encode([
        'status' => 'incomplete',
        'message' => 'Please finish all lessons before taking the quiz.'
    ]);
} else {
    echo json_encode(['status' => 'complete']);
}
?>
