<?php
include 'php/connection.php';

$resets = [
    'testuser' => 'password',
    'testagri' => 'password',
];

foreach ($resets as $username => $plainPassword) {
    $new_hash = password_hash($plainPassword, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
    $stmt->bind_param("ss", $new_hash, $username);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    if ($affected > 0) {
        echo "✅ <strong>$username</strong> — password reset to <code>$plainPassword</code><br>";
    } else {
        echo "⚠️ <strong>$username</strong> — not found or already set<br>";
    }
}

echo "<br><a href='php/login.php'>Go to Login →</a>";
?>
