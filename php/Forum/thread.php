<?php
session_start();
include "../connection.php";
include "moderation_helpers.php";
include "forum_reports_bootstrap.php";
include "thread_bootstrap.php";
include "../notifications/notifications_helper.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$currentRole = $_SESSION['role'] ?? 'student';

function role_label(string $role): string
{
    return match ($role) {
        'agriculturist' => 'Agriculturist',
        'admin' => 'Admin',
        default => 'Learner',
    };
}

function is_expert_role(string $role): bool
{
    return in_array($role, ['agriculturist', 'admin'], true);
}

if (!isset($_GET['id'])) {
    echo "No discussion selected.";
    exit;
}

$question_id = (int) $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $reply_body = trim($_POST['reply'] ?? '');
    $user_id = (int) $_SESSION['user_id'];

    if ($reply_body !== '') {
        $moderationError = forum_validate_clean_text([
            'reply' => $reply_body,
        ]);
        if ($moderationError !== null) {
            header("Location: thread.php?id=" . $question_id . "&reply_error=" . urlencode($moderationError));
            exit;
        }
        $insert_reply = $conn->prepare("INSERT INTO reply (question_id, user_id, body) VALUES (?, ?, ?)");
        $insert_reply->bind_param("iis", $question_id, $user_id, $reply_body);
        if ($insert_reply->execute()) {
            $ownerStmt = $conn->prepare("
                SELECT q.user_id, q.title
                FROM questions q
                WHERE q.question_id = ?
            ");
            $ownerStmt->bind_param("i", $question_id);
            $ownerStmt->execute();
            $ownerRow = $ownerStmt->get_result()->fetch_assoc();
            $ownerStmt->close();

            if ($ownerRow && (int) $ownerRow['user_id'] !== $user_id) {
                $type = in_array($currentRole, ['agriculturist', 'admin'], true) ? 'expert_reply' : 'thread_reply';
                $message = in_array($currentRole, ['agriculturist', 'admin'], true)
                    ? 'An expert replied to your thread "' . $ownerRow['title'] . '".'
                    : 'Someone replied to your thread "' . $ownerRow['title'] . '".';
                create_notification(
                    $conn,
                    (int) $ownerRow['user_id'],
                    $type,
                    $message,
                    '../Forum/thread.php?id=' . $question_id
                );
            }
            header("Location: thread.php?id=" . $question_id);
            exit;
        }
        $insert_reply->close();
    }
}

