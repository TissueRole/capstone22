<?php
session_start();

include('../connection.php');

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: adminpage.php?error=Invalid module id#module-management");
    exit();
}

$moduleId = (int) $_GET['id'];

try {
    $conn->begin_transaction();

    $deleteLessonProgress = $conn->prepare("
        DELETE lp
        FROM lesson_progress lp
        INNER JOIN lessons l ON l.lesson_id = lp.lesson_id
        WHERE l.module_id = ?
    ");
    $deleteLessonProgress->bind_param("i", $moduleId);
    $deleteLessonProgress->execute();
    $deleteLessonProgress->close();

    $deleteCertificates = $conn->prepare("DELETE FROM certificates WHERE module_id = ?");
    $deleteCertificates->bind_param("i", $moduleId);
    $deleteCertificates->execute();
    $deleteCertificates->close();

    $deleteRewards = $conn->prepare("DELETE FROM user_rewards WHERE module_id = ?");
    $deleteRewards->bind_param("i", $moduleId);
    $deleteRewards->execute();
    $deleteRewards->close();

    $deleteLessons = $conn->prepare("DELETE FROM lessons WHERE module_id = ?");
    $deleteLessons->bind_param("i", $moduleId);
    $deleteLessons->execute();
    $deleteLessons->close();

    $deleteQuizResults = $conn->prepare("
        DELETE qr
        FROM quiz_results qr
        INNER JOIN module_quizzes mq ON mq.quiz_id = qr.quiz_id
        WHERE mq.module_id = ?
    ");
    $deleteQuizResults->bind_param("i", $moduleId);
    $deleteQuizResults->execute();
    $deleteQuizResults->close();

    $deleteQuizzes = $conn->prepare("DELETE FROM module_quizzes WHERE module_id = ?");
    $deleteQuizzes->bind_param("i", $moduleId);
    $deleteQuizzes->execute();
    $deleteQuizzes->close();

    $deleteModule = $conn->prepare("DELETE FROM modules WHERE module_id = ?");
    $deleteModule->bind_param("i", $moduleId);
    $deleteModule->execute();

    if ($deleteModule->affected_rows !== 1) {
        throw new RuntimeException('Module not found or already deleted.');
    }

    $deleteModule->close();
    $conn->commit();

    header("Location: adminpage.php?success=Module deleted successfully#module-management");
    exit();
} catch (Throwable $e) {
    $conn->rollback();
    header("Location: adminpage.php?error=" . urlencode($e->getMessage()) . "#module-management");
    exit();
} finally {
    $conn->close();
}
?>
