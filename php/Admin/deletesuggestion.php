<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}
include('../connection.php');

// Delete ALL suggestions
if (isset($_GET['clear_all'])) {
    $conn->query("DELETE FROM suggestions");
    header("Location: adminpage.php?success=All+suggestions+deleted#suggestions");
    exit();
}

// Delete single suggestion
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM suggestions WHERE suggestion_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: adminpage.php?success=Suggestion+deleted#suggestions");
    exit();
}

header("Location: adminpage.php#suggestions");
exit();
?>
