<?php

include '../connection.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (isset($_GET['id'], $_GET['field'], $_GET['value'])) {

    $id = intval($_GET['id']);
    $field = $_GET['field'];
    $value = $_GET['value'];

    $allowedFields = ['role', 'status'];
    if (!in_array($field, $allowedFields)) {
        http_response_code(400);
        exit("Invalid field.");
    }

    $currentStmt = $conn->prepare("
        SELECT status, offense_count
        FROM users
        WHERE user_id = ?
    ");
    $currentStmt->bind_param("i", $id);
    $currentStmt->execute();
    $currentStmt->bind_result($currentStatus, $offenseCount);
    $currentStmt->fetch();
    $currentStmt->close();


    if (
        $field === "status" &&
        $value === "inactive" &&
        $currentStatus !== "inactive"
    ) {

        $offenseCount++;

        if ($offenseCount == 1) {
            $restrictionType = "temporary";
            $restrictionUntil = date('Y-m-d H:i:s', strtotime('+1 day'));
        } elseif ($offenseCount == 2) {
            $restrictionType = "temporary";
            $restrictionUntil = date('Y-m-d H:i:s', strtotime('+7 days'));
        } else {
            $restrictionType = "permanent";
            $restrictionUntil = null;
        }

        if ($restrictionType === "permanent") {

            $stmt = $conn->prepare("
                UPDATE users
                SET
                    status='inactive',
                    offense_count=?,
                    restriction_type='permanent',
                    restriction_until=NULL
                WHERE user_id=?
            ");

            $stmt->bind_param("ii", $offenseCount, $id);

        } else {

            $stmt = $conn->prepare("
                UPDATE users
                SET
                    status='inactive',
                    offense_count=?,
                    restriction_type='temporary',
                    restriction_until=?
                WHERE user_id=?
            ");

            $stmt->bind_param("isi", $offenseCount, $restrictionUntil, $id);
        }

    }

    elseif (
        $field === "status" &&
        $value === "active"
    ) {

        $stmt = $conn->prepare("
            UPDATE users
            SET
                status='active',
                restriction_type='none',
                restriction_until=NULL
            WHERE user_id=?
        ");

        $stmt->bind_param("i", $id);

    }

    else {

        $stmt = $conn->prepare("
            UPDATE users
            SET $field=?
            WHERE user_id=?
        ");

        $stmt->bind_param("si", $value, $id);
    }

    if ($stmt->execute()) {

        // Restriction notification
        if (
            $field === "status" &&
            $value === "inactive" &&
            $currentStatus !== "inactive"
        ) {

            if ($offenseCount == 1) {

                $message = "Your Farming Community access has been temporarily restricted for 1 day due to a violation of the community guidelines.";

            } elseif ($offenseCount == 2) {

                $message = "Your Farming Community access has been temporarily restricted for 7 days due to repeated violations of the community guidelines.";

            } else {

                $message = "Your Farming Community access has been permanently restricted due to repeated violations of the community guidelines.";
            }

            $link = "/capstone22/php/Forum/community.php";

            $notifStmt = $conn->prepare("
                INSERT INTO notifications (user_id, message, link, is_read, created_at)
                VALUES (?, ?, ?, 0, NOW())
            ");

            $notifStmt->bind_param("iss", $id, $message, $link);
            $notifStmt->execute();
            $notifStmt->close();
        }

        // Restoration notification
        if (
            $field === "status" &&
            $value === "active" &&
            $currentStatus === "inactive"
        ) {

            $message = "Your access to the Farming Community has been restored.";

            $link = "/capstone22/php/Forum/community.php";

            $notifStmt = $conn->prepare("
                INSERT INTO notifications (user_id, message, link, is_read, created_at)
                VALUES (?, ?, ?, 0, NOW())
            ");

            $notifStmt->bind_param("iss", $id, $message, $link);
            $notifStmt->execute();
            $notifStmt->close();
        }

        echo "Success";

    } else {

        http_response_code(500);
        echo "Database error: " . $conn->error;

    }

    $stmt->close();

} else {

    http_response_code(400);
    echo "Invalid request.";

}