<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    http_response_code(403);
    exit;
}
include('../connection.php');
$conn->query("UPDATE suggestions SET seen = 1 WHERE seen = 0");
echo json_encode(['success' => true]);
?>
