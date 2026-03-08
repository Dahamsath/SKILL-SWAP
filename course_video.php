<?php
// course_video.php - Course Video Player Page for SKILL SWAP
session_start();
include 'config.php';
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
header("Location: login.php");
exit();
}
$user_id = $_SESSION['user_id'];
$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$lesson_id = isset($_GET['lesson']) ? (int)$_GET['lesson'] : 1; // Default to lesson 1
$content_type = isset($_GET['content']) ? $_GET['content'] : 'video'; // video, quiz, assignment
$existing_review = null;
$total_reviews = 0;
// Fetch course details from database
$course = null;
$video_path = "videos/default_course_video.mp4"; // Default video path
$current_progress = 0;
if ($course_id > 0) {
// Fetch course details
$stmt = $pdo->prepare("SELECT c.* FROM courses c WHERE c.course_id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch();
// If course not found, redirect to dashboard
if (!$course) {
header("Location: student_dashboard.php");
exit();
}
// Fetch enrollment record to get current progress
$stmt = $pdo->prepare("SELECT * FROM enrollments WHERE user_id = ? AND course_id = ?");
$stmt->execute([$user_id, $course_id]);
$enrollment = $stmt->fetch();
if ($enrollment) {
$current_progress = $enrollment['progress'];
} else {
// If not enrolled, redirect to courses page
header("Location: courses.php");
exit();
}
// Handle different content types
if ($content_type === 'video') {
// Fetch the video for this specific lesson from the database
$stmt = $pdo->prepare("SELECT filepath FROM videos WHERE course_id = ? AND lesson_id = ? ORDER BY id LIMIT 1");
$stmt->execute([$course_id, $lesson_id]);
$video_result = $stmt->fetch();
if ($video_result) {
$video_path = $video_result['filepath'];
// Check if video file actually exists
if (!file_exists($video_path)) {
// Try alternative path (in case of relative path issues)
$alternative_path = '../' . $video_path;
if (file_exists($alternative_path)) {
$video_path = $alternative_path;
} else {
// Fallback to default video if file doesn't exist
$video_path = "videos/default_course_video.mp4";
}
}
} else {
// Fallback to default video if no video exists for this lesson
$video_path = "videos/default_course_video.mp4";
}
} elseif ($content_type === 'quiz') {
// Fetch quiz for this lesson
$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE course_id = ? AND lesson_id = ? LIMIT 1");
$stmt->execute([$course_id, $lesson_id]);
$quiz = $stmt->fetch();
if ($quiz) {
// Fetch questions for this quiz
$stmt = $pdo->prepare("SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY id");
$stmt->execute([$quiz['id']]);
$questions = $stmt->fetchAll();
// Fetch options for each question
$quiz_questions = [];
foreach ($questions as $question) {
$stmt = $pdo->prepare("SELECT * FROM quiz_options WHERE question_id = ? ORDER BY id");
$stmt->execute([$question['id']]);
$options = $stmt->fetchAll();
$question['options'] = $options;
$quiz_questions[] = $question;
}
}
} elseif ($content_type === 'assignment') {
// Fetch assignment for this lesson
$stmt = $pdo->prepare("SELECT * FROM assignments WHERE course_id = ? AND lesson_id = ? LIMIT 1");
$stmt->execute([$course_id, $lesson_id]);
$assignment = $stmt->fetch();
}
} elseif ($content_type === 'reviews') {
    // Check if user is enrolled in this course
    $stmt = $pdo->prepare("SELECT * FROM enrollments WHERE user_id = ? AND course_id = ?");
    $stmt->execute([$user_id, $course_id]);
    if (!$stmt->fetch()) {
        // Redirect if not enrolled
        header("Location: course_video.php?id=$course_id&lesson=1&content=video");
        exit();
    }
    
    // Check if user has already reviewed
    $stmt = $pdo->prepare("SELECT * FROM course_reviews WHERE user_id = ? AND course_id = ?");
    $stmt->execute([$user_id, $course_id]);
    $existing_review = $stmt->fetch();
    
    // Get total reviews count for display
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM course_reviews WHERE course_id = ?");
    $stmt->execute([$course_id]);
    $total_reviews = $stmt->fetch()['total'];
}

// Handle AJAX requests - SINGLE HANDLER FOR ALL ACTIONS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle review submission
    if (isset($_POST['action']) && $_POST['action'] === 'submit_review') {
        $user_id = $_SESSION['user_id'];
        $course_id = (int)$_POST['course_id'];
        $rating = (int)$_POST['rating'];
        $review_text = trim($_POST['review_text']);
        
        // Validate inputs
        if ($rating < 1 || $rating > 5) {
            echo json_encode(['status' => 'error', 'message' => 'Please select a valid rating (1-5 stars)']);
            exit();
        }
        
        if (empty($review_text)) {
            echo json_encode(['status' => 'error', 'message' => 'Please write a review']);
            exit();
        }
        
        try {
            // Check if user is enrolled in this course
            $stmt = $pdo->prepare("SELECT * FROM enrollments WHERE user_id = ? AND course_id = ?");
            $stmt->execute([$user_id, $course_id]);
            if (!$stmt->fetch()) {
                echo json_encode(['status' => 'error', 'message' => 'You must be enrolled in this course to leave a review']);
                exit();
            }
            
            // Insert review
            $stmt = $pdo->prepare("INSERT INTO course_reviews (user_id, course_id, rating, review_text) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$user_id, $course_id, $rating, $review_text])) {
                echo json_encode(['status' => 'success', 'message' => 'Thank you for your review!']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to submit review. Please try again.']);
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'This course has already been reviewed by you.']);
        }
        exit();
    }
    if (isset($_POST['action']) && $_POST['action'] === 'submit_quiz') {
        $quiz_id = (int)($_POST['quiz_id'] ?? 0);
        $answers = $_POST['answers'] ?? [];
        
        try {
            $pdo->beginTransaction();
            $total_points = 0;
            $max_points = 0;
            
            // Get quiz questions to verify answers
            $stmt = $pdo->prepare("SELECT qq.*, qo.id as option_id, qo.is_correct FROM quiz_questions qq LEFT JOIN quiz_options qo ON qq.id = qo.question_id WHERE qq.quiz_id = ? ORDER BY qq.id");
            $stmt->execute([$quiz_id]);
            $all_questions = $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
            
            foreach ($answers as $question_id => $answer) {
                $question_data = null;
                foreach ($all_questions as $q_id => $q_data) {
                    if ($q_id == $question_id) {
                        $question_data = $q_data[0];
                        break;
                    }
                }
                
                if ($question_data) {
                    $points_earned = 0;
                    $is_correct = false;
                    $selected_option_id = null;
                    $answer_text = '';
                    
                    if ($question_data['question_type'] === 'multiple_choice') {
                        $selected_option_id = (int)$answer;
                        // Check if selected option is correct
                        $stmt2 = $pdo->prepare("SELECT is_correct FROM quiz_options WHERE id = ?");
                        $stmt2->execute([$selected_option_id]);
                        $option_correct = $stmt2->fetch();
                        if ($option_correct && $option_correct['is_correct']) {
                            $is_correct = true;
                            $points_earned = $question_data['points'];
                        }
                        $answer_text = ''; // Not used for multiple choice
                    } else {
                        $answer_text = $answer;
                        // Check if answer matches correct answer 
                        if (strtolower(trim($answer_text)) === strtolower(trim($question_data['correct_answer']))) {
                            $is_correct = true;
                            $points_earned = $question_data['points'];
                        }
                        $selected_option_id = null;
                    }
                    
                    // Save response
                    $stmt3 = $pdo->prepare("INSERT INTO quiz_responses (user_id, quiz_id, question_id, selected_option_id, answer_text, is_correct, points_earned) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt3->execute([$user_id, $quiz_id, $question_id, $selected_option_id, $answer_text, $is_correct, $points_earned]);
                    
                    $total_points += $points_earned;
                    $max_points += $question_data['points'];
                }
            }
            
            $pdo->commit();
            
            // Recalculate full progress after quiz submission
            $new_progress = calculateCourseProgress($pdo, $user_id, $course_id);
            
            // Calculate score percentage
            $score_percentage = $max_points > 0 ? round(($total_points / $max_points) * 100) : 0;
            
            echo json_encode(['status' => 'success', 'score' => $score_percentage, 'points' => $total_points, 'max_points' => $max_points]);
            exit();
        } catch (Exception $e) {
            $pdo->rollback();
            echo json_encode(['status' => 'error', 'message' => 'Error submitting quiz: ' . $e->getMessage()]);
            exit();
        }
    }
    
    // Handle assignment submission
    if (isset($_POST['action']) && $_POST['action'] === 'submit_assignment') {
        $assignment_id = (int)($_POST['assignment_id'] ?? 0);
        
        // Validate assignment exists
        $stmt = $pdo->prepare("SELECT * FROM assignments WHERE id = ?");
        $stmt->execute([$assignment_id]);
        $assignment = $stmt->fetch();
        
        if (!$assignment) {
            echo json_encode(['status' => 'error', 'message' => 'Assignment not found']);
            exit();
        }
        
        // Handle file upload
        if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] === UPLOAD_ERR_OK) {
            $file_name = $_FILES['submission_file']['name'];
            $file_tmp = $_FILES['submission_file']['tmp_name'];
            $file_size = $_FILES['submission_file']['size'];
            
            // Allowed extensions
            $allowed_exts = ['pdf', 'doc', 'docx'];
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            if (!in_array($ext, $allowed_exts)) {
                echo json_encode(['status' => 'error', 'message' => 'Only PDF, DOC, DOCX files are allowed']);
                exit();
            } elseif ($file_size > 50 * 1024 * 1024) { // 50MB max
                echo json_encode(['status' => 'error', 'message' => 'File size must be less than 50MB']);
                exit();
            }
            
            // Create submissions directory
            $upload_dir = 'submissions/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            
            // Generate unique filename
            $unique_name = 'sub_' . $user_id . '_' . $assignment_id . '_' . time() . '.' . $ext;
            $target_path = $upload_dir . $unique_name;
            
            if (move_uploaded_file($file_tmp, $target_path)) {
                // Insert submission record
                $stmt = $pdo->prepare("INSERT INTO assignment_submissions (assignment_id, user_id, submission_file, submitted_at, status) VALUES (?, ?, ?, NOW(), 'pending')");
                if ($stmt->execute([$assignment_id, $user_id, $target_path])) {
                    echo json_encode(['status' => 'success', 'message' => 'Assignment submitted successfully!']);
                    exit();
                } else {
                    unlink($target_path); // Clean up on DB failure
                    echo json_encode(['status' => 'error', 'message' => 'Database error']);
                    exit();
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to save assignment file']);
                exit();
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Please select a file to upload']);
            exit();
        }
    }
}

// Handle AJAX progress update requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_progress') {
$progress = min(100, max(0, (float)$_POST['progress']));
// Update basic video progress first
$stmt = $pdo->prepare("UPDATE enrollments SET progress = ?, last_accessed = NOW() WHERE user_id = ? AND course_id = ?");
$stmt->execute([$progress, $user_id, $course_id]);
// Then recalculate comprehensive course progress
if (function_exists('calculateCourseProgress')) {
$comprehensive_progress = calculateCourseProgress($pdo, $user_id, $course_id);
}
echo json_encode(['status' => 'success']);
exit();
}
// Handle comprehensive progress recalculation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'recalculate_progress') {
if (function_exists('calculateCourseProgress')) {
$new_progress = calculateCourseProgress($pdo, $user_id, $course_id);
echo json_encode(['status' => 'success', 'progress' => $new_progress]);
} else {
// Fallback calculation for video completion
$progress = 40; // Videos contribute 40% to total progress
$stmt = $pdo->prepare("UPDATE enrollments SET progress = ?, last_accessed = NOW() WHERE user_id = ? AND course_id = ?");
$stmt->execute([$progress, $user_id, $course_id]);
echo json_encode(['status' => 'success', 'progress' => $progress]);
}
exit();
}
if (!$course) {
$course = [
'course_id' => 15,
'title' => 'Programming in Python - 1. Python for Beginners',
'description' => 'Learn the fundamentals of Python programming with this comprehensive beginner course.',
'duration' => '8 weeks',
'level' => 'Beginner'
];

$quiz_questions = [
[
'id' => 1,
'question_text' => 'What is the output of print(2 ** 3)?',
'question_type' => 'multiple_choice',
'points' => 10,
'options' => [
['id' => 1, 'option_text' => '6', 'is_correct' => false],
['id' => 2, 'option_text' => '8', 'is_correct' => true],
['id' => 3, 'option_text' => '9', 'is_correct' => false],
['id' => 4, 'option_text' => 'Error', 'is_correct' => false]
]
],
[
'id' => 2,
'question_text' => 'Python is a compiled language.',
'question_type' => 'true_false',
'points' => 10,
'correct_answer' => 'False'
]
];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($course['title']) ?> - SKILL SWAP</title>
<link rel="stylesheet" href="index.css">
<link rel="stylesheet" href="footer.css">
<style>
* {
margin: 0;
padding: 0;
box-sizing: border-box;
}
body {
font-family: Arial, sans-serif;
background-color: #f5f5f5;
}
.header {
background-color: white;
padding: 15px 30px;
display: flex;
justify-content: space-between;
align-items: center;
box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.logo-section {
display: flex;
align-items: center;
gap: 10px;
}
.logo {
width: 40px;
height: 40px;
background-color: #8B4513;
border-radius: 50%;
}
.logo-text h3 {
color: #c44;
font-size: 14px;
}
.logo-text p {
color: #666;
font-size: 11px;
}
.nav {
display: flex;
gap: 30px;
}
.nav a {
text-decoration: none;
color: #333;
font-size: 14px;
}
.nav a:hover {
color: #c44;
}
.user-section {
display: flex;
align-items: center;
gap: 15px;
}
.user-avatar {
width: 35px;
height: 35px;
background-color: #ccc;
border-radius: 50%;
}
.container {
max-width: 1200px;
margin: 0 auto;
padding: 30px;
}
.course-header {
margin-bottom: 30px;
}
.course-title {
font-size: 24px;
color: #333;
margin-bottom: 15px;
}
.back-link {
display: inline-flex;
align-items: center;
gap: 5px;
color: #666;
text-decoration: none;
margin-bottom: 20px;
font-size: 14px;
}
.back-link:hover {
color: #c44;
}
.course-content {
background-color: white;
padding: 30px;
border-radius: 8px;
box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.lecture-container {
display: flex;
gap: 30px;
margin-bottom: 30px;
}
.lecture-sidebar {
width: 250px;
border-right: 1px solid #e0e0e0;
padding-right: 20px;
}
.sidebar-header {
display: flex;
align-items: center;
gap: 10px;
margin-bottom: 20px;
padding-bottom: 15px;
border-bottom: 1px solid #e0e0e0;
}
.sidebar-header i {
color: #666;
font-size: 18px;
}
.sidebar-header h3 {
font-size: 16px;
color: #333;
}
.lecture-list {
list-style: none;
}
.lecture-item {
padding: 10px 0;
border-bottom: 1px solid #f0f0f0;
cursor: pointer;
transition: background-color 0.2s;
}
.lecture-item:hover {
background-color: #f9f9f9;
}
.lecture-item.active {
background-color: #f0f0f0;
border-left: 4px solid #c44;
padding-left: 16px;
}
.lecture-item h4 {
font-size: 14px;
color: #333;
margin-bottom: 5px;
}
.lecture-item ul {
list-style: none;
padding-left: 20px;
margin-top: 5px;
}
.lecture-item li {
font-size: 13px;
color: #666;
margin-bottom: 5px;
}
.lecture-item li a {
color: #666;
text-decoration: none;
display: block;
padding: 5px 0;
border-radius: 4px;
transition: background-color 0.2s;
}
.lecture-item li a:hover {
color: #c44;
background-color: #f0f0f0;
}
.lecture-video {
flex: 1;
position: relative;
height: 400px;
background-color: #e8e8e8;
display: flex;
align-items: center;
justify-content: center;
border: 1px solid #ddd;
border-radius: 4px;
}
.video-player {
width: 100%;
height: 100%;
display: flex;
align-items: center;
justify-content: center;
background-color: #000;
}
.video-controls {
display: flex;
justify-content: space-between;
align-items: center;
margin-top: 20px;
}
.summary-button {
padding: 10px 20px;
background-color: #f0f0f0;
border: 1px solid #ddd;
border-radius: 4px;
cursor: pointer;
font-size: 14px;
color: #333;
transition: background-color 0.2s;
}
.summary-button:hover {
background-color: #e0e0e0;
}
.fullscreen-button {
width: 30px;
height: 30px;
background-color: #f0f0f0;
border-radius: 4px;
display: flex;
align-items: center;
justify-content: center;
cursor: pointer;
transition: background-color 0.2s;
}
.fullscreen-button:hover {
background-color: #e0e0e0;
}
.fullscreen-button i {
color: #666;
font-size: 16px;
}
.section-divider {
height: 1px;
background-color: #e0e0e0;
margin: 30px 0;
}
.section-header {
display: flex;
align-items: center;
gap: 10px;
margin-bottom: 20px;
}
.section-header i {
color: #666;
font-size: 18px;
}
.section-header h3 {
font-size: 16px;
color: #333;
}
.back-to-course {
display: inline-flex;
align-items: center;
gap: 5px;
color: #666;
text-decoration: none;
margin-top: 20px;
font-size: 14px;
}
.back-to-course:hover {
color: #c44;
}
/* Added styles for the video player */
.video-placeholder {
width: 100%;
height: 100%;
display: flex;
align-items: center;
justify-content: center;
background-color: #000;
}
.video-placeholder i {
font-size: 60px;
color: #999;
}
/* Responsive Design */
@media (max-width: 768px) {
.lecture-container {
flex-direction: column;
}
.lecture-sidebar {
width: 100%;
border-right: none;
border-bottom: 1px solid #e0e0e0;
padding-right: 0;
padding-bottom: 20px;
}
.lecture-video {
height: 300px;
}
}
/* Custom video player controls */
.custom-video-controls {
display: flex;
align-items: center;
gap: 10px;
padding: 10px;
background-color: rgba(0, 0, 0, 0.7);
color: white;
position: absolute;
bottom: 0;
left: 0;
right: 0;
z-index: 10;
}
.custom-video-controls input[type="range"] {
flex: 1;
height: 5px;
-webkit-appearance: none;
background: #666;
outline: none;
border-radius: 3px;
}
.custom-video-controls input[type="range"]::-webkit-slider-thumb {
-webkit-appearance: none;
appearance: none;
width: 15px;
height: 15px;
border-radius: 50%;
background: #c44;
cursor: pointer;
}
.custom-video-controls button {
background: none;
border: none;
color: white;
cursor: pointer;
font-size: 16px;
padding: 5px;
}
.custom-video-controls button:hover {
color: #c44;
}
.custom-video-controls .time-display {
font-size: 14px;
min-width: 80px;
text-align: center;
}
/* Progress indicator */
.progress-indicator {
position: absolute;
top: 10px;
right: 10px;
background: rgba(0, 0, 0, 0.7);
color: white;
padding: 5px 10px;
border-radius: 4px;
font-size: 12px;
z-index: 11;
}
/* Video error message */
.video-error {
color: #ff4444;
font-size: 14px;
text-align: center;
padding: 20px;
}
/* Quiz styles */
.quiz-container {
padding: 20px;
}
.quiz-question {
margin-bottom: 25px;
padding: 15px;
background-color: #f8f9fa;
border-radius: 8px;
border-left: 4px solid #3498db;
}
.question-header {
display: flex;
justify-content: space-between;
margin-bottom: 10px;
}
.question-number {
font-weight: bold;
color: #3498db;
}
.question-points {
color: #27ae60;
font-weight: bold;
}
.question-text {
margin-bottom: 15px;
font-size: 16px;
line-height: 1.5;
}
.options-list {
list-style: none;
}
.option-item {
margin-bottom: 8px;
}
.option-label {
display: flex;
align-items: center;
padding: 10px;
background-color: white;
border: 1px solid #e0e0e0;
border-radius: 4px;
cursor: pointer;
transition: all 0.2s;
}
.option-label:hover {
background-color: #f0f7ff;
border-color: #3498db;
}
.option-input {
margin-right: 10px;
}
.option-text {
flex: 1;
}
.short-answer-input {
width: 100%;
padding: 10px;
border: 1px solid #ddd;
border-radius: 4px;
font-size: 14px;
margin-top: 10px;
}
.submit-quiz-btn {
background-color: #27ae60;
color: white;
border: none;
padding: 12px 24px;
border-radius: 4px;
cursor: pointer;
font-size: 16px;
font-weight: bold;
margin-top: 20px;
}
.submit-quiz-btn:hover {
background-color: #229954;
}
.quiz-result {
text-align: center;
padding: 30px;
background-color: #f8f9fa;
border-radius: 8px;
margin-top: 20px;
}
.result-score {
font-size: 24px;
font-weight: bold;
color: #27ae60;
margin: 10px 0;
}
.result-message {
font-size: 18px;
margin: 10px 0;
}
.result-details {
margin: 20px 0;
padding: 15px;
background-color: white;
border-radius: 4px;
}
/* Assignment styles */
.assignment-container {
padding: 20px;
}
.assignment-header {
display: flex;
justify-content: space-between;
margin-bottom: 15px;
}
.assignment-title {
font-size: 18px;
color: #3498db;
font-weight: bold;
}
.assignment-meta {
color: #7f8c8d;
font-size: 14px;
margin-bottom: 15px;
}
.assignment-description {
margin-bottom: 20px;
line-height: 1.6;
}
.assignment-file {
margin: 15px 0;
padding: 10px;
background: #f8f9fa;
border-radius: 4px;
}
.assignment-file a {
color: #3498db;
text-decoration: none;
font-weight: bold;
}
.assignment-file a:hover {
text-decoration: underline;
}
.no-assignment {
text-align: center;
padding: 40px;
color: #7f8c8d;
}
.no-assignment i {
font-size: 48px;
margin-bottom: 15px;
opacity: 0.5;
}
/* Submission form styles */
.submission-form {
margin-top: 20px;
padding: 20px;
background: #f8f9fa;
border-radius: 6px;
}
.submission-form label {
display: block;
margin-bottom: 5px;
font-weight: bold;
color: #333;
}
.submission-form input[type="file"] {
display: block;
margin: 5px 0 10px 0;
}
.submission-form small {
color: #666;
display: block;
margin-top: 5px;
}
.btn-submit {
background: #27ae60;
color: white;
padding: 10px 20px;
border: none;
border-radius: 4px;
cursor: pointer;
font-size: 14px;
font-weight: bold;
}
.btn-submit:hover {
background: #229954;
}
/* Submission status styles */
.submission-status {
margin-top: 20px;
padding: 20px;
background: #e8f4ea;
border-radius: 6px;
}
.submission-status h3 {
color: #27ae60;
margin-bottom: 10px;
}
.submission-status p {
margin: 5px 0;
}
.status-pending {
color: #f39c12;
font-weight: bold;
}
.status-graded {
color: #27ae60;
font-weight: bold;
}
.view-submission {
display: inline-block;
padding: 8px 16px;
background: #3498db;
color: white;
text-decoration: none;
border-radius: 4px;
margin-top: 10px;
}
.view-submission:hover {
background: #2980b9;
}
/* Course Reviews Styles */
.reviews-container {
    padding: 20px;
}

.rating-input {
    display: flex;
    gap: 5px;
    margin: 15px 0;
}

.rating-input input[type="radio"] {
    display: none;
}

.rating-input label {
    font-size: 24px;
    color: #ddd;
    cursor: pointer;
    transition: color 0.2s;
}

.rating-input input[type="radio"]:checked ~ label,
.rating-input label:hover,
.rating-input label:hover ~ label {
    color: #f39c12;
}

.review-textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    resize: vertical;
    min-height: 100px;
    margin: 10px 0;
}

.submit-review-btn {
    background: #9b59b6;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
    margin-top: 10px;
}

.reviews-list {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.review-item {
    padding: 15px 0;
    border-bottom: 1px solid #eee;
}

.review-item:last-child {
    border-bottom: none;
}

.review-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
}

.review-user {
    font-weight: bold;
    color: #3498db;
}

.review-rating {
    color: #f39c12;
    margin: 5px 0;
}
</style>
</head>
<body>
<!-- Navigation Bar -->
<nav class="navbar">
<div class="logo-placeholder">SKILL SWAP</div>
<div class="navbar-links">
<span>Hey, <?= htmlspecialchars($_SESSION['name'] ?? 'User') ?>!</span>
<a href="index.php">Home</a>
<a href="courses.php">Courses</a>
<a href="about.php">About Us</a>
<a href="contact.php">Contact</a>
<?php if (isset($_SESSION['user_id'])): ?>
<!-- User is logged in -->
<?php if ($_SESSION['role'] === 'student'): ?>
<a href="student_dashboard.php">Dashboard</a>
<?php elseif ($_SESSION['role'] === 'admin'): ?>
<a href="admin_dashboard.php">Dashboard</a>
<?php endif; ?>
<a href="profile.php">Profile</a>
<a href="logout.php" class="btn1">Logout</a>
<?php else: ?>
<!-- User is NOT logged in -->
<a href="login.php">Login</a>
<a href="register.php">Register</a>
<?php endif; ?>
</div>
</nav>
<div class="container">
<div class="course-header">
<h1 class="course-title"><?= htmlspecialchars($course['title']) ?></h1>
<a href="student_dashboard.php" class="back-link">
<i class="fas fa-arrow-left"></i> Back to Dashboard
</a>
</div>
<div class="course-content">
<div class="lecture-container">
<div class="lecture-sidebar">
<div class="sidebar-header">
<i class="fas fa-bars"></i>
<h3>Lecture Content</h3>
</div>
<ul class="lecture-list">
<li class="lecture-item <?= ($lesson_id == 1 && $content_type == 'video') ? 'active' : '' ?>">
<h4>1.1 Introduction</h4>
<ul>
<li><a href="course_video.php?id=<?= $course_id ?>&lesson=1&content=video">Lecture Video</a></li>
<li><a href="course_video.php?id=<?= $course_id ?>&lesson=1&content=quiz">Quiz</a></li>
</ul>
</li>
<li class="lecture-item <?= ($lesson_id == 2 && $content_type == 'video') ? 'active' : '' ?>">
<h4>1.2 Lesson 1</h4>
<ul>
<li><a href="course_video.php?id=<?= $course_id ?>&lesson=2&content=video">Lecture Video</a></li>
<li><a href="course_video.php?id=<?= $course_id ?>&lesson=2&content=quiz">Quiz</a></li>
<li><a href="course_video.php?id=<?= $course_id ?>&lesson=2&content=assignment">Assignment 01</a></li>
</ul>
</li>
<li class="lecture-item <?= ($lesson_id == 3 && $content_type == 'video') ? 'active' : '' ?>">
<h4>1.3 Lesson 2</h4>
<ul>
<li><a href="course_video.php?id=<?= $course_id ?>&lesson=3&content=video">Lecture Video</a></li>
<li><a href="course_video.php?id=<?= $course_id ?>&lesson=3&content=quiz">Quiz</a></li>
<li><a href="course_video.php?id=<?= $course_id ?>&lesson=3&content=assignment">Assignment 02</a></li>
<li><a href="course_video.php?id=<?= $course_id ?>&lesson=3&content=assignment">Assignment 03</a></li>
<?php
// Check if certificate exists for this course
$stmt = $pdo->prepare("SELECT certificate_path FROM certificates WHERE user_id = ? AND course_id = ? AND status = 'issued'");
$stmt->execute([$_SESSION['user_id'], $course_id]);
$certificate = $stmt->fetch();
?>
<?php if ($certificate): ?>
    <li><a href="<?= htmlspecialchars($certificate['certificate_path']) ?>" target="_blank">📄 View Certificate</a></li>
    <li><a href="<?= htmlspecialchars($certificate['certificate_path']) ?>" download>⬇️ Download Certificate</a></li>
<?php else: ?>
    <li><a href="#" style="color: #999; cursor: not-allowed;" title="Certificate not yet issued">🔒 Obtain your Certificate</a></li>
<?php endif; ?>
</ul>
</li>
</ul>
<button class="summary-button">Summary & submit</button>
</div>

<div class="lecture-video">
<?php if ($content_type === 'video'): ?>
<!-- Video player container -->
<div class="video-player">
<!-- HTML5 Video Player -->
<video id="courseVideo" width="100%" height="100%" controls>
<!-- Primary MP4 source -->
<source src="<?= htmlspecialchars($video_path) ?>" type="video/mp4">
<!-- Fallback WebM source -->
<source src="<?= htmlspecialchars(str_replace('.mp4', '.webm', $video_path)) ?>" type="video/webm">
<!-- Fallback OGG source -->
<source src="<?= htmlspecialchars(str_replace('.mp4', '.ogg', $video_path)) ?>" type="video/ogg">
<!-- Fallback for unsupported browsers -->
Your browser does not support the video tag.
</video>
<!-- Progress indicator -->
<div id="progressIndicator" class="progress-indicator">
Progress: <?= round($current_progress) ?>%
</div>
<!-- Video error message -->
<div id="videoError" class="video-error" style="display: none;">
<i class="fas fa-exclamation-triangle"></i> Video playback error. Please try refreshing the page or contact support.
</div>
</div>
<?php elseif ($content_type === 'quiz'): ?>
<!-- Quiz container -->
<div class="quiz-container">
<?php if (isset($quiz_questions) && !empty($quiz_questions)): ?>
<h2 style="margin-bottom: 20px; color: #333;">Quiz: <?= htmlspecialchars($quiz['title'] ?? 'Lesson Quiz') ?></h2>
<form id="quizForm">
<input type="hidden" name="quiz_id" value="<?= $quiz['id'] ?? 1 ?>">
<?php foreach ($quiz_questions as $index => $question): ?>
<div class="quiz-question">
<div class="question-header">
<span class="question-number">Question <?= $index + 1 ?></span>
<span class="question-points"><?= $question['points'] ?> points</span>
</div>
<div class="question-text"><?= htmlspecialchars($question['question_text']) ?></div>
<?php if ($question['question_type'] === 'multiple_choice'): ?>
<ul class="options-list">
<?php foreach ($question['options'] as $option): ?>
<li class="option-item">
<label class="option-label">
<input type="radio" name="answers[<?= $question['id'] ?>]" value="<?= $option['id'] ?>" class="option-input" required>
<span class="option-text"><?= htmlspecialchars($option['option_text']) ?></span>
</label>
</li>
<?php endforeach; ?>
</ul>
<?php elseif ($question['question_type'] === 'true_false'): ?>
<ul class="options-list">
<li class="option-item">
<label class="option-label">
<input type="radio" name="answers[<?= $question['id'] ?>]" value="True" class="option-input" required>
<span class="option-text">True</span>
</label>
</li>
<li class="option-item">
<label class="option-label">
<input type="radio" name="answers[<?= $question['id'] ?>]" value="False" class="option-input" required>
<span class="option-text">False</span>
</label>
</li>
</ul>
<?php else: // short_answer ?>
<input type="text" name="answers[<?= $question['id'] ?>]" class="short-answer-input" placeholder="Enter your answer" required>
<?php endif; ?>
</div>
<?php endforeach; ?>
<button type="submit" class="submit-quiz-btn">Submit Quiz</button>
</form>
<div id="quizResult" style="display: none;"></div>
<?php else: ?>
<div style="text-align: center; padding: 40px; color: #666;">
<i class="fas fa-clipboard-list" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
<h3>No Quiz Available</h3>
<p>This lesson doesn't have a quiz yet.</p>
</div>
<?php endif; ?>
</div>
<?php elseif ($content_type === 'reviews'): ?>
<!-- Reviews container -->
<div class="reviews-container">
    <h2 style="margin-bottom: 20px; color: #333;">Course Reviews</h2>
    
    <?php if ($existing_review): ?>
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <h3>You've already reviewed this course:</h3>
            <div style="color: #f39c12; margin: 10px 0;">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="star <?= $i <= $existing_review['rating'] ? 'filled' : '' ?>">★</span>
                <?php endfor; ?>
            </div>
            <p><?= htmlspecialchars($existing_review['review_text']) ?></p>
        </div>
    <?php else: ?>
        <!-- Review Form -->
        <div class="review-form">
            <h3>Write a Review for: <?= htmlspecialchars($course['title']) ?></h3>
            <form id="reviewForm">
                <input type="hidden" name="course_id" value="<?= $course_id ?>">
                <input type="hidden" name="action" value="submit_review">
                
                <div class="rating-input">
                    <input type="radio" id="star1" name="rating" value="1" required>
                    <label for="star1">★</label>
                    <input type="radio" id="star2" name="rating" value="2" required>
                    <label for="star2">★</label>
                    <input type="radio" id="star3" name="rating" value="3" required>
                    <label for="star3">★</label>
                    <input type="radio" id="star4" name="rating" value="4" required>
                    <label for="star4">★</label>
                    <input type="radio" id="star5" name="rating" value="5" required>
                    <label for="star5">★</label>
                </div>
                
                <textarea name="review_text" class="review-textarea" placeholder="Write your review here..." required></textarea>
                
                <button type="submit" class="submit-review-btn">Submit Review</button>
            </form>
            <div id="reviewResult" style="display: none;"></div>
        </div>
    <?php endif; ?>
    
    <!-- All Reviews -->
    <div class="reviews-list">
        <h3>All Reviews (<?= $total_reviews ?>)</h3>
        <?php
        $stmt = $pdo->prepare("SELECT cr.*, u.name as user_name FROM course_reviews cr JOIN users u ON cr.user_id = u.user_id WHERE cr.course_id = ? ORDER BY cr.created_at DESC");
        $stmt->execute([$course_id]);
        $all_reviews = $stmt->fetchAll();
        
        if (empty($all_reviews)): ?>
            <p>No reviews yet. Be the first to review this course!</p>
        <?php else: ?>
            <?php foreach ($all_reviews as $review): ?>
                <div class="review-item">
                    <div class="review-header">
                        <span class="review-user"><?= htmlspecialchars($review['user_name']) ?></span>
                        <span class="review-date"><?= date('M j, Y', strtotime($review['created_at'])) ?></span>
                    </div>
                    <div class="review-rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="star <?= $i <= $review['rating'] ? 'filled' : '' ?>">★</span>
                        <?php endfor; ?>
                    </div>
                    <p><?= htmlspecialchars($review['review_text']) ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// Handle review form submission
document.getElementById('reviewForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        const resultDiv = document.getElementById('reviewResult');
        if (result.status === 'success') {
            resultDiv.innerHTML = '<div style="color: green; padding: 10px; background: #d4edda; border-radius: 4px;">' + result.message + '</div>';
            resultDiv.style.display = 'block';
            // Reload page after 2 seconds
            setTimeout(() => location.reload(), 2000);
        } else {
            resultDiv.innerHTML = '<div style="color: red; padding: 10px; background: #f8d7da; border-radius: 4px;">' + result.message + '</div>';
            resultDiv.style.display = 'block';
        }
    });
});
</script>
<?php elseif ($content_type === 'assignment'): ?>
<!-- Assignment container -->
<div class="assignment-container">
<?php if (isset($assignment) && $assignment): ?>
<div class="assignment-header">
<h2 class="assignment-title"><?= htmlspecialchars($assignment['title']) ?></h2>
<?php if (!empty($assignment['due_date'])): ?>
<span class="assignment-meta">Due: <?= date('M j, Y', strtotime($assignment['due_date'])) ?></span>
<?php endif; ?>
</div>
<?php if (!empty($assignment['description'])): ?>
<div class="assignment-description">
<?= nl2br(htmlspecialchars($assignment['description'])) ?>
</div>
<?php endif; ?>
<?php
// Check if student has already submitted
$stmt = $pdo->prepare("SELECT * FROM assignment_submissions WHERE assignment_id = ? AND user_id = ?");
$stmt->execute([$assignment['id'], $user_id]);
$submission = $stmt->fetch();
?>
<?php if ($submission): ?>
<!-- Student has already submitted -->
<div class="submission-status">
<h3>Your Submission</h3>
<p><strong>Submitted:</strong> <?= date('M j, Y H:i', strtotime($submission['submitted_at'])) ?></p>
<?php if ($submission['status'] === 'graded'): ?>
<p><strong>Status:</strong> <span class="status-graded">Graded</span> - <?= $submission['points_awarded'] ?>/<?= $assignment['max_points'] ?> points</p>
<?php if (!empty($submission['feedback'])): ?>
<p><strong>Feedback:</strong> <?= nl2br(htmlspecialchars($submission['feedback'])) ?></p>
<?php endif; ?>
<?php else: ?>
<p><strong>Status:</strong> <span class="status-pending">Pending review</span></p>
<?php endif; ?>
<a href="<?= htmlspecialchars($submission['submission_file']) ?>" target="_blank" class="view-submission">📁 View Your Submission</a>
</div>
<?php else: ?>
<!-- Student hasn't submitted yet -->
<div class="assignment-instructions">
<h3>Instructions</h3>
<p>Please complete the assignment and upload your answer file below.</p>
<?php if (!empty($assignment['submission_file'])): ?>
<div style="margin: 20px 0;">
<strong>Assignment Template:</strong>
<br>
<a href="<?= htmlspecialchars($assignment['submission_file']) ?>" target="_blank" style="color: #3498db; text-decoration: underline;">
📄 Download Assignment Template
</a>
</div>
<?php endif; ?>
</div>
<div class="submission-form">
<form id="assignmentForm" enctype="multipart/form-data">
<input type="hidden" name="assignment_id" value="<?= $assignment['id'] ?>">
<div>
<label for="submission_file">Upload Your Assignment Answer:</label>
<input type="file" name="submission_file" id="submission_file" accept=".pdf,.doc,.docx" required>
<small>PDF, DOC, or DOCX files only (Max 50MB)</small>
</div>
<button type="submit" class="btn-submit">Submit Assignment</button>
</form>
</div>
<?php endif; ?>
<?php if (!empty($assignment['max_points'])): ?>
<div class="assignment-meta">Maximum Points: <?= $assignment['max_points'] ?></div>
<?php endif; ?>
<?php else: ?>
<div class="no-assignment">
<i class="fas fa-file-alt"></i>
<h3>No Assignment Available</h3>
<p>This lesson doesn't have an assignment yet.</p>
</div>
<?php endif; ?>
</div>
<?php else: ?>
<!-- Certificate or other content -->
<div style="padding: 40px; text-align: center; color: #666;">
<i class="fas fa-file-alt" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
<h3><?= ucfirst($content_type) ?> Content</h3>
<p><?= ucfirst($content_type) ?> content will be available soon.</p>
</div>
<?php endif; ?>
</div>
</div>
<div class="video-controls">
<div class="section-header">
<i class="fas fa-arrow-up"></i>
<h3><?= ucfirst($content_type) ?> Content</h3>
</div>
<div class="fullscreen-button">
<i class="fas fa-expand-arrows-alt"></i>
</div>
</div>
<li class="lecture-item <?= ($lesson_id == 1 && $content_type == 'reviews') ? 'active' : '' ?>">
    <h4>Course Reviews</h4>
    <ul>
        <li><a href="course_video.php?id=<?= $course_id ?>&lesson=1&content=reviews">View/Write Reviews</a></li>
    </ul>
