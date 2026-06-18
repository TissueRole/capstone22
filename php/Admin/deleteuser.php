<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

include('../connection.php');

// Check if user ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: adminpage.php?error=No user ID provided");
    exit();
}

$user_id = intval($_GET['id']);

// Prevent admin from deleting themselves
if ($user_id == $_SESSION['user_id']) {
    header("Location: adminpage.php?error=You cannot delete your own account");
    exit();
}

// Check if the user exists
$stmt = $conn->prepare("SELECT username, role FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {

    $user = $result->fetch_assoc();

    // Start transaction
    $conn->begin_transaction();

    try {

        // Delete user's replies
        $deleteReplies = $conn->prepare("DELETE FROM reply WHERE user_id = ?");
        $deleteReplies->bind_param("i", $user_id);
        $deleteReplies->execute();
        $deleteReplies->close();

        // Delete user's questions
        $deleteQuestions = $conn->prepare("DELETE FROM questions WHERE user_id = ?");
        $deleteQuestions->bind_param("i", $user_id);
        $deleteQuestions->execute();
        $deleteQuestions->close();

        // Delete user's lesson progress
        $deleteProgress = $conn->prepare("DELETE FROM lesson_progress WHERE user_id = ?");
        $deleteProgress->bind_param("i", $user_id);
        $deleteProgress->execute();
        $deleteProgress->close();

        // Delete user's certificates
        $deleteCertificates = $conn->prepare("DELETE FROM certificates WHERE user_id = ?");
        $deleteCertificates->bind_param("i", $user_id);
        $deleteCertificates->execute();
        $deleteCertificates->close();

        // Delete the user
        $deleteUser = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $deleteUser->bind_param("i", $user_id);
        $deleteUser->execute();
        $deleteUser->close();

        // Commit transaction
        $conn->commit();

        header("Location: adminpage.php?success=User '" . urlencode($user['username']) . "' deleted successfully");
        exit();

    } catch (Exception $e) {

        // Rollback if anything fails
        $conn->rollback();

        header("Location: adminpage.php?error=" . urlencode("Failed to delete user: " . $e->getMessage()));
        exit();
    }

} else {

    header("Location: adminpage.php?error=User not found");
    exit();
}

$stmt->close();
$conn->close();
?>