<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

if ($_POST) {
    $course_id = (int)$_POST['course_id'];
    $lesson_id = (int)$_POST['lesson_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $due_date = $_POST['due_date'] ?? null;
    $max_points = (int)($_POST['max_points'] ?? 100);

    // Validate required fields
    if (empty($course_id) || empty($lesson_id) || empty($title)) {
        $error = "Course, lesson, and title are required.";
    } else {
        $submission_file = null;

        // Handle file upload
        if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] === UPLOAD_ERR_OK) {
            $file_name = $_FILES['submission_file']['name'];
            $file_tmp = $_FILES['submission_file']['tmp_name'];
            $file_size = $_FILES['submission_file']['size'];

            // Allowed extensions
            $allowed_exts = ['pdf', 'doc', 'docx', 'txt'];
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed_exts)) {
                $error = "Only PDF, DOC, DOCX, TXT files are allowed.";
            } elseif ($file_size > 50 * 1024 * 1024) { // 50MB max
                $error = "File size must be less than 50MB.";
            } else {
                // Create uploads directory
                $upload_dir = 'uploads/assignments/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

                // Generate unique filename
                $unique_name = 'assign_' . $course_id . '_' . $lesson_id . '_' . time() . '.' . $ext;
                $target_path = $upload_dir . $unique_name;

                if (move_uploaded_file($file_tmp, $target_path)) {
                    $submission_file = $target_path;
                } else {
                    $error = "Failed to save assignment file.";
                }
            }
        }

        if (!$error) {
            // Insert assignment into DB
            $stmt = $pdo->prepare("INSERT INTO assignments (course_id, lesson_id, title, description, due_date, max_points, submission_file) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$course_id, $lesson_id, $title, $description, $due_date, $max_points, $submission_file])) {
                $success = "Assignment created successfully!";
            } else {
                $error = "Database error: " . implode(', ', $stmt->errorInfo());
            }
        }
    }
}

// Get courses for dropdown
try {
    $stmt = $pdo->query("SELECT course_id, title FROM courses ORDER BY title");
    $courses = $stmt->fetchAll();
} catch (Exception $e) {
    die("Error fetching courses: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Assignment - Admin</title>
    <style>
        body { font-family: sans-serif; background: #f9f9f9; padding: 20px; }
        .card { background: white; padding: 20px; border-radius: 6px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; }
        h2 { color: #2c3e50; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, textarea, select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        textarea { height: 80px; resize: vertical; }
        .btn { background: #3498db; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; margin-right: 10px; }
        .btn:hover { background: #2980b9; }
        .message { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
<div class="card">
    <h2>Create New Assignment</h2>
    <?php if ($error): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="message success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label>Course:</label>
            <select name="course_id" required>
                <option value="">-- Select Course --</option>
                <?php foreach ($courses as $c): ?>
                    <option value="<?= $c['course_id'] ?>"><?= htmlspecialchars($c['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Lesson ID:</label>
            <input type="number" name="lesson_id" min="1" required placeholder="e.g., 1, 2, 3...">
        </div>

        <div class="form-group">
            <label>Assignment Title:</label>
            <input type="text" name="title" required placeholder="e.g., Python Basics Exercise">
        </div>

        <div class="form-group">
            <label>Description (Optional):</label>
            <textarea name="description" placeholder="Brief instructions..."></textarea>
        </div>

        <div class="form-group">
            <label>Due Date (Optional):</label>
            <input type="date" name="due_date">
        </div>

        <div class="form-group">
            <label>Max Points:</label>
            <input type="number" name="max_points" min="1" max="100" value="100" required>
        </div>

        <div class="form-group">
            <label>Upload Assignment File (PDF/DOC/TXT, ≤50MB):</label>
            <input type="file" name="submission_file" accept=".pdf,.doc,.docx,.txt">
        </div>

        <button type="submit" class="btn">Create Assignment</button>
        <a href="admin_dashboard.php" class="btn" style="background:#95a5a6;">Cancel</a>
    </form>
</div>
</body>
</html>