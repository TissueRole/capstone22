<?php
require '../../connection.php';
require_once __DIR__ . '../../../../vendor/autoload.php';
session_start();

if (!isset($_SESSION['user_id'])) die("Not logged in");

$user_id = $_SESSION['user_id'];
$module_id = intval($_GET['module_id'] ?? 0);

// Fetch student
$user = $conn->query("SELECT name FROM users WHERE user_id=$user_id")->fetch_assoc();
if (!$user) die("User not found.");

// Fetch module
$module = $conn->query("SELECT title FROM modules WHERE module_id=$module_id")->fetch_assoc();
if (!$module) die("Module not found.");

// Fetch date
$cert = $conn->query("SELECT completion_date FROM certificates WHERE user_id=$user_id AND module_id=$module_id")->fetch_assoc();
$completion_date = $cert['completion_date'] ?? date('F j, Y');

// Background absolute path
$bg = __DIR__ . '/cert_template/Certificate.jpg';

// ========= MPDF SETUP =========
$mpdf = new \Mpdf\Mpdf([
    'format' => 'A4-L',  // Landscape
    'margin_left' => 0,
    'margin_right' => 0,
    'margin_top' => 0,
    'margin_bottom' => 0
]);

// Set full background image
$mpdf->SetWatermarkImage($bg, 1.0); 
$mpdf->showWatermarkImage = true;

// ========= HTML CONTENT =========
$html = '
<style>
body{
    font-family:"Times New Roman", serif;
}
.student-name{
    position:absolute;
    top:330px;
    width:100%;
    text-align:center;
    font-size:52px;
    font-weight:bold;
}
.module-name{
    position:absolute;
    top:420px;
    width:100%;
    text-align:center;
    font-size:34px;
    font-style:italic;
}
.date{
    position:absolute;
    top:520px;
    width:100%;
    text-align:center;
    font-size:22px;
}
</style>

<div class="student-name">'.htmlspecialchars($user['name']).'</div>
<div class="module-name">'.htmlspecialchars($module['title']).'</div>
<div class="date">Completed on '.$completion_date.'</div>
';

$mpdf->WriteHTML($html);
$mpdf->Output("Certificate_{$module['title']}.pdf","D");
