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

// ✅ Fetch attempt count & previous score
$stmt = $conn->prepare("SELECT attempt_count, score FROM quiz_results WHERE user_id = ? AND quiz_id = ?");
$stmt->bind_param("ii", $user_id, $quiz_id);
$stmt->execute();
$result = $stmt->get_result();
$existing = $result->fetch_assoc();

$attempt_count = $existing['attempt_count'] ?? 0;
$alreadyPassed = isset($existing['score']) && (float)$existing['score'] >= 70;

// ✅ Check if already passed
if ($alreadyPassed) {
    echo json_encode([
        "success" => false,
        "message" => "✅ You already passed this quiz. Retakes are disabled.",
        "locked" => true,
        "attempts" => $attempt_count
    ]);
    exit;
}

// ✅ Check attempt limit (3 max)
if ($attempt_count >= 3) {
    echo json_encode([
        "success" => false,
        "message" => "❌ You have reached the maximum of 3 attempts for this quiz.",
        "locked" => true,
        "attempts" => $attempt_count
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

// ✅ Update or insert quiz result
if ($existing) {
    $stmt = $conn->prepare("
        UPDATE quiz_results 
        SET score = ?, taken_at = NOW(), attempt_count = attempt_count + 1 
        WHERE user_id = ? AND quiz_id = ?
    ");
    $stmt->bind_param("dii", $score, $user_id, $quiz_id);
} else {
    $stmt = $conn->prepare("
        INSERT INTO quiz_results (user_id, quiz_id, score, taken_at, attempt_count)
        VALUES (?, ?, ?, NOW(), 1)
    ");
    $stmt->bind_param("iid", $user_id, $quiz_id, $score);
}
$stmt->execute();

// ✅ Mark module as completed if passed
if ($score >= 70) {
    // Fetch module id from quiz_id
    $stmt = $conn->prepare("SELECT module_id FROM module_quizzes WHERE quiz_id = ?");
    $stmt->bind_param("i", $quiz_id);
    $stmt->execute();
    $moduleResult = $stmt->get_result()->fetch_assoc();
    $module_id = $moduleResult['module_id'];

    // Insert certificate entry if not yet generated
    $stmt = $conn->prepare("SELECT id FROM certificates WHERE user_id = ? AND module_id = ?");
    $stmt->bind_param("ii", $user_id, $module_id);
    $stmt->execute();
    $certExist = $stmt->get_result()->fetch_assoc();

    if (!$certExist) {
        $certificateFile = "certs/cert_" . $user_id . "_" . $module_id . ".pdf";

        $stmt = $conn->prepare("
            INSERT INTO certificates (user_id, module_id, certificate_path)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("iis", $user_id, $module_id, $certificateFile);
        $stmt->execute();
    }
}


// ✅ Return quiz result summary
echo json_encode([
    "success" => true,
    "correct" => $correct,
    "total" => $total_questions,
    "score" => $score,
    "attempts" => $attempt_count + 1,
    "passed" => $score >= 70
]);
?>
