<?php
$host = 'localhost';
$db   = 'skillswap_db';
$user = 'root'; 
$pass = '';     
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
function calculateCourseProgress($pdo, $user_id, $course_id) {
    try {
        // Get total content counts for this course
        $total_videos = $pdo->prepare("SELECT COUNT(*) FROM videos WHERE course_id = ?");
        $total_videos->execute([$course_id]);
        $total_videos_count = $total_videos->fetchColumn();
        
        $total_quizzes = $pdo->prepare("SELECT COUNT(*) FROM quizzes WHERE course_id = ?");
        $total_quizzes->execute([$course_id]);
        $total_quizzes_count = $total_quizzes->fetchColumn();
        
        $total_assignments = $pdo->prepare("SELECT COUNT(*) FROM assignments WHERE course_id = ?");
        $total_assignments->execute([$course_id]);
        $total_assignments_count = $total_assignments->fetchColumn();
        
        // Get completed content counts for this user
        $completed_videos = $pdo->prepare("SELECT COUNT(*) FROM videos v WHERE v.course_id = ? AND EXISTS (SELECT 1 FROM enrollments e WHERE e.user_id = ? AND e.course_id = v.course_id AND e.progress >= 50)");
        $completed_videos->execute([$course_id, $user_id]);
        $completed_videos_count = $completed_videos->fetchColumn();
        
        $completed_quizzes = $pdo->prepare("SELECT COUNT(DISTINCT qr.quiz_id) FROM quiz_responses qr JOIN quizzes q ON qr.quiz_id = q.id WHERE q.course_id = ? AND qr.user_id = ?");
        $completed_quizzes->execute([$course_id, $user_id]);
        $completed_quizzes_count = $completed_quizzes->fetchColumn();
        
        $completed_assignments = $pdo->prepare("SELECT COUNT(DISTINCT asub.assignment_id) FROM assignment_submissions asub JOIN assignments a ON asub.assignment_id = a.id WHERE a.course_id = ? AND asub.user_id = ?");
        $completed_assignments->execute([$course_id, $user_id]);
        $completed_assignments_count = $completed_assignments->fetchColumn();
        
        // Calculate weighted progress (40% videos, 30% quizzes, 30% assignments)
        $total_weight = 0;
        $completed_weight = 0;
        
        if ($total_videos_count > 0) {
            $total_weight += 40;
            $completed_weight += ($completed_videos_count / $total_videos_count) * 40;
        }
        if ($total_quizzes_count > 0) {
            $total_weight += 30;
            $completed_weight += ($completed_quizzes_count / $total_quizzes_count) * 30;
        }
        if ($total_assignments_count > 0) {
            $total_weight += 30;
            $completed_weight += ($completed_assignments_count / $total_assignments_count) * 30;
        }
        
        $progress = $total_weight > 0 ? round(($completed_weight / $total_weight) * 100) : 0;
        $status = $progress >= 100 ? 'Completed' : 'In Progress';
        
        // Update enrollment record with comprehensive progress
        $stmt = $pdo->prepare("
            UPDATE enrollments 
            SET progress = ?, status = ?, 
                videos_completed = ?, quizzes_completed = ?, assignments_completed = ?,
                total_videos = ?, total_quizzes = ?, total_assignments = ?,
                last_accessed = NOW()
            WHERE user_id = ? AND course_id = ?
        ");
        $stmt->execute([
            $progress, $status,
            $completed_videos_count, $completed_quizzes_count, $completed_assignments_count,
            $total_videos_count, $total_quizzes_count, $total_assignments_count,
            $user_id, $course_id
        ]);
        
        return $progress;
    } catch (Exception $e) {
        error_log("Error calculating progress: " . $e->getMessage());
        return 0;
    }
}

?>