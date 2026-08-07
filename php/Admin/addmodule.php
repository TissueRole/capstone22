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
    $rewards = trim($_POST['rewards'] ?? '');
    $image_path = "";

    if ($title !== "" && $description !== "") {

        // Handle File Upload
        if (isset($_FILES['module_image']) && $_FILES['module_image']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath   = $_FILES['module_image']['tmp_name'];
            $fileName      = $_FILES['module_image']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($fileExtension, $allowedExtensions)) {
                // Physical disk upload target (up 2 levels from php/admin/ to capstone22/images/modules/)
                $uploadDir = '../../images/modules/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $newFileName = 'module_' . time() . '_' . uniqid() . '.' . $fileExtension;
                $destPath = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    // Stored formatted for php/modulepage.php
                    $image_path = '../images/modules/' . $newFileName;
                } else {
                    $error = "❌ Error uploading the image file.";
                }
            } else {
                $error = "❌ Invalid image format. Allowed: JPG, PNG, GIF, WEBP.";
            }
        }

        if (empty($error)) {
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
        body { background: linear-gradient(135deg, #e8f5e9, #c8e6c9); font-family: 'Poppins', sans-serif; }
        .page-header { background: #388e3c; color: white; padding: 1.5rem; border-radius: 0.75rem; margin-bottom: 2rem; box-shadow: 0px 4px 15px rgba(0,0,0,0.15); }
        .card { border-radius: 1rem; box-shadow: 0px 6px 18px rgba(0,0,0,0.1); }
        .btn-success { background: #66bb6a; border: none; }
        .btn-success:hover { background: #388e3c; }
        .form-label { font-weight: 600; color: #2e7d32; }
        #imagePreview { max-height: 200px; object-fit: cover; border-radius: 0.5rem; display: none; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h2 class="mb-0"><i class="bi bi-folder-fill me-2"></i> Module Management</h2>
        <a href="adminpage.php" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="card border-0 mb-4">
        <div class="card-header bg-success text-white rounded-top">
            <h5 class="mb-0"><i class="bi bi-folder-plus me-2"></i> Add New Module</h5>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
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
                    <textarea name="rewards" class="form-control" rows="1" placeholder="Enter Reward"></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Module Image</label>
                    <input type="file" name="module_image" id="moduleImageInput" class="form-control" accept="image/*">
                    <div class="mt-2">
                        <img id="imagePreview" src="#" alt="Image Preview" class="img-thumbnail">
                    </div>
                </div>

                <button type="submit" name="add_module" class="btn btn-success w-100">
                    <i class="bi bi-plus-circle me-1"></i> Add Module
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    const imageInput = document.getElementById('moduleImageInput');
    const imagePreview = document.getElementById('imagePreview');

    imageInput.addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            imagePreview.src = URL.createObjectURL(file);
            imagePreview.style.display = 'block';
        } else {
            imagePreview.src = '#';
            imagePreview.style.display = 'none';
        }
    });
</script>
</body>
</html>