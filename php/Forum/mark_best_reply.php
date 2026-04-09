<?php
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['agriculturist', 'admin'], true)) {
    header("Location: ../login.php");
    exit();
}

include "../connection.php";
include "thread_bootstrap.php";

$threadId = (int) ($_GET['thread_id'] ?? 0);
$replyId = (int) ($_GET['reply_id'] ?? 0);

if ($threadId <= 0 || $replyId <= 0) {
    header("Location: community.php");
    exit();
}

$replyStmt = $conn->prepare("
    SELECT r.reply_id
    FROM reply r
    JOIN users u ON r.user_id = u.user_id
    WHERE r.reply_id = ? AND r.question_id = ? AND u.role IN ('agriculturist', 'admin')
");
$replyStmt->bind_param("ii", $replyId, $threadId);
$replyStmt->execute();
$validReply = $replyStmt->get_result()->fetch_assoc();
$replyStmt->close();

if ($validReply) {
    $updateStmt = $conn->prepare("UPDATE questions SET best_reply_id = ? WHERE question_id = ?");
    $updateStmt->bind_param("ii", $replyId, $threadId);
    $updateStmt->execute();
    $updateStmt->close();
}

header("Location: thread.php?id=" . $threadId);
exit();
