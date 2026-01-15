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
if (!$user) {
    die("User not found");
}


$module = $conn->query("SELECT title FROM modules WHERE module_id = $module_id")->fetch_assoc();
if (!$module) {
    die("Module not found");
}


$cert = $conn->query("
    SELECT completion_date
    FROM certificates
    WHERE user_id = $user_id AND module_id = $module_id
")->fetch_assoc();

if (!empty($cert['completion_date'])) {
    $completion_date = date('F d, Y', strtotime($cert['completion_date']));
} else {
    $completion_date = date('F d, Y');
}


$bg_path = realpath(__DIR__ . '/cert_template/Certificate.jpg');
if (!$bg_path) {
    die("Certificate background not found");
}


$mpdf = new \Mpdf\Mpdf([
    'format'        => 'A4-L',
    'margin_left'   => 0,
    'margin_right'  => 0,
    'margin_top'    => 0,
    'margin_bottom' => 0,
    'autoPageBreak' => false
]);


$mpdf->SetDefaultBodyCSS('background', "url('$bg_path')");
$mpdf->SetDefaultBodyCSS('background-image-resize', 6); // FIT PAGE


$html = '
<!DOCTYPE html>
<html>
<head>
<style>
body {
    margin: 0;
    padding: 0;
    position: relative;
    font-family: "Times New Roman", serif;
}

/* Student name */
.student-name {
    position: absolute;
    top: 78mm;
    left: 0;
    right: 0;
    text-align: center;
    font-size: 52px;
    font-weight: bold;
    font-style: italic;
}

/* Module title */
.module-name {
    position: absolute;
    top: 108mm;
    left: 0;
    right: 0;
    text-align: center;
    font-size: 34px;
    font-style: italic;
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
    <div class="module-name">' . htmlspecialchars($module['title']) . '</div>
    <div class="completion-date">Completed on: ' . $completion_date . '</div>
</body>
</html>
';

$mpdf->WriteHTML($html);

$filename = 'Certificate_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $module['title']) . '.pdf';
$mpdf->Output($filename, 'D');
