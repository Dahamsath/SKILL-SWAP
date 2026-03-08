<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$user_id = (int)$_POST['user_id'];
$course_id = (int)$_POST['course_id'];
$lesson_id = (int)$_POST['lesson_id'];
$progress = (float)$_POST['progress'];
$completed = (int)$_POST['completed'];

try {
    // Check if record exists
    $stmt = $pdo->prepare("SELECT id FROM video_progress WHERE user_id = ? AND course_id = ? AND lesson_id = ?");
    $stmt->execute([$user_id, $course_id, $lesson_id]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Update existing record
        $stmt = $pdo->prepare("UPDATE video_progress SET 
            progress_percentage = ?, 
            completed = ?, 
            last_accessed = NOW() 
            WHERE user_id = ? AND course_id = ? AND lesson_id = ?");
        $stmt->execute([$progress, $completed, $user_id, $course_id, $lesson_id]);
    } else {
        // Insert new record
        $stmt = $pdo->prepare("INSERT INTO video_progress (user_id, course_id, lesson_id, progress_percentage, completed, last_accessed) 
            VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$user_id, $course_id, $lesson_id, $progress, $completed]);
    }

    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>