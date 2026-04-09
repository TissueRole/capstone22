<?php
require_once 'connection.php';
require_once __DIR__ . '/../vendor/autoload.php';

class TeenAnimLearning {
    private $conn;

    public function __construct($connection) {
        $this->conn = $connection;
    }

    public function getModulesWithLessons($user_id) {
        $sql = "SELECT m.*,
                   (SELECT COUNT(*) FROM lessons l WHERE l.module_id = m.module_id) as total_lessons,
                   (SELECT COUNT(*) FROM lesson_progress lp
                    JOIN lessons l ON lp.lesson_id = l.lesson_id
                    WHERE l.module_id = m.module_id AND lp.user_id = ? AND lp.completed = 1) as completed_lessons
                FROM modules m
                ORDER BY m.created_at DESC, m.module_id DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $modules = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($modules as &$module) {
            $module['lessons'] = $this->getLessonsByModule($module['module_id'], $user_id);
            $module['progress'] = $module['total_lessons'] > 0
                ? round(($module['completed_lessons'] / $module['total_lessons']) * 100)
                : 0;
        }

        return $modules;
    }

    public function getModuleWithLessons($module_id, $user_id) {
        $sql = "SELECT * FROM modules WHERE module_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $module_id);
        $stmt->execute();
        $module = $stmt->get_result()->fetch_assoc();

        if (!$module) {
            return null;
        }

        $sql = "SELECT l.*,
                    (SELECT COUNT(*) FROM lesson_progress p
                     WHERE p.lesson_id = l.lesson_id AND p.user_id = ?) AS completed
                FROM lessons l
                WHERE l.module_id = ?
                ORDER BY l.lesson_order ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $module_id);
        $stmt->execute();
        $lessons = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $sql = "SELECT quiz_id FROM module_quizzes WHERE module_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $module_id);
        $stmt->execute();
        $quiz = $stmt->get_result()->fetch_assoc();

        $module['lessons'] = $lessons;
        $module['quiz_id'] = $quiz ? $quiz['quiz_id'] : null;

        $totalLessons = count($lessons);
        $completedLessons = count(array_filter($lessons, fn($lesson) => $lesson['completed']));
        $module['progress'] = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;

        return $module;
    }

    public function getLessonsByModule($module_id, $user_id) {
        $sql = "SELECT l.*,
                   COALESCE(lp.completed, 0) as completed,
                   lp.completed_at
                FROM lessons l
                LEFT JOIN lesson_progress lp ON l.lesson_id = lp.lesson_id AND lp.user_id = ?
                WHERE l.module_id = ?
                ORDER BY l.lesson_order, l.lesson_id";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $module_id);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getLesson($lesson_id, $user_id) {
        $sql = "SELECT l.*, m.title as module_title,
                    COALESCE(lp.completed, 0) as completed,
                    lp.completed_at
                FROM lessons l
                JOIN modules m ON l.module_id = m.module_id
                LEFT JOIN lesson_progress lp
                    ON l.lesson_id = lp.lesson_id AND lp.user_id = ?
                WHERE l.lesson_id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $lesson_id);
        $stmt->execute();
        $lesson = $stmt->get_result()->fetch_assoc();

        if ($lesson) {
            $lesson['content'] = $this->transformLessonEmbeds($lesson['content']);

            $parsedown = new Parsedown();
            $lesson['content'] = $parsedown->text($lesson['content']);
        }

        return $lesson;
    }

    public function markLessonComplete($lesson_id, $user_id) {
        $check_sql = "SELECT completed FROM lesson_progress WHERE user_id = ? AND lesson_id = ?";
        $stmt = $this->conn->prepare($check_sql);
        $stmt->bind_param("ii", $user_id, $lesson_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result && $result['completed']) {
            return ['success' => false, 'message' => 'Lesson already completed'];
        }

        $progress_sql = "INSERT INTO lesson_progress (user_id, lesson_id, completed, completed_at)
                         VALUES (?, ?, 1, NOW())
                         ON DUPLICATE KEY UPDATE
                         completed = 1, completed_at = NOW()";

        $stmt = $this->conn->prepare($progress_sql);
        $stmt->bind_param("ii", $user_id, $lesson_id);

        if ($stmt->execute()) {
            return ['success' => true];
        }

        return ['success' => false, 'message' => 'Failed to update progress'];
    }

    public function getUserProgress($user_id) {
        $total_lessons = $this->conn->query("SELECT COUNT(*) as count FROM lessons")->fetch_assoc()['count'];
        $completed_lessons = $this->conn->query("SELECT COUNT(*) as count FROM lesson_progress WHERE user_id = $user_id AND completed = 1")->fetch_assoc()['count'];

        $progress = $total_lessons > 0 ? round(($completed_lessons / $total_lessons) * 100) : 0;

        $recent_sql = "SELECT l.title as lesson_title, m.title as module_title, lp.completed_at
                       FROM lesson_progress lp
                       JOIN lessons l ON lp.lesson_id = l.lesson_id
                       JOIN modules m ON l.module_id = m.module_id
                       WHERE lp.user_id = ? AND lp.completed = 1
                       ORDER BY lp.completed_at DESC LIMIT 10";

        $stmt = $this->conn->prepare($recent_sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $recent_activity = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        return [
            'total_lessons' => $total_lessons,
            'completed_lessons' => $completed_lessons,
            'progress_percentage' => $progress,
            'recent_activity' => $recent_activity
        ];
    }

    private function transformLessonEmbeds($content) {
        $content = preg_replace_callback('/\[youtube:(.*?)\]/i', function ($matches) {
            $videoId = $this->extractYouTubeVideoId(trim($matches[1]));

            if ($videoId === null) {
                return '';
            }

            return "\n\n<iframe width=\"100%\" height=\"415\" src=\"https://www.youtube.com/embed/{$videoId}\" title=\"Lesson video\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share\" allowfullscreen></iframe>\n\n";
        }, $content);

        $content = preg_replace_callback('/\[checkpoint:(.*?)\]/i', function ($matches) {
            $decoded = base64_decode(trim($matches[1]), true);
            if ($decoded === false) {
                return '';
            }

            $payload = json_decode($decoded, true);
            if (!is_array($payload) || empty($payload['type']) || empty($payload['question'])) {
                return '';
            }

            $json = htmlspecialchars(json_encode($payload), ENT_QUOTES, 'UTF-8');
            return "\n\n<div class=\"lesson-checkpoint\" data-checkpoint=\"{$json}\"></div>\n\n";
        }, $content);

        return $content;
    }

    private function extractYouTubeVideoId($value) {
        if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $value)) {
            return $value;
        }

        $parsedUrl = parse_url($value);
        if (!$parsedUrl || empty($parsedUrl['host'])) {
            return null;
        }

        $host = strtolower($parsedUrl['host']);

        if (strpos($host, 'youtu.be') !== false) {
            return isset($parsedUrl['path']) ? trim($parsedUrl['path'], '/') : null;
        }

        if (strpos($host, 'youtube.com') !== false) {
            if (!empty($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $queryParams);
                if (!empty($queryParams['v'])) {
                    return $queryParams['v'];
                }
            }

            if (!empty($parsedUrl['path']) && preg_match('#/(embed|shorts)/([a-zA-Z0-9_-]{11})#', $parsedUrl['path'], $pathMatches)) {
                return $pathMatches[2];
            }
        }

        return null;
    }
}
?>
