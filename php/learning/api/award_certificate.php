<?php
require '../../connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}

$user_id = $_SESSION['user_id'];
$module_id = intval($_GET['module_id']); // module id coming from quiz result page

// Prevent duplicate certificates
$check = $conn->prepare("SELECT * FROM certificates WHERE user_id=? AND module_id=?");
$check->bind_param("ii", $user_id, $module_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows == 0) {
    $insert = $conn->prepare("INSERT INTO certificates (user_id, module_id, completion_date) VALUES (?, ?, NOW())");
    $insert->bind_param("ii", $user_id, $module_id);
    $insert->execute();
}

header("Location: certificate.php?module_id=$module_id");
exit;
?>
