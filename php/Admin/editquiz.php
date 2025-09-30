<?php
session_start();
include('../connection.php');

// Only admins can access
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// ✅ Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quiz_id = intval($_POST['quiz_id']);
    $title   = trim($_POST['title']);

    if ($title == "") {
        die("Quiz title is required!");
    }

    // Update quiz title
    $stmt = $conn->prepare("UPDATE module_quizzes SET title=? WHERE quiz_id=?");
    $stmt->bind_param("si", $title, $quiz_id);
    $stmt->execute();

    // Get current question IDs
    $existing_ids = [];
    $res = $conn->query("SELECT question_id FROM quiz_questions WHERE quiz_id=$quiz_id");
    while ($row = $res->fetch_assoc()) {
        $existing_ids[] = $row['question_id'];
    }

    $submitted_ids = [];

    // Update existing questions
    if (!empty($_POST['questions'])) {
        foreach ($_POST['questions'] as $qid => $q) {
            $qid = intval($qid);
            $submitted_ids[] = $qid;
            $text    = trim($q['text']);
            $a       = trim($q['a']);
            $b       = trim($q['b']);
            $c       = trim($q['c']);
            $d       = trim($q['d']);
            $correct = $q['correct'];

            if ($text !== "" && $a !== "" && $b !== "" && $c !== "" && $d !== "") {
                $stmt = $conn->prepare("UPDATE quiz_questions 
                    SET question_text=?, option_a=?, option_b=?, option_c=?, option_d=?, correct_option=? 
                    WHERE question_id=? AND quiz_id=?");
                $stmt->bind_param("ssssssii", $text, $a, $b, $c, $d, $correct, $qid, $quiz_id);
                $stmt->execute();
            }
        }
    }

    // Delete removed questions
    $to_delete = array_diff($existing_ids, $submitted_ids);
    if (!empty($to_delete)) {
        $ids_str = implode(",", array_map("intval", $to_delete));
        $conn->query("DELETE FROM quiz_questions WHERE quiz_id=$quiz_id AND question_id IN ($ids_str)");
    }

    // Insert new questions
    if (!empty($_POST['new_questions'])) {
        foreach ($_POST['new_questions'] as $nq) {
            $text    = trim($nq['text']);
            $a       = trim($nq['a']);
            $b       = trim($nq['b']);
            $c       = trim($nq['c']);
            $d       = trim($nq['d']);
            $correct = $nq['correct'];

            if ($text !== "" && $a !== "" && $b !== "" && $c !== "" && $d !== "") {
                $stmt = $conn->prepare("INSERT INTO quiz_questions 
                    (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_answer) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssss", $quiz_id, $text, $a, $b, $c, $d, $correct);
                $stmt->execute();
            }
        }
    }

    header("Location: editquiz.php?id=$quiz_id&success=1");
    exit();
}

// ✅ If not POST → load quiz
if (!isset($_GET['id'])) {
    die("Quiz ID is required!");
}
$quiz_id = intval($_GET['id']);

// Fetch quiz
$quiz_stmt = $conn->prepare("SELECT * FROM module_quizzes WHERE quiz_id = ?");
$quiz_stmt->bind_param("i", $quiz_id);
$quiz_stmt->execute();
$quiz = $quiz_stmt->get_result()->fetch_assoc();

if (!$quiz) {
    die("Quiz not found!");
}

// Fetch questions
$questions = $conn->query("SELECT * FROM quiz_questions WHERE quiz_id = $quiz_id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Quiz</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <style>
      body { background: linear-gradient(135deg, #e8f5e9, #c8e6c9); font-family: 'Poppins', sans-serif; }
      .page-header { background: #2e7d32; color: white; padding: 1.2rem; border-radius: 0.75rem; margin-bottom: 2rem; }
      .card { border-radius: 1rem; box-shadow: 0px 6px 18px rgba(0,0,0,0.1); }
      .btn-success { background: #66bb6a; border: none; }
      .btn-success:hover { background: #2e7d32; }
  </style>
</head>
<body>
<div class="container py-5">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h2 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Quiz</h2>
        <a href="adminpage.php#quiz-management" class="btn btn-light">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <!-- ✅ Success Message -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">✅ Quiz updated successfully!</div>
    <?php endif; ?>

    <div class="card border-0">
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="quiz_id" value="<?= $quiz['quiz_id'] ?>">

                <div class="mb-3">
                    <label class="form-label">Quiz Title</label>
                    <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($quiz['title']) ?>" required>
                </div>

                <h4>Questions</h4>
                <div id="questions-container">
                    <?php while ($q = $questions->fetch_assoc()): ?>
                        <div class="question-block mb-4 border p-3 rounded position-relative">
                            <button type="button" class="btn-close position-absolute top-0 end-0 m-2" aria-label="Remove" onclick="this.parentElement.remove()"></button>

                            <input type="hidden" name="questions[<?= $q['question_id'] ?>][id]" value="<?= $q['question_id'] ?>">

                            <label class="form-label">Question</label>
                            <input type="text" name="questions[<?= $q['question_id'] ?>][text]" class="form-control mb-2" value="<?= htmlspecialchars($q['question_text']) ?>" required>

                            <label class="form-label">Options</label>
                            <input type="text" name="questions[<?= $q['question_id'] ?>][a]" class="form-control mb-1" value="<?= htmlspecialchars($q['option_a']) ?>" required>
                            <input type="text" name="questions[<?= $q['question_id'] ?>][b]" class="form-control mb-1" value="<?= htmlspecialchars($q['option_b']) ?>" required>
                            <input type="text" name="questions[<?= $q['question_id'] ?>][c]" class="form-control mb-1" value="<?= htmlspecialchars($q['option_c']) ?>" required>
                            <input type="text" name="questions[<?= $q['question_id'] ?>][d]" class="form-control mb-1" value="<?= htmlspecialchars($q['option_d']) ?>" required>

                            <label class="form-label">Correct Answer</label>
                            <select name="questions[<?= $q['question_id'] ?>][correct]" class="form-select">
                                <option value="A" <?= $q['correct_option']=="A"?"selected":"" ?>>A</option>
                                <option value="B" <?= $q['correct_option']=="B"?"selected":"" ?>>B</option>
                                <option value="C" <?= $q['correct_option']=="C"?"selected":"" ?>>C</option>
                                <option value="D" <?= $q['correct_option']=="D"?"selected":"" ?>>D</option>
                            </select>
                        </div>
                    <?php endwhile; ?>
                </div>
                <button type="button" class="btn btn-outline-success mb-3" onclick="addQuestion()">+ Add Question</button>
                <br>
                <button type="submit" class="btn btn-success w-100">
                    <i class="bi bi-save me-1"></i> Save Changes
                </button>
            </form>
        </div>
    </div>
</div>

<script>
let newQuestionCount = 0;
function addQuestion() {
    let container = document.getElementById("questions-container");
    let block = document.createElement("div");
    block.classList.add("question-block","mb-4","border","p-3","rounded","position-relative");

    block.innerHTML = `
        <button type="button" class="btn-close position-absolute top-0 end-0 m-2" aria-label="Remove" onclick="this.parentElement.remove()"></button>

        <label class="form-label">Question</label>
        <input type="text" name="new_questions[${newQuestionCount}][text]" class="form-control mb-2" required>

        <label class="form-label">Options</label>
        <input type="text" name="new_questions[${newQuestionCount}][a]" placeholder="Option A" class="form-control mb-1" required>
        <input type="text" name="new_questions[${newQuestionCount}][b]" placeholder="Option B" class="form-control mb-1" required>
        <input type="text" name="new_questions[${newQuestionCount}][c]" placeholder="Option C" class="form-control mb-1" required>
        <input type="text" name="new_questions[${newQuestionCount}][d]" placeholder="Option D" class="form-control mb-1" required>

        <label class="form-label">Correct Answer</label>
        <select name="new_questions[${newQuestionCount}][correct]" class="form-select">
            <option value="A">A</option>
            <option value="B">B</option>
            <option value="C">C</option>
            <option value="D">D</option>
        </select>
    `;
    container.appendChild(block);
    newQuestionCount++;
}
</script>
</body>
</html>
