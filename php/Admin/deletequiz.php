<?php
// Include database connection
require_once '../connection.php'; 

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $quiz_id = intval($_GET['id']);

    $stmt = $conn->prepare("DELETE FROM module_quizzes WHERE quiz_id = ?");
    $stmt->bind_param("i", $quiz_id);

    if ($stmt->execute()) {
        header("Location: adminpage.php?error=Quiz Deleted Successfully#quiz-management");
        exit();
    } else {
        header("Location: adminpage.php?error=Invalid quiz id#quiz-management");
        exit();
    }

    $stmt->close();
} else {
    header("Location: index.php");
    exit();
}
?>