<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}
include('../connection.php');

// Handle form
$success = $error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $module_id = intval($_POST['module_id']);
    $title = trim($_POST['lesson_title']);
    $content = trim($_POST['lesson_content']);
    $lesson_order = intval($_POST['lesson_order']);

    if ($module_id > 0 && $title != "") {
        $stmt = $conn->prepare("INSERT INTO lessons (module_id, title, content, lesson_order) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issi", $module_id, $title, $content, $lesson_order);

        if ($stmt->execute()) {
            $success = "✅ Lesson added successfully!";
        } else {
            $error = "❌ Error adding lesson: " . $stmt->error;
        }
    } else {
        $error = "❌ Module and Lesson Title are required.";
    }
}

// Fetch modules
$modules = $conn->query("SELECT module_id, title FROM modules ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Lesson</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background: linear-gradient(135deg, #e8f5e9, #c8e6c9); font-family: 'Poppins', sans-serif; }
        .page-header { background: #388e3c; color: white; padding: 1.5rem; border-radius: 0.75rem; margin-bottom: 2rem; }
        .card { border-radius: 1rem; box-shadow: 0px 6px 18px rgba(0,0,0,0.1); }
        .btn-success { background: #66bb6a; border: none; }
        .btn-success:hover { background: #388e3c; }
        .form-label { font-weight: 600; color: #2e7d32; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h2 class="mb-0"><i class="bi bi-journal-plus me-2"></i> Add Lesson</h2>
        <a href="adminpage.php#lesson-management" class="btn btn-light">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

    <div class="card border-0">
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Select Module</label>
                    <select name="module_id" class="form-select" required>
                        <option value="">-- Choose Module --</option>
                        <?php while ($m = $modules->fetch_assoc()): ?>
                            <option value="<?= $m['module_id'] ?>"><?= htmlspecialchars($m['title']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Lesson Title</label>
                    <input type="text" name="lesson_title" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Lesson Content</label>
                    <textarea name="lesson_content" class="form-control" rows="8" placeholder="## Section Title&#10;- Bullet point&#10;Paragraph text..." required></textarea>
                    <small class="form-text text-muted">
                        You can use simple formatting:
                        <ul>
                            <li><code>##</code> for section headers</li>
                            <li><code>-</code> for bullet points</li>
                            <li>Blank lines for paragraphs</li>
                        </ul>
                    </small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Order Index</label>
                    <input type="number" name="lesson_order" class="form-control" min="1" value="1">
                </div>
                <button type="submit" class="btn btn-success w-100">
                    <i class="bi bi-plus-circle me-1"></i> Add Lesson
                </button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
