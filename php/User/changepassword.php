<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Redirect back to the page where the form was submitted
$redirectPage = $_SERVER['HTTP_REFERER'] ?? '../login.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {

    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Get current password
    $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {

        $stmt->bind_result($hashed_password);
        $stmt->fetch();

        // Verify current password
        if (password_verify($current_password, $hashed_password)) {

            // Check if new passwords match
            if ($new_password === $confirm_password) {

                // Hash new password
                $new_hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

                // Update password
                $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $update_stmt->bind_param("si", $new_hashed_password, $user_id);

                if ($update_stmt->execute()) {

                    // Log the user out
                    session_unset();
                    session_destroy();

                    echo "
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                    </head>
                    <body>
                    <script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Password Changed!',
                        text: 'Your password has been changed successfully. You need to log in again.',
                        confirmButtonText: 'OK',
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    }).then(() => {
                        window.location.href = '../login.php';
                    });
                    </script>
                    </body>
                    </html>";
                    exit();

                } else {

                    echo "
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                    </head>
                    <body>
                    <script>
                    Swal.fire({
                        icon: 'error',
                        title: 'Database Error',
                        text: 'Error updating password.',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = '$redirectPage';
                    });
                    </script>
                    </body>
                    </html>";
                    exit();
                }

                $update_stmt->close();

            } else {

                echo "
                <!DOCTYPE html>
                <html>
                <head>
                    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                </head>
                <body>
                <script>
                Swal.fire({
                    icon: 'error',
                    title: 'Passwords Do Not Match',
                    text: 'The new password and confirm password do not match.',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.href = '$redirectPage';
                });
                </script>
                </body>
                </html>";
                exit();
            }

        } else {

            echo "
            <!DOCTYPE html>
            <html>
            <head>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            </head>
            <body>
            <script>
            Swal.fire({
                icon: 'error',
                title: 'Incorrect Password',
                text: 'The current password you entered is incorrect.',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.href = '$redirectPage';
            });
            </script>
            </body>
            </html>";
            exit();
        }

    } else {

        echo "
        <!DOCTYPE html>
        <html>
        <head>
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        </head>
        <body>
        <script>
        Swal.fire({
            icon: 'error',
            title: 'User Not Found',
            text: 'Unable to find your account.',
            confirmButtonText: 'OK'
        }).then(() => {
            window.location.href = '$redirectPage';
        });
        </script>
        </body>
        </html>";
        exit();
    }

    $stmt->close();
}

$conn->close();
?>