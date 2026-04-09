<?php
if (!isset($conn) || !($conn instanceof mysqli)) {
    return;
}

$bestReplyColumn = $conn->query("SHOW COLUMNS FROM questions LIKE 'best_reply_id'");
if ($bestReplyColumn && $bestReplyColumn->num_rows === 0) {
    $conn->query("ALTER TABLE questions ADD COLUMN best_reply_id INT DEFAULT NULL AFTER status");
    $conn->query("ALTER TABLE questions ADD INDEX idx_questions_best_reply_id (best_reply_id)");
}
