<?php
require '../../connection.php';
session_start();

if (!isset($_SESSION['user_id'], $_POST['module_id'])) {
    http_response_code(400);
    exit('Invalid request');
}

$user_id = (int)$_SESSION['user_id'];
$module_id = (int)$_POST['module_id'];

// Get reward text
$stmt = $conn->prepare("SELECT rewards FROM modules WHERE module_id = ?");
$stmt->bind_param("i", $module_id);
$stmt->execute();
$module = $stmt->get_result()->fetch_assoc();

if (!$module || empty($module['rewards'])) {
    exit('No reward for this module');
}

// Insert reward only once
$stmt = $conn->prepare("
    INSERT IGNORE INTO user_rewards (user_id, module_id, reward_text)
    VALUES (?, ?, ?)
");
$stmt->bind_param("iis", $user_id, $module_id, $module['rewards']);
$stmt->execute();

echo 'Reward unlocked';
