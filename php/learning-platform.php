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
    header("Location: modulepage.php");
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
                <div id="lesson-read-hint" class="lesson-read-hint">
                    Read at your own pace and ENJOY!
                </div>
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
        this.lessonReachedEnd = false;
        this.lessonUnlockSent = false;
        this.hasScrolledThroughLesson = false;
        this.currentLessonCheckpointsPassed = true;
        this.lessonContentEl = document.querySelector('.lesson-content');
        this.onLessonScroll = this.handleLessonScroll.bind(this);
        
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

        // Quiz button with Bootstrap modal alert
        const quizBtn = document.querySelector('.quiz-item');
        if (quizBtn) {
            quizBtn.addEventListener('click', async (e) => {
                e.stopPropagation();

                const quizId = quizBtn.dataset.quizId;
                const moduleId = this.currentModule.module_id;

                try {
                    const res = await fetch(`learning/api/check_progress.php?module_id=${moduleId}`);
                    const data = await res.json();

                    if (data.status === 'incomplete') {
                        const warningModal = new bootstrap.Modal(document.getElementById('lessonWarningModal'));
                        warningModal.show();
                        return;
                    }

                    this.loadQuiz(quizId);
                } catch (error) {
                    console.error('Error checking progress:', error);
                    const warningModal = new bootstrap.Modal(document.getElementById('lessonWarningModal'));
                    document.querySelector('#lessonWarningModal .modal-body').textContent =
                        'Please finish all lessons in this module before you can take the quiz.';
                    warningModal.show();
                }
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

        if (this.lessonContentEl) {
            this.lessonContentEl.addEventListener('scroll', this.onLessonScroll);
        }
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
                const moduleLesson = this.currentModule.lessons.find(
                    lesson => parseInt(lesson.lesson_id) === parseInt(lessonId)
                );
                if (moduleLesson) {
                    moduleLesson.completed = 1;
                }
                this.updateCompleteButton();
                this.updateLessonStatus(lessonId);
                this.updateProgress();

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

        this.lessonReachedEnd = this.currentLesson.completed == 1;
        this.lessonUnlockSent = this.currentLesson.completed == 1;
        this.hasScrolledThroughLesson = false;
        this.currentLessonCheckpointsPassed = this.currentLesson.completed == 1;
        if (this.lessonContentEl) {
            this.lessonContentEl.scrollTop = 0;
        }
        this.initLessonCheckpoints();
        this.updateCompleteButton();
        this.updateNavigationButtons();
        this.updateReadHint();
        requestAnimationFrame(() => this.evaluateLessonReadiness());
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
            completeBtn.querySelector('.complete-text').textContent = this.canUnlockCompletion()
                ? 'Mark Complete'
                : 'Finish Lesson Requirements';
            completeBtn.disabled = !this.canUnlockCompletion();
        }
    }

    updateReadHint() {
        const hint = document.getElementById('lesson-read-hint');
        if (!hint || !this.currentLesson) return;

        if (this.currentLesson.completed == 1) {
            hint.classList.add('ready');
            hint.textContent = 'This lesson is already completed.';
            return;
        }

        if (!this.currentLessonCheckpointsPassed) {
            hint.classList.remove('ready');
            //hint.textContent = 'Answer all checkpoint questions correctly to unlock completion.';
            return;
        }

        if (this.lessonReachedEnd) {
            hint.classList.add('ready');
            //hint.textContent = 'Completion unlocked. You reached the end of the lesson.';
            return;
        }

        hint.classList.remove('ready');
        //hint.textContent = 'Scroll to the end of this lesson to unlock completion.';
    }

    handleLessonScroll() {
        if (this.lessonContentEl && this.lessonContentEl.scrollTop > 16) {
            this.hasScrolledThroughLesson = true;
        }
        this.evaluateLessonReadiness();
    }

    canUnlockCompletion() {
        return this.lessonReachedEnd && this.currentLessonCheckpointsPassed;
    }

    evaluateLessonReadiness() {
        if (!this.lessonContentEl || !this.currentLesson || this.currentLesson.completed == 1) {
            return;
        }

        const { scrollTop, clientHeight, scrollHeight } = this.lessonContentEl;
        const fitsWithoutScrolling = scrollHeight <= clientHeight + 24;
        const reachedEnd = scrollTop + clientHeight >= scrollHeight - 24;

        if (!fitsWithoutScrolling && !this.hasScrolledThroughLesson) {
            return;
        }

        if ((!fitsWithoutScrolling && !reachedEnd) || this.lessonReachedEnd) {
            return;
        }

        this.lessonReachedEnd = true;
        this.updateReadHint();
        this.updateCompleteButton();
        if (this.canUnlockCompletion()) {
            this.unlockLessonCompletion(this.currentLesson.lesson_id);
        }
    }

    initLessonCheckpoints() {
        const blocks = document.querySelectorAll('.lesson-checkpoint');
        this.currentLessonCheckpointsPassed = blocks.length === 0 || this.currentLesson.completed == 1;

        blocks.forEach((block, index) => {
            const payloadRaw = block.dataset.checkpoint;

            try {
                const payload = JSON.parse(payloadRaw);
                block.innerHTML = this.renderCheckpointMarkup(payload, index);
                const submitButton = block.querySelector('.checkpoint-submit');
                submitButton?.addEventListener('click', () => this.handleCheckpointSubmit(block, payload));
            } catch (error) {
                console.error('Invalid checkpoint payload:', error);
            }
        });
    }

    renderCheckpointMarkup(payload, index) {
        const type = payload.type;
        const optionEntries = Object.entries(payload.options || {}).filter(([key, value]) => {
            if (type === 'true_false') {
                return key === 'A' || key === 'B';
            }
            return value && value.trim() !== '';
        });
        const inputType = type === 'multi_select' ? 'checkbox' : 'radio';

        const optionsHtml = optionEntries.map(([key, value]) => `
            <label class="checkpoint-option">
                <input type="${inputType}" name="checkpoint-${index}" value="${key}">
                <span>${value}</span>
            </label>
        `).join('');

        return `
            <div class="checkpoint-card" data-type="${type}">
                <div class="checkpoint-badge">Checkpoint</div>
                <h4 class="checkpoint-question">${payload.question}</h4>
                <div class="checkpoint-options">${optionsHtml}</div>
                <div class="checkpoint-actions">
                    <button type="button" class="btn btn-success checkpoint-submit">Check Answer</button>
                    <div class="checkpoint-feedback"></div>
                </div>
            </div>
        `;
    }

    handleCheckpointSubmit(block, payload) {
        const type = payload.type;
        const selected = Array.from(block.querySelectorAll('input:checked')).map((input) => input.value).sort();
        const correct = Array.isArray(payload.correct) ? [...payload.correct].sort() : [];
        const feedback = block.querySelector('.checkpoint-feedback');

        let passed = false;
        if (type === 'multi_select') {
            passed = selected.length === correct.length && selected.every((value, index) => value === correct[index]);
        } else {
            passed = selected.length === 1 && correct.length > 0 && selected[0] === correct[0];
        }

        if (passed) {
            block.classList.add('passed');
            block.querySelectorAll('input').forEach((input) => input.disabled = true);
            block.querySelector('.checkpoint-submit').disabled = true;
            feedback.textContent = 'Correct. Section checkpoint passed.';
            feedback.className = 'checkpoint-feedback success';
        } else {
            feedback.textContent = 'Incorrect. Review this section and try again.';
            feedback.className = 'checkpoint-feedback error';
        }

        const allPassed = Array.from(document.querySelectorAll('.lesson-checkpoint')).every((item) => item.classList.contains('passed'));
        this.currentLessonCheckpointsPassed = allPassed;
        this.updateReadHint();
        this.updateCompleteButton();

        if (this.canUnlockCompletion()) {
            this.unlockLessonCompletion(this.currentLesson.lesson_id);
        }
    }

    async unlockLessonCompletion(lessonId) {
        if (this.lessonUnlockSent) {
            return;
        }

        this.lessonUnlockSent = true;

        try {
            const response = await fetch('learning/api/unlock_lesson_completion.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ lesson_id: lessonId })
            });

            if (!response.ok) {
                throw new Error('Failed to unlock lesson completion');
            }
        } catch (error) {
            console.error('Failed to unlock lesson completion:', error);
            this.lessonUnlockSent = false;
            this.lessonReachedEnd = false;
            this.updateReadHint();
            this.updateCompleteButton();
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
    async loadQuiz(quizId, forceRetake = false) {
        try {
            const response = await fetch(`learning/api/get_quiz.php?id=${quizId}`);
            const quiz = await response.json();

            if (!quiz || quiz.error) {
                throw new Error(quiz.error || 'Quiz error');
            }

            // ✅ 1. ALWAYS SHOW PASSED RESULT FIRST
            if (quiz.user_result && quiz.user_result.score >= 70) {
                this.showQuizResult(quiz.user_result, quizId);
                return;
            }

            // 🔒 2. Locked (only if NOT passed)
            if (quiz.locked === true) {
                this.hideAllViews();
                const quizView = document.getElementById('quiz-view');
                quizView.style.display = 'flex';
                quizView.innerHTML = `
                    <div class="text-center p-5 w-100">
                        <h3 class="text-danger">❌ Quiz Locked</h3>
                        <p>You have reached the maximum number of attempts.</p>
                        <p><strong>Attempts Used:</strong> ${quiz.attempts}/3</p>
                        <button class="btn btn-secondary mt-3" onclick="location.href='modulepage.php'">
                            Go Back
                        </button>
                    </div>
                `;
                return;
            }

            // ⚠️ No questions
            if (!quiz.questions || quiz.questions.length === 0) {
                alert('Quiz has no questions.');
                return;
            }

            // ▶️ Show quiz
            this.showQuizView(quiz, quizId);

        } catch (error) {
            console.error('Failed to load quiz:', error);
            alert('Failed to load quiz.');
        }
    }

    showQuizView(quiz, quizId) {
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
        submitBtn.onclick = () => this.submitQuiz(quizId);
    }

    async submitQuiz(quizId) {
        const answers = [];

        document.querySelectorAll('.quiz-question').forEach(q => {
            const input = q.querySelector('input[type="radio"]:checked');
            if (input) {
                const questionId = parseInt(input.name.replace('q', '')); // ✅ Convert to integer
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
     async showQuizResult(result, quizId) {
        this.hideAllViews();
        const quizView = document.getElementById('quiz-view');
        quizView.style.display = 'flex';
        quizView.innerHTML = ''; // clear current content
        const currentModuleId = this.currentModule;

        // 🎉 Passed
        if (result.score >= 70) {
            // ✅ SAVE CERTIFICATE TO DATABASE (ONCE)
            if (this.currentModule.module_id !== 1) {
                try {
                    console.log('🔄 Attempting to save certificate for module:', this.currentModule.module_id);
                    
                    const response = await fetch('learning/api/award_certificate.php', {
                        method: 'POST',
                        headers: { 
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `module_id=${this.currentModule.module_id}`
                    });
                    
                    const responseText = await response.text();
                    console.log('📥 Server response:', responseText);
                    console.log('📊 Response status:', response.status);
                    
                    if (!response.ok) {
                        console.error('❌ Certificate save failed:', response.status, responseText);
                        alert('⚠️ Failed to save certificate. Please contact support.');
                    } else {
                        console.log('✅ Certificate saved successfully!');
                    }
                } catch (err) {
                    console.error('❌ Certificate save network error:', err);
                    alert('⚠️ Network error while saving certificate. Please check your connection.');
                }
            } else {
                console.log('ℹ️ Module 1 - No certificate issued (by design)');
            }
            if (this.currentModule.rewards) {
                try {
                    const rewardResponse = await fetch('learning/api/award_reward.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `module_id=${this.currentModule.module_id}`
                    });

                    const rewardText = await rewardResponse.text();
                    console.log('🎁 Reward response:', rewardText);

                    if (!rewardResponse.ok) {
                        console.error('❌ Failed to save reward');
                    }
                } catch (err) {
                    console.error('❌ Reward save error:', err);
                }
            }
            quizView.innerHTML = `
                <div class="text-center p-5 w-100">
                    <h3 class="text-success fw-bold">🎉 Congratulations!</h3>
                    <p>You passed with a score of <strong>${result.score}%</strong>!</p>
                     <h5 class="fw-bold mb-1">🎁 Reward Unlocked!</h5>
                     <p class="mb-0">${this.currentModule.rewards}</p>
                    <p class="mt-3 text-success fw-semibold">
                        ${this.currentModule.module_id === 1 
                            ? 'ℹ️ This introductory module does not include a certificate.'
                            : '🏆 Certificate Unlocked!'
                            }
                    </p>

                    <div class="d-flex justify-content-center gap-3 mt-4">
                         ${this.currentModule.module_id !== 1 ? `
                        <a href="learning/api/certificate.php?module_id=${this.currentModule.module_id}&from=quiz" 
                        target="_blank"
                        class="btn btn-primary px-4 py-2">
                            📄 View Certificate
                        </a>` : ``}
                        
                        <a href="modulepage.php" class="btn btn-outline-secondary px-4 py-2">
                            Back to Modules
                        </a>
                    </div>

                    <p class="text-muted mt-4 mb-0">
                        This quiz is <strong>completed</strong> and cannot be retaken. <br>
                        You can view your certificates and rewards in your profile.
                    </p>
                </div>
            `;
            return;
        }
        // ❌ Failed — show retry option or locked message
        // IMPORTANT: default attempts to 0 (not 1) when server didn't provide it
        const attempts = (typeof result.attempts !== 'undefined') ? Number(result.attempts) : 0;
        const maxAttempts = 3;
        const remaining = maxAttempts - attempts;

        // If user already used up attempts
        if (attempts >= maxAttempts) {
            quizView.innerHTML = `
                <div class="text-center p-5 w-100">
                    <h3 class="text-danger">You scored ${result.score}%</h3>
                    <p>You need at least 70% to pass.</p>
                    <p><strong>Attempt ${attempts} of ${maxAttempts}</strong></p>
                    <p class="text-danger fw-bold">❌ You have reached the maximum number of attempts.</p>
                </div>
            `;
            return;
        }

        // If user can still retry (including attempts === 0)
        quizView.innerHTML = `
            <div class="text-center p-5 w-100">
                <h3 class="text-danger">You scored ${result.score}%</h3>
                <p>You need at least 70% to pass.</p>
                <p><strong>Attempt ${attempts} of ${maxAttempts}</strong></p>
                <p class="text-muted">You have ${remaining} attempt${remaining > 1 ? 's' : ''} left.</p>
                <button id="retry-quiz" class="btn btn-outline-success mt-3">🔁 Try Again</button>
            </div>
        `;

        // ✅ Add retry event handler safely
        const retryBtn = document.getElementById('retry-quiz');
        if (retryBtn) {
            retryBtn.addEventListener('click', async () => {
                quizView.style.opacity = '0';

                setTimeout(async () => {
                    quizView.innerHTML = `
                        <div class="lesson-body" id="quiz-questions"></div>
                        <div class="lesson-footer d-flex justify-content-between">
                            <button id="submit-quiz" class="btn btn-success" style="display:none;">Submit Quiz</button>
                        </div>
                    `;

                    try {
                        await this.loadQuiz(quizId, true);

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
    }
}

    document.addEventListener('DOMContentLoaded', () => {
        new TeenAnimLearning();
});
</script>
<!-- Modal: Quiz Access Warning -->
<div class="modal fade" id="lessonWarningModal" tabindex="-1" aria-labelledby="lessonWarningLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-warning">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title" id="lessonWarningLabel">Finish Lessons First</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        You need to finish all lessons in this module before you can take the quiz.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Okay</button>
      </div>
    </div>
  </div>
</div>
<!-- Attempt Warning Modal -->
<div class="modal fade" id="attemptModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title">Quiz Attempt Notice</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="attemptModalBody">
        Checking your attempts...
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="proceedQuizBtn" class="btn btn-primary">Start Quiz</button>
      </div>
    </div>
  </div>
</div>

</body>
</html>
