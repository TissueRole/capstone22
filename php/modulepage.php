<?php
// File: php/modulepage.php (Cleaned version)
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

// Include learning functions
require_once 'learning-functions.php';
$learning = new TeenAnimLearning($conn);

// Get user's progress data
$user_progress = $learning->getUserProgress($_SESSION['user_id']);

// Existing filter code
$Type = isset($_POST['Type']) ? $conn->real_escape_string($_POST['Type']) : '';
$Category = isset($_POST['Category']) ? $conn->real_escape_string($_POST['Category']) : '';
$sortOption = isset($_POST['sortOption']) ? $conn->real_escape_string($_POST['sortOption']) : '';

$sql = "SELECT m.*, 
           (SELECT COUNT(*) FROM lessons l WHERE l.module_id = m.module_id) as lesson_count,
           (SELECT COUNT(*) FROM lesson_progress lp 
            JOIN lessons l ON lp.lesson_id = l.lesson_id 
            WHERE l.module_id = m.module_id AND lp.user_id = ? AND lp.completed = 1) as completed_lessons
        FROM modules m WHERE 1=1";

$params = [$_SESSION['user_id']];
$param_types = "i";

if ($Type != '') {
    $sql .= " AND type = ?";
    $params[] = $Type;
    $param_types .= "s";
}
if ($Category != '') {
    $sql .= " AND category = ?";
    $params[] = $Category;
    $param_types .= "s";
}
if ($sortOption != '') {
    if ($sortOption == 'title') {
        $sql .= " ORDER BY title ASC";
    } elseif ($sortOption == 'date') {
        $sql .= " ORDER BY created_at DESC";
    }
}

$stmt = $conn->prepare($sql);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

if ($result === false) {
    die("Error: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learning Modules - Teen-Anim</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/homepage.css">
    <style>
        body {
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
        }
        .progress-card {
            background: linear-gradient(135deg, #43a047 0%, #66bb6a 100%);
            color: white;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(76,175,80,0.15);
        }
        .progress-stats {
            display: flex;
            justify-content: space-around;
            margin-top: 1rem;
        }
        .stat-item {
            text-align: center;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
        }
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        .module-card {
            border-radius: 1rem;
            box-shadow: 0 4px 24px rgba(76,175,80,0.10);
            transition: transform 0.18s, box-shadow 0.18s;
            overflow: hidden;
            background: #fff;
            min-height: 420px;
            position: relative;
        }
        .module-card:hover {
            transform: translateY(-8px) scale(1.03);
            box-shadow: 0 8px 32px rgba(76,175,80,0.18);
        }
        .module-card-img {
            height: 180px;
            object-fit: cover;
            border-radius: 1rem 1rem 0 0;
        }
        .progress-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, transparent 100%);
            padding: 1rem;
            color: white;
        }
        .progress-bar-custom {
            height: 6px;
            background: rgba(255,255,255,0.3);
            border-radius: 3px;
            overflow: hidden;
            margin-top: 0.5rem;
        }
        .progress-fill-custom {
            height: 100%;
            background: #4caf50;
            border-radius: 3px;
            transition: width 0.3s ease;
        }
        .lesson-count {
            display: inline-block;
            background: #e8f5e9;
            color: #2e7d32;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }
        .view-btn {
            background: #43a047;
            color: #fff;
            border-radius: 2rem;
            font-weight: 600;
            transition: background 0.2s;
            border: none;
            padding: 0.75rem 1.5rem;
        }
        .view-btn:hover {
            background: #256029;
            color: #fff;
        }
        .interactive-btn {
            background: linear-gradient(45deg, #43a047, #66bb6a);
            color: #fff;
            border: none;
            border-radius: 2rem;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(76,175,80,0.2);
        }
        .interactive-btn:hover {
            background: linear-gradient(45deg, #388e3c, #43a047);
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(76,175,80,0.3);
        }
        .module-card-title {
            font-weight: 700;
            color: #388e3c;
            margin-bottom: 1rem;
        }
        .module-card-desc {
            color: #444;
            min-height: 60px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container py-4">
    <!-- Progress Overview Card -->
    <div class="progress-card" data-aos="fade-up">
        <h3><i class="bi bi-graph-up me-2"></i>Your Learning Progress</h3>
        <div class="progress-stats">
            <div class="stat-item">
                <div class="stat-number"><?php echo $user_progress['progress_percentage']; ?>%</div>
                <div class="stat-label">Overall Progress</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $user_progress['completed_lessons']; ?></div>
                <div class="stat-label">Lessons Completed</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $progress = $row['lesson_count'] > 0 ? 
                        round(($row['completed_lessons'] / $row['lesson_count']) * 100) : 0;

                    // Card link target
                    $card_link = "learning-platform.php?module=" . $row['module_id'];

                    echo '<div class="col-md-6 col-lg-4" data-aos="fade-up">';
                    echo '<a href="' . $card_link . '" class="text-decoration-none text-dark">';
                    echo '<div class="card module-card h-100">';

                    // Handle image display
                    $image_path = $row['image_path'];
                    if (filter_var($image_path, FILTER_VALIDATE_URL)) {
                        echo '<img src="' . htmlspecialchars($image_path) . '" class="module-card-img card-img-top" alt="' . htmlspecialchars($row['title']) . '" onerror="this.src=\'../images/default-module.jpg\'; this.onerror=null;">';
                    } else {
                        echo '<img src="' . htmlspecialchars($image_path) . '" class="module-card-img card-img-top" alt="' . htmlspecialchars($row['title']) . '" onerror="this.src=\'../images/default-module.jpg\'; this.onerror=null;">';
                    }

                    // Progress overlay
                    if ($row['lesson_count'] > 0) {
                        echo '<div class="progress-overlay">';
                        echo '<div class="d-flex justify-content-between align-items-center">';
                        echo '<span style="font-size: 0.9rem;">' . $row['completed_lessons'] . '/' . $row['lesson_count'] . ' lessons</span>';
                        echo '<span style="font-weight: bold;">' . $progress . '%</span>';
                        echo '</div>';
                        echo '<div class="progress-bar-custom">';
                        echo '<div class="progress-fill-custom" style="width: ' . $progress . '%"></div>';
                        echo '</div>';
                        echo '</div>';
                    }

                    echo '<div class="card-body d-flex flex-column">';

                    if ($row['lesson_count'] > 0) {
                        echo '<span class="lesson-count"><i class="bi bi-book me-1"></i>' . $row['lesson_count'] . ' Lessons</span>';
                    }

                    echo '<h5 class="module-card-title">' . htmlspecialchars($row['title']) . '</h5>';
                    echo '<p class="module-card-desc flex-grow-1">' . htmlspecialchars($row['description']) . '</p>';

                    echo '</div>'; // card-body
                    echo '</div>'; // card
                    echo '</a>';   // link
                    echo '</div>'; // col
                }
            } else {
                echo "<div class='col-12'><h3 class='text-center text-muted mt-5'>No modules found.</h3></div>";
            }
        ?>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
AOS.init();
</script>
</body>
</html>
