<?php

function save_community_update_image(array $file, ?string $existingPath = null): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'path' => $existingPath];
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Image upload failed.'];
    }

    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        return ['success' => false, 'message' => 'Uploaded image must be 2MB or smaller.'];
    }

    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    $mimeType = mime_content_type($file['tmp_name']);
    if (!isset($allowedTypes[$mimeType])) {
        return ['success' => false, 'message' => 'Only JPG, PNG, GIF, or WEBP images are allowed.'];
    }

    $uploadDir = dirname(__DIR__, 2) . '/images/community_updates';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
        return ['success' => false, 'message' => 'Unable to prepare the upload folder.'];
    }

    $filename = 'update_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $allowedTypes[$mimeType];
    $destination = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'message' => 'Unable to save the uploaded image.'];
    }

    return ['success' => true, 'path' => '../../images/community_updates/' . $filename];
}
