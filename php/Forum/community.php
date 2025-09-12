<?php

  session_start();

  if (!isset($_SESSION['user_id'])) {
      header("Location: ../login.php");
      exit();
  }
  
  include "../connection.php"; 

  $sql = "SELECT q.question_id, q.title, q.body, q.created_at, u.username, u.name, u.profile_picture, u.role,
          COUNT(r.reply_id) as reply_count
          FROM questions q
          JOIN users u ON q.user_id = u.user_id
          LEFT JOIN reply r ON q.question_id = r.question_id
          GROUP BY q.question_id, q.title, q.body, q.created_at, u.username, u.name, u.profile_picture, u.role
          ORDER BY q.created_at DESC
          LIMIT 10";
  $result = $conn->query($sql);
  
  
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
  <style>
    html, body {
      height: 100%;
    }
    body {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
    }
    .main-content {
      flex: 1 0 auto;
    }
    footer {
      flex-shrink: 0;
    }
    .community-header {
      background: linear-gradient(90deg, #43a047 0%, #66bb6a 100%);
      color: #fff;
      border-radius: 1rem;
      padding: 1.5rem 2rem 1rem 2rem;
      margin-bottom: 2rem;
      box-shadow: 0 4px 24px rgba(76,175,80,0.08);
      width: 100%;
    }
    .discussion-form-card {
      border-radius: 1rem;
      box-shadow: 0 4px 24px rgba(76,175,80,0.10);
      margin-bottom: 2rem;
      overflow: hidden;
      min-height: 300px;
      max-height: 1000px;
      width: 100%;
    }
    .discussion-form-card .card-header {
      background: #388e3c;
      color: #fff;
      font-weight: 600;
      font-size: 1.2rem;
      border-radius: 1rem 1rem 0 0;
    }
    .discussion-form-card .btn-success {
      border-radius: 2rem;
      font-weight: 600;
      padding: 0.5rem 2rem;
    }
    .discussion-form-card .form-control {
      font-size: 1.2rem;
      min-height: 3rem;
    }
    .discussion-form-card textarea.form-control {
      min-height: 200px;
    }
    .discussion-list-card {
      border-radius: 1rem;
      box-shadow: 0 4px 24px rgba(76,175,80,0.10);
      overflow: hidden;
      min-height: 500px;
      width: 100%;
      /* max-height: 900px; */
    }
    .discussion-list-card .list-group {
      max-height: 480px;
      overflow-y: auto;
    }
    .discussion-list-card .list-group::-webkit-scrollbar {
      width: 8px;
    }
    .discussion-list-card .list-group::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 10px;
    }
    .discussion-list-card .list-group::-webkit-scrollbar-thumb {
      background: #43a047;
      border-radius: 10px;
    }
    .discussion-list-card .list-group::-webkit-scrollbar-thumb:hover {
      background: #388e3c;
    }
    .discussion-list-card .card-header {
      background: #18804b;
      color: #fff;
      font-weight: 600;
      font-size: 1.1rem;
      border-radius: 1rem 1rem 0 0;
    }
    .discussion-item {
      transition: background 0.15s, box-shadow 0.15s;
      border-bottom: 1px solid #e0e0e0;
      padding: 1rem 1.2rem;
      display: flex;
      align-items: flex-start;
      gap: 1rem;
      background: #fff;
    }
    .discussion-item:last-child {
      border-bottom: none;
    }
    .discussion-item:hover {
      background: #f1f8e9;
      box-shadow: 0 2px 12px rgba(76,175,80,0.08);
    }
    .discussion-title {
      font-size: 1.1rem;
      font-weight: 600;
      color: #388e3c;
      margin-bottom: 0.2rem;
      text-decoration: none;
      transition: color 0.15s;
    }
    .discussion-title:hover {
      color: #256029;
      text-decoration: underline;
    }
    .discussion-meta {
      font-size: 0.95rem;
      color: #888;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .discussion-delete {
      margin-left: auto;
      color: #e53935;
      background: #fff;
      border: none;
      border-radius: 50%;
      width: 32px;
      height: 32px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
      transition: background 0.2s;
    }
    .discussion-delete:hover {
      background: #e53935;
      color: #fff;
    }
    @media (max-width: 767.98px) {
      .community-header {
        padding: 1rem 0.5rem 0.7rem 0.5rem;
      }
      .discussion-item {
        flex-direction: column;
        gap: 0.3rem;
        padding: 0.8rem 0.5rem;
      }
    }
    .new-discussion-layout {
      display: flex;
      align-items: flex-start;
      gap: 1rem;
      padding: 1.2rem 1.2rem 1rem 1.2rem;
      background: #fff;
      border-radius: 0.75rem;
      box-shadow: 0 2px 8px rgba(76,175,80,0.07);
      margin-bottom: 1.1rem;
      position: relative;
    }
    .discussion-avatar img {
      width: 44px;
      height: 44px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid #c8e6c9;
    }
    .discussion-avatar {
      flex-shrink: 0;
    }
    .discussion-content {
      flex: 1 1 auto;
      min-width: 0;
    }
    .discussion-meta-top {
      font-size: 0.97rem;
      color: #388e3c;
      margin-bottom: 0.1rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .discussion-username {
      font-weight: 600;
    }
    .discussion-meta-text {
      color: #888;
      font-weight: 400;
    }
    .role-badge {
      font-size: 0.75rem;
      padding: 0.2rem 0.5rem;
      border-radius: 1rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .role-student {
      background: #e3f2fd;
      color: #1976d2;
    }
    .role-admin {
      background: #ffebee;
      color: #d32f2f;
    }
    .role-agriculturist {
      background: #e8f5e8;
      color: #388e3c;
    }
    .new-title {
      font-size: 1.08rem;
      font-weight: 700;
      color: #256029;
      margin-bottom: 0.2rem;
      display: block;
      text-decoration: none;
    }
    .new-title:hover {
      color: #18804b;
      text-decoration: underline;
    }
    .discussion-body-preview {
      color: #666;
      font-size: 0.98rem;
      margin-bottom: 0.5rem;
      margin-top: 0.1rem;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .discussion-actions {
      display: flex;
      align-items: center;
      gap: 1.2rem;
      font-size: 1.05rem;
      color: #888;
      margin-top: 0.2rem;
    }
    .action-icon {
      display: flex;
      align-items: center;
      gap: 0.3rem;
    }
    @media (max-width: 767.98px) {
      .new-discussion-layout {
        flex-direction: column;
        gap: 0.5rem;
        padding: 0.8rem 0.5rem 0.7rem 0.5rem;
      }
      .discussion-avatar img {
        width: 36px;
        height: 36px;
      }
    }
  </style>
</head>
<body>
<?php include '../navbar.php'; ?>
<div class="main-content">
  <div class="container py-4">
    <div class="row justify-content-center g-4">
      <div class="col-12 col-lg-8 mx-auto">
        <div class="community-header mb-4" data-aos="fade-down">
          <h2 class="mb-1"><i class="bi bi-people-fill me-2"></i>Farming Community</h2>
          <p class="mb-0">Connect, share, and grow with fellow young farmers. Start a new discussion or join the conversation!</p>
        </div>
        <!-- Start New Discussion Button -->
        <button class="btn btn-success w-100 mb-3 py-3 fs-5 fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#newDiscussionForm" aria-expanded="false" aria-controls="newDiscussionForm">
          <i class="bi bi-chat-dots me-2"></i>Start a New Discussion
        </button>
        <!-- Collapsible Form -->
        <div class="collapse" id="newDiscussionForm">
          <div class="card discussion-form-card mb-4" data-aos="fade-up">
            <div class="card-header">
              <i class="bi bi-chat-dots me-2"></i>Start a New Discussion
            </div>
            <div class="card-body">
              <form method="POST" action="addquestion.php">
                <div class="mb-3">
                    <label for="threadTitle" class="form-label">Thread Title</label>
                    <input type="text" id="threadTitle" name="title" class="form-control" placeholder="Enter title" required>
                </div>
                <div class="mb-3">
                    <label for="threadMessage" class="form-label">Message</label>
                    <textarea id="threadMessage" name="body" class="form-control" rows="5" placeholder="Write your message here..." required></textarea>
                </div>
                <button type="submit" class="btn btn-success">Create Thread</button>
              </form>
            </div>
          </div>
        </div>
        <div class="card discussion-list-card" data-aos="fade-up" data-aos-delay="100">
          <div class="card-header">
            <i class="bi bi-clock-history me-2"></i>Recent Discussions
          </div>
          <div class="list-group list-group-flush">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="discussion-item list-group-item new-discussion-layout">
                        <div class="discussion-avatar">
                          <img src="<?= !empty($row['profile_picture']) ? '../../images/profile_pics/' . htmlspecialchars($row['profile_picture']) : '../../images/clearteenalogo.png' ?>" alt="User Avatar" onerror="this.onerror=null;this.src='../../images/clearteenalogo.png'">
                        </div>
                        <div class="discussion-content">
                          <div class="discussion-meta-top">
                            <span class="discussion-username"><?= htmlspecialchars($row['name']) ?></span>
                            <span class="role-badge role-<?= $row['role'] ?>"><?= ucfirst($row['role']) ?></span>
                            <span class="discussion-meta-text">&bull; <?= date('M d, Y', strtotime($row['created_at'])) ?></span>
                          </div>
                          <a href="thread.php?id=<?= $row['question_id'] ?>" class="discussion-title new-title">
                            <?= htmlspecialchars($row['title']) ?>
                          </a>
                          <div class="discussion-body-preview">
                            <?= htmlspecialchars(mb_strimwidth($row['body'], 0, 100, '...')) ?>
                          </div>
                          <div class="discussion-actions">
                            <span class="action-icon"><i class="bi bi-hand-thumbs-up"></i> 1</span>
                            <span class="action-icon"><i class="bi bi-chat"></i> <?= $row['reply_count'] ?></span>
                            <span class="action-icon"><i class="bi bi-clock"></i> <?= date('H:i', strtotime($row['created_at'])) ?></span>
                          </div>
                        </div>
                        <?php if ($_SESSION['role'] === 'agriculturist'): ?>
                            <form method="GET" action="delete.php" onsubmit="return confirm('Are you sure you want to delete this question?');">
                              <input type="hidden" name="type" value="question">
                              <input type="hidden" name="id" value="<?= $row['question_id'] ?>">
                              <button type="submit" class="discussion-delete" title="Delete"><i class="bi bi-x"></i></button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="discussion-item list-group-item new-discussion-layout">
                    <p class="mb-0 text-muted">No questions yet. Start the first discussion!</p>
                </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<footer class="bg-success text-center text-white py-3 mt-auto">
  <p class="mb-0">&copy; 2024 Farming Community. All rights reserved.</p>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
AOS.init();
</script>
</body>
</html>
