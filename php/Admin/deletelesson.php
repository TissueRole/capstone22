<?php
session_start();

include('../connection.php');

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: adminpage.php?error=Invalid lesson id#lesson-management");
    exit();
}

$lessonId = (int)$_GET['id'];

try {
    $conn->begin_transaction();

    // Delete lesson progress
    $deleteLessonProgress = $conn->prepare("DELETE FROM lesson_progress WHERE lesson_id = ?");
    $deleteLessonProgress->bind_param("i", $lessonId);
    $deleteLessonProgress->execute();
    $deleteLessonProgress->close();

    // Delete the lesson
    $deleteLesson = $conn->prepare("DELETE FROM lessons WHERE lesson_id = ?");
    $deleteLesson->bind_param("i", $lessonId);
    $deleteLesson->execute();

    if ($deleteLesson->affected_rows !== 1) {
        throw new RuntimeException("Lesson not found or already deleted.");
    }

    $deleteLesson->close();

    $conn->commit();

    header("Location: adminpage.php?success=Lesson deleted successfully#lesson-management");
    exit();

} catch (Throwable $e) {
    $conn->rollback();
    header("Location: adminpage.php?error=" . urlencode($e->getMessage()) . "#lesson-management");
    exit();

} finally {
    $conn->close();
}