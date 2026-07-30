<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

$error_message = '';
$success_message = '';

if (
    !isset($_SESSION['verification_user_id']) ||
    !isset($_SESSION['verification_email'])
) {

    header("Location: signup.php");
    exit();
}

$user_id = $_SESSION['verification_user_id'];
$email = $_SESSION['verification_email'];

include('connection.php');

$stmt = $conn->prepare(
    "SELECT user_id, name, email, email_verified,
            verification_code, verification_expires
     FROM users
     WHERE user_id = ?
     LIMIT 1"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {

    $stmt->close();
    $conn->close();

    session_unset();
    session_destroy();

    header("Location: signup.php");
    exit();
}

$user = $result->fetch_assoc();

$stmt->close();

if ((int)$user['email_verified'] === 1) {

    unset($_SESSION['verification_user_id']);
    unset($_SESSION['verification_email']);

    $conn->close();

    header("Location: login.php");
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? 'verify';

    if ($action === 'verify') {

        $entered_code = trim($_POST['verification_code'] ?? '');

        if (!preg_match('/^[0-9]{6}$/', $entered_code)) {

            $error_message =
                "Please enter the 6-digit verification code.";

        } else {

            if (empty($user['verification_code'])) {

                $error_message =
                    "No verification code is available. "
                    . "Please request a new code.";

            } elseif (
                empty($user['verification_expires']) ||
                strtotime($user['verification_expires']) < time()
            ) {

                $error_message =
                    "Your verification code has expired. "
                    . "Please request a new code.";

            } elseif (
                !hash_equals(
                    (string)$user['verification_code'],
                    (string)$entered_code
                )
            ) {

                $error_message =
                    "Incorrect verification code. "
                    . "Please try again.";

            } else {

                $update = $conn->prepare(
                    "UPDATE users
                     SET email_verified = 1,
                         verification_code = NULL,
                         verification_expires = NULL
                     WHERE user_id = ?"
                );

                $update->bind_param("i", $user_id);

                if ($update->execute()) {

                    $update->close();
                    $conn->close();

                    unset($_SESSION['verification_user_id']);
                    unset($_SESSION['verification_email']);

                    $_SESSION['verification_success'] =
                        "Your email has been successfully verified.";

                    header("Location: login.php");
                    exit();

                } else {

                    $error_message =
                        "Unable to verify your account. "
                        . "Please try again.";

                    $update->close();
                }
            }
        }
    }

    elseif ($action === 'resend') {

        $new_code = str_pad(
            random_int(0, 999999),
            6,
            '0',
            STR_PAD_LEFT
        );

        $new_expiration = date(
            'Y-m-d H:i:s',
            time() + (10 * 60)
        );

        $mail = new PHPMailer(true);

        try {

            $mail->isSMTP();

            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'sdfasdasfdas@gmail.com';
            $mail->Password = 'schggdylrqxwarsz';

            $mail->SMTPSecure =
                PHPMailer::ENCRYPTION_STARTTLS;

            $mail->Port = 587;
            $mail->setFrom(
                'sdfasdasfdas@gmail.com',
                'TEEN-ANIM'
            );
            $mail->addAddress(
                $user['email'],
                $user['name']
            );
            $mail->isHTML(true);

            $mail->Subject =
                'TEEN-ANIM New Verification Code';


            $mail->Body = '

            <div style="
                font-family: Arial, sans-serif;
                max-width: 600px;
                margin: auto;
                padding: 30px;
                border: 1px solid #ddd;
                border-radius: 12px;
                background: #ffffff;
            ">

                <h2 style="
                    color: #4caf50;
                    margin-bottom: 20px;
                ">
                    TEEN-ANIM
                </h2>

                <h3>
                    Your New Verification Code
                </h3>

                <p>
                    Hello
                    <strong>' .
                    htmlspecialchars($user['name']) .
                    '</strong>,
                </p>

                <p>
                    You requested a new verification code
                    for your TEEN-ANIM account.
                </p>

                <div style="
                    text-align: center;
                    margin: 30px 0;
                ">

                    <div style="
                        display: inline-block;
                        padding: 15px 25px;
                        background: #f1f8e9;
                        border-radius: 8px;
                        font-size: 32px;
                        font-weight: bold;
                        letter-spacing: 8px;
                        color: #4caf50;
                    ">
                        ' . $new_code . '
                    </div>

                </div>

                <p>
                    This code will expire in
                    <strong>10 minutes</strong>.
                </p>

                <p>
                    If you did not request this code,
                    you may safely ignore this email.
                </p>

                <hr>

                <p style="
                    font-size: 12px;
                    color: #777;
                ">
                    TEEN-ANIM<br>
                    Urban Farming Learning and
                    Simulation System
                </p>

            </div>
            ';
            $mail->AltBody =
                "Your new TEEN-ANIM verification code is: "
                . $new_code;
            $mail->send();
            $update = $conn->prepare(
                "UPDATE users
                 SET verification_code = ?,
                     verification_expires = ?
                 WHERE user_id = ?"
            );
            $update->bind_param(
                "ssi",
                $new_code,
                $new_expiration,
                $user_id
            );
            if ($update->execute()) {

                $success_message =
                    "A new verification code has been "
                    . "sent to your email.";
                $user['verification_code'] = $new_code;
                $user['verification_expires'] =
                    $new_expiration;
            } else {
                $error_message =
                    "The email was sent, but the verification "
                    . "code could not be saved.";
            }
            $update->close();
        } catch (Exception $e) {

            $error_message =
             "Email error: " . $mail->ErrorInfo;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>
<title>Verify Email - TEEN-ANIM</title>
<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
>
<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css"
>
<link
    href="../css/signup.css"
    rel="stylesheet"
>
<style>

    .verification-card {
        width: 100%;
        max-width: 500px;
        margin: 0 auto;

        padding: 35px;

        background: #ffffff;

        border-radius: 20px;

        box-shadow:
            0 12px 35px rgba(31, 38, 135, 0.12),
            0 3px 10px rgba(0, 0, 0, 0.04);
    }
    .verification-icon {
        width: 70px;
        height: 70px;
        margin: 0 auto 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #e9f6ea;
        color: #4caf50;
        font-size: 32px;
    }
    .verification-title {
        font-size: 1.6rem;
        font-weight: 700;
        text-align: center;
        margin-bottom: 10px;
    }
    .verification-description {
        text-align: center;
        color: #777;
        font-size: 0.9rem;
        line-height: 1.6;
        margin-bottom: 25px;
    }
    .verification-email {
        display: block;
        color: #4caf50;
        font-weight: 600;
        word-break: break-word;
    }
    .verification-input {
        width: 100%;
        height: 58px;
        text-align: center;
        font-size: 28px;
        font-weight: 600;
        letter-spacing: 10px;
        border: 1px solid #dfe4ea;
        border-radius: 10px;
        outline: none;
        transition:
            border-color 0.2s ease,
            box-shadow 0.2s ease;
    }
    .verification-input:focus {
        border-color: #a3c9f7;
        box-shadow:
            0 0 0 3px rgba(163, 201, 247, 0.25);
    }
    .verification-input::placeholder {
        color: #c5c9ce;
        letter-spacing: 8px;
    }
    .verify-button {
        width: 100%;
        height: 46px;
        margin-top: 20px;
        background: #4caf50;
        border: none;
        border-radius: 9px;
        color: #ffffff;
        font-weight: 600;
        transition:
            background 0.2s ease,
            transform 0.2s ease;
    }
    .verify-button:hover {
        background: #43a047;
        transform: translateY(-1px);
    }
    .resend-section {
        text-align: center;
        margin-top: 25px;
        padding-top: 20px;
        border-top: 1px solid #eeeeee;
    }
    .resend-text {
        color: #777;
        font-size: 0.82rem;
        margin-bottom: 8px;
    }
    .resend-button {
        background: none;
        border: none;
        color: #4caf50;
        font-weight: 600;
        font-size: 0.85rem;
        padding: 0;
        cursor: pointer;
    }
    .resend-button:hover {
        color: #388e3c;
        text-decoration: underline;
    }
    .back-login {
        display: block;
        text-align: center;
        margin-top: 20px;
        color: #777 !important;
        font-size: 0.8rem;
    }
    .back-login:hover {
        color: #4caf50 !important;
    }
    @media (max-width: 576px) {
        .verification-card {
            padding: 28px 20px;
            border-radius: 16px;
        }
        .verification-input {
            font-size: 24px;
            letter-spacing: 7px;
        }
    }
</style>
</head>
<body>
<header class="custom-header">
<div class="header-content">
    <img
        src="../images/clearteenalogo.png"
        alt="TEEN-ANIM Logo"
        class="header-logo"
    >
    <span class="header-title">
        TEEN-ANIM
    </span>
</div>
</header>
<div
    class="container-fluid d-flex justify-content-center"
    style="
        padding-top: 50px;
        padding-bottom: 80px;
        min-height: calc(100vh - 65px);
    "
>
<div class="verification-card">
    <div class="verification-icon">
        <i class="bi bi-envelope-check-fill"></i>
    </div>
    <h2 class="verification-title">
        Verify Your Email
    </h2>
    <p class="verification-description">
        We've sent a 6-digit verification code to:
        <span class="verification-email">
            <?= htmlspecialchars($user['email']) ?>
        </span>
    </p>
    <?php if (!empty($error_message)): ?>
        <div
            class="alert alert-danger"
            role="alert"
        >
            <i
                class="bi bi-exclamation-triangle-fill me-2"
            ></i>

            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($success_message)): ?>
        <div
            class="alert alert-success"
            role="alert"
        >
            <i
                class="bi bi-check-circle-fill me-2"
            ></i>
            <?= htmlspecialchars($success_message) ?>
        </div>
    <?php endif; ?>
    <form
        action="verify.php"
        method="POST"
        autocomplete="off"
    >
        <input
            type="hidden"
            name="action"
            value="verify"
        >
        <label
            for="verification_code"
            class="form-label text-center d-block"
        >
            Enter Verification Code
        </label>
        <input
            type="text"
            name="verification_code"
            id="verification_code"
            class="verification-input"
            placeholder="000000"
            maxlength="6"
            inputmode="numeric"
            pattern="[0-9]{6}"
            autocomplete="one-time-code"
            required
        >
        <button
            type="submit"
            class="verify-button"
        >
            <i class="bi bi-check-circle me-1"></i>
            Verify Email
        </button>
    </form>
    <div class="resend-section">
        <p class="resend-text">
            Didn't receive the code?
        </p>
        <form
            action="verify.php"
            method="POST"
        >
            <input
                type="hidden"
                name="action"
                value="resend"
            >
            <button
                type="submit"
                class="resend-button"
            >
                <i class="bi bi-arrow-clockwise me-1"></i>
                Resend Verification Code
            </button>
        </form>
    </div>
    <a
        href="login.php"
        class="back-login"
    >
        <i class="bi bi-arrow-left me-1"></i>
        Back to Login
    </a>
</div>
</div>
<footer>
<div class="container-fluid footer-bg fixed-bottom">
    <div
        class="pt-1 mx-5 d-flex
               justify-content-around
               align-items-center"
    >
        <p>
        </p>
        <img
            src="../images/clearteenalogo.png"
            class="teenanimlogo mb-2"
            alt="TEENANIM LOGO"
        >
        <p>
        </p>
    </div>
</div>
</footer>

<script>
    const verificationInput =
        document.getElementById('verification_code');
    verificationInput.addEventListener(
        'input',
        function () {

            this.value =
                this.value.replace(/\D/g, '');

        }
    );
</script>
</body>
</html>
