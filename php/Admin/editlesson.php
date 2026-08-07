<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include('../connection.php');

$lesson_id = intval($_GET['id'] ?? 0);
if ($lesson_id <= 0) {
    header("Location: adminpage.php#lesson-management");
    exit();
}

/**
 * Builds standard markdown string from Template Builder inputs.
 */
function buildLessonContentFromTemplate($builderPayloadJson, $imagePaths = [], $existingImages = []) {
    $payload = json_decode($builderPayloadJson, true);
    if (!is_array($payload)) {
        return '';
    }

    $parts = [];
    $intro = trim($payload['intro'] ?? '');
    $introVideoUrl = trim($payload['intro_video_url'] ?? '');
    $endCheckpoints = is_array($payload['end_checkpoints'] ?? null) ? $payload['end_checkpoints'] : [];
    $sections = is_array($payload['sections'] ?? null) ? $payload['sections'] : [];

    if ($intro !== '') {
        $parts[] = $intro;
    }

    if ($introVideoUrl !== '') {
        $parts[] = "[youtube:$introVideoUrl]";
    }

    foreach ($sections as $index => $section) {
        $heading = trim($section['heading'] ?? '');
        $body = trim($section['body'] ?? '');
        
        // Prioritize newly uploaded image, fallback to existing image URL if not replaced
        $imageUrl = !empty($imagePaths[$index]) ? $imagePaths[$index] : ($existingImages[$index] ?? '');
        
        $videoUrl = trim($section['video_url'] ?? '');
        $linkUrl = trim($section['link_url'] ?? '');
        $linkLabel = trim($section['link_label'] ?? '');
    
        if ($heading === '' && $body === '' && $imageUrl === '' && $videoUrl === '' && $linkUrl === '') {
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
    }

    foreach ($endCheckpoints as $checkpoint) {
        $type = trim($checkpoint['type'] ?? 'none');
        $question = trim($checkpoint['question'] ?? '');
        $correct = array_values(array_filter($checkpoint['correct'] ?? []));
        
        if (!in_array($type, ['true_false', 'multi_select'], true) || $question === '') {
            continue;
        }
        
        $options = [
            'A' => $type === 'true_false' ? 'True' : trim($checkpoint['option_a'] ?? ''),
            'B' => $type === 'true_false' ? 'False' : trim($checkpoint['option_b'] ?? ''),
            'C' => $type === 'multi_select' ? trim($checkpoint['option_c'] ?? '') : '',
            'D' => $type === 'multi_select' ? trim($checkpoint['option_d'] ?? '') : ''
        ];

        if ($type === 'true_false') {
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

    return trim(implode("\n\n", $parts));
}

/**
 * Parses raw stored content back into structured JSON for the builder.
 */
function parseRawContentToBuilder($rawContent) {
    $payload = [
        'intro' => '',
        'intro_video_url' => '',
        'sections' => [],
        'end_checkpoints' => []
    ];

    $blocks = preg_split('/\n\s*\n/', trim($rawContent));
    $currentSection = null;
    $isIntro = true;

    foreach ($blocks as $block) {
        $block = trim($block);
        if ($block === '') continue;

        // Check for Checkpoints
        if (preg_match('/^\[checkpoint:(.+)\]$/', $block, $matches)) {
            $decoded = json_decode(base64_decode($matches[1]), true);
            if ($decoded) {
                $payload['end_checkpoints'][] = [
                    'type' => $decoded['type'] ?? 'true_false',
                    'question' => $decoded['question'] ?? '',
                    'option_a' => $decoded['options']['A'] ?? '',
                    'option_b' => $decoded['options']['B'] ?? '',
                    'option_c' => $decoded['options']['C'] ?? '',
                    'option_d' => $decoded['options']['D'] ?? '',
                    'correct' => $decoded['correct'] ?? []
                ];
            }
            continue;
        }

        // Check for Heading -> Starts a new section
        if (strpos($block, '## ') === 0) {
            $isIntro = false;
            if ($currentSection !== null) {
                $payload['sections'][] = $currentSection;
            }
            $currentSection = [
                'heading' => trim(substr($block, 3)),
                'image_url' => '',
                'video_url' => '',
                'link_url' => '',
                'link_label' => '',
                'body' => ''
            ];
            continue;
        }

        // Check for YouTube Embed
        if (preg_match('/^\[youtube:(.+)\]$/', $block, $matches)) {
            if ($isIntro && empty($payload['sections'])) {
                $payload['intro_video_url'] = $matches[1];
            } else if ($currentSection !== null) {
                $currentSection['video_url'] = $matches[1];
            }
            continue;
        }

        // Check for Image
        if (preg_match('/^!\[(.*?)\]\((.*?)\)$/', $block, $matches)) {
            if ($currentSection !== null) {
                $rawPath = $matches[2];
                
                // If the path starts with "../images/", add another "../" so the browser 
                // in admin directory can locate "../../images/lessons/..."
                if (strpos($rawPath, '../images/') === 0) {
                    $previewPath = '../' . $rawPath;
                } else {
                    $previewPath = $rawPath;
                }

                $currentSection['image_url'] = $rawPath;     // Retain original path for database saves
                $currentSection['preview_url'] = $previewPath; // Relative path for admin page preview
            }
            continue;
        }

        // Check for Link
        if (preg_match('/^\[(.*?)\]\((.*?)\)$/', $block, $matches)) {
            if ($currentSection !== null) {
                $currentSection['link_label'] = $matches[1];
                $currentSection['link_url'] = $matches[2];
            }
            continue;
        }

        // Regular Text
        if ($isIntro && empty($payload['sections'])) {
            $payload['intro'] = $payload['intro'] !== '' ? $payload['intro'] . "\n\n" . $block : $block;
        } else if ($currentSection !== null) {
            $currentSection['body'] = $currentSection['body'] !== '' ? $currentSection['body'] . "\n\n" . $block : $block;
        }
    }

    if ($currentSection !== null) {
        $payload['sections'][] = $currentSection;
    }

    return $payload;
}

$success = $error = "";

// Fetch existing lesson data
$stmt = $conn->prepare("SELECT * FROM lessons WHERE lesson_id = ?");
$stmt->bind_param("i", $lesson_id);
$stmt->execute();
$lesson = $stmt->get_result()->fetch_assoc();

if (!$lesson) {
    die("Lesson not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $module_id = intval($_POST['module_id'] ?? 0);
    $title = trim($_POST['lesson_title'] ?? '');
    $lesson_order = intval($_POST['lesson_order'] ?? 1);
    $imagePaths = [];
    $existingImages = $_POST['existing_section_images'] ?? [];

    if (!empty($_FILES['section_image']['name'][0])) {
        $uploadDir = "../../images/lessons/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        foreach ($_FILES['section_image']['tmp_name'] as $i => $tmpName) {
            if ($_FILES['section_image']['error'][$i] != UPLOAD_ERR_OK) continue;

            $ext = strtolower(pathinfo($_FILES['section_image']['name'][$i], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp'];

            if (!in_array($ext, $allowed)) continue;

            $newName = uniqid("lesson_") . "." . $ext;
            if (move_uploaded_file($tmpName, $uploadDir . $newName)) {
                $imagePaths[$i] = "../images/lessons/" . $newName;
            }
        }
    }
    
    $contentMode = $_POST['content_mode'] ?? 'builder';

    if ($contentMode === 'raw') {
        $content = trim($_POST['lesson_content'] ?? '');
    } else {
        $content = buildLessonContentFromTemplate(
            $_POST['builder_payload'] ?? '',
            $imagePaths,
            $existingImages
        );
    }

    if ($module_id > 0 && $title !== '' && $content !== '') {
        $updateStmt = $conn->prepare(
            "UPDATE lessons SET module_id = ?, title = ?, content = ?, lesson_order = ? WHERE lesson_id = ?"
        );
        $updateStmt->bind_param("issii", $module_id, $title, $content, $lesson_order, $lesson_id);

        if ($updateStmt->execute()) {
            $success = "Lesson updated successfully.";
            // Refresh local data
            $lesson['module_id'] = $module_id;
            $lesson['title'] = $title;
            $lesson['content'] = $content;
            $lesson['lesson_order'] = $lesson_order;
        } else {
            $error = "Error updating lesson: " . $updateStmt->error;
        }
    } else {
        $error = "Module, lesson title, and content are required.";
    }
}

$modules = $conn->query("SELECT module_id, title FROM modules ORDER BY created_at DESC");
$parsedPayloadJson = json_encode(parseRawContentToBuilder($lesson['content']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Lesson</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #e8f5e9, #c8e6c9); font-family: 'Poppins', sans-serif; }
        .page-header { background: #388e3c; color: #fff; padding: 1.5rem; border-radius: 0.75rem; margin-bottom: 2rem; }
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

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body p-4">
            <form method="POST" id="lessonForm" enctype="multipart/form-data">
                <input type="hidden" name="content_mode" id="content_mode" value="builder">
                <input type="hidden" name="builder_payload" id="builder_payload">

                <div class="mb-3">
                    <label class="form-label">Select Module</label>
                    <select name="module_id" class="form-select" required>
                        <option value="">-- Choose Module --</option>
                        <?php while ($module = $modules->fetch_assoc()): ?>
                            <option value="<?= $module['module_id'] ?>" <?= $module['module_id'] == $lesson['module_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($module['title']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

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
                    <button type="button" class="btn builder-tab active" data-mode="builder">Template Builder</button>
                    <button type="button" class="btn builder-tab" data-mode="raw">Raw Editor</button>
                </div>

                <div id="builder-panel" class="builder-panel active">
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
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="mb-1">End-of-Lesson Checkpoints</h5>
                                <small class="text-muted">Questions the learner answers after completing the lesson.</small>
                            </div>
                            <button type="button" class="btn btn-outline-success" id="add-end-checkpoint-btn">
                                <i class="bi bi-plus-circle me-1"></i>Add Checkpoint
                            </button>
                        </div>

                        <div id="end-checkpoints-container"></div>

                        <div id="no-end-checkpoints" class="text-muted text-center py-3">
                            No checkpoints added yet.
                        </div>
                    </div>
                </div>

                <div id="raw-panel" class="builder-panel">
                    <label class="form-label">Lesson Content</label>
                    <textarea name="lesson_content" id="lesson_content" class="form-control"><?= htmlspecialchars($lesson['content']) ?></textarea>
                    <small class="text-muted d-block mt-2">
                        Use the raw editor only when the template is too limiting. Supports markdown, images, YouTube placeholders, and links.
                    </small>
                </div>

                <button type="submit" class="btn btn-success w-100 mt-3">
                    <i class="bi bi-check-circle me-1"></i> Save Changes
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
            <label class="form-label">Upload Image</label>
            <input type="file" name="section_image[]" class="form-control section-image-file" accept="image/*">
            <input type="hidden" name="existing_section_images[]" data-role="existing-image">
            <img class="img-fluid rounded mt-2 image-preview" style="display:none;max-height:250px;">
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
                <option value="true_false">True or False</option>
                <option value="multi_select">Multiple Choice</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Question</label>
            <input type="text" data-role="checkpoint-question" class="form-control" placeholder="Question for this section">
        </div>
        <div class="row">
            <div class="col-md-6 mb-3 checkpoint-option-a-wrapper">
                <label class="form-label">Option A</label>
                <input type="text" data-role="checkpoint-option-a" class="form-control" placeholder="Option A">
            </div>
            <div class="col-md-6 mb-3 checkpoint-option-b-wrapper">
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
        </div>
    </div>
</template>

<script>
const existingData = <?= $parsedPayloadJson ?>;

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
const endCheckpointsContainer = document.getElementById('end-checkpoints-container');
const addEndCheckpointBtn = document.getElementById('add-end-checkpoint-btn');
const noEndCheckpoints = document.getElementById('no-end-checkpoints');

function updateSectionLabels() {
    sectionsContainer.querySelectorAll('.section-block').forEach((section, index) => {
        section.querySelector('.section-handle').textContent = `Section ${index + 1}`;
    });
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
}

function addSection(data = null) {
    const section = sectionTemplate.content.firstElementChild.cloneNode(true);
    sectionsContainer.appendChild(section);
    attachSectionEvents(section);

    if (data) {
        section.querySelector('[data-role="section-heading"]').value = data.heading || '';
        section.querySelector('[data-role="section-video"]').value = data.video_url || '';
        section.querySelector('[data-role="section-link-url"]').value = data.link_url || '';
        section.querySelector('[data-role="section-link-label"]').value = data.link_label || '';
        section.querySelector('[data-role="section-body"]').value = data.body || '';
        
        if (data.image_url) {
            // Keep the database path in the hidden input
            section.querySelector('[data-role="existing-image"]').value = data.image_url;
            
            // Use the adjusted preview path for the <img> src
            const preview = section.querySelector('.image-preview');
            preview.src = data.preview_url || data.image_url;
            preview.style.display = 'block';
        }
    }

    updateSectionLabels();
}

function updateEndCheckpointLabels() {
    const checkpoints = endCheckpointsContainer.querySelectorAll('.checkpoint-block');
    noEndCheckpoints.style.display = checkpoints.length === 0 ? '' : 'none';
    checkpoints.forEach((checkpoint, index) => {
        checkpoint.querySelector('.checkpoint-handle').textContent = `Checkpoint ${index + 1}`;
    });
}

function syncCheckpointUI(checkpoint) {
    const typeSelect = checkpoint.querySelector('[data-role="checkpoint-type"]');
    const type = typeSelect.value;
    const optionA = checkpoint.querySelector('[data-role="checkpoint-option-a"]');
    const optionB = checkpoint.querySelector('[data-role="checkpoint-option-b"]');
    const optionC = checkpoint.querySelector('[data-role="checkpoint-option-c"]');
    const optionD = checkpoint.querySelector('[data-role="checkpoint-option-d"]');
    const optionCWrapper = optionC.closest('.col-md-6');
    const optionDWrapper = optionD.closest('.col-md-6');
    const extraAnswers = checkpoint.querySelectorAll('.checkpoint-extra-answer');

    if (type === 'true_false') {
        optionA.value = 'True';
        optionB.value = 'False';
        optionA.readOnly = true;
        optionB.readOnly = true;
        optionC.value = '';
        optionD.value = '';
        optionC.disabled = true;
        optionD.disabled = true;
        optionCWrapper.style.display = 'none';
        optionDWrapper.style.display = 'none';
        extraAnswers.forEach(answer => {
            answer.style.display = 'none';
            const checkbox = answer.querySelector('input');
            checkbox.checked = false;
            checkbox.disabled = true;
        });
    } else if (type === 'multi_select') {
        optionA.readOnly = false;
        optionB.readOnly = false;
        optionC.disabled = false;
        optionD.disabled = false;
        optionCWrapper.style.display = '';
        optionDWrapper.style.display = '';
        extraAnswers.forEach(answer => {
            answer.style.display = '';
            const checkbox = answer.querySelector('input');
            checkbox.disabled = false;
        });
    }
}

function addEndCheckpoint(data = null) {
    const checkpoint = checkpointTemplate.content.firstElementChild.cloneNode(true);
    endCheckpointsContainer.appendChild(checkpoint);

    const typeSelect = checkpoint.querySelector('[data-role="checkpoint-type"]');

    typeSelect.addEventListener('change', () => {
        syncCheckpointUI(checkpoint);
    });

    checkpoint.querySelectorAll('.checkpoint-correct').forEach((input) => {
        input.addEventListener('change', () => {
            const type = typeSelect.value;
            if (type !== 'multi_select' && input.checked) {
                checkpoint.querySelectorAll('.checkpoint-correct').forEach((other) => {
                    if (other !== input) other.checked = false;
                });
            }
        });
    });

    checkpoint.querySelector('.remove-checkpoint-btn').addEventListener('click', () => {
        checkpoint.remove();
        updateEndCheckpointLabels();
    });

    checkpoint.querySelector('.move-checkpoint-up-btn').addEventListener('click', () => {
        const previous = checkpoint.previousElementSibling;
        if (previous) {
            endCheckpointsContainer.insertBefore(checkpoint, previous);
            updateEndCheckpointLabels();
        }
    });

    checkpoint.querySelector('.move-checkpoint-down-btn').addEventListener('click', () => {
        const next = checkpoint.nextElementSibling;
        if (next) {
            endCheckpointsContainer.insertBefore(next, checkpoint);
            updateEndCheckpointLabels();
        }
    });

    if (data) {
        typeSelect.value = data.type || 'true_false';
        checkpoint.querySelector('[data-role="checkpoint-question"]').value = data.question || '';
        checkpoint.querySelector('[data-role="checkpoint-option-a"]').value = data.option_a || '';
        checkpoint.querySelector('[data-role="checkpoint-option-b"]').value = data.option_b || '';
        checkpoint.querySelector('[data-role="checkpoint-option-c"]').value = data.option_c || '';
        checkpoint.querySelector('[data-role="checkpoint-option-d"]').value = data.option_d || '';

        if (Array.isArray(data.correct)) {
            checkpoint.querySelectorAll('.checkpoint-correct').forEach(cb => {
                cb.checked = data.correct.includes(cb.value);
            });
        }
    }

    syncCheckpointUI(checkpoint);
    updateEndCheckpointLabels();
}

function buildBuilderPayload() {
    const sections = Array.from(sectionsContainer.querySelectorAll('.section-block')).map((section) => ({
        heading: section.querySelector('[data-role="section-heading"]').value.trim(),
        video_url: section.querySelector('[data-role="section-video"]').value.trim(),
        link_url: section.querySelector('[data-role="section-link-url"]').value.trim(),
        link_label: section.querySelector('[data-role="section-link-label"]').value.trim(),
        body: section.querySelector('[data-role="section-body"]').value.trim()
    }));

    const end_checkpoints = Array.from(endCheckpointsContainer.querySelectorAll('.checkpoint-block')).map((checkpoint) => ({
        type: checkpoint.querySelector('[data-role="checkpoint-type"]').value,
        question: checkpoint.querySelector('[data-role="checkpoint-question"]').value.trim(),
        option_a: checkpoint.querySelector('[data-role="checkpoint-option-a"]').value.trim(),
        option_b: checkpoint.querySelector('[data-role="checkpoint-option-b"]').value.trim(),
        option_c: checkpoint.querySelector('[data-role="checkpoint-option-c"]').value.trim(),
        option_d: checkpoint.querySelector('[data-role="checkpoint-option-d"]').value.trim(),
        correct: Array.from(checkpoint.querySelectorAll('.checkpoint-correct:checked')).map(input => input.value)
    }));

    return {
        intro: document.getElementById('lesson_intro').value.trim(),
        intro_video_url: document.getElementById('lesson_video_url').value.trim(),
        sections,
        end_checkpoints
    };
}

// Initialize form inputs with decoded payload
function populateBuilderData(data) {
    document.getElementById('lesson_intro').value = data.intro || '';
    document.getElementById('lesson_video_url').value = data.intro_video_url || '';

    if (data.sections && data.sections.length > 0) {
        data.sections.forEach(sec => addSection(sec));
    } else {
        addSection();
    }

    if (data.end_checkpoints && data.end_checkpoints.length > 0) {
        data.end_checkpoints.forEach(cp => addEndCheckpoint(cp));
    }
}

modeButtons.forEach((button) => {
    button.addEventListener('click', () => {
        const mode = button.dataset.mode;
        modeInput.value = mode;
        builderPanel.classList.toggle('active', mode === 'builder');
        rawPanel.classList.toggle('active', mode === 'raw');
        modeButtons.forEach((item) => item.classList.toggle('active', item === button));
    });
});

lessonForm.addEventListener('submit', () => {
    if (modeInput.value === 'builder') {
        builderPayloadInput.value = JSON.stringify(buildBuilderPayload());
    }
});

addSectionBtn.addEventListener('click', () => addSection());
addEndCheckpointBtn.addEventListener('click', () => addEndCheckpoint());

document.addEventListener("change", function (e) {
    if (!e.target.classList.contains("section-image-file")) return;
    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(event) {
        const section = e.target.closest(".section-block");
        const preview = section.querySelector(".image-preview");
        preview.src = event.target.result;
        preview.style.display = "block";
    };
    reader.readAsDataURL(file);
});

// Load stored values into fields
populateBuilderData(existingData);
</script>
</body>
</html>