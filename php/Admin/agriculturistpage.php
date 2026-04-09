<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include '../connection.php';
include '../Forum/community_updates_bootstrap.php';

$userId = (int) $_SESSION['user_id'];

$userResult = $conn->query("SELECT * FROM users WHERE user_id = {$userId}");
if (!$userResult || $userResult->num_rows === 0) {
    echo "User details not found.";
    exit();
}
$user = $userResult->fetch_assoc();
$profilePic = !empty($user['profile_picture'])
    ? '../../images/profile_pics/' . htmlspecialchars($user['profile_picture'])
    : '../../images/clearteenalogo.png';

$replyResult = $conn->query("
    SELECT r.*, q.title
    FROM reply r
    JOIN questions q ON r.question_id = q.question_id
    WHERE r.user_id = {$userId}
    ORDER BY r.created_at DESC
");

$updatesResult = $conn->query("
    SELECT cu.*, u.name, u.username
    FROM community_updates cu
    JOIN users u ON cu.user_id = u.user_id
    WHERE cu.user_id = {$userId}
    ORDER BY cu.is_pinned DESC, cu.created_at DESC
");

$recentThreadsResult = $conn->query("
    SELECT q.question_id, q.title, q.status, q.created_at, u.name, u.username
    FROM questions q
    JOIN users u ON q.user_id = u.user_id
    ORDER BY q.created_at DESC
    LIMIT 5
");

$communityStats = $conn->query("
    SELECT
        (SELECT COUNT(*) FROM reply WHERE user_id = {$userId}) AS replies_count,
        (SELECT COUNT(*) FROM community_updates WHERE user_id = {$userId}) AS updates_count,
        (SELECT COUNT(*) FROM questions WHERE status = 'pending') AS pending_questions_count,
        (SELECT COUNT(*) FROM questions WHERE status = 'approved') AS approved_questions_count
")->fetch_assoc();

$activeSection = $_GET['section'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agriculturist Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/homepage.css">
</head>
<body class="agri-dashboard-body">
<?php include '../navbar.php'; ?>

<div class="agri-sidebar">
    <img src="<?php echo $profilePic; ?>" class="avatar" alt="User Avatar" onerror="this.onerror=null;this.src='../../images/clearteenalogo.png';">
    <div class="welcome">Agriculturist Hub</div>
    <div class="agri-role-note"><?php echo htmlspecialchars($user['name']); ?></div>
    <nav class="nav flex-column w-100 mt-3">
        <a class="nav-link<?php echo ($activeSection === 'dashboard') ? ' active' : ''; ?>" href="?section=dashboard"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
        <a class="nav-link<?php echo ($activeSection === 'profile') ? ' active' : ''; ?>" href="?section=profile"><i class="bi bi-person-circle"></i> Profile</a>
        <a class="nav-link<?php echo ($activeSection === 'updates') ? ' active' : ''; ?>" href="?section=updates"><i class="bi bi-megaphone-fill"></i> Community Updates</a>
        <a class="nav-link<?php echo ($activeSection === 'settings') ? ' active' : ''; ?>" href="?section=settings"><i class="bi bi-gear"></i> Settings</a>
        <a class="nav-link" href="../Forum/community.php"><i class="bi bi-people-fill"></i> Farming Community</a>
    </nav>
</div>

<main class="agri-main-content">
    <div class="agri-dashboard-shell">
        <section class="agri-page-header">
            <div>
                <div class="agri-kicker">Community moderation and expert guidance</div>
                <h1>
                    <?php
                    echo match ($activeSection) {
                        'profile' => 'Profile',
                        'updates' => 'Community Updates',
                        'settings' => 'Account Settings',
                        default => 'Agriculturist Dashboard',
                    };
                    ?>
                </h1>
                <p>Monitor community activity, publish official updates, and keep learner discussions moving with timely expert support.</p>
            </div>
            <div class="agri-header-actions">
                <a href="?section=updates" class="btn btn-success"><i class="bi bi-plus-circle me-2"></i>New Update</a>
                <a href="../Forum/community.php" class="btn btn-outline-success"><i class="bi bi-box-arrow-up-right me-2"></i>Open Community</a>
            </div>
        </section>

        <?php if ($activeSection === 'dashboard'): ?>
            <section class="agri-overview-grid">
                <article class="agri-stat-card">
                    <div class="agri-stat-icon"><i class="bi bi-reply-fill"></i></div>
                    <div>
                        <div class="agri-stat-value"><?php echo (int) ($communityStats['replies_count'] ?? 0); ?></div>
                        <div class="agri-stat-label">Your Replies</div>
                    </div>
                </article>
                <article class="agri-stat-card">
                    <div class="agri-stat-icon"><i class="bi bi-megaphone-fill"></i></div>
                    <div>
                        <div class="agri-stat-value"><?php echo (int) ($communityStats['updates_count'] ?? 0); ?></div>
                        <div class="agri-stat-label">Published Updates</div>
                    </div>
                </article>
                <article class="agri-stat-card">
                    <div class="agri-stat-icon"><i class="bi bi-hourglass-split"></i></div>
                    <div>
                        <div class="agri-stat-value"><?php echo (int) ($communityStats['pending_questions_count'] ?? 0); ?></div>
                        <div class="agri-stat-label">Pending Threads</div>
                    </div>
                </article>
                <article class="agri-stat-card">
                    <div class="agri-stat-icon"><i class="bi bi-chat-square-text-fill"></i></div>
                    <div>
                        <div class="agri-stat-value"><?php echo (int) ($communityStats['approved_questions_count'] ?? 0); ?></div>
                        <div class="agri-stat-label">Approved Threads</div>
                    </div>
                </article>
            </section>

            <section class="agri-dashboard-grid">
                <div class="agri-card">
                    <div class="agri-card-header">Quick Actions</div>
                    <div class="agri-action-list">
                        <a href="?section=updates" class="agri-action-card">
                            <i class="bi bi-megaphone-fill"></i>
                            <div>
                                <strong>Publish announcement</strong>
                                <span>Share CVAO events, advisories, and links with the community.</span>
                            </div>
                        </a>
                        <a href="../Forum/community.php?filter=unanswered" class="agri-action-card">
                            <i class="bi bi-chat-left-dots-fill"></i>
                            <div>
                                <strong>Review unanswered threads</strong>
                                <span>Jump to community questions that still need expert guidance.</span>
                            </div>
                        </a>
                        <a href="?section=settings" class="agri-action-card">
                            <i class="bi bi-person-gear"></i>
                            <div>
                                <strong>Update account settings</strong>
                                <span>Keep your name and password current.</span>
                            </div>
                        </a>
                    </div>
                </div>

                <div class="agri-card">
                    <div class="agri-card-header">Latest Community Threads</div>
                    <?php if ($recentThreadsResult && $recentThreadsResult->num_rows > 0): ?>
                        <div class="agri-thread-list">
                            <?php while ($thread = $recentThreadsResult->fetch_assoc()): ?>
                                <a class="agri-thread-item" href="../Forum/thread.php?id=<?php echo (int) $thread['question_id']; ?>">
                                    <div>
                                        <strong><?php echo htmlspecialchars($thread['title']); ?></strong>
                                        <div class="agri-thread-meta">
                                            <?php echo htmlspecialchars($thread['name'] ?: $thread['username']); ?> • <?php echo date('M d, Y g:i A', strtotime($thread['created_at'])); ?>
                                        </div>
                                    </div>
                                    <span class="agri-status-pill status-<?php echo htmlspecialchars($thread['status']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($thread['status'])); ?>
                                    </span>
                                </a>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="mb-0">No community threads yet.</p>
                    <?php endif; ?>
                </div>
            </section>

            <section class="agri-dashboard-grid">
                <div class="agri-card">
                    <div class="agri-card-header">Recent Replies</div>
                    <?php if ($replyResult && $replyResult->num_rows > 0): ?>
                        <?php $replyPreviewCount = 0; ?>
                        <?php while ($reply = $replyResult->fetch_assoc()): ?>
                            <?php if ($replyPreviewCount >= 4) { break; } ?>
                            <?php $replyPreviewCount++; ?>
                            <div class="agri-reply-card">
                                <div class="agri-reply-title">
                                    In <a href="../Forum/thread.php?id=<?php echo $reply['question_id']; ?>" class="text-decoration-none text-success fw-bold"><?php echo htmlspecialchars($reply['title']); ?></a>
                                </div>
                                <div class="agri-reply-body"><?php echo htmlspecialchars($reply['body']); ?></div>
                                <div class="agri-reply-date">Replied on: <?php echo date('M d, Y', strtotime($reply['created_at'])); ?></div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="mb-0">No replies found.</p>
                    <?php endif; ?>
                </div>

                <div class="agri-card">
                    <div class="agri-card-header">Recent Updates</div>
                    <?php if ($updatesResult && $updatesResult->num_rows > 0): ?>
                        <?php $updatePreviewCount = 0; ?>
                        <?php while ($update = $updatesResult->fetch_assoc()): ?>
                            <?php if ($updatePreviewCount >= 3) { break; } ?>
                            <?php $updatePreviewCount++; ?>
                            <div class="agri-update-card">
                                <div class="agri-update-head">
                                    <div>
                                        <h5><?php echo htmlspecialchars($update['title']); ?></h5>
                                        <div class="agri-reply-date">Published on: <?php echo date('M d, Y g:i A', strtotime($update['created_at'])); ?></div>
                                    </div>
                                </div>
                                <div class="agri-update-body"><?php echo nl2br(htmlspecialchars($update['body'])); ?></div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="mb-0">No published updates yet.</p>
                    <?php endif; ?>
                </div>
            </section>
        <?php elseif ($activeSection === 'profile'): ?>
            <section class="agri-card">
                <div class="agri-card-header">Profile Summary</div>
                <div class="agri-profile-grid">
                    <div class="agri-profile-panel">
                        <h5>Profile Information</h5>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
                        <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                        <p><strong>Role:</strong> Agriculturist</p>
                    </div>
                    <div class="agri-profile-panel">
                        <h5>Community Snapshot</h5>
                        <p><strong>Your replies:</strong> <?php echo (int) ($communityStats['replies_count'] ?? 0); ?></p>
                        <p><strong>Your updates:</strong> <?php echo (int) ($communityStats['updates_count'] ?? 0); ?></p>
                        <p><strong>Pending threads in community:</strong> <?php echo (int) ($communityStats['pending_questions_count'] ?? 0); ?></p>
                    </div>
                </div>
            </section>

            <section class="agri-card">
                <div class="agri-card-header">Your Replies in the Farming Community</div>
                <?php if ($replyResult && $replyResult->num_rows > 0): ?>
                    <?php while ($reply = $replyResult->fetch_assoc()): ?>
                        <div class="agri-reply-card">
                            <div class="agri-reply-title">
                                In <a href="../Forum/thread.php?id=<?php echo $reply['question_id']; ?>" class="text-decoration-none text-success fw-bold"><?php echo htmlspecialchars($reply['title']); ?></a>
                            </div>
                            <div class="agri-reply-body"><?php echo htmlspecialchars($reply['body']); ?></div>
                            <div class="agri-reply-date">Replied on: <?php echo date('M d, Y', strtotime($reply['created_at'])); ?></div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="mb-0">No replies found.</p>
                <?php endif; ?>
            </section>
        <?php elseif ($activeSection === 'updates'): ?>
            <section class="agri-card">
                <div class="agri-card-header">Publish Community Update</div>
                <div id="communityUpdateMessage" class="alert d-none mb-3"></div>
                <form id="communityUpdateForm" class="agri-update-form">
                    <div class="mb-3">
                        <label class="form-label">Update Title</label>
                        <input type="text" name="title" class="form-control" maxlength="255" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Update Text</label>
                        <textarea name="body" class="form-control" rows="6" maxlength="5000" required placeholder="Share event details, announcements, advisory notes, or guidance."></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Image URL</label>
                            <input type="text" name="image_url" class="form-control" placeholder="https://...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">External Link</label>
                            <input type="url" name="external_url" class="form-control" placeholder="https://...">
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label">Upload Image</label>
                            <input type="file" name="image_file" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp">
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check form-switch agri-featured-toggle">
                                <input class="form-check-input" type="checkbox" name="is_pinned" id="isPinnedUpdate">
                                <label class="form-check-label" for="isPinnedUpdate">Feature this update at the top of the community</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-text mt-2">Use this for community events, official advisories, featured resources, or helpful announcements. Upload a poster directly if you have one.</div>
                    <button type="submit" class="btn btn-success mt-3">Publish Update</button>
                </form>
            </section>

            <section class="agri-card">
                <div class="agri-card-header">Your Published Updates</div>
                <?php if ($updatesResult && $updatesResult->num_rows > 0): ?>
                    <?php while ($update = $updatesResult->fetch_assoc()): ?>
                        <div class="agri-update-card">
                            <div class="agri-update-head">
                                <div>
                                    <h5><?php echo htmlspecialchars($update['title']); ?></h5>
                                    <div class="agri-reply-date">Published on: <?php echo date('M d, Y g:i A', strtotime($update['created_at'])); ?></div>
                                </div>
                                <div class="agri-update-actions">
                                    <?php if (!empty($update['is_pinned'])): ?>
                                        <span class="agri-update-badge">Featured</span>
                                    <?php endif; ?>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-success js-toggle-update-editor"
                                            data-target="update-editor-<?php echo (int) $update['update_id']; ?>">
                                        Edit
                                    </button>
                                    <a href="../Forum/delete_update.php?id=<?php echo (int) $update['update_id']; ?>"
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Delete this update?');">
                                        Delete
                                    </a>
                                </div>
                            </div>
                            <div class="agri-update-body"><?php echo nl2br(htmlspecialchars($update['body'])); ?></div>
                            <?php if (!empty($update['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($update['image_url']); ?>" alt="Community update image" class="agri-update-image" onerror="this.style.display='none'">
                            <?php endif; ?>
                            <?php if (!empty($update['external_url'])): ?>
                                <a href="<?php echo htmlspecialchars($update['external_url']); ?>" target="_blank" rel="noopener noreferrer" class="agri-update-link">
                                    <i class="bi bi-box-arrow-up-right"></i> Open attached link
                                </a>
                            <?php endif; ?>
                            <form class="agri-update-editor d-none js-update-editor-form" id="update-editor-<?php echo (int) $update['update_id']; ?>">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="update_id" value="<?php echo (int) $update['update_id']; ?>">
                                <div class="mb-3">
                                    <label class="form-label">Edit Title</label>
                                    <input type="text" name="title" class="form-control" maxlength="255" value="<?php echo htmlspecialchars($update['title']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Edit Text</label>
                                    <textarea name="body" class="form-control" rows="5" maxlength="5000" required><?php echo htmlspecialchars($update['body']); ?></textarea>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Image URL</label>
                                        <input type="text" name="image_url" class="form-control" value="<?php echo htmlspecialchars($update['image_url'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">External Link</label>
                                        <input type="url" name="external_url" class="form-control" value="<?php echo htmlspecialchars($update['external_url'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="row g-3 mt-1">
                                    <div class="col-md-6">
                                        <label class="form-label">Replace Image</label>
                                        <input type="file" name="image_file" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp">
                                    </div>
                                    <div class="col-md-6 d-flex align-items-end">
                                        <div class="form-check form-switch agri-featured-toggle">
                                            <input class="form-check-input" type="checkbox" name="is_pinned" id="isPinnedEdit<?php echo (int) $update['update_id']; ?>" <?php echo !empty($update['is_pinned']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="isPinnedEdit<?php echo (int) $update['update_id']; ?>">Feature this update</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="pending-editor-actions mt-3">
                                    <button type="submit" class="btn btn-success btn-sm">Save Update</button>
                                    <button type="button"
                                            class="btn btn-light btn-sm js-toggle-update-editor"
                                            data-target="update-editor-<?php echo (int) $update['update_id']; ?>">
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="mb-0">No published updates yet.</p>
                <?php endif; ?>
            </section>
        <?php elseif ($activeSection === 'settings'): ?>
            <section class="agri-card">
                <div class="agri-card-header">Account Settings</div>
                <div class="agri-settings-top">
                    <div class="agri-settings-avatar-wrap">
                        <img src="<?php echo $profilePic; ?>" alt="Profile picture" class="agri-settings-avatar" onerror="this.onerror=null;this.src='../../images/clearteenalogo.png';">
                        <button type="button" class="btn btn-outline-success btn-sm mt-3" data-bs-toggle="modal" data-bs-target="#uploadProfilePicModal">
                            <i class="bi bi-camera-fill me-2"></i>Change Profile Picture
                        </button>
                    </div>
                    <div class="agri-settings-copy">
                        <h5>Profile picture</h5>
                        <p>Upload a clear profile image so learners can recognize expert replies and official updates more easily.</p>
                        <div class="text-muted small">Allowed: JPG, PNG, GIF. Max size: 2MB.</div>
                    </div>
                </div>
                <div class="agri-tabs mb-3">
                    <button class="agri-tab-btn active" id="tab-profile" type="button" onclick="showAgriTab('profile')">Update Profile</button>
                    <button class="agri-tab-btn" id="tab-password" type="button" onclick="showAgriTab('password')">Change Password</button>
                </div>
                <div id="agri-tab-profile">
                    <form action="User/editprofile.php" method="POST">
                        <div class="mb-3">
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required placeholder=" ">
                            <label for="name" class="form-label">Name</label>
                        </div>
                        <div class="mb-3">
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required placeholder=" ">
                            <label for="username" class="form-label">Username</label>
                        </div>
                        <button type="submit" class="btn btn-success">Update Profile</button>
                    </form>
                </div>
                <div id="agri-tab-password" style="display:none;">
                    <form action="User/changepassword.php" method="POST">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <div class="agri-password-row">
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                                <button class="btn btn-outline-secondary agri-password-toggle" type="button" onclick="togglePassword('current_password')"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <div class="agri-password-row">
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <button class="btn btn-outline-secondary agri-password-toggle" type="button" onclick="togglePassword('new_password')"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <div class="agri-password-row">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                <button class="btn btn-outline-secondary agri-password-toggle" type="button" onclick="togglePassword('confirm_password')"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary" name="change_password">Change Password</button>
                    </form>
                </div>
            </section>
        <?php endif; ?>
    </div>
</main>

<div class="modal fade" id="uploadProfilePicModal" tabindex="-1" aria-labelledby="uploadProfilePicModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" action="../User/upload_profile_picture.php" method="POST" enctype="multipart/form-data">
      <div class="modal-header">
        <h5 class="modal-title" id="uploadProfilePicModalLabel">Upload Profile Picture</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="file" class="form-control" name="profile_picture" accept="image/*" required>
        <small class="text-muted">Max size: 2MB. Allowed: JPG, PNG, GIF.</small>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-success">Upload</button>
      </div>
    </form>
  </div>
</div>

<script>
function showAgriTab(tab) {
  document.getElementById('agri-tab-profile').style.display = (tab === 'profile') ? '' : 'none';
  document.getElementById('agri-tab-password').style.display = (tab === 'password') ? '' : 'none';
  document.getElementById('tab-profile').classList.toggle('active', tab === 'profile');
  document.getElementById('tab-password').classList.toggle('active', tab === 'password');
}

function togglePassword(id) {
  const input = document.getElementById(id);
  input.type = input.type === 'password' ? 'text' : 'password';
}

document.getElementById('communityUpdateForm')?.addEventListener('submit', function(e) {
  e.preventDefault();

  const msg = document.getElementById('communityUpdateMessage');
  msg.classList.remove('d-none', 'alert-success', 'alert-danger');

  fetch('../Forum/save_update.php', {
    method: 'POST',
    body: new FormData(this)
  })
  .then(res => res.json())
  .then(data => {
    msg.classList.add(data.success ? 'alert-success' : 'alert-danger');
    msg.textContent = data.message || 'Unable to publish update.';
    if (data.success) {
      window.location.reload();
    }
  })
  .catch(() => {
    msg.classList.add('alert-danger');
    msg.textContent = 'Network error. Please try again.';
  });
});

document.querySelectorAll('.js-toggle-update-editor').forEach((button) => {
  button.addEventListener('click', () => {
    const editor = document.getElementById(button.getAttribute('data-target'));
    if (editor) {
      editor.classList.toggle('d-none');
    }
  });
});

document.querySelectorAll('.js-update-editor-form').forEach((form) => {
  form.addEventListener('submit', function(e) {
    e.preventDefault();

    fetch('../Forum/manage_update.php', {
      method: 'POST',
      body: new FormData(this)
    })
    .then(res => res.json())
    .then(data => {
      alert(data.message || 'Unable to save update.');
      if (data.success) {
        window.location.reload();
      }
    })
    .catch(() => {
      alert('Network error. Please try again.');
    });
  });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