$threadStmt = $conn->prepare("
    SELECT
        q.title,
        q.body,
        q.created_at,
        u.username,
        u.name,
        u.profile_picture,
        u.role,
        q.best_reply_id
    FROM questions q
    JOIN users u ON q.user_id = u.user_id
    WHERE q.question_id = ?
");
$threadStmt->bind_param("i", $question_id);
$threadStmt->execute();
$thread = $threadStmt->get_result()->fetch_assoc();
$threadStmt->close();

if (!$thread) {
    echo "Discussion not found.";
    exit;
}

$replyStmt = $conn->prepare("
    SELECT
        r.reply_id,
        r.body,
        r.created_at,
        u.username,
        u.name,
        u.profile_picture,
        u.role
    FROM reply r
    JOIN users u ON r.user_id = u.user_id
    WHERE r.question_id = ?
    ORDER BY r.created_at ASC
");
$replyStmt->bind_param("i", $question_id);
$replyStmt->execute();
$replies = $replyStmt->get_result();

$replyRows = [];
$expertReplyCount = 0;
$bestReplyId = (int) ($thread['best_reply_id'] ?? 0);
while ($reply = $replies->fetch_assoc()) {
    if (is_expert_role((string) $reply['role'])) {
        $expertReplyCount++;
    }
    $reply['is_best_reply'] = ((int) $reply['reply_id'] === $bestReplyId);
    $replyRows[] = $reply;
}
$replyStmt->close();

usort($replyRows, static function (array $left, array $right): int {
    if (($left['is_best_reply'] ?? false) === ($right['is_best_reply'] ?? false)) {
        return strtotime($left['created_at']) <=> strtotime($right['created_at']);
    }

    return ($left['is_best_reply'] ?? false) ? -1 : 1;
});

$replyTotal = count($replyRows);
$hasExpertReply = $expertReplyCount > 0;
$replyError = $_GET['reply_error'] ?? '';
$reportStatus = $_GET['report'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discussion - <?= htmlspecialchars($thread['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/homepage.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../css/thread.css">
</head>
<body class="<?= in_array($currentRole, ['agriculturist', 'admin'], true) ? 'agri-dashboard-body' : '' ?>">
<?php include '../navbar.php'; ?>

<?php if (in_array($currentRole, ['agriculturist', 'admin'], true)): ?>
<div class="agri-sidebar">
    <img src="../../images/clearteenalogo.png" class="avatar" alt="User Avatar">
    <div class="welcome"><?= $currentRole === 'admin' ? 'Admin Hub' : 'Agriculturist Hub' ?></div>
    <div class="agri-role-note"><?= htmlspecialchars($_SESSION['username'] ?? '') ?></div>
    <nav class="nav flex-column w-100 mt-3">
        <?php if ($currentRole === 'admin'): ?>
            <a class="nav-link" href="../Admin/adminpage.php"><i class="bi bi-speedometer2"></i> Admin Dashboard</a>
        <?php else: ?>
            <a class="nav-link" href="../Admin/agriculturistpage.php?section=dashboard"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
            <a class="nav-link" href="../Admin/agriculturistpage.php?section=profile"><i class="bi bi-person-circle"></i> Profile</a>
            <a class="nav-link" href="../Admin/agriculturistpage.php?section=updates"><i class="bi bi-megaphone-fill"></i> Community Updates</a>
            <a class="nav-link" href="../Admin/agriculturistpage.php?section=settings"><i class="bi bi-gear"></i> Settings</a>
        <?php endif; ?>
        <a class="nav-link active" href="community.php"><i class="bi bi-people-fill"></i> Farming Community</a>
    </nav>
</div>
<?php endif; ?>

    <main class="container my-4 <?= in_array($currentRole, ['agriculturist', 'admin'], true) ? 'agri-main-content agri-dashboard-shell' : '' ?>">
        <div class="thread-topbar">
            <a href="community.php" class="back-link"><i class="bi bi-arrow-left"></i> Back to Community</a>
            <a href="#replyForm" class="quick-reply-link"><i class="bi bi-reply"></i> Jump to Reply</a>
        </div>

        <?php if ($reportStatus === 'submitted'): ?>
            <div class="alert alert-success">Report submitted. Moderators can now review this content.</div>
        <?php elseif ($reportStatus === 'duplicate'): ?>
            <div class="alert alert-info">You already reported this content.</div>
        <?php endif; ?>

        <div class="thread-card mb-4">
            <div class="thread-header">
                <div class="thread-avatar">
                    <img src="<?= !empty($thread['profile_picture']) ? '../../images/profile_pics/' . htmlspecialchars($thread['profile_picture']) : '../../images/clearteenalogo.png' ?>"
                         alt="User Avatar"
                         style="width:100%;height:100%;object-fit:cover;border-radius:50%;"
                         onerror="this.onerror=null;this.src='../../images/clearteenalogo.png';">
                </div>
                <div class="thread-heading">
                    <div class="thread-title"><?= htmlspecialchars($thread['title']) ?></div>
                    <div class="thread-meta">
                        <span>Posted by <?= htmlspecialchars($thread['name'] ?: $thread['username']) ?></span>
                        <span class="role-badge role-<?= htmlspecialchars($thread['role']) ?>"><?= role_label((string) $thread['role']) ?></span>
                        <span><?= date('M d, Y g:i A', strtotime($thread['created_at'])) ?></span>
                    </div>
                </div>
            </div>
            <div class="thread-body">
                <?= nl2br(htmlspecialchars($thread['body'])) ?>
            </div>
            <div class="thread-footer-actions">
                <button type="button"
                        class="btn btn-outline-secondary btn-sm report-trigger"
                        data-bs-toggle="modal"
                        data-bs-target="#reportContentModal"
                        data-target-type="question"
                        data-target-id="<?= $question_id ?>"
                        data-target-label="thread">
                    <i class="bi bi-flag"></i> Report Thread
                </button>
            </div>
        </div>

        <div class="thread-insights">
            <div class="thread-insight">
                <span class="insight-value"><?= $replyTotal ?></span>
                <span class="insight-label"><?= $replyTotal === 1 ? 'Reply' : 'Replies' ?></span>
            </div>
            <div class="thread-insight">
                <span class="insight-value"><?= $expertReplyCount ?></span>
                <span class="insight-label">Expert Replies</span>
            </div>
            <div class="thread-insight <?= $hasExpertReply ? 'is-positive' : 'is-neutral' ?>">
                <span class="insight-value"><?= $hasExpertReply ? 'Yes' : 'No' ?></span>
                <span class="insight-label">Expert Response</span>
            </div>
        </div>

        <div class="reply-count">
            <?= $replyTotal ?> <?= $replyTotal === 1 ? 'Reply' : 'Replies' ?>
            <?php if ($hasExpertReply): ?>
                <span class="reply-count-note">Agriculturist/admin guidance is included in this thread.</span>
            <?php elseif ($replyTotal > 0): ?>
                <span class="reply-count-note">Community responses are available.</span>
            <?php else: ?>
                <span class="reply-count-note">No replies yet. Be the first to help.</span>
            <?php endif; ?>
        </div>

        <div class="replies-container">
            <?php if ($replyTotal > 0): ?>
                <?php foreach ($replyRows as $reply): ?>
                    <?php $isExpertReply = is_expert_role((string) $reply['role']); ?>
                    <?php $isBestReply = !empty($reply['is_best_reply']); ?>
                    <div class="reply-card-container">
                        <div class="reply-card <?= $isExpertReply ? 'reply-card-expert' : '' ?> <?= $isBestReply ? 'reply-card-best' : '' ?>">
                            <div class="reply-avatar">
                                <img src="<?= !empty($reply['profile_picture']) ? '../../images/profile_pics/' . htmlspecialchars($reply['profile_picture']) : '../../images/clearteenalogo.png' ?>"
                                     alt="User Avatar"
                                     style="width:100%;height:100%;object-fit:cover;border-radius:50%;"
                                     onerror="this.onerror=null;this.src='../../images/clearteenalogo.png';">
                            </div>
                            <div class="reply-content">
                                <div class="reply-meta">
                                    <span><?= htmlspecialchars($reply['name'] ?: $reply['username']) ?></span>
                                    <span class="role-badge role-<?= htmlspecialchars($reply['role']) ?>"><?= role_label((string) $reply['role']) ?></span>
                                    <?php if ($isBestReply): ?>
                                        <span class="role-badge role-best"><i class="bi bi-patch-check-fill me-1"></i>Best Answer</span>
                                    <?php endif; ?>
                                    <span class="reply-date"><?= date('M d, Y g:i A', strtotime($reply['created_at'])) ?></span>
                                </div>
                                <div class="reply-text"><?= nl2br(htmlspecialchars($reply['body'])) ?></div>
                                <?php if (($currentRole === 'agriculturist' || $currentRole === 'admin') && $isExpertReply): ?>
                                    <div class="reply-action-row">
                                        <a href="mark_best_reply.php?thread_id=<?= $question_id ?>&reply_id=<?= (int) $reply['reply_id'] ?>"
                                           class="btn btn-sm <?= $isBestReply ? 'btn-outline-success' : 'btn-success' ?>">
                                            <?= $isBestReply ? 'Highlighted Answer' : 'Highlight as Best Answer' ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <button type="button"
                                    class="reply-report"
                                    data-bs-toggle="modal"
                                    data-bs-target="#reportContentModal"
                                    data-target-type="reply"
                                    data-target-id="<?= (int) $reply['reply_id'] ?>"
                                    data-target-label="reply">
                                <i class="bi bi-flag"></i>
                            </button>
                            <?php if ($currentRole === 'agriculturist' || $currentRole === 'admin'): ?>
                                <a href="delete.php?type=reply&id=<?= $reply['reply_id'] ?>"
                                   class="reply-delete"
                                   onclick="return confirm('Are you sure you want to delete this reply?');">
                                    <i class="bi bi-x"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="thread-empty-state">
                    <div class="empty-icon"><i class="bi bi-chat-left-text"></i></div>
                    <h4>No replies yet</h4>
                    <p>Start the conversation with a clear, helpful reply so this thread becomes useful to the next learner too.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="post-reply-card">
            <div class="card-header">
                <h6>Post a Reply</h6>
            </div>
            <div class="card-body">
                <?php if ($replyError !== ''): ?>
                    <div class="alert alert-danger mb-3"><?= htmlspecialchars($replyError) ?></div>
                <?php endif; ?>
                <form method="POST" id="replyForm">
                    <div class="mb-3">
                        <textarea name="reply" class="form-control" rows="4" maxlength="3000" placeholder="Write your reply..." required></textarea>
                    </div>
                    <div class="reply-form-actions">
                        <div class="reply-form-note">Be specific. Mention what the user should check, change, or try next. Inappropriate language is blocked and reported content can be reviewed by moderators.</div>
                        <button type="submit" class="btn btn-success">Submit Reply</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <div class="modal fade" id="reportContentModal" tabindex="-1" aria-labelledby="reportContentModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <form class="modal-content" action="report_content.php" method="POST">
          <div class="modal-header">
            <h5 class="modal-title" id="reportContentModalLabel">Report Content</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="target_type" id="reportTargetType">
            <input type="hidden" name="target_id" id="reportTargetId">
            <input type="hidden" name="thread_id" value="<?= $question_id ?>">
            <p class="mb-2">You are reporting this <span id="reportTargetLabel">content</span>.</p>
            <label for="reportReason" class="form-label">Reason</label>
            <select class="form-select" id="reportReason" name="reason" required>
              <option value="">Select a reason</option>
              <option value="Profanity or abusive language">Profanity or abusive language</option>
              <option value="Spam or irrelevant content">Spam or irrelevant content</option>
              <option value="Misleading or harmful advice">Misleading or harmful advice</option>
              <option value="Other moderation concern">Other moderation concern</option>
            </select>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-danger">Submit Report</button>
          </div>
        </form>
      </div>
    </div>

    <footer class="bg-success text-center text-white py-3 mt-auto">
      <p class="mb-0">&copy; 2024 Farming Community. All rights reserved.</p>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.querySelectorAll('.report-trigger, .reply-report').forEach((button) => {
        button.addEventListener('click', () => {
            document.getElementById('reportTargetType').value = button.getAttribute('data-target-type');
            document.getElementById('reportTargetId').value = button.getAttribute('data-target-id');
            document.getElementById('reportTargetLabel').textContent = button.getAttribute('data-target-label');
        });
    });
    </script>
</body>
</html>
