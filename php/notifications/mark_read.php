<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../connection.php';
include 'notifications_bootstrap.php';

$notificationId = (int) ($_GET['id'] ?? 0);
$fallback = $_GET['redirect'] ?? '../userpage.php';

if ($notificationId > 0) {
    $stmt = $conn->prepare("
        UPDATE notifications
        SET is_read = 1
        WHERE notification_id = ? AND user_id = ?
    ");
    $stmt->bind_param("ii", $notificationId, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();

    $lookup = $conn->prepare("SELECT link FROM notifications WHERE notification_id = ? AND user_id = ?");
    $lookup->bind_param("ii", $notificationId, $_SESSION['user_id']);
    $lookup->execute();
    $row = $lookup->get_result()->fetch_assoc();
    $lookup->close();
    if (!empty($row['link'])) {
        header("Location: " . $row['link']);
        exit();
    }
}

header("Location: " . $fallback);
exit();
