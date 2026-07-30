<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require __DIR__ . '/../vendor/autoload.php';
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include('connection.php');
    $fullname = trim($_POST['fullname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $repassword = $_POST['repassword'] ?? '';
    if ($password !== $repassword) {
        $error_message = "Passwords do not match!";
    } elseif (!preg_match("/^[a-zA-Z0-9]*$/", $username)) {
        $error_message = "Username should contain only letters and numbers.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } elseif (strlen($password) < 8 || strlen($password) > 20) {
        $error_message = "Password must be between 8 and 20 characters long.";
    } else {

        $stmt = $conn->prepare(
            "SELECT user_id FROM users WHERE username = ? LIMIT 1"
        );
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $error_message = "Username already exists.";
            $stmt->close();
            $conn->close();
        } else {
            $stmt->close();

            $stmt = $conn->prepare(
                "SELECT user_id FROM users WHERE email = ? LIMIT 1"
            );
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $error_message = "Email address is already registered.";
                $stmt->close();
                $conn->close();
            } else {
                $stmt->close();
                $hashed_password = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );
                $verification_code = str_pad(
                    random_int(0, 999999),
                    6,
                    '0',
                    STR_PAD_LEFT
                );
                $verification_expires = date(
                    'Y-m-d H:i:s',
                    time() + (10 * 60)
                );
                $stmt = $conn->prepare(
                    "INSERT INTO users
                    (
                        name,
                        username,
                        email,
                        password,
                        role,
                        verification_code,
                        verification_expires,
                        email_verified
                    )
                    VALUES (?, ?, ?, ?, 'new user', ?, ?, 0)"
                );
                $stmt->bind_param(
                    "ssssss",
                    $fullname,
                    $username,
                    $email,
                    $hashed_password,
                    $verification_code,
                    $verification_expires
                );
                if ($stmt->execute()) {
                    $userId = $stmt->insert_id;
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
                            $email,
                            $fullname
                        );
                        $mail->isHTML(true);
                        $mail->Subject =
                            'TEEN-ANIM Email Verification Code';
                        $mail->Body = '
                        <div style="
                            font-family: Arial, sans-serif;
                            max-width: 600px;
                            margin: auto;
                            padding: 30px;
                            border: 1px solid #ddd;
                            border-radius: 12px;
                        ">
                            <h2 style="color: #4caf50;">
                                TEEN-ANIM
                            </h2>

                            <h3>
                                Verify Your Email Address
                            </h3>
                            <p>
                                Hello <strong>' .
                                htmlspecialchars($fullname) .
                                '</strong>,
                            </p>
                            <p>
                                Thank you for creating a TEEN-ANIM
                                account. Please enter the verification
                                code below to verify your email address.
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
                                    ' . $verification_code . '
                                </div>
                            </div>
                            <p>
                                This code will expire in
                                <strong>10 minutes</strong>.
                            </p>

                            <p>
                                If you did not create this account,
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
                            "Your TEEN-ANIM verification code is: "
                            . $verification_code;
                        $mail->send();
                        $_SESSION['verification_user_id'] = $userId;
                        $_SESSION['verification_email'] = $email;

                        $stmt->close();
                        $conn->close();
                        header("Location: verify.php");
                        exit();

                    } catch (Exception $e) {
                        $delete = $conn->prepare(
                            "DELETE FROM users WHERE user_id = ?"
                        );
                        $delete->bind_param("i", $userId);
                        $delete->execute();
                        $delete->close();
                        $error_message =
                            "The verification email could not be sent. "
                            . "Please check your email settings.";
                        $stmt->close();
                        $conn->close();
                    }
                } else {
                    $error_message =
                        "Error creating account: " . $stmt->error;
                    $stmt->close();
                    $conn->close();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport"
      content="width=device-width, initial-scale=1.0">
<title>Sign Up</title>
<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<link href="../css/signup.css" rel="stylesheet">
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
    style="padding-top: 40px; padding-bottom: 40px;"
>
<div class="signup-card">
    <h3 class="mb-4 text-center">
        Sign Up
    </h3>
    <?php if (!empty($error_message)): ?>
        <div
            class="alert alert-danger"
            role="alert"
            id="errorAlert"
        >
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>
    <form
        action="signup.php"
        method="post"
        autocomplete="off"
        id="signupForm"
    >
        <label
            for="fullname"
            class="form-label"
        >
            Full Name:
        </label>
        <div class="input-group mb-3">
            <span class="input-group-text">
                <i class="bi bi-person"></i>
            </span>
            <input
                class="form-control"
                type="text"
                name="fullname"
                placeholder="Full name..."
                id="fullname"
                required
            >
        </div>
        <label
            for="username"
            class="form-label"
        >
            Enter Username:
        </label>
        <div class="input-group mb-3">
            <span class="input-group-text">
                <i class="bi bi-person-badge"></i>
            </span>
            <input
                class="form-control"
                type="text"
                name="username"
                placeholder="Username..."
                id="username"
                required
            >
        </div>
        <div
            id="usernameFeedback"
            class="form-text mb-2 text-danger d-none"
        ></div>
        <label
            for="email"
            class="form-label"
        >
            Email Address:
        </label>
        <div class="input-group mb-3">
            <span class="input-group-text">
                <i class="bi bi-envelope"></i>
            </span>
            <input
                class="form-control"
                type="email"
                name="email"
                placeholder="Email address..."
                id="email"
                required
            >
        </div>
        <label
            for="password"
            class="form-label"
        >
            Password:
        </label>
        <div
            class="input-group"
            id="password-group"
        >
            <span class="input-group-text">
                <i class="bi bi-lock"></i>
            </span>
            <input
                type="password"
                name="password"
                id="password"
                class="form-control"
                placeholder="Type your password..."
                required
            >
            <span
                class="input-group-text toggle-password"
                onclick="togglePassword('password', this)"
            >
              <i class="bi bi-eye"></i>
            </span>
        </div>
        <div
            class="password-strength bg-secondary"
            id="passwordStrength"
        ></div>
        <div
            id="passwordtext"
            class="form-text mb-3"
        >
            Your password must be 8-20 characters long,
            contain letters and numbers, and must not contain
            spaces, special characters, or emoji.
        </div>
        <label
            for="repassword"
            class="form-label"
        >
            Re-enter Password:
        </label>
        <div class="input-group mb-3">
            <span class="input-group-text">
                <i class="bi bi-lock-fill"></i>
            </span>
            <input
                type="password"
                name="repassword"
                id="repassword"
                class="form-control"
                placeholder="Re-enter your password..."
                required
            >
            <span
                class="input-group-text toggle-password"
                onclick="togglePassword('repassword', this)"
            >
                <i class="bi bi-eye"></i>
            </span>
        </div>
        <div
            id="matchFeedback"
            class="form-text mb-3 text-danger d-none"
        ></div>
        <div class="row">
            <div class="col d-flex align-items-end">
                <a
                    href="login.php"
                    class="link-offset-2
                           link-offset-3-hover
                           link-underline
                           link-underline-opacity-0
                           link-underline-opacity-75-hover"
                >
                    Already have an account?
                </a>
            </div>
            <div class="col d-flex justify-content-end">
                <button
                    type="submit"
                    class="btn btn-success mt-2 w-100"
                >
                    Sign Up
                </button>
            </div>
        </div>
    </form>
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
            Copyright 2024
        </p>
        <img
            src="../images/clearteenalogo.png"
            class="teenanimlogo mb-2"
            alt="TEENANIM LOGO"
        >
        <p>
            Terms & Conditions / Privacy Policy
        </p>
    </div>
</div>
</footer>
<script>
    function togglePassword(fieldId, el) {
        const input = document.getElementById(fieldId);
        const icon = el.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');

        }
    }
    document
        .getElementById('password')
        .addEventListener('input', function () {
            const val = this.value;
            const strengthBar =
                document.getElementById('passwordStrength');
            let strength = 0;
            if (val.length >= 8) strength++;
            if (val.match(/[A-Z]/)) strength++;
            if (val.match(/[0-9]/)) strength++;
            if (val.match(/[a-z]/)) strength++;
            if (val.length >= 12) strength++;
            let width = '20%';
            if (strength >= 4) {
                width = '60%';
            }
            if (strength >= 5) {
                width = '100%';
            }
            strengthBar.style.width = width;
        });
    document
        .getElementById('repassword')
        .addEventListener('input', function () {
            const pass =
                document.getElementById('password').value;
            const repass = this.value;
            const feedback =
                document.getElementById('matchFeedback');
            if (repass && pass !== repass) {
                feedback.textContent =
                    'Passwords do not match!';
                feedback.classList.remove('d-none');
            } else {
                feedback.textContent = '';
                feedback.classList.add('d-none');
            }
        });
    document
        .getElementById('username')
        .addEventListener('input', function () {
            const val = this.value;
            const feedback =
                document.getElementById('usernameFeedback');
            if (!/^[a-zA-Z0-9]*$/.test(val)) {
                feedback.textContent =
                    'Username should contain only letters and numbers.';
                feedback.classList.remove('d-none');
            } else {
                feedback.textContent = '';
                feedback.classList.add('d-none');
            }
        });
    window.addEventListener(
        'DOMContentLoaded',
        function () {
            const alert =
                document.getElementById('errorAlert');
            if (alert) {
                setTimeout(function () {
                    alert.classList.add('alert-hide');
                }, 3500);
            }
        }
    );
</script>
</body>
</html>
