<?php
session_start();
include "../connection.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$title = $_POST['title'];
$body = $_POST['body'];
$user_id = $_SESSION['user_id'];

// Default new question to "pending"
$stmt = $conn->prepare("INSERT INTO questions (user_id, title, body, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
$stmt->bind_param("iss", $user_id, $title, $body);

if ($stmt->execute()) {
    echo "<script>
        alert('Your question has been submitted for admin approval.');
        window.location.href = 'community.php';
    </script>";
} else {
    echo "Error: " . $conn->error;
}
$stmt->close();
$conn->close();
?>
