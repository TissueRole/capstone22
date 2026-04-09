<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

include('../connection.php');

function buildLessonContentFromTemplate($builderPayloadJson) {
    $payload = json_decode($builderPayloadJson, true);
    if (!is_array($payload)) {
        return '';
    }

    $parts = [];

    $intro = trim($payload['intro'] ?? '');
    $introVideoUrl = trim($payload['intro_video_url'] ?? '');
    $takeaways = trim($payload['takeaways'] ?? '');
    $sections = is_array($payload['sections'] ?? null) ? $payload['sections'] : [];

    if ($intro !== '') {
        $parts[] = $intro;
    }

    if ($introVideoUrl !== '') {
        $parts[] = "[youtube:$introVideoUrl]";
    }

    foreach ($sections as $section) {
        $heading = trim($section['heading'] ?? '');
        $body = trim($section['body'] ?? '');
        $imageUrl = trim($section['image'] ?? '');
        $videoUrl = trim($section['video_url'] ?? '');
        $linkUrl = trim($section['link_url'] ?? '');
        $linkLabel = trim($section['link_label'] ?? '');
        $checkpoints = is_array($section['checkpoints'] ?? null) ? $section['checkpoints'] : [];

        if ($heading === '' && $body === '' && $imageUrl === '' && $videoUrl === '' && $linkUrl === '' && count($checkpoints) === 0) {
            continue;
        }

        if ($heading !== '') {
            $parts[] = "## $heading";
        }

        if ($imageUrl !== '') {
            $parts[] = "![]($imageUrl)";
        }

        if ($videoUrl !== '') {
            $parts[] = "[youtube:$videoUrl]";
        }

        if ($body !== '') {
            $parts[] = $body;
        }

        if ($linkUrl !== '') {
            $label = $linkLabel !== '' ? $linkLabel : 'Open Resource';
            $parts[] = "[$label]($linkUrl)";
        }

        foreach ($checkpoints as $checkpoint) {
            $type = trim($checkpoint['type'] ?? 'none');
            $question = trim($checkpoint['question'] ?? '');
            $correct = array_values(array_filter($checkpoint['correct'] ?? []));

            if ($type === 'none' || $question === '') {
                continue;
            }

            $options = [
                'A' => $type === 'true_false' ? 'True' : trim($checkpoint['option_a'] ?? ''),
                'B' => $type === 'true_false' ? 'False' : trim($checkpoint['option_b'] ?? ''),
                'C' => trim($checkpoint['option_c'] ?? ''),
                'D' => trim($checkpoint['option_d'] ?? '')
            ];

            if ($type === 'single_choice' || $type === 'true_false') {
                $correct = array_slice($correct, 0, 1);
            }

            $checkpointPayload = [
                'type' => $type,
                'question' => $question,
                'options' => $options,
                'correct' => $correct
            ];

            $parts[] = '[checkpoint:' . base64_encode(json_encode($checkpointPayload)) . ']';
        }
    }

    if ($takeaways !== '') {
        $parts[] = "## Key Takeaways\n$takeaways";
    }

    return trim(implode("\n\n", $parts));
}

$lesson_id = $_GET['id'] ?? null;
if (!$lesson_id) {
    header("Location: adminpage.php#lesson-management&error=Lesson ID missing");
    exit();
}

$success = $error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['lesson_title'] ?? '');
    $lesson_order = intval($_POST['lesson_order'] ?? 1);
    $contentMode = $_POST['content_mode'] ?? 'raw';

    if ($contentMode === 'builder') {
        $content = buildLessonContentFromTemplate($_POST['builder_payload'] ?? '');
    } else {
        $content = trim($_POST['lesson_content'] ?? '');
    }

    $stmt = $conn->prepare("UPDATE lessons SET title=?, content=?, lesson_order=? WHERE lesson_id=?");
    $stmt->bind_param("ssii", $title, $content, $lesson_order, $lesson_id);

    if ($stmt->execute()) {
        $success = "Lesson updated successfully.";
    } else {
        $error = "Error updating lesson: " . $stmt->error;
    }
}

