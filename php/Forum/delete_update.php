<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (!in_array($_SESSION['role'] ?? '', ['agriculturist', 'admin'], true)) {
    echo "You do not have permission to perform this action.";
    exit();
}

include "../connection.php";
include "community_updates_bootstrap.php";

$updateId = (int) ($_GET['id'] ?? 0);
if ($updateId <= 0) {
    echo "Invalid update.";
    exit();
}

$stmt = $conn->prepare("DELETE FROM community_updates WHERE update_id = ?");
$stmt->bind_param("i", $updateId);
$stmt->execute();
$stmt->close();

header("Location: ../Admin/agriculturistpage.php?section=updates");
exit();
