<?php
session_start();
include "../connection.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); 
    exit;
}

if (isset($_GET['id'])) {
    $question_id = intval($_GET['id']);

    $stmt = $conn->prepare("SELECT q.title, q.body, q.created_at, u.username, u.profile_picture FROM Questions q JOIN Users u ON q.user_id = u.user_id WHERE q.question_id = ?");
    $stmt->bind_param("i", $question_id);
    $stmt->execute();
    $stmt->bind_result($title, $body, $created_at, $poster_username, $poster_profile_picture);
    $stmt->fetch();
    $stmt->close();

    $reply_stmt = $conn->prepare("SELECT r.reply_id, r.body, r.created_at, u.username, u.profile_picture FROM reply r JOIN Users u ON r.user_id = u.user_id WHERE r.question_id = ? ORDER BY r.created_at ASC");
    $reply_stmt->bind_param("i", $question_id);
    $reply_stmt->execute();
    $replies = $reply_stmt->get_result();
} else {
    echo "No discussion selected.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $reply_body = $_POST['reply'];
    $user_id = $_SESSION['user_id'];

    $insert_reply = $conn->prepare("INSERT INTO reply (question_id, user_id, body) VALUES (?, ?, ?)");
    $insert_reply->bind_param("iis", $question_id, $user_id, $reply_body);

    if ($insert_reply->execute()) {
        header("Location: thread.php?id=" . $question_id);
        exit;
    } else {
        echo "Error: " . $conn->error;
    }
    $insert_reply->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discussion - <?= htmlspecialchars($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/homepage.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../css/thread.css">
</head>
<body>
<?php include '../navbar.php'; ?>
    <main class="container my-4">
        <div class="thread-card mb-4">
            <div class="thread-header">
                <div class="thread-avatar">
                  <img src="<?= !empty($poster_profile_picture) ? '../../images/profile_pics/' . htmlspecialchars($poster_profile_picture) : '../../images/clearteenalogo.png' ?>" alt="User Avatar" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" onerror="this.onerror=null;this.src='../../images/clearteenalogo.png';">
                </div>
                <div>
                    <div class="thread-title"><?= htmlspecialchars($title) ?></div>
                    <div class="thread-meta">Posted by <?= htmlspecialchars($poster_username) ?> on <?= $created_at ?></div>
                </div>
            </div>
            <div class="thread-body">
                <?= nl2br(htmlspecialchars($body)) ?>
            </div>
        </div>

        <div class="reply-count">
            <?php $reply_total = isset($replies) ? $replies->num_rows : 0; ?>
            <?= $reply_total ?> <?= $reply_total === 1 ? 'Reply' : 'Replies' ?>
        </div>

        <div class="replies-container">
            <?php if ($reply_total > 0): ?>
                <?php while ($reply = $replies->fetch_assoc()): ?>
                    <div class="reply-card-container">
                      <div class="reply-card">
                        <div class="reply-avatar">
                          <img src="<?= !empty($reply['profile_picture']) ? '../../images/profile_pics/' . htmlspecialchars($reply['profile_picture']) : '../../images/clearteenalogo.png' ?>" alt="User Avatar" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" onerror="this.onerror=null;this.src='../../images/clearteenalogo.png';">
                        </div>
                        <div class="reply-content">
                          <div class="reply-meta"><?= htmlspecialchars($reply['username']) ?> <span style="font-weight:400;color:#888;font-size:0.93em;">• <?= date('M d, Y', strtotime($reply['created_at'])) ?></span></div>
                          <div class="reply-text">
                            <?= htmlspecialchars($reply['body']) ?>
                          </div>
                        </div>
                        <?php if ($_SESSION['role'] === 'agriculturist'): ?>
                            <a href="delete.php?type=reply&id=<?= $reply['reply_id'] ?>" 
                            class="reply-delete"
                            onclick="return confirm('Are you sure you want to delete this reply?');">
                                <i class="bi bi-x"></i>
                            </a>
                        <?php endif; ?>
                      </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-muted mb-4">No replies yet. Be the first to reply!</div>
            <?php endif; ?>
        </div>

        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="post-reply-card">
                <div class="card-header">
                    <h6>Post a Reply</h6>
                </div>
                <div class="card-body">
                    <form method="POST" id="replyForm">
                        <div class="mb-3">
                            <textarea name="reply" class="form-control" rows="4" placeholder="Write your reply..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-success">Submit Reply</button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <p class="text-danger">You must <a href="../login.php">log in</a> to post a reply.</p>
        <?php endif; ?>
    </main>

    <footer class="bg-success text-center text-white py-3 mt-auto">
      <p class="mb-0">&copy; 2024 Farming Community. All rights reserved.</p>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Smooth scroll to reply form on submit
    document.getElementById('replyForm')?.addEventListener('submit', function(e) {
        setTimeout(function() {
            document.getElementById('replyForm').scrollIntoView({ behavior: 'smooth' });
        }, 100);
    });
    </script>
</body>
</html>
