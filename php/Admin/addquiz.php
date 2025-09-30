<?php
session_start();
include('../connection.php');

// Only admins can access
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$success = $error = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $module_id = intval($_POST['module_id']);
    $title = trim($_POST['title']);
    $questions = $_POST['questions'] ?? [];

    if ($module_id > 0 && $title !== "" && count($questions) > 0) {
        // Insert quiz
        $stmt = $conn->prepare("INSERT INTO module_quizzes (module_id, title) VALUES (?, ?)");
        $stmt->bind_param("is", $module_id, $title);

        if ($stmt->execute()) {
            $quiz_id = $stmt->insert_id;

            // Insert questions
            $qstmt = $conn->prepare("INSERT INTO quiz_questions 
                (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");

            foreach ($questions as $q) {
                $q_text = trim($q['text']);
                $a = trim($q['a']);
                $b = trim($q['b']);
                $c = trim($q['c']);
                $d = trim($q['d']);
                $correct = $q['correct'];

                if ($q_text !== "" && $a !== "" && $b !== "" && $c !== "" && $d !== "" && in_array($correct, ['A','B','C','D'])) {
                    $qstmt->bind_param("issssss", $quiz_id, $q_text, $a, $b, $c, $d, $correct);
                    $qstmt->execute();
                }
            }

            $success = "✅ Quiz added successfully!";
        } else {
            $error = "❌ Error adding quiz: " . $stmt->error;
        }
    } else {
        $error = "❌ Please fill in all required fields and add at least one question.";
    }
}

// Fetch modules for dropdown
$modules = $conn->query("SELECT module_id, title FROM modules ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Quiz</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background: linear-gradient(135deg, #e8f5e9, #c8e6c9); font-family: 'Poppins', sans-serif; }
        .page-header { background: #388e3c; color: white; padding: 1.5rem; border-radius: 0.75rem; margin-bottom: 2rem; }
        .card { border-radius: 1rem; box-shadow: 0px 6px 18px rgba(0,0,0,0.1); }
        .btn-success { background: #66bb6a; border: none; }
        .btn-success:hover { background: #388e3c; }
        .form-label { font-weight: 600; color: #2e7d32; }
        .question-block { border: 2px solid #a5d6a7; padding: 1rem; border-radius: 0.75rem; margin-bottom: 1rem; background: #ffffff; position: relative; }
        .remove-btn { position: absolute; top: 10px; right: 10px; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h2 class="mb-0"><i class="bi bi-question-circle me-2"></i> Add Quiz</h2>
        <a href="adminpage.php#quiz-management" class="btn btn-light">
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
                    <label class="form-label">Quiz Title</label>
                    <input type="text" name="title" class="form-control" required>
                </div>

                <h4 class="mt-4 mb-3 text-success"><i class="bi bi-list-task me-2"></i> Questions</h4>
                <div id="questions-container">
                    <div class="question-block">
                        <button type="button" class="btn btn-sm btn-danger remove-btn" onclick="removeQuestion(this)">
                            <i class="bi bi-x-lg"></i>
                        </button>
                        <label class="form-label">Question</label>
                        <input type="text" name="questions[0][text]" class="form-control mb-2" required>

                        <label class="form-label">Options</label>
                        <input type="text" name="questions[0][a]" placeholder="Option A" class="form-control mb-1" required>
                        <input type="text" name="questions[0][b]" placeholder="Option B" class="form-control mb-1" required>
                        <input type="text" name="questions[0][c]" placeholder="Option C" class="form-control mb-1" required>
                        <input type="text" name="questions[0][d]" placeholder="Option D" class="form-control mb-1" required>

                        <label class="form-label">Correct Answer</label>
                        <select name="questions[0][correct]" class="form-select">
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                        </select>
                    </div>
                </div>

                <button type="button" class="btn btn-outline-success mb-3" onclick="addQuestion()">
                    <i class="bi bi-plus-circle"></i> Add Another Question
                </button>

                <button type="submit" class="btn btn-success w-100">
                    <i class="bi bi-check-circle me-1"></i> Save Quiz
                </button>
            </form>
        </div>
    </div>
</div>

<script>
let questionCount = 1;
function addQuestion() {
    let container = document.getElementById("questions-container");
    let block = document.createElement("div");
    block.classList.add("question-block");
    block.innerHTML = `
        <button type="button" class="btn btn-sm btn-danger remove-btn" onclick="removeQuestion(this)">
            <i class="bi bi-x-lg"></i>
        </button>
        <label class="form-label">Question</label>
        <input type="text" name="questions[${questionCount}][text]" class="form-control mb-2" required>

        <label class="form-label">Options</label>
        <input type="text" name="questions[${questionCount}][a]" placeholder="Option A" class="form-control mb-1" required>
        <input type="text" name="questions[${questionCount}][b]" placeholder="Option B" class="form-control mb-1" required>
        <input type="text" name="questions[${questionCount}][c]" placeholder="Option C" class="form-control mb-1" required>
        <input type="text" name="questions[${questionCount}][d]" placeholder="Option D" class="form-control mb-1" required>

        <label class="form-label">Correct Answer</label>
        <select name="questions[${questionCount}][correct]" class="form-select">
            <option value="A">A</option>
            <option value="B">B</option>
            <option value="C">C</option>
            <option value="D">D</option>
        </select>
    `;
    container.appendChild(block);
    questionCount++;
}

function removeQuestion(button) {
    button.parentElement.remove();
}
</script>
</body>
</html>
