<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include "../connection.php";

/*
|--------------------------------------------------------------------------
| Forum restriction
| status = 'inactive' => forum restricted
|--------------------------------------------------------------------------
*/
$isForumRestricted = (isset($_SESSION['status']) && $_SESSION['status'] === 'inactive');

/* Fetch discussions ONLY if not restricted */
$result = null;

if (!$isForumRestricted) {
    $sql = "SELECT q.question_id, q.title, q.body, q.created_at,
                   u.username, u.name, u.profile_picture, u.role,
                   COUNT(r.reply_id) AS reply_count
            FROM questions q
            JOIN users u ON q.user_id = u.user_id
            LEFT JOIN reply r ON q.question_id = r.question_id
            WHERE q.status = 'approved'
            GROUP BY q.question_id, q.title, q.body, q.created_at,
                     u.username, u.name, u.profile_picture, u.role
            ORDER BY q.created_at DESC
            LIMIT 10";

    $result = $conn->query($sql);
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

<body>
<?php include '../navbar.php'; ?>

<div class="main-content">
  <div class="container py-4">
    <div class="row justify-content-center g-4">
      <div class="col-12 col-lg-8 mx-auto">

        <!-- Header -->
        <div class="community-header mb-4" data-aos="fade-down">
          <h2 class="mb-1">
            <i class="bi bi-people-fill me-2"></i>Farming Community
          </h2>
          <p class="mb-0">
            Connect, share, and grow with fellow young farmers.
          </p>
        </div>

        <!-- ================= RESTRICTED VIEW ================= -->
        <?php if ($isForumRestricted): ?>
          <div class="alert alert-warning text-center">
            🚫 You are restricted from accessing the community forum.
          </div>

        <!-- ================= NORMAL VIEW ================= -->
        <?php else: ?>

          <!-- Start Discussion Button -->
          <button class="btn btn-success w-100 mb-3 py-3 fs-5 fw-bold"
                  type="button"
                  data-bs-toggle="collapse"
                  data-bs-target="#newDiscussionForm">
            <i class="bi bi-chat-dots me-2"></i>Start a New Discussion
          </button>

          <!-- New Discussion Form -->
          <div class="collapse" id="newDiscussionForm">
            <div class="card discussion-form-card mb-4" data-aos="fade-up">
              <div class="card-header">
                <i class="bi bi-chat-dots me-2"></i>Start a New Discussion
              </div>
              <div class="card-body">
                <form id="questionForm" method="POST">
                  <div class="mb-3">
                    <label class="form-label">Thread Title</label>
                    <input type="text" name="title" class="form-control" required>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Message</label>
                    <textarea name="body" class="form-control" rows="5" required></textarea>
                  </div>
                  <button type="submit" class="btn btn-success">
                    Create Thread
                  </button>
                </form>
              </div>
            </div>
          </div>
          <div id="questionMessage" class="alert d-none mt-3"></div>
          <!-- Discussion List -->
          <div class="card discussion-list-card" data-aos="fade-up">
            <div class="card-header">
              <i class="bi bi-clock-history me-2"></i>Recent Discussions
            </div>

            <div class="list-group list-group-flush">
              <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                  <div class="discussion-item list-group-item new-discussion-layout">
                    <div class="discussion-avatar">
                      <img src="<?= !empty($row['profile_picture'])
                          ? '../../images/profile_pics/' . htmlspecialchars($row['profile_picture'])
                          : '../../images/clearteenalogo.png' ?>">
                    </div>

                    <div class="discussion-content">
                      <div class="discussion-meta-top">
                        <span class="discussion-username"><?= htmlspecialchars($row['name']) ?></span>
                        <span class="role-badge role-<?= $row['role'] ?>">
                          <?= ucfirst($row['role']) ?>
                        </span>
                        <span class="discussion-meta-text">
                          • <?= date('M d, Y', strtotime($row['created_at'])) ?>
                        </span>
                      </div>

                      <a href="thread.php?id=<?= $row['question_id'] ?>"
                         class="discussion-title new-title">
                        <?= htmlspecialchars($row['title']) ?>
                      </a>

                      <div class="discussion-body-preview">
                        <?= htmlspecialchars(mb_strimwidth($row['body'], 0, 100, '...')) ?>
                      </div>

                      <div class="discussion-actions">
                        <span><i class="bi bi-chat"></i> <?= $row['reply_count'] ?></span>
                        <span><i class="bi bi-clock"></i> <?= date('H:i', strtotime($row['created_at'])) ?></span>
                      </div>
                    </div>

                    <?php if ($_SESSION['role'] === 'agriculturist'): ?>
                      <form method="GET" action="delete.php"
                            onsubmit="return confirm('Delete this question?');">
                        <input type="hidden" name="type" value="question">
                        <input type="hidden" name="id" value="<?= $row['question_id'] ?>">
                        <button type="submit" class="discussion-delete">
                          <i class="bi bi-x"></i>
                        </button>
                      </form>
                    <?php endif; ?>
                  </div>
                <?php endwhile; ?>
              <?php else: ?>
                <div class="list-group-item text-muted">
                  No questions yet.
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

// AJAX submit for thread
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
            msg.innerHTML = '🕓 Your question has been submitted and is awaiting admin approval.';
            this.reset();

            // Collapse form
            const collapseEl = document.getElementById('newDiscussionForm');
            const bsCollapse = bootstrap.Collapse.getInstance(collapseEl);
            bsCollapse?.hide();

            setTimeout(() => msg.classList.add('d-none'), 8000);
        } else {
            msg.classList.add('alert-danger');
            msg.innerHTML = data.message || '❌ Failed to submit your question.';
        }
    })
    .catch(() => {
        msg.classList.add('alert-danger');
        msg.innerHTML = '⚠️ Network error. Please try again.';
    });
});
</script>

</body>
</html>
