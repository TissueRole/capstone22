<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include "../connection.php";
include "forum_reports_bootstrap.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "Invalid request.";
    exit();
}

$reporterUserId = (int) $_SESSION['user_id'];
$targetType = $_POST['target_type'] ?? '';
$targetId = (int) ($_POST['target_id'] ?? 0);
$threadId = (int) ($_POST['thread_id'] ?? 0);
$reason = trim($_POST['reason'] ?? '');

if (!in_array($targetType, ['question', 'reply'], true) || $targetId <= 0 || $reason === '') {
    header("Location: thread.php?id=" . $threadId);
    exit();
}

$checkStmt = $conn->prepare("
    SELECT report_id
    FROM forum_reports
    WHERE reporter_user_id = ? AND target_type = ? AND target_id = ?
");
$checkStmt->bind_param("isi", $reporterUserId, $targetType, $targetId);
$checkStmt->execute();
$existing = $checkStmt->get_result()->fetch_assoc();
$checkStmt->close();

if (!$existing) {
    $stmt = $conn->prepare("
        INSERT INTO forum_reports (reporter_user_id, target_type, target_id, reason)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("isis", $reporterUserId, $targetType, $targetId, $reason);
    $stmt->execute();
    $stmt->close();
    header("Location: thread.php?id=" . $threadId . "&report=submitted");
    exit();
}

header("Location: thread.php?id=" . $threadId . "&report=duplicate");
exit();
