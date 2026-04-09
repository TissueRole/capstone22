<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if (!in_array($_SESSION['role'] ?? '', ['agriculturist', 'admin'], true)) {
    echo json_encode(['success' => false, 'message' => 'Only agriculturists and admins can publish updates.']);
    exit;
}

include "../connection.php";
include "community_updates_bootstrap.php";
include "moderation_helpers.php";
include "update_image_helper.php";

$title = trim($_POST['title'] ?? '');
$body = trim($_POST['body'] ?? '');
$imageUrl = trim($_POST['image_url'] ?? '');
$externalUrl = trim($_POST['external_url'] ?? '');
$isPinned = isset($_POST['is_pinned']) ? 1 : 0;
$userId = (int) $_SESSION['user_id'];

if ($title === '' || $body === '') {
    echo json_encode(['success' => false, 'message' => 'Title and content are required.']);
    exit;
}

$moderationError = forum_validate_clean_text([
    'title' => $title,
    'content' => $body,
]);
if ($moderationError !== null) {
    echo json_encode(['success' => false, 'message' => $moderationError]);
    exit;
}

if ($imageUrl !== '' && !filter_var($imageUrl, FILTER_VALIDATE_URL) && strpos($imageUrl, '../../images/community_updates/') !== 0) {
    echo json_encode(['success' => false, 'message' => 'Image URL must be a valid link.']);
    exit;
}

if ($externalUrl !== '' && !filter_var($externalUrl, FILTER_VALIDATE_URL)) {
    echo json_encode(['success' => false, 'message' => 'External link must be a valid URL.']);
    exit;
}

$uploadResult = save_community_update_image($_FILES['image_file'] ?? []);
if (!$uploadResult['success']) {
    echo json_encode(['success' => false, 'message' => $uploadResult['message']]);
    exit;
}

if (!empty($uploadResult['path'])) {
    $imageUrl = $uploadResult['path'];
}

$stmt = $conn->prepare("
    INSERT INTO community_updates (user_id, title, body, image_url, external_url, is_pinned)
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->bind_param("issssi", $userId, $title, $body, $imageUrl, $externalUrl, $isPinned);
$success = $stmt->execute();
$stmt->close();

echo json_encode([
    'success' => $success,
    'message' => $success ? 'Community update published.' : 'Failed to publish update.'
]);
