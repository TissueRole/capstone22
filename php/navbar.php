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
<style>
  body {
    padding-top: 80px !important;
  }
  .navbar-modern {
    background: #fff !important;
    box-shadow: 0 2px 12px rgba(60, 120, 60, 0.08);
    border-bottom: 3px solid #4caf50;
    padding-top: 0.5rem;
    padding-bottom: 0.5rem;
    z-index: 1050;
  }
  .navbar-modern .navbar-brand {
    display: flex;
    align-items: center;
    font-weight: bold;
    font-size: 1.5rem;
    color: #388e3c !important;
    letter-spacing: 1px;
    transition: background 0.2s, color 0.2s, box-shadow 0.2s;
    border-radius: 10px;
    padding: 0.25rem 0.75rem;
    cursor: pointer;
    height: 56px;
    line-height: 48px;
    min-width: 0;
    box-sizing: border-box;
  }
  .navbar-modern .navbar-brand:hover, .navbar-modern .navbar-brand:focus {
    background: rgba(76, 175, 80, 0.10);
    color: #256029 !important;
    box-shadow: 0 2px 8px rgba(76, 175, 80, 0.10);
    text-decoration: none;
  }
  .navbar-modern .navbar-brand:hover .teenanimlogo, .navbar-modern .navbar-brand:focus .teenanimlogo {
    border-color: #256029;
    filter: brightness(0.95);
    transition: border-color 0.2s, filter 0.2s;
  }
  .navbar-modern .teenanimlogo {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 12px;
    border: 2px solid #4caf50;
    background: #fff;
  }
  .navbar-modern .navbar-nav .nav-link {
    color: #388e3c !important;
    font-weight: 500;
    font-size: 1.1rem;
    margin: 0 0.5rem;
    position: relative;
    transition: color 0.2s;
    padding: 0.5rem 1rem !important;
    border-radius: 8px;
  }
  .navbar-modern .navbar-nav .nav-link::after {
    content: '';
    display: block;
    width: 0;
    height: 3px;
    background: #4caf50;
    transition: width 0.3s ease;
    position: absolute;
    left: 50%;
    bottom: -2px;
    transform: translateX(-50%);
    border-radius: 2px;
  }
  .navbar-modern .navbar-nav .nav-link:hover,
  .navbar-modern .navbar-nav .nav-link.active {
    color: #256029 !important;
    background-color: rgba(76, 175, 80, 0.1);
  }
  .navbar-modern .navbar-nav .nav-link:hover::after,
  .navbar-modern .navbar-nav .nav-link.active::after {
    width: 80%;
  }
  .navbar-modern .navbar-nav .nav-link.active {
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(76, 175, 80, 0.15);
  }
  .navbar-modern .btn-signin, .navbar-modern .btn-profile {
    background: #4caf50;
    color: #fff;
    border-radius: 50px;
    padding: 0.5rem 1.5rem;
    font-weight: 600;
    border: none;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(76,175,80,0.08);
  }
  .navbar-modern .btn-signin:hover, .navbar-modern .btn-profile:hover {
    background: #388e3c;
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(76,175,80,0.2);
  }
  .navbar-toggler {
    border: none;
    outline: none;
  }
  .navbar-toggler:focus {
    box-shadow: 0 0 0 2px #4caf50;
  }
  @media (max-width: 991.98px) {
    .navbar-modern .navbar-nav .nav-link {
      margin: 0.5rem 0;
      font-size: 1.2rem;
    }
    .navbar-modern .navbar-collapse {
      background: #fff;
      border-radius: 0 0 1rem 1rem;
      box-shadow: 0 8px 24px rgba(76,175,80,0.08);
      padding: 1rem 0;
    }
  }
  .navbar-modern .navbar-nav {
    justify-content: center !important;
    width: 100%;
  }
  .profile-dropdown-topright {
    position: fixed;
    top: 200px;
    right: 40px;
    z-index: 2000;
  }
  .custom-profile-dropdown {
    min-width: 320px;
    top: 2px !important;
    right: 0 !important;
    left: auto !important;
    position: absolute !important;
    z-index: 2001;
    border-radius: 1.2rem;
    margin-top: 0 !important;
    background: #fff;
    color: #388e3c;
    padding-top: 0.5rem;
    padding-bottom: 0.5rem;
    border: solid 3px #4caf50;
  }
  .custom-profile-dropdown .dropdown-header {
    padding: 1rem 1rem 0.5rem 1rem;
    background: transparent;
    color: #388e3c;
  }
  .custom-profile-dropdown .dropdown-item {
    color: #388e3c;
    border-radius: 0.7rem;
    margin: 0 0.5rem;
    padding: 0.6rem 1rem;
    transition: background 0.2s, color 0.2s, padding 0.2s, font-size 0.2s, transform 0.2s;
    font-weight: 500;
  }
  .custom-profile-dropdown .dropdown-item:hover {
    background: #e8f5e9;
    color: #256029;
    padding: 0.9rem 1.3rem;
    font-size: 1.12rem;
    transform: scale(0.90);
  }
  .custom-profile-dropdown .dropdown-divider {
    border-top: 1px solid #c8e6c9;
    margin: 0.3rem 0;
  }
  /* Remove Bootstrap dropdown arrow/caret for profile dropdown */
  #profileDropdownTop::after {
    display: none !important;
  }
  
  /* Profile picture hover effects */
  .profile-pic-navbar {
    transition: all 0.3s ease;
    cursor: pointer;
  }
  
  .profile-pic-navbar:hover {
    transform: scale(1.1);
    border-color: #256029 !important;
    box-shadow: 0 4px 16px rgba(76, 175, 80, 0.3);
  }
  
  /* Profile dropdown button hover effects */
  #profileDropdownTop {
    transition: all 0.3s ease;
    border-radius: 50%;
    padding: 4px;
  }
  
  #profileDropdownTop:hover {
    background: rgba(76, 175, 80, 0.1) !important;
    transform: scale(1.05);
  }
  
  /* Profile dropdown header image hover */
  .custom-profile-dropdown .dropdown-header img {
    transition: all 0.3s ease;
  }
  
  .custom-profile-dropdown .dropdown-header:hover img {
    transform: scale(1.05);
    border-color: #256029;
  }
  .notification-dropdown {
    min-width: 360px;
    max-width: 420px;
    max-height: 440px;
    overflow-y: auto;
    border-radius: 1rem;
    border: 2px solid #d9eadb;
    padding: 0;
  }
  .notification-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.85rem 1rem;
    border-bottom: 1px solid #e7f1e8;
    font-weight: 700;
    color: #1b5e20;
  }
  .notification-item {
    display: block;
    padding: 0.85rem 1rem;
    border-bottom: 1px solid #edf5ee;
    text-decoration: none;
    color: #234127;
  }
  .notification-item:hover {
    background: #f4fbf5;
  }
  .notification-item.unread {
    background: #f7fcf8;
  }
  .notification-message {
    display: block;
    font-size: 0.94rem;
    line-height: 1.45;
  }
  .notification-time {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.78rem;
    color: #6b816f;
  }
  .notification-empty {
    padding: 1rem;
    color: #6b816f;
    text-align: center;
  }
  .notification-badge {
    position: absolute;
    top: -4px;
    right: -3px;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    border-radius: 999px;
    background: #d32f2f;
    color: #fff;
    font-size: 0.72rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }
</style>
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
