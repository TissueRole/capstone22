<?php
session_start();
header('Content-Type: application/json'); // ensure JSON output

include "../connection.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $body = trim($_POST['body'] ?? '');
    $user_id = $_SESSION['user_id'];

    if ($title === '' || $body === '') {
        echo json_encode(['success' => false, 'message' => 'Title and body are required']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO questions (user_id, title, body, status) VALUES (?, ?, ?, 'pending')");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param("iss", $user_id, $title, $body);
    $success = $stmt->execute();

    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to insert record']);
    }
    exit;
}

// fallback
echo json_encode(['success' => false, 'message' => 'Invalid request method']);
?>