$stmt = $conn->prepare("SELECT * FROM lessons WHERE lesson_id=?");
$stmt->bind_param("i", $lesson_id);
$stmt->execute();
$lesson = $stmt->get_result()->fetch_assoc();
if (!$lesson) {
    header("Location: adminpage.php#lesson-management&error=Lesson not found");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Lesson</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background: linear-gradient(135deg, #e8f5e9, #c8e6c9); font-family: 'Poppins', sans-serif; }
        .page-header { background: #388e3c; color: white; padding: 1.5rem; border-radius: 0.75rem; margin-bottom: 2rem; }
        .card { border-radius: 1rem; box-shadow: 0 6px 18px rgba(0,0,0,0.1); border: none; }
        .form-label { font-weight: 600; color: #2e7d32; }
        .builder-tab { border: 1px solid #c8e6c9; background: #f6fbf5; color: #2e7d32; }
        .builder-tab.active { background: #388e3c; color: #fff; border-color: #388e3c; }
        .builder-panel { display: none; }
        .builder-panel.active { display: block; }
        .section-block, .checkpoint-block { background: #f8fcf7; border: 1px solid #d8ead6; border-radius: 0.85rem; padding: 1rem; margin-bottom: 1rem; }
        .section-toolbar, .checkpoint-toolbar { display: flex; gap: 0.5rem; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; }
        .section-actions, .checkpoint-actions { display: flex; gap: 0.5rem; }
        .section-handle, .checkpoint-handle { font-size: 0.9rem; color: #5e745d; font-weight: 600; }
        .checkpoint-block { background: #ffffff; }
        #lesson_content { min-height: 420px; font-family: Consolas, monospace; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h2 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Lesson</h2>
        <a href="adminpage.php#lesson-management" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="card">
        <div class="card-body p-4">
            <form method="POST" id="lessonForm">
                <input type="hidden" name="content_mode" id="content_mode" value="raw">
                <input type="hidden" name="builder_payload" id="builder_payload">

                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label">Lesson Title</label>
                        <input type="text" name="lesson_title" class="form-control" value="<?= htmlspecialchars($lesson['title']) ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Order Index</label>
                        <input type="number" name="lesson_order" class="form-control" min="1" value="<?= htmlspecialchars($lesson['lesson_order']) ?>">
                    </div>
                </div>

                <div class="d-flex gap-2 mb-3">
                    <button type="button" class="btn builder-tab" data-mode="builder">Template Builder</button>
                    <button type="button" class="btn builder-tab active" data-mode="raw">Raw Editor</button>
                </div>

                <div class="alert alert-info">
                    The builder now supports multiple checkpoints per section, each with its own type. Existing lessons remain safest to edit in the raw editor unless you want to rebuild them in the builder.
                </div>

                <div id="builder-panel" class="builder-panel">
                    <div class="section-block">
                        <h5 class="mb-3">Lesson Intro</h5>
                        <div class="mb-3">
                            <label class="form-label">Intro Paragraph</label>
                            <textarea id="lesson_intro" class="form-control" placeholder="Short introduction for the lesson"></textarea>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Intro YouTube URL or Video ID</label>
                            <input type="text" id="lesson_video_url" class="form-control" placeholder="https://www.youtube.com/watch?v=...">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Content Sections</h5>
                        <button type="button" class="btn btn-outline-success" id="add-section-btn">
                            <i class="bi bi-plus-circle me-1"></i>Add Section
                        </button>
                    </div>

                    <div id="sections-container"></div>

                    <div class="section-block">
                        <h5 class="mb-3">Key Takeaways</h5>
                        <textarea id="lesson_takeaways" class="form-control" placeholder="- Main point&#10;- Another point&#10;- Final reminder"></textarea>
                    </div>
                </div>

                <div id="raw-panel" class="builder-panel active">
                    <label class="form-label">Lesson Content</label>
                    <textarea name="lesson_content" id="lesson_content" class="form-control" required><?= htmlspecialchars($lesson['content']) ?></textarea>
                    <small class="text-muted d-block mt-2">
                        Supported content: markdown headings, paragraphs, image URLs with <code>![](url)</code>, YouTube placeholders with <code>[youtube:url]</code>, normal links, and embedded checkpoint markers.
                    </small>
                </div>

                <button type="submit" class="btn btn-warning w-100 mt-3">
                    <i class="bi bi-save me-1"></i> Update Lesson
                </button>
            </form>
        </div>
    </div>
</div>

<template id="section-template">
    <div class="section-block">
        <div class="section-toolbar">
            <div class="section-handle">Section</div>
            <div class="section-actions">
                <button type="button" class="btn btn-sm btn-outline-secondary move-up-btn"><i class="bi bi-arrow-up"></i></button>
                <button type="button" class="btn btn-sm btn-outline-secondary move-down-btn"><i class="bi bi-arrow-down"></i></button>
                <button type="button" class="btn btn-sm btn-outline-danger remove-section-btn"><i class="bi bi-trash"></i></button>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Section Heading</label>
            <input type="text" data-role="section-heading" class="form-control" placeholder="Section title">
        </div>
        <div class="mb-3">
            <label class="form-label">Image URL</label>
            <input type="text" data-role="section-image" class="form-control" placeholder="https://example.com/image.jpg">
        </div>
        <div class="mb-3">
            <label class="form-label">YouTube URL or Video ID</label>
            <input type="text" data-role="section-video" class="form-control" placeholder="https://www.youtube.com/watch?v=...">
        </div>
        <div class="row">
            <div class="col-md-8 mb-3">
                <label class="form-label">Resource Link URL</label>
                <input type="text" data-role="section-link-url" class="form-control" placeholder="https://example.com/article">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Link Label</label>
                <input type="text" data-role="section-link-label" class="form-control" placeholder="Open resource">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Section Content</label>
            <textarea data-role="section-body" class="form-control" placeholder="Write short paragraphs or bullet points"></textarea>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">Section Checkpoints</h6>
            <button type="button" class="btn btn-sm btn-outline-success add-checkpoint-btn">
                <i class="bi bi-plus-circle me-1"></i>Add Checkpoint
            </button>
        </div>
        <div class="checkpoints-container"></div>
    </div>
</template>

<template id="checkpoint-template">
    <div class="checkpoint-block">
        <div class="checkpoint-toolbar">
            <div class="checkpoint-handle">Checkpoint</div>
            <div class="checkpoint-actions">
                <button type="button" class="btn btn-sm btn-outline-secondary move-checkpoint-up-btn"><i class="bi bi-arrow-up"></i></button>
                <button type="button" class="btn btn-sm btn-outline-secondary move-checkpoint-down-btn"><i class="bi bi-arrow-down"></i></button>
                <button type="button" class="btn btn-sm btn-outline-danger remove-checkpoint-btn"><i class="bi bi-trash"></i></button>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Checkpoint Type</label>
            <select data-role="checkpoint-type" class="form-select checkpoint-type">
                <option value="single_choice">Single Choice</option>
                <option value="true_false">True or False</option>
                <option value="multi_select">Multi-Select</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Question</label>
            <input type="text" data-role="checkpoint-question" class="form-control" placeholder="Question for this section">
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Option A</label>
                <input type="text" data-role="checkpoint-option-a" class="form-control" placeholder="Option A">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Option B</label>
                <input type="text" data-role="checkpoint-option-b" class="form-control" placeholder="Option B">
            </div>
        </div>
        <div class="row checkpoint-extra-options">
            <div class="col-md-6 mb-3">
                <label class="form-label">Option C</label>
                <input type="text" data-role="checkpoint-option-c" class="form-control" placeholder="Option C">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Option D</label>
                <input type="text" data-role="checkpoint-option-d" class="form-control" placeholder="Option D">
            </div>
        </div>
        <div class="mb-0">
            <label class="form-label">Correct Answer(s)</label>
            <div class="d-flex flex-wrap gap-3">
                <label class="form-check-label"><input class="form-check-input checkpoint-correct" type="checkbox" value="A"> A</label>
                <label class="form-check-label"><input class="form-check-input checkpoint-correct" type="checkbox" value="B"> B</label>
                <label class="form-check-label checkpoint-extra-answer"><input class="form-check-input checkpoint-correct" type="checkbox" value="C"> C</label>
                <label class="form-check-label checkpoint-extra-answer"><input class="form-check-input checkpoint-correct" type="checkbox" value="D"> D</label>
            </div>
            <small class="text-muted d-block mt-2">Use one correct answer for single choice and true/false. Use multiple correct answers for multi-select.</small>
        </div>
    </div>
</template>

<script>
const modeInput = document.getElementById('content_mode');
const builderPayloadInput = document.getElementById('builder_payload');
const builderPanel = document.getElementById('builder-panel');
const rawPanel = document.getElementById('raw-panel');
const modeButtons = document.querySelectorAll('.builder-tab');
const sectionsContainer = document.getElementById('sections-container');
const sectionTemplate = document.getElementById('section-template');
const checkpointTemplate = document.getElementById('checkpoint-template');
const addSectionBtn = document.getElementById('add-section-btn');
const lessonForm = document.getElementById('lessonForm');
const rawTextarea = document.getElementById('lesson_content');

function updateSectionLabels() {
    sectionsContainer.querySelectorAll('.section-block').forEach((section, index) => {
        section.querySelector('.section-handle').textContent = `Section ${index + 1}`;
        updateCheckpointLabels(section);
    });
}

function updateCheckpointLabels(section) {
    section.querySelectorAll('.checkpoint-block').forEach((checkpoint, index) => {
        checkpoint.querySelector('.checkpoint-handle').textContent = `Checkpoint ${index + 1}`;
    });
}

function syncCheckpointUI(checkpoint) {
    const type = checkpoint.querySelector('[data-role="checkpoint-type"]').value;
    const optionA = checkpoint.querySelector('[data-role="checkpoint-option-a"]');
    const optionB = checkpoint.querySelector('[data-role="checkpoint-option-b"]');
    const extraOptions = checkpoint.querySelector('.checkpoint-extra-options');
    const extraAnswers = checkpoint.querySelectorAll('.checkpoint-extra-answer');
    const correctInputs = checkpoint.querySelectorAll('.checkpoint-correct');

    if (type === 'true_false') {
        optionA.value = 'True';
        optionB.value = 'False';
        optionA.readOnly = true;
        optionB.readOnly = true;
        extraOptions.style.display = 'none';
        extraAnswers.forEach((item) => item.style.display = 'none');
        correctInputs.forEach((input) => {
            if (input.value === 'C' || input.value === 'D') input.checked = false;
        });
    } else {
        optionA.readOnly = false;
        optionB.readOnly = false;
        if (optionA.value === 'True') optionA.value = '';
        if (optionB.value === 'False') optionB.value = '';
        extraOptions.style.display = '';
        extraAnswers.forEach((item) => item.style.display = '');
    }

    if (type !== 'multi_select') {
        let seenChecked = false;
        correctInputs.forEach((input) => {
            if (input.checked) {
                if (seenChecked) input.checked = false;
                seenChecked = true;
            }
        });
    }
}

function attachCheckpointEvents(section, checkpoint) {
    checkpoint.querySelector('[data-role="checkpoint-type"]').addEventListener('change', () => syncCheckpointUI(checkpoint));
    checkpoint.querySelectorAll('.checkpoint-correct').forEach((input) => {
        input.addEventListener('change', () => {
            const type = checkpoint.querySelector('[data-role="checkpoint-type"]').value;
            if (type !== 'multi_select' && input.checked) {
                checkpoint.querySelectorAll('.checkpoint-correct').forEach((other) => {
                    if (other !== input) other.checked = false;
                });
            }
        });
    });
    checkpoint.querySelector('.remove-checkpoint-btn').addEventListener('click', () => {
        checkpoint.remove();
        updateCheckpointLabels(section);
    });
    checkpoint.querySelector('.move-checkpoint-up-btn').addEventListener('click', () => {
        const previous = checkpoint.previousElementSibling;
        if (previous) {
            checkpoint.parentNode.insertBefore(checkpoint, previous);
            updateCheckpointLabels(section);
        }
    });
    checkpoint.querySelector('.move-checkpoint-down-btn').addEventListener('click', () => {
        const next = checkpoint.nextElementSibling;
        if (next) {
            checkpoint.parentNode.insertBefore(next, checkpoint);
            updateCheckpointLabels(section);
        }
    });
    syncCheckpointUI(checkpoint);
}

function addCheckpoint(section) {
    const checkpoint = checkpointTemplate.content.firstElementChild.cloneNode(true);
    section.querySelector('.checkpoints-container').appendChild(checkpoint);
    attachCheckpointEvents(section, checkpoint);
    updateCheckpointLabels(section);
}

function attachSectionEvents(section) {
    section.querySelector('.remove-section-btn').addEventListener('click', () => {
        section.remove();
        updateSectionLabels();
    });
    section.querySelector('.move-up-btn').addEventListener('click', () => {
        const previous = section.previousElementSibling;
        if (previous) {
            sectionsContainer.insertBefore(section, previous);
            updateSectionLabels();
        }
    });
    section.querySelector('.move-down-btn').addEventListener('click', () => {
        const next = section.nextElementSibling;
        if (next) {
            sectionsContainer.insertBefore(next, section);
            updateSectionLabels();
        }
    });
    section.querySelector('.add-checkpoint-btn').addEventListener('click', () => addCheckpoint(section));
}

function addSection() {
    const section = sectionTemplate.content.firstElementChild.cloneNode(true);
    sectionsContainer.appendChild(section);
    attachSectionEvents(section);
    updateSectionLabels();
}

function buildBuilderPayload() {
    const sections = Array.from(sectionsContainer.querySelectorAll('.section-block')).map((section) => ({
        heading: section.querySelector('[data-role="section-heading"]').value.trim(),
        image: section.querySelector('[data-role="section-image"]').value.trim(),
        video_url: section.querySelector('[data-role="section-video"]').value.trim(),
        link_url: section.querySelector('[data-role="section-link-url"]').value.trim(),
        link_label: section.querySelector('[data-role="section-link-label"]').value.trim(),
        body: section.querySelector('[data-role="section-body"]').value.trim(),
        checkpoints: Array.from(section.querySelectorAll('.checkpoint-block')).map((checkpoint) => ({
            type: checkpoint.querySelector('[data-role="checkpoint-type"]').value,
            question: checkpoint.querySelector('[data-role="checkpoint-question"]').value.trim(),
            option_a: checkpoint.querySelector('[data-role="checkpoint-option-a"]').value.trim(),
            option_b: checkpoint.querySelector('[data-role="checkpoint-option-b"]').value.trim(),
            option_c: checkpoint.querySelector('[data-role="checkpoint-option-c"]').value.trim(),
            option_d: checkpoint.querySelector('[data-role="checkpoint-option-d"]').value.trim(),
            correct: Array.from(checkpoint.querySelectorAll('.checkpoint-correct:checked')).map((input) => input.value)
        }))
    }));

    return {
        intro: document.getElementById('lesson_intro').value.trim(),
        intro_video_url: document.getElementById('lesson_video_url').value.trim(),
        takeaways: document.getElementById('lesson_takeaways').value.trim(),
        sections
    };
}

modeButtons.forEach((button) => {
    button.addEventListener('click', () => {
        const mode = button.dataset.mode;
        modeInput.value = mode;
        builderPanel.classList.toggle('active', mode === 'builder');
        rawPanel.classList.toggle('active', mode === 'raw');
        rawTextarea.required = mode === 'raw';
        modeButtons.forEach((item) => item.classList.toggle('active', item === button));
    });
});

lessonForm.addEventListener('submit', () => {
    if (modeInput.value === 'builder') {
        builderPayloadInput.value = JSON.stringify(buildBuilderPayload());
    }
});

addSectionBtn.addEventListener('click', addSection);
addSection();
</script>
</body>
</html>
