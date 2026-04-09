<?php
require_once __DIR__ . '/notifications_bootstrap.php';

function create_notification(mysqli $conn, int $userId, string $type, string $message, ?string $link = null): void
{
    if ($userId <= 0 || $message === '') {
        return;
    }

    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, type, message, link)
        VALUES (?, ?, ?, ?)
    ");
    if (!$stmt) {
        return;
    }
    $stmt->bind_param("isss", $userId, $type, $message, $link);
    $stmt->execute();
    $stmt->close();
}
