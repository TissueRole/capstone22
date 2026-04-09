<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../connection.php';
include 'notifications_bootstrap.php';

$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->close();

$redirect = $_GET['redirect'] ?? '../index.php';
header("Location: " . $redirect);
exit();
