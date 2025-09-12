<?php
session_start();
require_once '../../connection.php';
require_once '../../learning-functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$learning = new TeenAnimLearning($conn);
$progress = $learning->getUserProgress($_SESSION['user_id']);

echo json_encode($progress);
?>