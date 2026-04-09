<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include "../connection.php";
include "community_updates_bootstrap.php";

$currentUserId = (int) $_SESSION['user_id'];
$currentRole = $_SESSION['role'] ?? 'student';
$isForumRestricted = (isset($_SESSION['status']) && $_SESSION['status'] === 'inactive');

$search = trim($_GET['q'] ?? '');
$filter = $_GET['filter'] ?? 'all';
$allowedFilters = ['all', 'my_posts', 'unanswered', 'expert'];
if (!in_array($filter, $allowedFilters, true)) {
    $filter = 'all';
}

$summary = [
    'approved_threads' => 0,
    'my_threads' => 0,
    'unanswered_threads' => 0,
    'pending_threads' => 0,
];

$result = null;
$pendingThreads = [];
$communityUpdates = [];

if (!$isForumRestricted) {
    $summarySql = "
        SELECT
            COUNT(*) AS approved_threads,
            SUM(CASE WHEN q.user_id = ? THEN 1 ELSE 0 END) AS my_threads,
            SUM(CASE WHEN q.user_id = ? AND q.status = 'pending' THEN 1 ELSE 0 END) AS pending_threads,
            SUM(
                CASE
                    WHEN (
                        SELECT COUNT(*)
                        FROM reply reply_count
                        WHERE reply_count.question_id = q.question_id
                    ) = 0 THEN 1 ELSE 0
                END
            ) AS unanswered_threads
        FROM questions q
        WHERE q.status = 'approved'
    ";
    $summaryStmt = $conn->prepare($summarySql);
    $summaryStmt->bind_param("ii", $currentUserId, $currentUserId);
    $summaryStmt->execute();
    $summaryResult = $summaryStmt->get_result();
    if ($summaryRow = $summaryResult->fetch_assoc()) {
        $summary = [
            'approved_threads' => (int) ($summaryRow['approved_threads'] ?? 0),
            'my_threads' => (int) ($summaryRow['my_threads'] ?? 0),
            'unanswered_threads' => (int) ($summaryRow['unanswered_threads'] ?? 0),
            'pending_threads' => (int) ($summaryRow['pending_threads'] ?? 0),
        ];
    }
    $summaryStmt->close();

    $pendingStmt = $conn->prepare("
        SELECT question_id, title, body, created_at
        FROM questions
        WHERE user_id = ? AND status = 'pending'
        ORDER BY created_at DESC
    ");
    $pendingStmt->bind_param("i", $currentUserId);
    $pendingStmt->execute();
    $pendingResult = $pendingStmt->get_result();
    while ($pendingRow = $pendingResult->fetch_assoc()) {
        $pendingThreads[] = $pendingRow;
    }
    $pendingStmt->close();

    $sql = "
        SELECT
            q.question_id,
            q.title,
            q.body,
            q.created_at,
            u.user_id,
            u.username,
            u.name,
            u.profile_picture,
            u.role,
            COUNT(DISTINCT r.reply_id) AS reply_count,
            MAX(COALESCE(r.created_at, q.created_at)) AS last_activity_at,
            SUM(CASE WHEN reply_author.role IN ('agriculturist', 'admin') THEN 1 ELSE 0 END) AS expert_reply_count
        FROM questions q
        JOIN users u ON q.user_id = u.user_id
        LEFT JOIN reply r ON q.question_id = r.question_id
        LEFT JOIN users reply_author ON r.user_id = reply_author.user_id
        WHERE q.status = 'approved'
    ";

    $params = [];
    $types = '';

    if ($search !== '') {
        $sql .= " AND (q.title LIKE ? OR q.body LIKE ? OR u.name LIKE ? OR u.username LIKE ?)";
        $searchParam = '%' . $search . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= 'ssss';
    }

    if ($filter === 'my_posts') {
        $sql .= " AND q.user_id = ?";
        $params[] = $currentUserId;
        $types .= 'i';
    }

    $sql .= "
        GROUP BY
            q.question_id, q.title, q.body, q.created_at,
            u.user_id, u.username, u.name, u.profile_picture, u.role
    ";

    if ($filter === 'unanswered') {
        $sql .= " HAVING COUNT(DISTINCT r.reply_id) = 0";
    } elseif ($filter === 'expert') {
        $sql .= " HAVING SUM(CASE WHEN reply_author.role IN ('agriculturist', 'admin') THEN 1 ELSE 0 END) > 0";
    }

    $sql .= " ORDER BY last_activity_at DESC, q.created_at DESC LIMIT 20";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
    }

    $updatesQuery = "
        SELECT cu.*, u.name, u.username, u.role
        FROM community_updates cu
        JOIN users u ON cu.user_id = u.user_id
        ORDER BY cu.is_pinned DESC, cu.created_at DESC
        LIMIT 6
    ";
    $updatesResult = $conn->query($updatesQuery);
    if ($updatesResult) {
        while ($updateRow = $updatesResult->fetch_assoc()) {
            $communityUpdates[] = $updateRow;
        }
    }
}

