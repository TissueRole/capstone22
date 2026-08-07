<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include('../connection.php');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    exit("❌ Error: Missing Module ID parameter in URL.");
}

$id_param = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT * FROM modules WHERE module_id = ?");
if (!$stmt) {
    exit("❌ SQL Error on SELECT: " . $conn->error);
}

$stmt->bind_param("i", $id_param);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    exit("❌ Error: Module ID $id_param was not found in the database.");
}

$module = $result->fetch_assoc();
$success = $error = "";

if (isset($_POST['update_module'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $rewards = trim($_POST['rewards'] ?? '');
    $image_path = $module['image_path'] ?? '';

    if (!empty($title) && !empty($description)) {

        if (isset($_FILES['module_image']) && $_FILES['module_image']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath   = $_FILES['module_image']['tmp_name'];
            $fileName      = $_FILES['module_image']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($fileExtension, $allowed)) {
                $uploadDir = '../../images/modules/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $newFileName = 'module_' . time() . '_' . uniqid() . '.' . $fileExtension;
                $destPath = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    // Delete previous image file from server if it exists
                    if (!empty($module['image_path'])) {
                        // Adds extra '../' to convert '../images/...' into '../../images/...' for admin scope
                        $oldFilePath = '../' . $module['image_path'];
                        if (file_exists($oldFilePath) && is_file($oldFilePath)) {
                            @unlink($oldFilePath);
                        }
                    }
                    // Stored formatted for php/modulepage.php
                    $image_path = '../images/modules/' . $newFileName;
                } else {
                    $error = "❌ Failed to save uploaded image file.";
                }
            } else {
                $error = "❌ Invalid image format. Allowed formats: JPG, PNG, GIF, WEBP.";
            }
        }

        if (empty($error)) {
            $updateStmt = $conn->prepare("
                UPDATE modules 
                SET title = ?, description = ?, image_path = ?, rewards = ?, updated_at = NOW() 
                WHERE module_id = ?
            ");

            if (!$updateStmt) {
                exit("❌ SQL Error on UPDATE: " . $conn->error);
            }

            $updateStmt->bind_param("ssssi", $title, $description, $image_path, $rewards, $id_param);

            if ($updateStmt->execute()) {
                $success = "✅ Module updated successfully!";
                $module['title'] = $title;
                $module['description'] = $description;
                $module['image_path'] = $image_path;
                $module['rewards'] = $rewards;
            } else {
                $error = "❌ Update execution failed: " . $updateStmt->error;
            }
        }
    } else {
        $error = "❌ Title and Description are required.";
    }
}

// Convert DB path ('../images/...') into Admin browser path ('../../images/...') by prepending '../'
$preview_src = "";
if (!empty($module['image_path'])) {
    $preview_src = '../' . $module['image_path'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Edit Module</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background: linear-gradient(135deg, #e8f5e9, #c8e6c9); font-family: 'Poppins', sans-serif; }
        .page-header { background: #388e3c; color: white; padding: 1.5rem; border-radius: 0.75rem; margin-bottom: 2rem; box-shadow: 0px 4px 15px rgba(0,0,0,0.15); }
        .card { border-radius: 1rem; box-shadow: 0px 6px 18px rgba(0,0,0,0.1); }
        .btn-success { background: #66bb6a; border: none; }
        .btn-success:hover { background: #388e3c; }
        .form-label { font-weight: 600; color: #2e7d32; }
        #imagePreview { max-height: 200px; object-fit: cover; border-radius: 0.5rem; display: <?= !empty($preview_src) ? 'block' : 'none' ?>; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h2 class="mb-0"><i class="bi bi-pencil-square me-2"></i> Edit Module</h2>
        <a href="adminpage.php" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="card border-0 mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="bi bi-pencil-fill me-2"></i> Update Module Details (ID: <?= $id_param ?>)</h5>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Module Title</label>
                    <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($module['title'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Module Description</label>
                    <textarea name="description" class="form-control" rows="5" required><?= htmlspecialchars($module['description'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Module Rewards</label>
                    <textarea name="rewards" class="form-control" rows="2" placeholder="Enter rewards"><?= htmlspecialchars($module['rewards'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Module Image</label>
                    <input type="file" name="module_image" id="moduleImageInput" class="form-control" accept="image/*">
                    <small class="text-muted">Leave empty to keep current image.</small>
                    <div class="mt-2">
                        <img id="imagePreview" src="<?= htmlspecialchars($preview_src) ?>" alt="Image Preview" class="img-thumbnail">
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" name="update_module" class="btn btn-success flex-grow-1">
                        <i class="bi bi-save me-1"></i> Save Changes
                    </button>
                    <a href="adminpage.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const imageInput = document.getElementById('moduleImageInput');
    const imagePreview = document.getElementById('imagePreview');
    const originalSrc = "<?= htmlspecialchars($preview_src) ?>";

    imageInput.addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            imagePreview.src = URL.createObjectURL(file);
            imagePreview.style.display = 'block';
        } else if (originalSrc) {
            imagePreview.src = originalSrc;
            imagePreview.style.display = 'block';
        } else {
            imagePreview.src = '';
            imagePreview.style.display = 'none';
        }
    });
</script>
</body>
</html>