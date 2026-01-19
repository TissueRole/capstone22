<?php
require '../../connection.php';
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit("Not logged in");
}

$user_id = (int)$_SESSION['user_id'];
$module_id = (int)($_POST['module_id'] ?? 0);

// Validate module_id
if ($module_id <= 0) {
    http_response_code(400);
    exit("Invalid module ID");
}

/* ❌ Module 1 DOES NOT GET A CERTIFICATE */
if ($module_id === 1) {
    exit("Module 1 does not issue certificates.");
}

/* ✅ Verify user actually passed the quiz for this module */
$verify = $conn->prepare("
    SELECT score 
    FROM quiz_results 
    WHERE user_id = ? AND quiz_id = (
        SELECT quiz_id FROM module_quizzes WHERE module_id = ?
    )
    ORDER BY taken_at DESC 
    LIMIT 1
");
$verify->bind_param("ii", $user_id, $module_id);
$verify->execute();
$verify_result = $verify->get_result();

if ($verify_result->num_rows === 0) {
    http_response_code(400);
    exit("No quiz result found for this module.");
}

$quiz_data = $verify_result->fetch_assoc();
if ($quiz_data['score'] < 70) {
    http_response_code(400);
    exit("Quiz not passed. Score: " . $quiz_data['score']);
}
$verify->close();

/* ✅ Check if certificate already exists */
$check = $conn->prepare("
    SELECT id 
    FROM certificates 
    WHERE user_id = ? AND module_id = ?
");
$check->bind_param("ii", $user_id, $module_id);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    // Already has certificate — this is OK, not an error
    $check->close();
    http_response_code(200);
    exit("Certificate already exists.");
}
$check->close();

/* ✅ INSERT CERTIFICATE */
$insert = $conn->prepare("
    INSERT INTO certificates (user_id, module_id, completion_date)
    VALUES (?, ?, NOW())
");
$insert->bind_param("ii", $user_id, $module_id);

if (!$insert->execute()) {
    http_response_code(500);
    exit("Failed to save certificate: " . $conn->error);
}

$certificate_id = $conn->insert_id;
$insert->close();

/* ✅ Success */
http_response_code(200);
echo "Certificate saved successfully (ID: $certificate_id)";