function role_label(string $role): string
{
    return match ($role) {
        'agriculturist' => 'Agriculturist',
        'admin' => 'Admin',
        default => 'Learner',
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Farming Community</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../../css/homepage.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
  <link rel="stylesheet" href="../../css/community.css">
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

<div class="main-content <?= in_array($currentRole, ['agriculturist', 'admin'], true) ? 'agri-main-content' : '' ?>">
  <div class="container py-4 <?= in_array($currentRole, ['agriculturist', 'admin'], true) ? 'agri-dashboard-shell' : '' ?>">
    <div class="row justify-content-center g-4">
      <div class="col-12 col-xl-9 mx-auto">
        <div class="community-header mb-4" data-aos="fade-down">
          <div>
            <h2 class="mb-1">
              <i class="bi bi-people-fill me-2"></i>Farming Community
            </h2>
            <p class="mb-0">Ask questions, follow expert replies, and learn from real discussions.</p>
            <div class="community-onboarding-note">New threads are submitted for approval first. Replies should stay respectful and can be reported for moderator review.</div>
          </div>
          <?php if (!$isForumRestricted): ?>
            <div class="community-summary">
              <div class="summary-chip">
                <span class="summary-value"><?= $summary['approved_threads'] ?></span>
                <span class="summary-label">Approved Threads</span>
              </div>
              <div class="summary-chip">
                <span class="summary-value"><?= $summary['my_threads'] ?></span>
                <span class="summary-label">My Posts</span>
              </div>
              <div class="summary-chip">
                <span class="summary-value"><?= $summary['pending_threads'] ?></span>
                <span class="summary-label">Pending</span>
              </div>
              <div class="summary-chip">
                <span class="summary-value"><?= $summary['unanswered_threads'] ?></span>
                <span class="summary-label">Need Replies</span>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <?php if ($isForumRestricted): ?>
          <div class="restricted-card text-center" data-aos="fade-up">
            <div class="restricted-icon"><i class="bi bi-shield-lock"></i></div>
            <h3>Community Access Restricted</h3>
            <p>Your account cannot post or view forum discussions right now. Contact an admin or agriculturist if you think this is a mistake.</p>
          </div>
        <?php else: ?>

          <button class="btn btn-success w-100 mb-3 py-3 fs-5 fw-bold"
                  type="button"
                  data-bs-toggle="collapse"
                  data-bs-target="#newDiscussionForm">
            <i class="bi bi-chat-dots me-2"></i>Start a New Discussion
          </button>

          <div class="collapse" id="newDiscussionForm">
            <div class="card discussion-form-card mb-4" data-aos="fade-up">
              <div class="card-header">
                <i class="bi bi-chat-dots me-2"></i>Start a New Discussion
              </div>
              <div class="card-body">
                <form id="questionForm" method="POST">
                  <div class="mb-3">
                    <label class="form-label">Thread Title</label>
                    <input type="text" name="title" class="form-control" maxlength="150" required>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Message</label>
                    <textarea name="body" class="form-control" rows="5" maxlength="3000" required></textarea>
                  </div>
                  <div class="form-hint">Clear titles and specific details make it easier for agriculturists and other learners to help.</div>
                  <button type="submit" class="btn btn-success mt-3">Create Thread</button>
                </form>
              </div>
            </div>
          </div>

          <div id="questionMessage" class="alert d-none mt-3"></div>

          <?php if (!empty($communityUpdates)): ?>
            <div class="card community-updates-card mb-4" data-aos="fade-up">
              <div class="card-header">
                <i class="bi bi-megaphone-fill me-2"></i>Agriculturist Updates
              </div>
              <div class="card-body">
                <div class="community-update-list">
                  <?php foreach ($communityUpdates as $update): ?>
                    <article class="community-update-item">
                      <div class="community-update-meta">
                        <?php if (!empty($update['is_pinned'])): ?>
                          <span class="community-update-badge">Featured Update</span>
                        <?php endif; ?>
                        <span class="role-badge role-<?= htmlspecialchars($update['role']) ?>"><?= role_label((string) $update['role']) ?></span>
                        <span class="discussion-username"><?= htmlspecialchars($update['name'] ?: $update['username']) ?></span>
                        <span class="discussion-meta-text"><?= date('M d, Y g:i A', strtotime($update['created_at'])) ?></span>
                      </div>
                      <h4><?= htmlspecialchars($update['title']) ?></h4>
                      <p><?= nl2br(htmlspecialchars($update['body'])) ?></p>
                      <?php if (!empty($update['image_url'])): ?>
                        <img src="<?= htmlspecialchars($update['image_url']) ?>" alt="Community update image" class="community-update-image" onerror="this.style.display='none'">
                      <?php endif; ?>
                      <?php if (!empty($update['external_url'])): ?>
                        <a href="<?= htmlspecialchars($update['external_url']) ?>" target="_blank" rel="noopener noreferrer" class="community-update-link">
                          <i class="bi bi-box-arrow-up-right"></i> View linked resource
                        </a>
                      <?php endif; ?>
                    </article>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          <?php endif; ?>

          <div class="card pending-threads-card mb-4" data-aos="fade-up">
            <div class="card-header">
              <i class="bi bi-hourglass-split me-2"></i>My Pending Threads
            </div>
            <div class="card-body">
              <p class="pending-note mb-3">Pending threads are waiting for admin approval. You can still edit or delete them before they are published.</p>
              <div id="pendingThreadMessage" class="alert d-none mb-3"></div>

              <?php if (!empty($pendingThreads)): ?>
                <div class="pending-thread-list">
                  <?php foreach ($pendingThreads as $pendingThread): ?>
                    <div class="pending-thread-item" id="pending-thread-<?= (int) $pendingThread['question_id'] ?>">
                      <div class="pending-thread-head">
                        <div>
                          <h5><?= htmlspecialchars($pendingThread['title']) ?></h5>
                          <div class="pending-thread-meta">
                            Submitted <?= date('M d, Y g:i A', strtotime($pendingThread['created_at'])) ?>
                            <span class="meta-badge meta-badge-warning">Pending Approval</span>
                          </div>
                        </div>
                        <div class="pending-thread-actions">
                          <button type="button"
                                  class="btn btn-outline-success btn-sm js-toggle-pending-editor"
                                  data-target="pending-editor-<?= (int) $pendingThread['question_id'] ?>">
                            Edit
                          </button>
                          <button type="button"
                                  class="btn btn-outline-danger btn-sm js-delete-pending-thread"
                                  data-question-id="<?= (int) $pendingThread['question_id'] ?>">
                            Delete
                          </button>
                        </div>
                      </div>

                      <p class="pending-thread-preview"><?= nl2br(htmlspecialchars($pendingThread['body'])) ?></p>

                      <form class="pending-editor d-none js-pending-editor-form"
                            id="pending-editor-<?= (int) $pendingThread['question_id'] ?>">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="question_id" value="<?= (int) $pendingThread['question_id'] ?>">
                        <div class="mb-3">
                          <label class="form-label">Edit Title</label>
                          <input type="text" name="title" class="form-control" maxlength="150" value="<?= htmlspecialchars($pendingThread['title']) ?>" required>
                        </div>
                        <div class="mb-3">
                          <label class="form-label">Edit Message</label>
                          <textarea name="body" class="form-control" rows="5" maxlength="3000" required><?= htmlspecialchars($pendingThread['body']) ?></textarea>
                        </div>
                        <div class="pending-editor-actions">
                          <button type="submit" class="btn btn-success btn-sm">Save Changes</button>
                          <button type="button"
                                  class="btn btn-light btn-sm js-toggle-pending-editor"
                                  data-target="pending-editor-<?= (int) $pendingThread['question_id'] ?>">
                            Cancel
                          </button>
                        </div>
                      </form>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <div class="community-empty-state compact-empty-state">
                  <div class="empty-icon"><i class="bi bi-send-check"></i></div>
                  <h4>No pending threads</h4>
                  <p>Once you submit a new discussion, it will appear here until an admin approves it.</p>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <div class="card discussion-list-card" data-aos="fade-up">
            <div class="card-header discussion-list-header">
              <div>
                <i class="bi bi-clock-history me-2"></i>Community Discussions
              </div>
              <span class="list-header-note">Browse latest, unanswered, or expert-active threads</span>
            </div>

            <div class="discussion-toolbar">
              <form method="GET" class="discussion-search-form">
                <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                <div class="search-box">
                  <i class="bi bi-search"></i>
                  <input type="search"
                         name="q"
                         value="<?= htmlspecialchars($search) ?>"
                         class="form-control"
                         placeholder="Search titles, keywords, or authors">
                </div>
              </form>

              <div class="discussion-filters">
                <a href="?filter=all<?= $search !== '' ? '&q=' . urlencode($search) : '' ?>" class="filter-pill <?= $filter === 'all' ? 'active' : '' ?>">All</a>
                <a href="?filter=my_posts<?= $search !== '' ? '&q=' . urlencode($search) : '' ?>" class="filter-pill <?= $filter === 'my_posts' ? 'active' : '' ?>">My Posts</a>
                <a href="?filter=unanswered<?= $search !== '' ? '&q=' . urlencode($search) : '' ?>" class="filter-pill <?= $filter === 'unanswered' ? 'active' : '' ?>">Unanswered</a>
                <a href="?filter=expert<?= $search !== '' ? '&q=' . urlencode($search) : '' ?>" class="filter-pill <?= $filter === 'expert' ? 'active' : '' ?>">Expert Replies</a>
              </div>
            </div>

            <div class="list-group list-group-flush">
              <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                  <?php
                  $replyCount = (int) $row['reply_count'];
                  $expertReplyCount = (int) ($row['expert_reply_count'] ?? 0);
                  $isMyPost = ((int) $row['user_id'] === $currentUserId);
                  ?>
                  <div class="discussion-item list-group-item new-discussion-layout">
                    <div class="discussion-avatar">
                      <img src="<?= !empty($row['profile_picture'])
                          ? '../../images/profile_pics/' . htmlspecialchars($row['profile_picture'])
                          : '../../images/clearteenalogo.png' ?>"
                           alt="Profile picture"
                           onerror="this.onerror=null;this.src='../../images/clearteenalogo.png';">
                    </div>

                    <div class="discussion-content">
                      <div class="discussion-meta-top">
                        <span class="discussion-username"><?= htmlspecialchars($row['name'] ?: $row['username']) ?></span>
                        <span class="role-badge role-<?= htmlspecialchars($row['role']) ?>">
                          <?= role_label((string) $row['role']) ?>
                        </span>
                        <?php if ($isMyPost): ?>
                          <span class="meta-badge meta-badge-self">My Post</span>
                        <?php endif; ?>
                        <?php if ($replyCount === 0): ?>
                          <span class="meta-badge meta-badge-warning">Needs Reply</span>
                        <?php endif; ?>
                        <?php if ($expertReplyCount > 0): ?>
                          <span class="meta-badge meta-badge-expert">Expert Active</span>
                        <?php endif; ?>
                      </div>

                      <a href="thread.php?id=<?= $row['question_id'] ?>" class="discussion-title new-title">
                        <?= htmlspecialchars($row['title']) ?>
                      </a>

                      <div class="discussion-body-preview">
                        <?= htmlspecialchars(mb_strimwidth($row['body'], 0, 140, '...')) ?>
                      </div>

                      <div class="discussion-actions">
                        <span><i class="bi bi-chat"></i> <?= $replyCount ?> <?= $replyCount === 1 ? 'reply' : 'replies' ?></span>
                        <span><i class="bi bi-clock"></i> Last activity <?= date('M d, Y g:i A', strtotime($row['last_activity_at'])) ?></span>
                        <?php if ($expertReplyCount > 0): ?>
                          <span><i class="bi bi-patch-check"></i> <?= $expertReplyCount ?> expert <?= $expertReplyCount === 1 ? 'reply' : 'replies' ?></span>
                        <?php endif; ?>
                      </div>
                    </div>

                    <?php if ($currentRole === 'agriculturist' || $currentRole === 'admin'): ?>
                      <form method="GET" action="delete.php" onsubmit="return confirm('Delete this question?');">
                        <input type="hidden" name="type" value="question">
                        <input type="hidden" name="id" value="<?= $row['question_id'] ?>">
                        <button type="submit" class="discussion-delete" aria-label="Delete discussion">
                          <i class="bi bi-x"></i>
                        </button>
                      </form>
                    <?php endif; ?>
                  </div>
                <?php endwhile; ?>
              <?php else: ?>
                <div class="community-empty-state">
                  <div class="empty-icon"><i class="bi bi-chat-square-text"></i></div>
                  <h4>No discussions match this view</h4>
                  <p>
                    <?php if ($search !== ''): ?>
                      No threads matched <strong><?= htmlspecialchars($search) ?></strong>. Try a broader keyword or switch filters.
                    <?php else: ?>
                      There are no approved discussions here yet. Start the first one and invite the community to respond.
                    <?php endif; ?>
                  </p>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<footer class="bg-success text-center text-white py-3">
  &copy; 2024 Farming Community
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
AOS.init();

document.getElementById('questionForm')?.addEventListener('submit', function(e) {
    e.preventDefault();

    const msg = document.getElementById('questionMessage');
    msg.classList.remove('d-none', 'alert-success', 'alert-danger');

    fetch('addquestion.php', {
        method: 'POST',
        body: new FormData(this)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            msg.classList.add('alert-success');
            msg.textContent = 'Your question has been submitted and is awaiting admin approval.';
            this.reset();

            const collapseEl = document.getElementById('newDiscussionForm');
            const bsCollapse = bootstrap.Collapse.getInstance(collapseEl);
            bsCollapse?.hide();

            setTimeout(() => msg.classList.add('d-none'), 8000);
        } else {
            msg.classList.add('alert-danger');
            msg.textContent = data.message || 'Failed to submit your question.';
        }
    })
    .catch(() => {
        msg.classList.add('alert-danger');
        msg.textContent = 'Network error. Please try again.';
    });
});

document.querySelectorAll('.js-toggle-pending-editor').forEach((button) => {
    button.addEventListener('click', () => {
        const targetId = button.getAttribute('data-target');
        const editor = document.getElementById(targetId);
        if (editor) {
            editor.classList.toggle('d-none');
        }
    });
});

document.querySelectorAll('.js-pending-editor-form').forEach((form) => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const msg = document.getElementById('pendingThreadMessage');
        msg.classList.remove('d-none', 'alert-success', 'alert-danger');

        fetch('manage_pending.php', {
            method: 'POST',
            body: new FormData(this)
        })
        .then(res => res.json())
        .then(data => {
            msg.classList.add(data.success ? 'alert-success' : 'alert-danger');
            msg.textContent = data.message || 'Unable to update pending thread.';
            if (data.success) {
                window.location.reload();
            }
        })
        .catch(() => {
            msg.classList.add('alert-danger');
            msg.textContent = 'Network error. Please try again.';
        });
    });
});

document.querySelectorAll('.js-delete-pending-thread').forEach((button) => {
    button.addEventListener('click', () => {
        if (!confirm('Delete this pending thread?')) {
            return;
        }

        const msg = document.getElementById('pendingThreadMessage');
        msg.classList.remove('d-none', 'alert-success', 'alert-danger');

        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('question_id', button.getAttribute('data-question-id'));

        fetch('manage_pending.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            msg.classList.add(data.success ? 'alert-success' : 'alert-danger');
            msg.textContent = data.message || 'Unable to delete pending thread.';
            if (data.success) {
                const row = document.getElementById(`pending-thread-${button.getAttribute('data-question-id')}`);
                row?.remove();
            }
        })
        .catch(() => {
            msg.classList.add('alert-danger');
            msg.textContent = 'Network error. Please try again.';
        });
    });
});
</script>

</body>
</html>
