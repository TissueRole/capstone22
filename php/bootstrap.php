<?php
$projectSessionPath = __DIR__ . '/../tmp/sessions';

if (!is_dir($projectSessionPath)) {
    @mkdir($projectSessionPath, 0777, true);
}

if (is_dir($projectSessionPath) && is_writable($projectSessionPath)) {
    ini_set('session.save_path', $projectSessionPath);
}
?>
