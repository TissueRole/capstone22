<?php
session_start();
if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include('../connection.php');
include('../Forum/forum_reports_bootstrap.php');

$reportId = (int) ($_GET['id'] ?? 0);
if ($reportId <= 0) {
    header("Location: adminpage.php#forum-management");
    exit();
}

$stmt = $conn->prepare("DELETE FROM forum_reports WHERE report_id = ?");
$stmt->bind_param("i", $reportId);
$stmt->execute();
$stmt->close();

header("Location: adminpage.php#forum-management");
exit();
