<?php
session_start();
require_once '../../connection.php';
header('Content-Type: application/json');

// ✅ Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "User not logged in"]);
    exit;
}

$user_id = $_SESSION['user_id'];

// ✅ Read JSON input
$input = json_decode(file_get_contents("php://input"), true);
if (!$input) {
    echo json_encode(["success" => false, "message" => "No JSON data received"]);
    exit;
}

$quiz_id = intval($input['quiz_id']);
$answers = $input['answers'] ?? [];

// ✅ Check if already passed
$stmt = $conn->prepare("SELECT score FROM quiz_results WHERE user_id = ? AND quiz_id = ?");
$stmt->bind_param("ii", $user_id, $quiz_id);
$stmt->execute();
$res = $stmt->get_result();

$alreadyPassed = false;
if ($res->num_rows > 0) {
    $existing = $res->fetch_assoc();
    if ((float)$existing['score'] >= 70) {
        $alreadyPassed = true;
    }
}

if ($alreadyPassed) {
    echo json_encode([
        "success" => false,
        "message" => "✅ You already passed this quiz. Retakes are disabled.",
        "locked" => true
    ]);
    exit;
}

// ✅ Compute score
$total_questions = count($answers);
$correct = 0;

foreach ($answers as $ans) {
    $stmt = $conn->prepare("SELECT correct_option FROM quiz_questions WHERE question_id = ?");
    $stmt->bind_param("i", $ans['question_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();

    if ($row && strtoupper(trim($row['correct_option'])) === strtoupper(trim($ans['selected_option']))) {
        $correct++;
    }
}

$score = ($total_questions > 0) ? round(($correct / $total_questions) * 100) : 0;

// ✅ Save result
$stmt = $conn->prepare("
    INSERT INTO quiz_results (user_id, quiz_id, score, taken_at)
    VALUES (?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE score = VALUES(score), taken_at = NOW()
");
$stmt->bind_param("iid", $user_id, $quiz_id, $score);
$stmt->execute();

// ✅ Mark module as completed if passed
if ($score >= 70) {
    $stmt = $conn->prepare("
        UPDATE module_progress 
        SET completed = 1, completed_at = NOW() 
        WHERE user_id = ? AND module_id = (
            SELECT module_id FROM module_quizzes WHERE quiz_id = ?
        )
    ");
    $stmt->bind_param("ii", $user_id, $quiz_id);
    $stmt->execute();
}

echo json_encode([
    "success" => true,
    "correct" => $correct,
    "total" => $total_questions,
    "score" => $score,
    "passed" => $score >= 70
]);
?>
