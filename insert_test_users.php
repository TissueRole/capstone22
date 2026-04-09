<?php
include 'php/connection.php';

$password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'; // password: "password"

$users = [
    ['Test User',         'testuser',  $password_hash, 'new user',      'active'],
    ['Test Admin',        'testadmin', $password_hash, 'admin',         'active'],
    ['Test Agriculturist','testagri',  $password_hash, 'agriculturist', 'active'],
];

$inserted = [];
$skipped  = [];

foreach ($users as [$name, $username, $pw, $role, $status]) {
    $check = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $skipped[] = $username . " (already exists)";
        $check->close();
        continue;
    }
    $check->close();

    $stmt = $conn->prepare("INSERT INTO users (name, username, password, role, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $username, $pw, $role, $status);
    if ($stmt->execute()) {
        $inserted[] = $username . " (role: $role)";
    }
    $stmt->close();
}

echo "<h2>✅ Done</h2>";
if ($inserted) echo "<p><strong>Inserted:</strong> " . implode(', ', $inserted) . "</p>";
if ($skipped)  echo "<p><strong>Skipped:</strong> " . implode(', ', $skipped) . "</p>";
echo "<hr><p>All 3 accounts use password: <code>password</code></p>";
echo "<p><a href='php/login.php'>Go to Login</a></p>";

// Self-delete this script after running for security
// Uncomment the line below after testing if you want auto-cleanup:
// unlink(__FILE__);
?>
