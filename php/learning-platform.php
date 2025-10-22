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

if ($module_id) {
    // ✅ Only load the selected module
    $modules = [$learning->getModuleWithLessons($module_id, $user_id)];
    $current_module = $modules[0];
} else {
    // ❌ No module selected → redirect back
    header("Location: ../modulepage.php");
    exit();
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
    <link rel="stylesheet" href="../css/learning.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="learning-container">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2><i class="bi bi-mortarboard me-2"></i><?php echo htmlspecialchars($current_module['title']); ?></h2>
            <div class="progress-overview">
                <div class="progress-text">
                    <span>Overall Progress</span>
                    <span id="total-progress"><?php echo $current_module['progress']; ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" id="total-progress-bar" style="width: <?php echo $current_module['progress']; ?>%"></div>
                </div>
            </div>
        </div>
        
        <div class="sidebar-content">
            <?php if (!empty($current_module['lessons'])): ?>
                <div class="module-section">
                    <div class="module-header active" data-module-id="<?php echo $current_module['module_id']; ?>">
                        <div class="module-title"><?php echo htmlspecialchars($current_module['title']); ?></div>
                        <div class="module-meta">
                            <span><?php echo count($current_module['lessons']); ?> lessons</span>
                            <span><?php echo $current_module['progress']; ?>% complete</span>
                        </div>
                    </div>
                    
                    <div class="lessons-list">
                        <?php foreach ($current_module['lessons'] as $lesson): ?>
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
                </div>
            <?php endif; ?>
            <?php if (!empty($current_module['quiz_id'])): ?>
                <div class="lesson-item quiz-item" data-quiz-id="<?php echo $current_module['quiz_id']; ?>">
                    <div class="lesson-status"></div>
                    <div class="lesson-details">
                        <div class="lesson-title">
                            <i class="bi bi-question-circle me-2"></i> Module Quiz
                        </div>
                        <div class="lesson-meta text-muted">
                            Test your knowledge of this module
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div id="welcome-screen" class="welcome-screen">
            <div>
                <div class="welcome-icon">🌱</div>
                <h2><?php echo htmlspecialchars($current_module['title']); ?></h2>
                <p class="text-muted mb-4"><?php echo htmlspecialchars($current_module['description']); ?></p>
                <a href="modulepage.php" class="btn btn-outline-success">
                    <i class="bi bi-arrow-left me-1"></i>Back to Modules
                </a>
            </div>
        </div>

        <div id="lesson-view" class="lesson-view" style="display: none;">
            <!-- Lesson Header -->
            <div class="lesson-header d-flex justify-content-between align-items-start">
                <div class="lesson-info">
                    <h1 id="lesson-title"></h1>
                    <p id="lesson-module"><?php echo htmlspecialchars($current_module['title']); ?></p>
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

        <div id="quiz-view" class="lesson-view" style="display: none;">
            <!-- Quiz Header -->
            <div class="lesson-header d-flex justify-content-between align-items-start">
                <div class="lesson-info">
                    <h1>Module Quiz</h1>
                    <p><?php echo htmlspecialchars($current_module['title']); ?></p>
                </div>
            </div>

            <!-- Quiz Content -->
            <div class="lesson-content">
                <div class="lesson-body" id="quiz-questions"></div>
            </div>

            <!-- Quiz Footer -->
            <div class="lesson-footer d-flex justify-content-between">
                <div id="quiz-questions"></div>
                <button id="submit-quiz" class="btn btn-success">Submit Quiz</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
class TeenAnimLearning {
    constructor() {
        this.currentLesson = null;
        this.currentModule = <?php echo json_encode($current_module); ?>;
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
        // Lessons
        document.querySelectorAll('.lesson-item[data-lesson-id]').forEach(item => {
            item.addEventListener('click', (e) => {
                const lessonId = parseInt(e.currentTarget.dataset.lessonId);
                this.loadLesson(lessonId);
            });
        });

        // Quiz button
        const quizBtn = document.querySelector('.quiz-item');
        if (quizBtn) {
            quizBtn.addEventListener('click', (e) => {
                e.stopPropagation(); // prevent triggering lesson clicks
                this.loadQuiz(quizBtn.dataset.quizId);
            });
        }

        // Complete lesson button
        document.getElementById('complete-btn').addEventListener('click', () => {
            if (this.currentLesson) {
                this.markLessonComplete(this.currentLesson.lesson_id);
            }
        });

        // Navigation
        document.getElementById('prev-btn').addEventListener('click', () => {
            this.navigateLesson('previous');
        });
        
        document.getElementById('next-btn').addEventListener('click', () => {
            this.navigateLesson('next');
        });
    }

    // Hide everything before showing one screen
    hideAllViews() {
        document.getElementById('welcome-screen').style.display = 'none';
        document.getElementById('lesson-view').style.display = 'none';
        document.getElementById('quiz-view').style.display = 'none';
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
        this.hideAllViews();
        document.getElementById('lesson-view').style.display = 'flex';
        
        document.getElementById('lesson-title').textContent = this.currentLesson.title;
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
        const totalLessons = this.currentModule.lessons.length;
        const completedLessons = this.currentModule.lessons.filter(l => l.completed).length;
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

    // ---- QUIZ ----
    async loadQuiz(quizId) {
        try {
            const response = await fetch(`learning/api/get_quiz.php?id=${quizId}`);
            const quiz = await response.json();

            if (!quiz || quiz.error) throw new Error(quiz.error || "Failed to load quiz data.");

            // ✅ Show result if user already took it
            if (quiz.user_result) {
                this.showQuizResult(quiz.user_result, quizId);
            } else {
                this.showQuizView(quiz);
            }

        } catch (error) {
            console.error('Failed to load quiz:', error);
            alert('Failed to load quiz. Try again.');
        }
    }

    showQuizView(quiz) {
        this.hideAllViews();

        const quizView = document.getElementById('quiz-view');
        const quizContainer = document.getElementById('quiz-questions');
        if (!quizView || !quizContainer) {
            console.error("Quiz elements not found in DOM.");
            alert("Something went wrong loading the quiz view.");
            return;
        }

        quizView.style.display = 'flex';
        quizContainer.innerHTML = '';

        quiz.questions.forEach((q, index) => {
            const qEl = document.createElement('div');
            qEl.classList.add('quiz-question');
            qEl.innerHTML = `
                <h5>Q${index + 1}: ${q.question_text}</h5>
                <label class="option"><input type="radio" name="q${q.question_id}" value="A"> ${q.option_a}</label>
                <label class="option"><input type="radio" name="q${q.question_id}" value="B"> ${q.option_b}</label>
                <label class="option"><input type="radio" name="q${q.question_id}" value="C"> ${q.option_c}</label>
                <label class="option"><input type="radio" name="q${q.question_id}" value="D"> ${q.option_d}</label>
            `;
            quizContainer.appendChild(qEl);
        });

        const submitBtn = document.getElementById('submit-quiz');
        submitBtn.onclick = () => this.submitQuiz(quiz.quiz_id);
    }

    async submitQuiz(quizId) {
        const answers = [];

        document.querySelectorAll('.quiz-question').forEach(q => {
            const input = q.querySelector('input[type="radio"]:checked');
            if (input) {
                const questionId = input.name.replace('q', '');
                answers.push({
                    question_id: questionId,
                    selected_option: input.value
                });
            }
        });

        if (answers.length === 0) {
            alert('⚠️ Please answer all questions before submitting.');
            return;
        }

        try {
            const response = await fetch('learning/api/submit_quiz.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ quiz_id: quizId, answers })
            });

            const result = await response.json();
            console.log('Quiz result:', result);

            if (result.success) {
                this.showQuizResult(result, quizId);
            } else {
                alert(result.message || '⚠️ Failed to evaluate quiz.');
            }
        } catch (error) {
            console.error('Quiz submission error:', error);
            alert('⚠️ Network or server error. Please try again.');
        }
    }

    // ✅ Show result screen (after submission or reload)
    // ✅ Show result screen (after submission or reload)
    showQuizResult(result, quizId) {
        this.hideAllViews();
        const quizView = document.getElementById('quiz-view');
        quizView.style.display = 'flex';

        // Clear current content
        quizView.innerHTML = '';

        // 🎉 Passed
        if (result.score >= 70) {
            quizView.innerHTML = `
                <div class="text-center p-5 w-100">
                    <h3 class="text-success">🎉 Congratulations!</h3>
                    <p>You passed with a score of <strong>${result.score}%</strong>.</p>
                    <p>This quiz is now <strong>locked</strong>.</p>
                </div>
            `;
            return;
        }

        // ❌ Failed — show retry option
        quizView.innerHTML = `
            <div class="text-center p-5 w-100">
                <h3 class="text-danger">You scored ${result.score}%</h3>
                <p>You need at least 70% to pass.</p>
                <button id="retry-quiz" class="btn btn-outline-success mt-3">🔁 Try Again</button>
            </div>
        `;

        // ✅ Add retry event handler safely
        const retryBtn = document.getElementById('retry-quiz');
        retryBtn.addEventListener('click', async () => {
            // Hide the retry screen instantly
            quizView.style.opacity = '0';

            // Wait a short moment for a smoother transition
            setTimeout(async () => {
                quizView.innerHTML = `
                    <div class="lesson-body" id="quiz-questions"></div>
                    <div class="lesson-footer d-flex justify-content-between">
                        <button id="submit-quiz" class="btn btn-success" style="display:none;">Submit Quiz</button>
                    </div>
                `;

                try {
                    // Load the quiz questions again
                    await this.loadQuiz(quizId, true);

                    // ✅ Show submit button only after questions are ready
                    const submitBtn = document.getElementById('submit-quiz');
                    if (submitBtn) submitBtn.style.display = 'inline-block';

                    quizView.style.opacity = '1';
                } catch (error) {
                    console.error('Failed to reload quiz:', error);
                    alert('Failed to reload quiz. Please try again.');
                }
            }, 150);
        });
    }


    // ✅ Reload quiz data (used by Try Again)
    async loadQuiz(quizId, forceRetake = false) {
        try {
            const url = forceRetake 
                ? `learning/api/get_quiz.php?id=${quizId}&forceRetake=1` 
                : `learning/api/get_quiz.php?id=${quizId}`;
            const response = await fetch(url);
            const quiz = await response.json();

            if (!quiz || quiz.error) throw new Error(quiz.error || "Failed to load quiz data.");

            if (quiz.user_result && !forceRetake) {
                this.showQuizResult(quiz.user_result, quizId);
            } else {
                this.showQuizView(quiz);
            }

        } catch (error) {
            console.error('Failed to load quiz:', error);
            alert('Failed to load quiz. Try again.');
        }
    }


}

    document.addEventListener('DOMContentLoaded', () => {
        new TeenAnimLearning();
});
</script>

</body>
</html>
