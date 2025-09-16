<?php
// File: php/learning-functions.php
require_once 'connection.php';

class TeenAnimLearning {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    // Get all modules with their lessons
    public function getModulesWithLessons($user_id) {
        $sql = "SELECT m.*, 
                   (SELECT COUNT(*) FROM lessons l WHERE l.module_id = m.module_id) as total_lessons,
                   (SELECT COUNT(*) FROM lesson_progress lp 
                    JOIN lessons l ON lp.lesson_id = l.lesson_id 
                    WHERE l.module_id = m.module_id AND lp.user_id = ? AND lp.completed = 1) as completed_lessons
                FROM modules m 
                ORDER BY m.module_id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $modules = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        foreach ($modules as &$module) {
            $module['lessons'] = $this->getLessonsByModule($module['module_id'], $user_id);
            $module['progress'] = $module['total_lessons'] > 0 ? 
                round(($module['completed_lessons'] / $module['total_lessons']) * 100) : 0;
        }
        
        return $modules;
    }
    
    // ✅ NEW: Get one module with its lessons
    public function getModuleWithLessons($module_id, $user_id) {
        $sql = "SELECT m.*, 
                   (SELECT COUNT(*) FROM lessons l WHERE l.module_id = m.module_id) as total_lessons,
                   (SELECT COUNT(*) FROM lesson_progress lp 
                    JOIN lessons l ON lp.lesson_id = l.lesson_id 
                    WHERE l.module_id = m.module_id AND lp.user_id = ? AND lp.completed = 1) as completed_lessons
                FROM modules m 
                WHERE m.module_id = ?
                LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $module_id);
        $stmt->execute();
        $module = $stmt->get_result()->fetch_assoc();
        
        if (!$module) {
            return null; // no module found
        }

        // attach lessons
        $module['lessons'] = $this->getLessonsByModule($module['module_id'], $user_id);
        $module['progress'] = $module['total_lessons'] > 0 ? 
            round(($module['completed_lessons'] / $module['total_lessons']) * 100) : 0;
        
        return $module;
    }
    
    // Get lessons for a specific module
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
    
    // Get specific lesson with module info
    public function getLesson($lesson_id, $user_id) {
        $sql = "SELECT l.*, m.title as module_title,
                   COALESCE(lp.completed, 0) as completed,
                   lp.completed_at
                FROM lessons l
                JOIN modules m ON l.module_id = m.module_id
                LEFT JOIN lesson_progress lp ON l.lesson_id = lp.lesson_id AND lp.user_id = ?
                WHERE l.lesson_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $lesson_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    // Mark lesson as complete (simplified, no points/time)
    public function markLessonComplete($lesson_id, $user_id) {
        // Check if already completed
        $check_sql = "SELECT completed FROM lesson_progress WHERE user_id = ? AND lesson_id = ?";
        $stmt = $this->conn->prepare($check_sql);
        $stmt->bind_param("ii", $user_id, $lesson_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result && $result['completed']) {
            return ['success' => false, 'message' => 'Lesson already completed'];
        }
        
        // Mark lesson complete
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
    
    // Get user's learning progress
    public function getUserProgress($user_id) {
        $total_lessons = $this->conn->query("SELECT COUNT(*) as count FROM lessons")->fetch_assoc()['count'];
        $completed_lessons = $this->conn->query("SELECT COUNT(*) as count FROM lesson_progress WHERE user_id = $user_id AND completed = 1")->fetch_assoc()['count'];
        
        $progress = $total_lessons > 0 ? round(($completed_lessons / $total_lessons) * 100) : 0;
        
        // Get recent activity
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
}
?>
