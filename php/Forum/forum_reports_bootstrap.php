<?php
if (!isset($conn) || !($conn instanceof mysqli)) {
    return;
}

$conn->query("
    CREATE TABLE IF NOT EXISTS forum_reports (
        report_id INT AUTO_INCREMENT PRIMARY KEY,
        reporter_user_id INT NOT NULL,
        target_type ENUM('question', 'reply') NOT NULL,
        target_id INT NOT NULL,
        reason VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_forum_reports_target (target_type, target_id),
        INDEX idx_forum_reports_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");
