<?php
require '../../connection.php';
require_once __DIR__ . '/../../../vendor/autoload.php';
session_start();

if (!isset($_SESSION['user_id'])) die("Not logged in");

$user_id = intval($_SESSION['user_id']);
$module_id = intval($_GET['module_id'] ?? 0);

// Fetch student
$user = $conn->query("SELECT name FROM users WHERE user_id=$user_id")->fetch_assoc();
if (!$user) die("User not found.");

// Fetch module
$module = $conn->query("SELECT title FROM modules WHERE module_id=$module_id")->fetch_assoc();
if (!$module) die("Module not found.");

// Fetch certificate date
$cert = $conn->query("
    SELECT completion_date 
    FROM certificates 
    WHERE user_id=$user_id AND module_id=$module_id
")->fetch_assoc();

$completion_date = $cert['completion_date'] ?? date('F j, Y');

// Background absolute path
$bg_path = realpath(__DIR__ . '/cert_template/Certificate.jpg');

if (!$bg_path) {
    die("Background image not found.");
}

$html = '
<html>
<head>
<style>
body {
    margin: 0;
    padding: 0;
}

.certificate {
    background-image: url("' . $bg_path . '");
    background-size: cover;
    width: 100%;
    height: 100%;
    position: relative;
}

.student-name {
    position: absolute;
    top: 40%;
    width: 100%;
    text-align: center;
    font-size: 52px;
    font-weight: bold;
    font-family: "Times New Roman", serif;
    font-style: italic;
}

.module-name {
    position: absolute;
    top: 52%;
    width: 100%;
    text-align: center;
    font-size: 34px;
    font-style: italic;
    font-family: "Times New Roman", serif;
}

.completion-date {
    position: absolute;
    bottom: 40px;
    width: 100%;
    text-align: center;
    font-size: 18px;
    font-family: "Times New Roman", serif;
}
</style>
</head>

<body>
    <div class="certificate">
        <div class="student-name">' . htmlspecialchars($user['name']) . '</div>
        <div class="module-name">' . htmlspecialchars($module['title']) . '</div>
        <div class="completion-date">Completed on: ' . $completion_date . '</div>
    </div>
</body>
</html>
';

// Create PDF
$mpdf = new \Mpdf\Mpdf([
    'format' => 'A4-L', // A4 landscape
    'margin_left' => 0,
    'margin_right' => 0,
    'margin_top' => 0,
    'margin_bottom' => 0
]);

$mpdf->WriteHTML($html);
$mpdf->Output('Certificate_' . $module['title'] . '.pdf', 'D');

