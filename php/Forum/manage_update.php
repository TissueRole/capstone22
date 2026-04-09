<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if (!in_array($_SESSION['role'] ?? '', ['agriculturist', 'admin'], true)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include "../connection.php";
include "community_updates_bootstrap.php";
include "moderation_helpers.php";
include "update_image_helper.php";

$userId = (int) $_SESSION['user_id'];
$updateId = (int) ($_POST['update_id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $updateId <= 0 || $action !== 'update') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$ownerStmt = $conn->prepare("SELECT update_id FROM community_updates WHERE update_id = ? AND user_id = ?");
$ownerStmt->bind_param("ii", $updateId, $userId);
$ownerStmt->execute();
$owned = $ownerStmt->get_result()->fetch_assoc();
$ownerStmt->close();

if (!$owned) {
    echo json_encode(['success' => false, 'message' => 'You can only edit your own updates.']);
    exit;
}

$title = trim($_POST['title'] ?? '');
$body = trim($_POST['body'] ?? '');
$imageUrl = trim($_POST['image_url'] ?? '');
$externalUrl = trim($_POST['external_url'] ?? '');
$isPinned = isset($_POST['is_pinned']) ? 1 : 0;

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
    echo json_encode(['success' => false, 'message' => 'Image URL must be valid.']);
    exit;
}

if ($externalUrl !== '' && !filter_var($externalUrl, FILTER_VALIDATE_URL)) {
    echo json_encode(['success' => false, 'message' => 'External link must be valid.']);
    exit;
}

$currentStmt = $conn->prepare("SELECT image_url FROM community_updates WHERE update_id = ? AND user_id = ?");
$currentStmt->bind_param("ii", $updateId, $userId);
$currentStmt->execute();
$currentRow = $currentStmt->get_result()->fetch_assoc();
$currentStmt->close();

$uploadResult = save_community_update_image($_FILES['image_file'] ?? [], $currentRow['image_url'] ?? null);
if (!$uploadResult['success']) {
    echo json_encode(['success' => false, 'message' => $uploadResult['message']]);
    exit;
}

if (!empty($uploadResult['path'])) {
    $imageUrl = $uploadResult['path'];
} elseif ($imageUrl === '') {
    $imageUrl = $currentRow['image_url'] ?? '';
}

$stmt = $conn->prepare("
    UPDATE community_updates
    SET title = ?, body = ?, image_url = ?, external_url = ?, is_pinned = ?
    WHERE update_id = ? AND user_id = ?
");
$stmt->bind_param("ssssiii", $title, $body, $imageUrl, $externalUrl, $isPinned, $updateId, $userId);
$success = $stmt->execute();
$stmt->close();

echo json_encode([
    'success' => $success,
    'message' => $success ? 'Community update saved.' : 'Failed to save update.'
]);
