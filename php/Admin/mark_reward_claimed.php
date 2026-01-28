<?php
session_start();
include('../connection.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if (!isset($_POST['reward_id'])) {
    header("Location: adminpage.php");
    exit();
}

$reward_id = (int)$_POST['reward_id'];

$stmt = $conn->prepare("
    UPDATE user_rewards
    SET status = 'claimed',
        claimed_at = NOW()
    WHERE reward_id = ?
      AND status = 'unclaimed'
");
$stmt->bind_param("i", $reward_id);
$stmt->execute();

header("Location: adminpage.php#reward-management");
exit();
