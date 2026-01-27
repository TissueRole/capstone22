<?php
    include 'connection.php';
    session_start();
    // Prevent caching
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    $sql = "SELECT * FROM users WHERE user_id = {$_SESSION['user_id']}";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    } else {
        echo "User details not found.";
        exit();
    }

    $question_sql = "SELECT * FROM questions WHERE user_id = {$_SESSION['user_id']} ORDER BY created_at DESC";
    $question_result = $conn->query($question_sql);

    $reply_sql = "SELECT r.*, q.title, q.body AS question_body, q.created_at AS question_created_at, u.name AS author_name, u.profile_picture AS author_profile_picture FROM reply r
                  JOIN questions q ON r.question_id = q.question_id
                  JOIN users u ON q.user_id = u.user_id
                  WHERE r.user_id = {$_SESSION['user_id']} ORDER BY r.created_at DESC";
    $reply_result = $conn->query($reply_sql);


    $active_section = isset($_GET['section']) ? $_GET['section'] : 'profile'; 

    $profile_pic = !empty($user['profile_picture']) ? '../images/profile_pics/' . htmlspecialchars($user['profile_picture']) : '../images/clearteenalogo.png';

    $cert_sql = "SELECT c.*, m.title FROM certificates c
            JOIN modules m ON c.module_id = m.module_id
            WHERE c.user_id = {$_SESSION['user_id']}
              AND c.module_id != 1
            ORDER BY c.completion_date DESC";
    $cert_result = $conn->query($cert_sql);

    $reward_sql = "
        SELECT 
            m.module_id,
            m.title AS module_title,
            m.rewards,
            qr.taken_at AS completion_date
        FROM quiz_results qr
        JOIN module_quizzes mq ON qr.quiz_id = mq.quiz_id
        JOIN modules m ON mq.module_id = m.module_id
        WHERE qr.user_id = {$_SESSION['user_id']}
        AND qr.score >= 75
        AND m.rewards IS NOT NULL
        AND m.rewards != ''
        ORDER BY qr.taken_at DESC
    ";
    $reward_result = $conn->query($reward_sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/userpage.css">
    <link rel="stylesheet" href="../css/homepage.css">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="sidebar d-flex flex-column align-items-center">
    <img src="<?php echo $profile_pic; ?>" class="avatar profile-picture" alt="User Avatar" onerror="this.onerror=null;this.src='../images/clearteenalogo.png';">
    <div class="username">Hi, <?php echo htmlspecialchars($user['name']); ?>!</div>
    <nav class="nav flex-column w-100 mt-4">
        <a class="nav-link <?php echo ($active_section == 'profile') ? 'active' : ''; ?>" href="?section=profile"><i class="bi bi-person-circle"></i> Profile</a>
        <a class="nav-link <?php echo ($active_section == 'settings') ? 'active' : ''; ?>" href="?section=settings"><i class="bi bi-gear"></i> Settings</a>
        <a class="nav-link <?php echo ($active_section == 'certificates') ? 'active' : ''; ?>" href="?section=certificates"><i class="bi bi-award"></i> Certificates</a>
        <a class="nav-link <?php echo ($active_section == 'rewards') ? 'active' : ''; ?>" href="?section=rewards"><i class="bi bi-gift"></i> Rewards</a>
        <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </nav>
</div>
<div class="main-content">
    <div class="container">
        <?php if ($active_section == 'profile'): ?>
            <div class="card shadow-sm mb-4" data-aos="fade-up">
                <div class="card-header bg-success text-white d-flex align-items-center justify-content-between">
                    <h5 class="mb-0"><i class="bi bi-person-circle me-2"></i>Your Profile</h5>
                    <button class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#editProfileModal"><i class="bi bi-pencil"></i> Edit</button>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <img src="<?php echo $profile_pic; ?>" class="avatar me-3 profile-picture" alt="User Avatar" onerror="this.onerror=null;this.src='../images/clearteenalogo.png';">
                        <div>
                            <h4 class="mb-0"><?php echo htmlspecialchars($user['name']); ?></h4>
                            <div style="font-size: 1.05em; color: #388e3c; font-weight: 500; margin-bottom: 2px;">
                                <?php echo htmlspecialchars(ucfirst($user['role'])); ?>
                            </div>
                            <div class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></div>
                            <button class="btn btn-outline-secondary btn-sm ms-2" data-bs-toggle="modal" data-bs-target="#uploadProfilePicModal">
                                <i class="bi bi-upload"></i> Change Picture
                            </button>
                        </div>
                    </div>
                    <hr>
                    <h5 class="mb-3">Your Questions in the Farming Community</h5>
                    <?php if ($question_result->num_rows > 0): ?>
                        <div class="accordion mb-3" id="questionsAccordion">
                            <?php $qidx = 0; while ($question = $question_result->fetch_assoc()): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="qheading<?php echo $qidx; ?>">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#qcollapse<?php echo $qidx; ?>" aria-expanded="false" aria-controls="qcollapse<?php echo $qidx; ?>">
                                            <div class="d-flex align-items-center w-100">
                                                <img src="<?php echo $profile_pic; ?>" alt="User Avatar" style="width:36px;height:36px;border-radius:50%;object-fit:cover;margin-right:12px;" onerror="this.onerror=null;this.src='../images/clearteenalogo.png';">
                                                <div>
                                                    <span class="fw-semibold text-success"><?php echo htmlspecialchars($user['name']); ?></span><br>
                                                    <span class="text-muted" style="font-size:0.95em;">Title: <?php echo htmlspecialchars($question['title']); ?></span>
                                                </div>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="qcollapse<?php echo $qidx; ?>" class="accordion-collapse collapse" aria-labelledby="qheading<?php echo $qidx; ?>" data-bs-parent="#questionsAccordion">
                                        <div class="accordion-body">
                                            <div class="mb-3 p-3 rounded bg-light border">
                                                <div class="fw-semibold mb-1">Question:</div>
                                                <div class="mb-1"><?php echo htmlspecialchars($question['body']); ?></div>
                                                <small class="text-muted">Asked on: <?php echo $question['created_at']; ?></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php $qidx++; endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p>No questions found.</p>
                    <?php endif; ?>
                    <hr>
                    <h5 class="mb-3">Your Replies in the Farming Community</h5>
                    <?php
                    // Group replies by question
                    $grouped_replies = [];
                    if ($reply_result->num_rows > 0) {
                        while ($reply = $reply_result->fetch_assoc()) {
                            $qid = $reply['question_id'];
                            if (!isset($grouped_replies[$qid])) {
                                $grouped_replies[$qid] = [
                                    'title' => $reply['title'],
                                    'question_body' => $reply['question_body'],
                                    'question_created_at' => $reply['question_created_at'],
                                    'author_name' => $reply['author_name'],
                                    'author_profile_picture' => $reply['author_profile_picture'],
                                    'replies' => []
                                ];
                            }
                            $grouped_replies[$qid]['replies'][] = $reply;
                        }
                    }
                    ?>
                    <?php if (!empty($grouped_replies)): ?>
                        <div class="accordion" id="repliesAccordion">
                            <?php $ridx = 0; foreach ($grouped_replies as $qid => $qdata): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="rheading<?php echo $ridx; ?>">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#rcollapse<?php echo $ridx; ?>" aria-expanded="false" aria-controls="rcollapse<?php echo $ridx; ?>">
                                            <div class="d-flex align-items-center w-100">
                                                <img src="<?php echo !empty($qdata['author_profile_picture']) ? '../images/profile_pics/' . htmlspecialchars($qdata['author_profile_picture']) : '../images/clearteenalogo.png'; ?>" alt="Author Avatar" style="width:36px;height:36px;border-radius:50%;object-fit:cover;margin-right:12px;" onerror="this.onerror=null;this.src='../images/clearteenalogo.png';">
                                                <div>
                                                    <span class="fw-semibold text-success"><?php echo htmlspecialchars($qdata['author_name']); ?></span><br>
                                                    <span class="text-muted" style="font-size:0.95em;">In: <?php echo htmlspecialchars($qdata['title']); ?></span>
                                                </div>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="rcollapse<?php echo $ridx; ?>" class="accordion-collapse collapse" aria-labelledby="rheading<?php echo $ridx; ?>" data-bs-parent="#repliesAccordion">
                                        <div class="accordion-body">
                                            <div class="mb-3 p-3 rounded bg-light border">
                                                <div class="fw-semibold mb-1">Question:</div>
                                                <div class="mb-1"><?php echo htmlspecialchars($qdata['question_body']); ?></div>
                                                <small class="text-muted">Posted on: <?php echo $qdata['question_created_at']; ?></small>
                                            </div>
                                            <div class="fw-semibold mb-2">Your Replies:</div>
                                            <?php foreach ($qdata['replies'] as $reply): ?>
                                                <div class="mb-3 p-2 border-start border-success border-3 bg-white rounded">
                                                    <p class="mb-1"><?php echo htmlspecialchars($reply['body']); ?></p>
                                                    <small class="text-muted">Replied on: <?php echo $reply['created_at']; ?></small>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php $ridx++; endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>No replies found.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php elseif ($active_section == 'certificates'): ?>
            <div class="card shadow-sm mb-4" data-aos="fade-up" id="certificates">
                <div class="card-header bg-success text-white">
                    <h5><i class="bi bi-award me-2"></i>Your Certificates</h5>
                </div>
                <div class="card-body">
                    <?php if ($cert_result->num_rows > 0): ?>
                        <div class="row">
                            <?php while ($cert = $cert_result->fetch_assoc()): ?>
                                <?php if ((int)$cert['module_id'] === 1) continue; ?>
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="card h-100 shadow-sm">
                                            <div class="card-body text-center">
                                                <i class="bi bi-patch-check-fill text-success" style="font-size: 3rem;"></i>
                                                <h5 class="mt-3 fw-bold"><?php echo htmlspecialchars($cert['title']); ?></h5>
                                                <p class="text-muted">
                                                    Completed on: <?php echo date('F j, Y', strtotime($cert['completion_date'])); ?>
                                                </p>
                                                <div class="d-flex justify-content-center gap-2 mt-3">
                                                <a href="learning/api/certificate.php?module_id=<?= $cert['module_id'] ?>&from=profile" 
                                                class="btn btn-primary px-3 py-2"
                                                target="_blank">
                                                    📄 View Certificate
                                                </a>
                                                
                                                <a href="learning/api/download_cert.php?module_id=<?php echo $cert['module_id']; ?>"
                                                class="btn btn-success px-3 py-2">
                                                    <i class="bi bi-download"></i> Download
                                                </a>
                                            </div>
                                            </div>
                                        </div>
                                    </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">You have no certificates yet.</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php elseif ($active_section == 'rewards'): ?>
                <div class="card shadow-sm mb-4" data-aos="fade-up">
                    <div class="card-header bg-success text-white">
                        <h5><i class="bi bi-gift me-2"></i>Your Rewards</h5>
                    </div>
                    <div class="card-body">

                        <?php if ($reward_result && $reward_result->num_rows > 0): ?>
                            <div class="row">
                                <?php while ($reward = $reward_result->fetch_assoc()): ?>
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="card h-100 shadow-sm border-success">
                                            <div class="card-body text-center">
                                                <i class="bi bi-gift-fill text-success" style="font-size: 3rem;"></i>

                                                <h5 class="mt-3 fw-bold">
                                                    <?php echo htmlspecialchars($reward['module_title']); ?>
                                                </h5>

                                                <p class="mt-2">
                                                    🎁 <strong><?php echo htmlspecialchars($reward['rewards']); ?></strong>
                                                </p>

                                                <p class="text-muted small">
                                                    Earned on:
                                                    <?php echo date('F j, Y', strtotime($reward['completion_date'])); ?>
                                                </p>

                                                <span class="badge bg-success">Unlocked</span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center mb-0">
                                You haven’t unlocked any rewards yet. Complete a module quiz to earn rewards 🌱
                            </p>
                        <?php endif; ?>

                    </div>
                </div>
        <?php elseif ($active_section == 'settings'): ?>
            <div class="card shadow-sm mb-4" data-aos="fade-up">
                <div class="card-header bg-success text-white">
                    <h5><i class="bi bi-gear me-2"></i>Account Settings</h5>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs mb-3" id="settingsTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="true">Update Profile</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password" type="button" role="tab" aria-controls="password" aria-selected="false">Change Password</button>
                        </li>
                    </ul>
                    <div class="tab-content" id="settingsTabContent">
                        <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                            <form action="User/editprofile.php" method="POST">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Name</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>">
                                </div>
                                <button type="submit" class="btn btn-success">Update Profile</button>
                            </form>
                        </div>
                        <div class="tab-pane fade" id="password" role="tabpanel" aria-labelledby="password-tab">
                            <form action="User/changepassword.php" method="POST">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password">
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password">
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                </div>
                                <button type="submit" class="btn btn-primary" name="change_password">Change Password</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="User/editprofile.php" method="POST">
        <div class="modal-body">
          <div class="mb-3">
            <label for="modal_name" class="form-label">Name</label>
            <input type="text" class="form-control" id="modal_name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>">
          </div>
          <div class="mb-3">
            <label for="modal_username" class="form-label">Username</label>
            <input type="text" class="form-control" id="modal_username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Upload Profile Picture Modal -->
<div class="modal fade" id="uploadProfilePicModal" tabindex="-1" aria-labelledby="uploadProfilePicModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" action="User/upload_profile_picture.php" method="POST" enctype="multipart/form-data">
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
AOS.init();
</script>
</body>
</html>
