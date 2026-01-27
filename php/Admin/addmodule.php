<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}
include('../connection.php');

$success = $error = "";

// Handle module submission
if (isset($_POST['add_module'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $image_path = trim($_POST['image_path']);
    $rewards = trim($_POST['rewards'] ?? '');

    if ($title !== "" && $description !== "") {

        $stmt = $conn->prepare("
            INSERT INTO modules (title, description, image_path, rewards, created_at, updated_at) 
            VALUES (?, ?, ?, ?, NOW(), NOW())
        ");

        $stmt->bind_param("ssss", $title, $description, $image_path, $rewards);

        if ($stmt->execute()) {
            $success = "✅ Module added successfully!";
        } else {
            $error = "❌ Error adding module: " . $stmt->error;
        }

    } else {
        $error = "❌ Title and Description are required.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Module Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            font-family: 'Poppins', sans-serif;
        }
        .page-header {
            background: #388e3c;
            color: white;
            padding: 1.5rem;
            border-radius: 0.75rem;
            margin-bottom: 2rem;
            box-shadow: 0px 4px 15px rgba(0,0,0,0.15);
        }
        .card {
            border-radius: 1rem;
            box-shadow: 0px 6px 18px rgba(0,0,0,0.1);
            transition: transform 0.2s ease;
        }
        .card:hover {
            transform: translateY(-4px);
        }
        .btn-success {
            background: #66bb6a;
            border: none;
        }
        .btn-success:hover {
            background: #388e3c;
        }
        .form-label {
            font-weight: 600;
            color: #2e7d32;
        }
    </style>
</head>
<body>
<div class="container py-5">
    <!-- Page Header -->
    <div class="page-header d-flex justify-content-between align-items-center">
        <h2 class="mb-0"><i class="bi bi-folder-fill me-2"></i> Module Management</h2>
        <a href="adminpage.php" class="btn btn-light">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <!-- Notifications -->
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <!-- Add Module Form -->
    <div class="card border-0 mb-4">
        <div class="card-header bg-success text-white rounded-top">
            <h5 class="mb-0"><i class="bi bi-folder-plus me-2"></i> Add New Module</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Module Title</label>
                    <input type="text" name="title" class="form-control" placeholder="Enter module title" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Module Description</label>
                    <textarea name="description" class="form-control" rows="5" placeholder="Brief module description..." required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Module Rewards</label>
                    <textarea 
                        name="rewards" 
                        class="form-control" 
                        rows="1"
                        placeholder="Enter Reward"
                    ></textarea>
                    <small class="text-muted">
                        These rewards will be shown after the user passes the quiz.
                    </small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Image Path (URL)</label>
                    <input type="text" name="image_path" class="form-control" placeholder="Enter image URL (optional)">
                </div>
                <button type="submit" name="add_module" class="btn btn-success w-100">
                    <i class="bi bi-plus-circle me-1"></i> Add Module
                </button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
