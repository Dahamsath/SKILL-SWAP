<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'config.php';

// Redirect if not logged in or not admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$course_id = $_GET['course_id'] ?? null;
$message = '';
$message_type = '';

if (!$course_id) {
    $message = "Course ID is required.";
    $message_type = 'error';
} else {
    // Fetch course data
    try {
        $stmt = $pdo->prepare("SELECT * FROM courses WHERE course_id = ?");
        $stmt->execute([$course_id]);
        $course = $stmt->fetch();

        if (!$course) {
            $message = "Course not found.";
            $message_type = 'error';
        }
    } catch (Exception $e) {
        $message = "Error fetching course data: " . $e->getMessage();
        $message_type = 'error';
    }
}

// Handle form submission
if ($_POST && isset($_POST['update_course'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $duration = trim($_POST['duration']);
    $branch_id = $_POST['branch_id'];
    $instructor_id = $_POST['instructor_id'];
    $fees = $_POST['fees'];

    if (empty($title) || empty($category) || empty($duration) || empty($branch_id) || empty($instructor_id) || empty($fees)) {
        $message = "All fields are required.";
        $message_type = 'error';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE courses SET title = ?, description = ?, category = ?, duration = ?, branch_id = ?, instructor_id = ?, fees = ? WHERE course_id = ?");
            if ($stmt->execute([$title, $description, $category, $duration, $branch_id, $instructor_id, $fees, $course_id])) {
                $message = "Course updated successfully!";
                $message_type = 'success';
            } else {
                $message = "Error updating course.";
                $message_type = 'error';
            }
        } catch (Exception $e) {
            $message = "Database error: " . $e->getMessage();
            $message_type = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Course - SKILL SWAP</title>
    <link rel="stylesheet" href="navigation.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: #f5f7fa; }
        .container { max-width: 600px; margin: 2rem auto; padding: 0 2rem; }
        .form-container { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #2c3e50; margin-bottom: 1.5rem; text-align: center; }
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: bold; color: #2c3e50; }
        input, select, textarea { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem; }
        .btn { background: #27ae60; color: white; padding: 0.75rem; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; font-weight: 600; width: 100%; margin-top: 1rem; }
        .btn:hover { background: #229954; }
        .message { padding: 1rem; margin-bottom: 1.5rem; border-radius: 4px; text-align: center; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .back-link { display: block; text-align: center; margin-top: 1rem; color: #3498db; text-decoration: none; }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
<nav class="navbar" id="navbar">
    <div class="logo-placeholder">SKILL SWAP</div>
    
    <div class="mobile-toggle" id="mobileToggle">
        <span></span>
        <span></span>
        <span></span>
    </div>

    <div class="navbar-links" id="navbarLinks">
        <span class="user-greeting">Hey, <?= htmlspecialchars($_SESSION['name'] ?? 'User') ?>!</span>
        <a href="admin_dashboard.php">Back</a>
        <a href="logout.php" class="btn-logout">Logout</a>
        
    </div>
</nav>
<!-- JavaScript for Navigation -->
<script>
// Mobile menu toggle
const mobileToggle = document.getElementById('mobileToggle');
const navbarLinks = document.getElementById('navbarLinks');

mobileToggle.addEventListener('click', function() {
    mobileToggle.classList.toggle('active');
    navbarLinks.classList.toggle('active');
});

// Navbar scroll effect
window.addEventListener('scroll', function() {
    const navbar = document.getElementById('navbar');
    if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
});

// Close mobile menu when clicking on links
document.querySelectorAll('.navbar-links a').forEach(link => {
    link.addEventListener('click', function() {
        mobileToggle.classList.remove('active');
        navbarLinks.classList.remove('active');
    });
});
</script>   
    </div>

    <div class="container">
        <div class="form-container">
            <h2 style=padding-top:0.8rem>Edit Course</h2>
            
            <?php if ($message): ?>
                <div class="message <?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Course Title *</label>
                    <input type="text" name="title" value="<?= htmlspecialchars($course['title'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3"><?= htmlspecialchars($course['description'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label>Category *</label>
                    <input type="text" name="category" value="<?= htmlspecialchars($course['category'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Instructor *</label>
                    <select name="instructor_id" required>
                        <option value="">-- Select Instructor --</option>
                        <?php
                        $instructors = $pdo->query("SELECT user_id, name FROM users WHERE role = 'instructor'")->fetchAll();
                        foreach ($instructors as $instructor):
                            $selected = ($instructor['user_id'] == $course['instructor_id']) ? 'selected' : '';
                            echo "<option value=\"{$instructor['user_id']}\" {$selected}>{$instructor['name']}</option>";
                        endforeach;
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Fees (Rs.) *</label>
                    <input type="number" name="fees" value="<?= htmlspecialchars($course['fees'] ?? '') ?>" min="0" step="100" required>
                </div>
                <button type="submit" name="update_course" class="btn">Update Course</button>
            </form>
            
            <a href="admin_dashboard.php" class="back-link">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>