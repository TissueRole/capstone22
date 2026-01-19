<?php
require '../../connection.php';
session_start();

if (!isset($_SESSION['user_id'])) die("Not logged in");

$user_id = $_SESSION['user_id'];
$module_id = intval($_GET['module_id'] ?? 0);

// Get the referrer to determine where user came from
$from = $_GET['from'] ?? 'quiz'; // default to 'quiz'

// Fetch student
$user = $conn->query("SELECT name FROM users WHERE user_id=$user_id")->fetch_assoc();
if (!$user) die("❗User not found in database.");

// Fetch module
$module = $conn->query("SELECT title FROM modules WHERE module_id=$module_id")->fetch_assoc();
if (!$module) die("❗Module not found or invalid module_id: ".$module_id);

// Fetch certificate date
$cert = $conn->query("
    SELECT completion_date FROM certificates 
    WHERE user_id=$user_id AND module_id=$module_id
")->fetch_assoc();

// Determine close URL based on where they came from
if ($from === 'profile' || $from === 'userpage') {
    $closeUrl = "../../userpage.php#certificates";
} else {
    $closeUrl = "../../learning-platform.php?module=$module_id";
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Certificate</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;700&family=Playfair+Display:wght@500;700&family=Merriweather:wght@300;400&display=swap" rel="stylesheet">
<style>
   body {
    font-family: Arial;
    text-align: center;
    background: #f0f0f0;
    }

    .modal {
        display: block;
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0,0,0,0.7);
        z-index: 999;
        pointer-events: none; /* allow clicks to pass through */
    }

    .modal-content {
        position: relative;
        margin: auto;
        margin-top: 40px;
        width: 80%;
        max-width: 950px;
        background: white;
        border-radius: 10px;
        overflow: hidden;
        animation: fade 0.4s ease;
        pointer-events: all; /* modal-content still clickable */
    }

    @keyframes fade { from {opacity:0;} to {opacity:1;} }

    .cert-wrapper {
        position: relative;
        background: url('cert_template/Certificate.jpg') no-repeat center;
        background-size: cover;
        width: 100%;
        height: 700px; 
    }

    .student-name {
        position: absolute;
        top: 41.25%;
        font-family: "Cormorant Garamond", serif;     
        left: 50%;       
         font-style: italic;
        transform: translate(-50%, -50%);
        font-size: 52px;
        font-weight: bold;
        white-space: nowrap;
    }

    .module-name {
        position: absolute;
        font-family: "Playfair Display", serif;
        top: 52%;       
        left: 50%;
        transform: translateX(-50%);
        font-size: 34px;
        font-style: italic;
    }

    .bottom {
        background: #fff;
        padding: 15px;
        text-align: center;
    }

    .btn {
        background: #0066ff;
        color: white;
        padding: 12px 22px;
        border-radius: 5px;
        text-decoration: none;
        font-size: 17px;
        margin: 8px;
        display: inline-block;
    }
</style>

</head>
<body>

<div class="modal">
    <div class="modal-content">
        <div class="cert-wrapper">
            <div class="student-name"><?= htmlspecialchars($user['name']) ?></div>
            <div class="module-name"><?= htmlspecialchars($module['title']) ?></div>
        </div>
    </div>
</div>

<!-- ========= BUTTONS OUTSIDE MODAL ========= -->
<div class="bottom-buttons" style="
    position: fixed; 
    bottom: 20px; 
    left: 50%; 
    transform: translateX(-50%);
    z-index: 1001; /* higher than modal overlay */
    text-align: center;
">
    <a href="download_cert.php?module_id=<?= $module_id ?>" class="btn">
        📄 Download Certificate (PDF)
    </a>
    <button onclick="window.close()" class="btn" style="background:#444; border:none; cursor:pointer;">
        Close
    </button>
</div>


</body>
</html>