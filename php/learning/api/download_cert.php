<?php
require '../../connection.php';
require_once __DIR__ . '/../../../vendor/autoload.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}

$user_id   = (int) $_SESSION['user_id'];
$module_id = (int) ($_GET['module_id'] ?? 0);


$user = $conn->query("SELECT name FROM users WHERE user_id = $user_id")->fetch_assoc();
if (!$user) die("User not found");

$module = $conn->query("SELECT title FROM modules WHERE module_id = $module_id")->fetch_assoc();
if (!$module) die("Module not found");

// ✅ Keep the title as-is but remove any unwanted line breaks from database
$module_title = trim($module['title']);
$module_title = preg_replace('/[\r\n]+/', ' ', $module_title); // Remove hard line breaks from DB

$cert = $conn->query("
    SELECT completion_date
    FROM certificates
    WHERE user_id = $user_id AND module_id = $module_id
")->fetch_assoc();

$completion_date = !empty($cert['completion_date'])
    ? date('F d, Y', strtotime($cert['completion_date']))
    : date('F d, Y');

$bg_path = realpath(__DIR__ . '/cert_template/Certificate.jpg');
if (!$bg_path) die("Certificate background not found");


$fontDir = __DIR__ . '/fonts';

$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4-L',
    'margin_left' => 0,
    'margin_right' => 0,
    'margin_top' => 0,
    'margin_bottom' => 0,
    'autoPageBreak' => false,
    'fontDir' => [$fontDir],
    'fontdata' => [
        'cormorant' => [
            'R' => 'CormorantGaramond-Regular.ttf',
            'B' => 'CormorantGaramond-Bold.ttf',
        ],
        'playfair' => [
            'R' => 'PlayfairDisplay-Regular.ttf',
            'B' => 'PlayfairDisplay-Bold.ttf',
        ],
    ],
    'default_font' => 'cormorant'
]);

$mpdf->SetDefaultBodyCSS('background', "url('$bg_path')");
$mpdf->SetDefaultBodyCSS('background-image-resize', 6);


$html = '
<!DOCTYPE html>
<html>
<head>
<style>
body {
    margin: 0;
    padding: 0;
}

/* Student Name */
.student-name {
    position: absolute;
    top: 78mm;
    left: 0;
    right: 0;
    text-align: center;
    font-family: cormorant;
    font-size: 52px;
    font-weight: bold;
    font-style: italic;
}

/* Module Name */
.module-name {
    position: absolute;
    top: 108mm;
    left: 80mm;
    right: 80mm;
    text-align: center;
    font-family: playfair;
    font-size: 34px;
    font-style: italic;
    line-height: 1.4;
    word-wrap: break-word;
}

/* Date */
.completion-date {
    position: absolute;
    top: 187.5mm;
    left: 0;
    right: 0;
    text-align: center;
    font-size: 18px;
}
</style>
</head>

<body>
    <div class="student-name">' . htmlspecialchars($user['name']) . '</div>
    <div class="module-name">' . htmlspecialchars($module_title) . '</div>
    <div class="completion-date">Completed on: ' . $completion_date . '</div>
</body>
</html>
';

$mpdf->WriteHTML($html);

$filename = 'Certificate_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $module_title) . '.pdf';
$mpdf->Output($filename, 'D');