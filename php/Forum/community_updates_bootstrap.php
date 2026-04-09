<?php
if (!isset($conn) || !($conn instanceof mysqli)) {
    return;
}

$conn->query("
    CREATE TABLE IF NOT EXISTS community_updates (
        update_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        body TEXT NOT NULL,
        image_url VARCHAR(500) DEFAULT NULL,
        external_url VARCHAR(500) DEFAULT NULL,
        is_pinned TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_community_updates_created_at (created_at),
        INDEX idx_community_updates_user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");

$hasPinnedColumn = $conn->query("SHOW COLUMNS FROM community_updates LIKE 'is_pinned'");
if ($hasPinnedColumn && $hasPinnedColumn->num_rows === 0) {
    $conn->query("ALTER TABLE community_updates ADD COLUMN is_pinned TINYINT(1) NOT NULL DEFAULT 0 AFTER external_url");
}