</li>

<div class="section-divider"></div>
<a href="student_dashboard.php" class="back-to-course">
<i class="fas fa-arrow-left"></i> Back to Dashboard
</a>
</div>
</div>
<!--Footer -->
<footer class="footer">
<div class="container">
<div class="footer-content">
<!-- Logo and Social Media -->
<div class="footer-column logo-social">
<div class="logo-placeholder">SKILL SWAP</div>
<h4>Follow Us On</h4>
<div class="social-icons">
<a href="#" aria-label="Facebook"><img src="images/facebook-logo.png" alt="Facebook" class="social-icon"></a>
<a href="#" aria-label="Instagram"><img src="images/instagram-logo.png" alt="Instagram" class="social-icon"></a>
<a href="#" aria-label="LinkedIn"><img src="images/linkedin-logo.png" alt="LinkedIn" class="social-icon"></a>
<a href="#" aria-label="YouTube"><img src="images/youtube-logo.png" alt="YouTube" class="social-icon"></a>
</div>
<!-- Optional: Remove or adjust the award badge if it's causing issues -->
<img src="images/award-badge.png" alt="Award Badge" class="award-badge">
</div>
<!-- Quick Links -->
<div class="footer-column">
<h4>Quick Links</h4>
<ul>
<li><a href="index.php">Home</a></li>
<li><a href="courses.php">Courses</a></li>
<li><a href="about.php">About Us</a></li>
<li><a href="contact.php">Contact Us</a></li>
</ul>
</div>
<!-- Important Links -->
<div class="footer-column">
<h4>Information</h4>
<ul>
<li><a href="terms.php">Terms & Conditions</a></li>
<li><a href="privacy.php">Privacy Policy</a></li>
<li><a href="refund_policy.php">Refund Policy</a></li>
<li><a href="payment_policy.php">Payment Policy</a></li>
<li><a href="quality_policy.php">Quality Policy</a></li>
</ul>
</div>
<!-- Contact Information -->
<div class="footer-column contact-info">
<h4>Contact Us</h4>
<ul>
<li><i class="fas fa-envelope"></i> info@skillswap.lk</li>
</ul>
</div>
</div>
<!-- Bottom Section -->
<div class="footer-bottom">
<hr>
<p>This Site was developed by Team 11- All Rights Reserved</p>
</div>
<!-- Back to Top Button -->
<button class="back-to-top" id="backToTop" aria-label="Back to top">
<i class="fas fa-arrow-up"></i>
</button>
</div>
</footer>
<!-- Font Awesome for icons -->
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
<!-- JavaScript for video player and quiz functionality -->
<script>
// Get video element and other elements
const video = document.getElementById('courseVideo');
const progressIndicator = document.getElementById('progressIndicator');
const videoError = document.getElementById('videoError');
let lastProgressUpdate = 0;
const courseId = <?= $course_id ?>;
const userId = <?= $user_id ?>;
const baseProgress = <?= $current_progress ?>;
// Show error if video fails to load
if (video) {
video.addEventListener('error', function() {
videoError.style.display = 'block';
console.error('Video failed to load:', video.error);
});
// Play/Pause functionality on click
video.addEventListener('click', function(e) {
// Only trigger if click is not on controls
if (e.offsetY < this.offsetHeight - 40) {
if (video.paused) {
video.play();
} else {
video.pause();
}
}
});
// Fullscreen functionality
document.querySelector('.fullscreen-button').addEventListener('click', function() {
const videoContainer = document.querySelector('.lecture-video');
if (videoContainer.requestFullscreen) {
videoContainer.requestFullscreen();
} else if (videoContainer.webkitRequestFullscreen) { // Safari
videoContainer.webkitRequestFullscreen();
} else if (videoContainer.msRequestFullscreen) { // IE11
videoContainer.msRequestFullscreen();
}
});
// Track video progress and update database
video.addEventListener('timeupdate', function() {
const currentTime = video.currentTime;
const duration = video.duration;
if (duration > 0) {
// Calculate video completion percentage
const videoCompletion = (currentTime / duration) * 100;
// The comprehensive calculation happens on quiz/assignment submission
const newProgress = Math.min(100, Math.round(videoCompletion));
// Update progress indicator
progressIndicator.textContent = `Progress: ${newProgress}%`;
// Update database every 10 seconds or when video ends
const now = Date.now();
if (now - lastProgressUpdate > 10000 || video.ended) {
lastProgressUpdate = now;
// Send AJAX request to update progress
fetch('', {
method: 'POST',
headers: {
'Content-Type': 'application/x-www-form-urlencoded',
},
body: `action=update_progress&progress=${newProgress}`
})
.then(response => response.json())
.then(data => {
if (data.status === 'success') {
console.log('Progress updated successfully');
}
})
.catch(error => {
console.error('Error updating progress:', error);
});
}
}
});
// Handle video completion
video.addEventListener('ended', function() {
// Recalculate comprehensive progress after video completion
fetch('', {
method: 'POST',
headers: {
'Content-Type': 'application/x-www-form-urlencoded',
},
body: 'action=recalculate_progress'
})
.then(response => response.json())
.then(data => {
if (data.status === 'success') {
progressIndicator.textContent = `Progress: ${data.progress}%`;
if (data.progress >= 100) {
setTimeout(() => {
alert('Congratulations! You have completed this video lecture.');
}, 1000);
}
}
})
.catch(error => {
console.error('Error recalculating progress:', error);
});
});
// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
// Spacebar to play/pause
if (e.code === 'Space') {
e.preventDefault();
if (video.paused) {
video.play();
} else {
video.pause();
}
}
// Arrow right to seek forward
else if (e.code === 'ArrowRight') {
video.currentTime += 10;
}
// Arrow left to seek backward
else if (e.code === 'ArrowLeft') {
video.currentTime -= 10;
}
// Arrow up to increase volume
else if (e.code === 'ArrowUp') {
video.volume = Math.min(1, video.volume + 0.1);
}
// Arrow down to decrease volume
else if (e.code === 'ArrowDown') {
video.volume = Math.max(0, video.volume - 0.1);
}
});
}
// Handle quiz form submission
const quizForm = document.getElementById('quizForm');
if (quizForm) {
quizForm.addEventListener('submit', function(e) {
e.preventDefault();
// Get form data as object
const formData = new FormData(quizForm);
const data = {
action: 'submit_quiz',
quiz_id: formData.get('quiz_id'),
answers: {}
};
// Get all answers
formData.forEach((value, key) => {
if (key.startsWith('answers[')) {
const questionId = key.match(/\[(\d+)\]/)[1];
data.answers[questionId] = value;
}
});
// Send AJAX request with proper content-type
fetch('', {
method: 'POST',
headers: {
'Content-Type': 'application/x-www-form-urlencoded',
},
body: `action=submit_quiz&quiz_id=${data.quiz_id}&${Object.keys(data.answers).map(key => `answers[${key}]=${encodeURIComponent(data.answers[key])}`).join('&')}`
})
.then(response => response.json())
.then(result => {
if (result.status === 'success') {
// Show results
const quizResult = document.getElementById('quizResult');
quizResult.innerHTML = `
<div class="quiz-result">
<h3>Quiz Completed!</h3>
<div class="result-score">${result.score}% (${result.points}/${result.max_points} points)</div>
<div class="result-message">
${result.score >= 70 ? '🎉 Congratulations! You passed!' : '📚 Keep studying and try again!'}
</div>
<button onclick="location.reload()" class="submit-quiz-btn" style="margin-top: 20px;">Try Again</button>
</div>
`;
quizResult.style.display = 'block';
quizForm.style.display = 'none';
} else {
alert('Error: ' + result.message);
}
})
.catch(error => {
console.error('Error:', error);
alert('An error occurred while submitting the quiz. Please try again.');
});
});
}
// Handle assignment submission 
const assignmentForm = document.getElementById('assignmentForm');
if (assignmentForm) {
assignmentForm.addEventListener('submit', function(e) {
e.preventDefault();
    
// Create FormData object from the form (this automatically handles file uploads)
const formData = new FormData(assignmentForm);
    
// Add the action parameter
formData.append('action', 'submit_assignment');
    
// Send the form data using fetch
fetch('', {
method: 'POST',
body: formData  // FormData automatically sets the correct Content-Type with boundary
})
.then(response => response.json())
.then(data => {
if (data.status === 'success') {
alert('✅ Assignment submitted successfully!');
location.reload(); // Refresh to show submission status
} else {
alert('❌ Error: ' + (data.message || 'Unknown error occurred'));
console.error('Submission error details:', data);
}
})
.catch(error => {
console.error('Fetch error:', error);
alert('Network error: ' + error.message);
});
});
}
</script>
</body>
</html>