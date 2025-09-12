<?php
// File: php/learning/learning-platform.php
session_start();
require_once 'connection.php';
require_once 'learning-functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$learning = new TeenAnimLearning($conn);
$user_id = $_SESSION['user_id'];

// Get module ID from URL
$module_id = isset($_GET['module']) ? intval($_GET['module']) : null;

// Get all modules with lessons
$modules = $learning->getModulesWithLessons($user_id);

// Get current module
$current_module = null;
if ($module_id) {
    foreach ($modules as $module) {
        if ($module['module_id'] == $module_id) {
            $current_module = $module;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interactive Learning - Teen-Anim</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/homepage.css">
    <link rel="stylesheet" href="../css/learning.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="learning-container">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2><i class="bi bi-mortarboard me-2"></i>Interactive Learning</h2>
            <div class="progress-overview">
                <div class="progress-text">
                    <span>Overall Progress</span>
                    <span id="total-progress">0%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" id="total-progress-bar"></div>
                </div>
            </div>
        </div>
        
        <div class="sidebar-content">
            <?php foreach ($modules as $module): ?>
                <?php if (!empty($module['lessons'])): ?>
                <div class="module-section">
                    <div class="module-header <?php echo ($current_module && $current_module['module_id'] == $module['module_id']) ? 'active' : ''; ?>" 
                         data-module-id="<?php echo $module['module_id']; ?>">
                        <div class="module-title"><?php echo htmlspecialchars($module['title']); ?></div>
                        <div class="module-meta">
                            <span><?php echo count($module['lessons']); ?> lessons</span>
                            <span><?php echo $module['progress']; ?>% complete</span>
                        </div>
                    </div>
                    
                    <?php if ($current_module && $current_module['module_id'] == $module['module_id']): ?>
                    <div class="lessons-list">
                        <?php foreach ($module['lessons'] as $lesson): ?>
                        <div class="lesson-item" data-lesson-id="<?php echo $lesson['lesson_id']; ?>">
                            <div class="lesson-status <?php echo $lesson['completed'] ? 'completed' : ''; ?>"></div>
                            <div class="lesson-details">
                                <div class="lesson-title"><?php echo htmlspecialchars($lesson['title']); ?></div>
                                <div class="lesson-meta">
                                    <?php if ($lesson['completed']): ?>
                                        <span class="text-success">Completed</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div id="welcome-screen" class="welcome-screen">
            <div>
                <div class="welcome-icon">🌱</div>
                <h2>Welcome to Interactive Learning</h2>
                <p class="text-muted mb-4">Select a module from the sidebar to start your farming education journey</p>
                <?php if (!$current_module): ?>
                <a href="../modulepage.php" class="btn btn-outline-success">
                    <i class="bi bi-arrow-left me-1"></i>Back to Modules
                </a>
                <?php endif; ?>
            </div>
        </div>

        <div id="lesson-view" class="lesson-view" style="display: none;">
            <!-- Lesson Header -->
            <div class="lesson-header d-flex justify-content-between align-items-start">
                <div class="lesson-info">
                    <h1 id="lesson-title"></h1>
                    <p id="lesson-module"></p>
                </div>
                <button id="complete-btn" class="complete-btn">
                    <span class="complete-icon"><i class="bi bi-circle"></i></span>
                    <span class="complete-text">Mark Complete</span>
                </button>
            </div>

            <!-- Lesson Content -->
            <div class="lesson-content">
                <div class="lesson-body" id="lesson-body"></div>
            </div>

            <!-- Navigation Footer -->
            <div class="lesson-footer">
                <button id="prev-btn" class="nav-btn"><i class="bi bi-arrow-left"></i> Previous</button>
                <button id="next-btn" class="nav-btn next-btn">Next <i class="bi bi-arrow-right"></i></button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
class TeenAnimLearning {
    constructor() {
        this.currentLesson = null;
        this.currentModule = <?php echo $current_module ? json_encode($current_module) : 'null'; ?>;
        this.modules = <?php echo json_encode($modules); ?>;
        this.userId = <?php echo $_SESSION['user_id']; ?>;
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.updateProgress();
        
        if (this.currentModule && this.currentModule.lessons.length > 0) {
            const firstIncompleteLesson = this.currentModule.lessons.find(lesson => !lesson.completed) || this.currentModule.lessons[0];
            this.loadLesson(firstIncompleteLesson.lesson_id);
        }
    }
    
    bindEvents() {
        document.querySelectorAll('.module-header').forEach(header => {
            header.addEventListener('click', (e) => {
                const moduleId = parseInt(e.currentTarget.dataset.moduleId);
                window.location.href = `learning-platform.php?module=${moduleId}`;
            });
        });
        
        document.querySelectorAll('.lesson-item').forEach(item => {
            item.addEventListener('click', (e) => {
                const lessonId = parseInt(e.currentTarget.dataset.lessonId);
                this.loadLesson(lessonId);
            });
        });
        
        document.getElementById('complete-btn').addEventListener('click', () => {
            if (this.currentLesson) {
                this.markLessonComplete(this.currentLesson.lesson_id);
            }
        });
        
        document.getElementById('prev-btn').addEventListener('click', () => {
            this.navigateLesson('previous');
        });
        
        document.getElementById('next-btn').addEventListener('click', () => {
            this.navigateLesson('next');
        });
    }
    
    async loadLesson(lessonId) {
        try {
            const response = await fetch(`learning/api/get-lesson.php?id=${lessonId}`);
            const lesson = await response.json();
            if (lesson.error) throw new Error(lesson.error);
            
            this.currentLesson = lesson;
            this.showLessonView();
            this.updateActiveLesson();
        } catch (error) {
            console.error('Failed to load lesson:', error);
            alert('Failed to load lesson. Please try again.');
        }
    }
    
    async markLessonComplete(lessonId) {
        try {
            const response = await fetch('learning/api/mark-complete.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ lesson_id: lessonId, user_id: this.userId })
            });
            
            const result = await response.json();
            if (result.success) {
                this.currentLesson.completed = 1;
                this.updateCompleteButton();
                this.updateLessonStatus(lessonId);
                this.updateProgress();
                setTimeout(() => this.navigateLesson('next'), 2000);
            } else {
                throw new Error(result.message || 'Failed to mark lesson as complete');
            }
        } catch (error) {
            console.error('Failed to mark lesson complete:', error);
            alert('Failed to mark lesson as complete. Please try again.');
        }
    }
    
    showLessonView() {
        document.getElementById('welcome-screen').style.display = 'none';
        document.getElementById('lesson-view').style.display = 'flex';
        
        document.getElementById('lesson-title').textContent = this.currentLesson.title;
        document.getElementById('lesson-module').textContent = this.currentLesson.module_title;
        document.getElementById('lesson-body').innerHTML = this.currentLesson.content;
        
        this.updateCompleteButton();
        this.updateNavigationButtons();
    }
    
    updateCompleteButton() {
        const completeBtn = document.getElementById('complete-btn');
        const isCompleted = this.currentLesson.completed == 1;
        
        if (isCompleted) {
            completeBtn.classList.add('completed');
            completeBtn.querySelector('.complete-icon').innerHTML = '<i class="bi bi-check-circle-fill"></i>';
            completeBtn.querySelector('.complete-text').textContent = 'Completed';
            completeBtn.disabled = true;
        } else {
            completeBtn.classList.remove('completed');
            completeBtn.querySelector('.complete-icon').innerHTML = '<i class="bi bi-circle"></i>';
            completeBtn.query
            completeBtn.querySelector('.complete-text').textContent = 'Mark Complete';
            completeBtn.disabled = false;
        }
    }
    
    updateLessonStatus(lessonId) {
        const lessonItem = document.querySelector(`.lesson-item[data-lesson-id="${lessonId}"]`);
        if (lessonItem) {
            const status = lessonItem.querySelector('.lesson-status');
            status.classList.add('completed');
            status.textContent = '✓';
            lessonItem.querySelector('.lesson-meta').innerHTML = '<span class="text-success">Completed</span>';
        }
    }
    
    updateProgress() {
        let totalLessons = 0, completedLessons = 0;
        
        this.modules.forEach(module => {
            module.lessons.forEach(lesson => {
                totalLessons++;
                if (lesson.completed) completedLessons++;
            });
        });
        
        const progress = totalLessons > 0 ? Math.round((completedLessons / totalLessons) * 100) : 0;
        document.getElementById('total-progress').textContent = `${progress}%`;
        document.getElementById('total-progress-bar').style.width = `${progress}%`;
    }
    
    updateNavigationButtons() {
        if (!this.currentModule || !this.currentLesson) return;
        
        const lessons = this.currentModule.lessons;
        const index = lessons.findIndex(l => l.lesson_id == this.currentLesson.lesson_id);
        
        document.getElementById('prev-btn').disabled = index <= 0;
        document.getElementById('next-btn').disabled = index >= lessons.length - 1;
    }
    
    navigateLesson(direction) {
        if (!this.currentModule || !this.currentLesson) return;
        
        const lessons = this.currentModule.lessons;
        const index = lessons.findIndex(l => l.lesson_id == this.currentLesson.lesson_id);
        
        if (direction === 'previous' && index > 0) {
            this.loadLesson(lessons[index - 1].lesson_id);
        } else if (direction === 'next' && index < lessons.length - 1) {
            this.loadLesson(lessons[index + 1].lesson_id);
        }
    }
    
    updateActiveLesson() {
        document.querySelectorAll('.lesson-item').forEach(item => {
            item.classList.remove('active');
            if (parseInt(item.dataset.lessonId) === this.currentLesson.lesson_id) {
                item.classList.add('active');
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new TeenAnimLearning();
});
</script>
</body>
</html>
