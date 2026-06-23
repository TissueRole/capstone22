<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/notifications/notifications_bootstrap.php';

// Determine base path for links and images
$base = '';
if (strpos($_SERVER['PHP_SELF'], 'Admin/') !== false) {
    $base = '../../';
} elseif (strpos($_SERVER['PHP_SELF'], 'Forum/') !== false) {
    $base = '../../';
} elseif (strpos($_SERVER['PHP_SELF'], 'php/') !== false) {
    $base = '../';
} else {
    $base = '';
}

// Get current page for active navigation
$current_page = basename($_SERVER['PHP_SELF']);
$current_path = $_SERVER['PHP_SELF'];
$session_role = $_SESSION['role'] ?? '';

// Function to check if a link should be active
function isActiveLink($link_path, $current_path, $current_page) {
    // Check for exact page match
    if (strpos($current_path, $link_path) !== false) {
        return true;
    }
    
    // Special cases for different sections
    if ($link_path === 'community.php' && strpos($current_path, 'Forum/') !== false) {
        return true;
    }
    
    if ($link_path === 'index.php' && $current_page === 'index.php') {
        return true;
    }
    
    return false;
}
?>
<link rel="stylesheet" href="<?php echo $base; ?>css/navbar.css">
<nav class="navbar navbar-expand-lg fixed-top navbar-modern w-100">
  <div class="container-fluid">
    <?php if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true): ?>
      <?php
        $is_login_page = strpos($_SERVER['PHP_SELF'], 'login.php') !== false;
      ?>
      <div class="d-flex w-100 align-items-center">
        <a class="navbar-brand me-auto" href="<?php echo $base; ?>index.php">
          <img src="<?php echo $base; ?>images/clearteenalogo.png" class="teenanimlogo" alt="home logo">
          TEEN-ANIM
        </a>
        <?php if (!$is_login_page): ?>
        <div class="ms-auto">
          <a href="<?php echo $base; ?>php/login.php" class="btn btn-signin">Sign In</a>
        </div>
        <div style="width: 120px;"></div>
        <?php else: ?>
        <div class="flex-grow-1"></div>
        <div style="width: 120px;"></div>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <a class="navbar-brand" href="<?php echo $base; ?>index.php">
        <img src="<?php echo $base; ?>images/clearteenalogo.png" class="teenanimlogo" alt="home logo">
        TEEN-ANIM
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <?php if (!in_array($session_role, ['admin', 'agriculturist'], true)): ?>
          <ul class="navbar-nav justify-content-center w-100 align-items-lg-center">
            <li class="nav-item">
              <a class="nav-link <?php echo isActiveLink('modulepage.php', $current_path, $current_page) ? 'active' : ''; ?>" href="<?php echo $base; ?>php/modulepage.php">Module</a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?php echo isActiveLink('community.php', $current_path, $current_page) ? 'active' : ''; ?>" href="<?php echo $base; ?>php/Forum/community.php">Farming Community</a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?php echo isActiveLink('simulation.php', $current_path, $current_page) ? 'active' : ''; ?>" href="<?php echo $base; ?>php/simulation.php">Simulation</a>
            </li>
          </ul>
        <?php else: ?>
          <div class="w-100"></div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</nav>
