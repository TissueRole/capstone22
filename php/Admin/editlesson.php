<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}
include('../connection.php');

$lesson_id = $_GET['id'] ?? null;
if (!$lesson_id) {
    header("Location: adminpage.php#lesson-management&error=Lesson ID missing");
    exit();
}

// Handle update
$success = $error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['lesson_title']);
    $content = trim($_POST['lesson_content']);
    $lesson_order = intval($_POST['lesson_order']);

    $stmt = $conn->prepare("UPDATE lessons SET title=?, content=?, lesson_order=? WHERE lesson_id=?");
    $stmt->bind_param("ssii", $title, $content, $lesson_order, $lesson_id);

    if ($stmt->execute()) {
        $success = "✅ Lesson updated successfully!";
    } else {
        $error = "❌ Error updating lesson: " . $stmt->error;
    }
}

// Fetch lesson
$stmt = $conn->prepare("SELECT * FROM lessons WHERE lesson_id=?");
$stmt->bind_param("i", $lesson_id);
$stmt->execute();
$lesson = $stmt->get_result()->fetch_assoc();
if (!$lesson) {
    header("Location: adminpage.php#lesson-management&error=Lesson not found");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Lesson</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background: linear-gradient(135deg, #e8f5e9, #c8e6c9); font-family: 'Poppins', sans-serif; }
        .page-header { background: #388e3c; color: white; padding: 1.5rem; border-radius: 0.75rem; margin-bottom: 2rem; }
        .card { border-radius: 1rem; box-shadow: 0px 6px 18px rgba(0,0,0,0.1); }
        .btn-warning { background: #fbc02d; border: none; }
        .btn-warning:hover { background: #f9a825; }
        .form-label { font-weight: 600; color: #2e7d32; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h2 class="mb-0"><i class="bi bi-pencil-square me-2"></i> Edit Lesson</h2>
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
                    <label class="form-label">Lesson Title</label>
                    <input type="text" name="lesson_title" class="form-control" value="<?= htmlspecialchars($lesson['title']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Lesson Content</label>
                    <textarea name="lesson_content" class="form-control" rows="8" required><?= htmlspecialchars($lesson['content']) ?></textarea>
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
                    <input type="number" name="lesson_order" class="form-control" min="1" value="<?= htmlspecialchars($lesson['lesson_order']) ?>">
                </div>
                <button type="submit" class="btn btn-warning w-100">
                    <i class="bi bi-save me-1"></i> Update Lesson
                </button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
