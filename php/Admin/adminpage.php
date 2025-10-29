<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}
include('../connection.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Page</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Irish+Grover&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/homepage.css">
    <link rel="stylesheet" href="../../css/adminpage.css">
</head>
<body>
    <div class="admin-header">
        <div class="header-left">
            <span class="logo">
                <img src="../../images/clearteenalogo.png" alt="Logo" class="img-fluid">
                Admin Dashboard
            </span>
        </div>
        <div class="header-center">
            <button class="admin-nav-toggle d-md-none" id="adminNavToggle" aria-label="Toggle navigation" style="background:none;border:none;color:#fff;font-size:2rem;">
                <i class="bi bi-list"></i>
            </button>
        </div>
        <div class="header-right">
            <a href="../logout.php" class="logout-link"><i class="bi bi-box-arrow-right"></i>Logout</a>
        </div>
    </div>
    <nav class="admin-nav" id="adminNav">
        <a class="nav-link" href="#" onclick="showSection('user-management'); setActiveNav(this); return false;" id="nav-user"><i class="bi bi-people"></i>User Management</a>
        <a class="nav-link" href="#" onclick="showSection('module-management'); setActiveNav(this); return false;" id="nav-module"><i class="bi bi-journal-text"></i>Module Management</a>
        <a class="nav-link" href="#" onclick="showSection('lesson-management'); setActiveNav(this); return false;" id="nav-lesson"><i class="bi bi-book"></i>Lesson Management</a>
        <a class="nav-link" href="#" onclick="showSection('quiz-management'); setActiveNav(this); return false;" id="nav-quiz"><i class="bi bi-question-circle"></i>Quiz Management</a>
        <a class="nav-link" href="#" onclick="showSection('forum-management'); setActiveNav(this); return false;" id="nav-forum"><i class="bi bi-chat-dots"></i>Forum Management</a>
        <a class="nav-link" href="#" onclick="showSection('suggestions'); setActiveNav(this); return false;" id="nav-suggestions"><i class="bi bi-lightbulb"></i>Suggestions</a>
    </nav>
    <div class="content">
        <div id="toast-container" class="position-fixed bottom-0 end-0 p-3" style="z-index: 1100;"></div>
        <?php if (isset($_GET['success'])): ?>
            <script>window.addEventListener('DOMContentLoaded', function() { showToast('<?php echo htmlspecialchars($_GET['success']); ?>', 'success'); });</script>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <script>window.addEventListener('DOMContentLoaded', function() { showToast('<?php echo htmlspecialchars($_GET['error']); ?>', 'danger'); });</script>
        <?php endif; ?>
        <section id="user-management" class="content-section card p-4">
            <div class="section-title"><i class="bi bi-people"></i>User Management</div>
            <?php
            // Notification for new users
            $new_users_result = $conn->query("SELECT * FROM users WHERE role = 'new user'");
            if ($new_users_result && $new_users_result->num_rows > 0) {
                echo '<div class="alert alert-warning d-flex align-items-center" role="alert" style="font-size:1.1em;"><i class="bi bi-exclamation-triangle-fill me-2"></i> There are&nbsp;<b>' . $new_users_result->num_rows . '</b>&nbsp;new user(s) awaiting role assignment. Please set their role below.</div>';
            }
            ?>
            <div class="mb-3">
                <input type="text" id="search" class="form-control" placeholder="Search user profiles...">
            </div>
            <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Date Created</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="userTable">
                    <?php
                    $result = $conn->query("SELECT * FROM users");
                    while ($row = $result->fetch_assoc()) {
                        $is_new_user = ($row['role'] === 'new user');
                        echo "<tr" . ($is_new_user ? " style='background:#fffbe6;'" : "") . ">";
                        echo "<td>" . htmlspecialchars($row['name']);
                        if ($is_new_user) {
                            echo " <span class='badge bg-warning text-dark ms-2'>New User</span>";
                        }
                        echo "</td>";
                        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                        echo "<td>
                                    <select class='form-select' onchange='updateUser(this, " . $row['user_id'] . ", \"role\")'>
                                        <option value='admin'" . ($row['role'] == 'admin' ? ' selected' : '') . ">Admin</option>
                                        <option value='student'" . ($row['role'] == 'student' ? ' selected' : '') . ">Student</option>
                                        <option value='agriculturist'" . ($row['role'] == 'agriculturist' ? ' selected' : '') . ">Agriculturist</option>
                                        <option value='new user'" . ($row['role'] == 'new user' ? ' selected' : '') . ">New User</option>
                                    </select>
                                </td>";
                        echo "<td>" . htmlspecialchars($row['date_created']) . "</td>";
                        echo "<td>
                            <select class='form-select' onchange='updateUser(this, " . $row['user_id'] . ", \"status\")'>
                                <option value='active'" . ($row['status'] == 'active' ? ' selected' : '') . ">Active</option>
                                <option value='inactive'" . ($row['status'] == 'inactive' ? ' selected' : '') . ">Inactive</option>
                            </select>
                        </td>";
                        echo "<td>";
                        // Don't show delete button for the current admin user
                        if ($row['user_id'] != $_SESSION['user_id']) {
                            echo "<a href='deleteuser.php?id=" . $row['user_id'] . "' class='btn btn-sm btn-danger' onclick='return confirmDelete(\"" . htmlspecialchars($row['username']) . "\", " . $row['user_id'] . ")'><i class='bi bi-trash'></i>Delete</a>";
                        } else {
                            echo "<span class='text-muted'>Current User</span>";
                        }
                        echo "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
            </div>
        </section>
        <section id="module-management" class="content-section card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="section-title mb-0"><i class="bi bi-journal-text"></i>Module Management</div>
                <a href="addmodule.php" class="btn btn-dark"><i class="bi bi-plus-circle"></i>Add New</a>
            </div>
            <div class="mb-3">
                <input type="text" id="module-search" class="form-control" placeholder="Search modules...">
            </div>
            <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Image</th>
                        <th>Created_at</th>
                        <th>Updated_at</th>
                        <th>Edit</th>
                    </tr>
                </thead>
                <tbody id="moduleTable">
                    <?php
                    $result2 = $conn->query("SELECT * FROM modules");
                    while ($row = $result2->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                        // Display image preview in admin table
                        $image_path = $row['image_path'];
                        if (filter_var($image_path, FILTER_VALIDATE_URL)) {
                            // External URL
                            echo "<td>
                                    <img src='" . htmlspecialchars($image_path) . "' alt='Module Image' style='width: 50px; height: 50px; object-fit: cover; border-radius: 5px;' onerror='this.style.display=\"none\"; this.nextElementSibling.style.display=\"block\";'>
                                    <span style='display: none; font-size: 0.8em; color: #666;'>External URL</span>
                                  </td>";
                        } else {
                            // Local file
                            echo "<td>
                                    <img src='../../" . htmlspecialchars($image_path) . "' alt='Module Image' style='width: 50px; height: 50px; object-fit: cover; border-radius: 5px;' onerror='this.style.display=\"none\"; this.nextElementSibling.style.display=\"block\";'>
                                    <span style='display: none; font-size: 0.8em; color: #666;'>Local File</span>
                                  </td>";
                        }
                        echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['updated_at']) . "</td>";
                        echo "<td>
                                <a href='editmodule.php?id=" . $row['module_id'] . "' class='btn btn-sm btn-warning'><i class='bi bi-pencil-square'></i>Edit</a>
                              </td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
            </div>
        </section>
        <section id="lesson-management" class="content-section card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="section-title mb-0"><i class="bi bi-book"></i>Lesson Management</div>
                <a href="addlesson.php" class="btn btn-dark"><i class="bi bi-plus-circle"></i>Add New Lesson</a>
            </div>
            <div class="mb-3">
                <input type="text" id="lesson-search" class="form-control" placeholder="Search lessons...">
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Lesson Title</th>
                            <th>Module</th>
                            <th>Order</th>
                            <th>Created_at</th>
                            <th>Edit</th>
                        </tr>
                    </thead>
                    <tbody id="lessonTable">
                        <?php
                        $lessons = $conn->query("SELECT l.*, m.title AS module_title FROM lessons l JOIN modules m ON l.module_id = m.module_id");
                        while ($row = $lessons->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['module_title']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['lesson_order']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
                            echo "<td><a href='editlesson.php?id=" . $row['lesson_id'] . "' class='btn btn-sm btn-warning'><i class='bi bi-pencil-square'></i>Edit</a></td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </section>
        <section id="quiz-management" class="content-section card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="section-title mb-0"><i class="bi bi-question-circle"></i>Quiz Management</div>
                <a href="addquiz.php" class="btn btn-dark"><i class="bi bi-plus-circle"></i>Add New Quiz</a>
            </div>
            <div class="mb-3">
                <input type="text" id="quiz-search" class="form-control" placeholder="Search quizzes...">
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Quiz Title</th>
                        <th>Module</th>
                        <th>Created At</th>
                        <th>Edit</th>
                    </tr>
                </thead>
                    <tbody id="quizTable">
                        <?php
                            $quizzes = $conn->query("
                                SELECT q.quiz_id, q.title, m.title AS module_title, m.created_at
                                FROM module_quizzes q
                                JOIN modules m ON q.module_id = m.module_id
                            ");
                            while ($row = $quizzes->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['module_title']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
                            echo "<td><a href='editquiz.php?id=" . $row['quiz_id'] . "' class='btn btn-sm btn-warning'><i class='bi bi-pencil-square'></i>Edit</a></td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </section>
        <section id="forum-management" class="content-section card p-4">
            <div class="section-title"><i class="bi bi-chat-dots"></i> Forum Management</div>

            <!-- ===== Manage Questions ===== -->
            <h5 class="mb-3"><i class="bi bi-question-circle"></i> Manage Questions</h5>
            <div class="mb-3">
                <input type="text" id="question-search" class="form-control" placeholder="Search questions...">
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Body</th>
                            <th>User</th>
                            <th>Created At</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="questionTable">
                        <?php
                        // Fetch all questions with user data and status
                        $questions = $conn->query("
                            SELECT q.*, u.username 
                            FROM questions q 
                            JOIN users u ON q.user_id = u.user_id 
                            ORDER BY q.created_at DESC
                        ");

                        while ($row = $questions->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['question_id']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['body']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";

                            // Status badge
                            $status = $row['status'] ?? 'pending';
                            $badgeClass = $status === 'approved' ? 'success' : ($status === 'rejected' ? 'danger' : 'warning text-dark');
                            echo "<td><span class='badge bg-$badgeClass text-uppercase'>" . htmlspecialchars($status) . "</span></td>";

                            // Actions
                            echo "<td>";
                            if ($status === 'pending') {
                                echo "
                                    <button class='btn btn-sm btn-success me-1' onclick='updateQuestionStatus(" . $row['question_id'] . ", \"approved\")'>
                                        <i class='bi bi-check-circle'></i> Approve
                                    </button>
                                ";
                            }
                            echo "
                                <a href='deletequestions.php?id=" . $row['question_id'] . "' class='btn btn-sm btn-outline-danger'>
                                    <i class='bi bi-trash'></i> Delete
                                </a>
                            ";
                            echo "</td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- ===== Manage Replies ===== -->
            <h5 class="mb-3 mt-4"><i class="bi bi-reply"></i> Manage Replies</h5>
            <div class="mb-3">
                <input type="text" id="reply-search" class="form-control" placeholder="Search replies...">
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Question ID</th>
                            <th>Body</th>
                            <th>User</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="replyTable">
                        <?php
                        $replies = $conn->query("
                            SELECT r.*, u.username 
                            FROM reply r 
                            JOIN users u ON r.user_id = u.user_id 
                            ORDER BY r.created_at DESC
                        ");
                        while ($row = $replies->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['reply_id']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['question_id']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['body']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
                            echo "<td>
                                    <a href='deletereply.php?id=" . $row['reply_id'] . "' class='btn btn-sm btn-outline-danger'>
                                        <i class='bi bi-trash'></i> Delete
                                    </a>
                                </td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </section>
        <section id="suggestions" class="content-section card p-4">
            <div class="section-title"><i class="bi bi-lightbulb"></i>Suggestions</div>
            <div class="mb-3">
                <input type="text" id="suggestion-search" class="form-control" placeholder="Search suggestions...">
            </div>
            <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Message</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="suggestionTable">
                    <?php
                    $suggestions = $conn->query("SELECT * FROM suggestions");
                    while ($row = $suggestions->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['message']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
                        echo "<td>
                                <select class='form-select' onchange='updateStatus1(this, " . $row['suggestion_id'] . ")'>
                                    <option value='pending'" . ($row['status'] == 'pending' ? ' selected' : '') . ">Pending</option>
                                    <option value='approved'" . ($row['status'] == 'approved' ? ' selected' : '') . ">Approved</option>
                                    <option value='rejected'" . ($row['status'] == 'rejected' ? ' selected' : '') . ">Rejected</option>
                                </select>
                            </td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
            </div>
        </section>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>
    <script>
        document.getElementById('search').addEventListener('keyup', function() {
            var searchValue = this.value.toLowerCase();
            var rows = document.getElementById('userTable').getElementsByTagName('tr');
            for (var i = 0; i < rows.length; i++) {
                var cells = rows[i].getElementsByTagName('td');
                var match = false;
                for (var j = 0; j < cells.length; j++) {
                    if (cells[j].innerText.toLowerCase().includes(searchValue)) {
                        match = true;
                        break;
                    }
                }
                rows[i].style.display = match ? '' : 'none';
            }
        });
        document.getElementById('module-search').addEventListener('keyup', function() {
            var searchValue = this.value.toLowerCase();
            var rows = document.getElementById('moduleTable').getElementsByTagName('tr');
            for (var i = 0; i < rows.length; i++) {
                var cells = rows[i].getElementsByTagName('td');
                var match = false;
                for (var j = 0; j < cells.length; j++) {
                    if (cells[j].innerText.toLowerCase().includes(searchValue)) {
                        match = true;
                        break;
                    }
                }
                rows[i].style.display = match ? '' : 'none';
            }
        });
        document.getElementById('lesson-search').addEventListener('keyup', function() {
            var searchValue = this.value.toLowerCase();
            var rows = document.getElementById('lessonTable').getElementsByTagName('tr');
            for (var i = 0; i < rows.length; i++) {
                var cells = rows[i].getElementsByTagName('td');
                var match = false;
                for (var j = 0; j < cells.length; j++) {
                    if (cells[j].innerText.toLowerCase().includes(searchValue)) {
                        match = true;
                        break;
                    }
                }
                rows[i].style.display = match ? '' : 'none';
            }
        });

        document.getElementById('quiz-search').addEventListener('keyup', function() {
        var searchValue = this.value.toLowerCase();
        var rows = document.getElementById('quizTable').getElementsByTagName('tr');
        for (var i = 0; i < rows.length; i++) {
            var cells = rows[i].getElementsByTagName('td');
            var match = false;
            for (var j = 0; j < cells.length; j++) {
                if (cells[j].innerText.toLowerCase().includes(searchValue)) {
                    match = true;
                    break;
                }
            }
            rows[i].style.display = match ? '' : 'none';
        }
    });

        function updateUser(select, userId, field) {
            var value = select.value;
            var xhr = new XMLHttpRequest();
            xhr.open("GET", "updateuser.php?id=" + userId + "&field=" + field + "&value=" + value, true);
            xhr.onreadystatechange = function () {
                if (xhr.readyState == 4) {
                    if (xhr.status == 200) {
                        showToast(field.charAt(0).toUpperCase() + field.slice(1) + " updated successfully.", 'success');
                        setTimeout(function() { location.reload(); }, 1000); // Refresh after 1 second
                    } else {
                        showToast("Error updating " + field + ": " + xhr.responseText, 'danger');
                    }
                }
            };
            xhr.send();
        }
        document.getElementById('question-search').addEventListener('keyup', function() {
            var searchValue = this.value.toLowerCase();
            var rows = document.getElementById('questionTable').getElementsByTagName('tr');
            for (var i = 0; i < rows.length; i++) {
                var cells = rows[i].getElementsByTagName('td');
                var match = false;
                for (var j = 0; j < cells.length; j++) {
                    if (cells[j].innerText.toLowerCase().includes(searchValue)) {
                        match = true;
                        break;
                    }
                }
                rows[i].style.display = match ? '' : 'none';
            }
        });
        document.getElementById('reply-search').addEventListener('keyup', function() {
            var searchValue = this.value.toLowerCase();
            var rows = document.getElementById('replyTable').getElementsByTagName('tr');
            for (var i = 0; i < rows.length; i++) {
                var cells = rows[i].getElementsByTagName('td');
                var match = false;
                for (var j = 0; j < cells.length; j++) {
                    if (cells[j].innerText.toLowerCase().includes(searchValue)) {
                        match = true;
                        break;
                    }
                }
                rows[i].style.display = match ? '' : 'none';
            }
        });
        document.getElementById('suggestion-search').addEventListener('keyup', function() {
            var searchValue = this.value.toLowerCase();
            var rows = document.getElementById('suggestionTable').getElementsByTagName('tr');
            for (var i = 0; i < rows.length; i++) {
                var cells = rows[i].getElementsByTagName('td');
                var match = false;
                for (var j = 0; j < cells.length; j++) {
                    if (cells[j].innerText.toLowerCase().includes(searchValue)) {
                        match = true;
                        break;
                    }
                }
                rows[i].style.display = match ? '' : 'none';
            }
        });
        function updateQuestionStatus(questionId, status) {
            if (!confirm(`Are you sure you want to mark this question as "${status}"?`)) return;

            fetch('../learning/api/update_question_status.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id: questionId, status: status })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // If approved, go back to Forum Management section
                    if (status === 'approved') {
                        window.location.href = 'adminpage.php#forum-management';
                        setTimeout(() => location.reload(), 300);
                    } else {
                        location.reload();
                    }

                } else {
                    alert('❌ Failed to update question status.\n' + (data.message || 'Unknown error.'));
                }
            })
            .catch(err => {
                console.error(err);
                alert('⚠️ Network error.');
            });
        }
        function showSection(sectionId) {
            var sections = document.querySelectorAll('.content-section');
            sections.forEach(function(section) {
                section.classList.remove('active');
            });
            var activeSection = document.getElementById(sectionId);
            activeSection.classList.add('active');
        }
        function setActiveNav(element) {
            document.querySelectorAll('.admin-nav .nav-link').forEach(function(nav) {
                nav.classList.remove('active');
            });
            element.classList.add('active');
        }
        window.onload = function() {
            // Check if there's a hash in the URL to determine which section to show
            var hash = window.location.hash.substring(1);
            if (hash && document.getElementById(hash)) {
                showSection(hash);
                // Set the corresponding nav link as active
                var navId = 'nav-' + hash.replace('-management', '');
                if (document.getElementById(navId)) {
                    setActiveNav(document.getElementById(navId));
                }
            } else {
                showSection('user-management');
                setActiveNav(document.getElementById('nav-user'));
            }
        };
        function updateStatus1(select, suggestionId) {
            var newStatus = select.value;
            var formData = new FormData();
            formData.append('suggestion_id', suggestionId);
            formData.append('status', newStatus);
            fetch('suggestionstatus.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                if (data === 'success') {
                    showToast('Status updated successfully!', 'success');
                } else {
                    showToast('Error updating status.', 'danger');
                }
            })
            .catch(error => showToast('Error: ' + error, 'danger'));
        }
        // Confirmation dialog for user deletion
        function confirmDelete(username, userId) {
            if (confirm('Are you sure you want to delete user "' + username + '"?\n\nThis action will permanently delete:\n• User account\n• All user questions and replies\n• All user favorites\n\nThis action cannot be undone.')) {
                window.location.href = 'deleteuser.php?id=' + userId;
                return true;
            }
            return false;
        }
        
        // Toast function
        function showToast(message, type) {
            var toastContainer = document.getElementById('toast-container');
            var toastId = 'toast-' + Date.now();
            var toast = document.createElement('div');
            toast.className = 'toast align-items-center text-bg-' + (type === 'danger' ? 'danger' : 'success') + ' border-0 show';
            toast.id = toastId;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close" onclick="document.getElementById('${toastId}').remove();"></button>
                </div>
            `;
            toastContainer.appendChild(toast);
            setTimeout(function() {
                if (document.getElementById(toastId)) {
                    document.getElementById(toastId).remove();
                }
            }, 3500);
        }
        // Hamburger menu toggle
        document.getElementById('adminNavToggle').addEventListener('click', function() {
            var nav = document.getElementById('adminNav');
            nav.classList.toggle('show');
        });
        
        // Handle hash changes (for browser back/forward buttons)
        window.addEventListener('hashchange', function() {
            var hash = window.location.hash.substring(1);
            if (hash && document.getElementById(hash)) {
                showSection(hash);
                var navId = 'nav-' + hash.replace('-management', '');
                if (document.getElementById(navId)) {
                    setActiveNav(document.getElementById(navId));
                }
            }
        });
    </script>
</body>
</html>