<?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
  <?php
    include_once dirname(__FILE__) . '/connection.php';
    require_once __DIR__ . '/notifications/notifications_bootstrap.php';
    $user_id = $_SESSION['user_id'];
    $profile_pic = '';
    $user_name = '';
    $user_role = '';
    $sql = "SELECT name, profile_picture, role FROM users WHERE user_id = ?";
    $notifications = [];
    $unread_notifications = 0;
    if ($stmt = $conn->prepare($sql)) {
      $stmt->bind_param("i", $user_id);
      $stmt->execute();
      $stmt->bind_result($name, $profile_picture, $role);
      if ($stmt->fetch()) {
        $user_name = htmlspecialchars($name);
        $user_role = htmlspecialchars(ucfirst($role));
        $profile_pic = !empty($profile_picture) ? $base . "images/profile_pics/" . htmlspecialchars($profile_picture) : $base . "images/clearteenalogo.png";
      } else {
        $profile_pic = $base . "images/clearteenalogo.png";
      }
      $stmt->close();
    } else {
      $profile_pic = $base . "images/clearteenalogo.png";
    }

    $notifCountStmt = $conn->prepare("
      SELECT COUNT(*)
      FROM notifications
      WHERE user_id = ? AND is_read = 0
    ");
    if ($notifCountStmt) {
      $notifCountStmt->bind_param("i", $user_id);
      $notifCountStmt->execute();
      $notifCountStmt->bind_result($unreadCount);
      if ($notifCountStmt->fetch()) {
        $unread_notifications = (int) $unreadCount;
      }
      $notifCountStmt->close();
    }

    $notifStmt = $conn->prepare("
      SELECT notification_id, message, link, is_read, created_at
      FROM notifications
      WHERE user_id = ?
      ORDER BY created_at DESC
      LIMIT 8
    ");
    if ($notifStmt) {
      $notifStmt->bind_param("i", $user_id);
      $notifStmt->execute();
      $notifResult = $notifStmt->get_result();
      while ($row = $notifResult->fetch_assoc()) {
        $notifications[] = $row;
      }
      $notifStmt->close();
    }
  ?>
  <div class="profile-dropdown-topright position-fixed d-flex align-items-start gap-2" style="top: 20px; right: 40px; z-index: 2000;">
    <div class="dropdown">
      <a class="btn btn-profile d-flex align-items-center justify-content-center position-relative p-0" href="#" id="notificationDropdownTop" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="background: none; border: none; width: 44px; height: 44px;">
        <svg width="21" height="21" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="display:block;">
          <path d="M12 3.75a4.25 4.25 0 0 0-4.25 4.25v1.14c0 .82-.24 1.62-.69 2.3l-1.1 1.67a2.25 2.25 0 0 0 1.88 3.49h8.32a2.25 2.25 0 0 0 1.88-3.49l-1.1-1.67a4.14 4.14 0 0 1-.69-2.3V8A4.25 4.25 0 0 0 12 3.75Z" stroke="#388e3c" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M9.75 18.5a2.25 2.25 0 0 0 4.5 0" stroke="#388e3c" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <?php if ($unread_notifications > 0): ?>
          <span class="notification-badge"><?= $unread_notifications ?></span>
        <?php endif; ?>
      </a>
      <div class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationDropdownTop">
        <div class="notification-header">
          <span>Notifications</span>
          <a href="<?php echo $base; ?>php/notifications/mark_all_read.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="text-decoration-none small">Mark all read</a>
        </div>
        <?php if (!empty($notifications)): ?>
          <?php foreach ($notifications as $notification): ?>
            <a class="notification-item <?php echo (int) $notification['is_read'] === 0 ? 'unread' : ''; ?>"
               href="<?php echo $base; ?>php/notifications/mark_read.php?id=<?php echo (int) $notification['notification_id']; ?>&redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">
              <span class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></span>
              <span class="notification-time"><?php echo date('M d, Y g:i A', strtotime($notification['created_at'])); ?></span>
            </a>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="notification-empty">No notifications yet.</div>
        <?php endif; ?>
      </div>
    </div>
    <div class="dropdown">
      <a class="btn btn-profile d-flex align-items-center p-0" href="#" id="profileDropdownTop" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="background: none; border: none;">
        <img src="<?php echo $profile_pic; ?>" alt="Profile" class="profile-pic-navbar" style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 2px solid #4caf50; background: #fff;">
      </a>
      <ul class="dropdown-menu dropdown-menu-end custom-profile-dropdown" aria-labelledby="profileDropdownTop">
        <li class="dropdown-header text-center">
          <img src="<?php echo $profile_pic; ?>" alt="Profile" style="width: 56px; height: 56px; border-radius: 50%; object-fit: cover; border: 2px solid #4caf50; background: #fff; margin-bottom: 8px;">
          <div style="font-weight: 600;"><?php echo $user_name; ?></div>
          <div style="font-size: 0.95em; color: #388e3c; margin-top: 2px; font-weight: 500;">
            <?php echo $user_role; ?>
          </div>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li>
            <a class="dropdown-item" 
              href="<?php 
                    if ($_SESSION['role'] === 'admin') {
                        echo $base . 'php/Admin/adminpage.php';
                    } elseif ($_SESSION['role'] === 'agriculturist') {
                        echo $base . 'php/Admin/agriculturistpage.php';
                    } else {
                        echo $base . 'php/userpage.php';
                    }
              ?>">
              Profile
            </a>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="<?php echo $base; ?>php/logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
<?php endif; ?> 
