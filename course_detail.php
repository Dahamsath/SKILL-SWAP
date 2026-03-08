<?php
// course_detail.php - Display detailed information for a single course

error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'config.php';

// --- Fetch Course Details ---
$course_id = $_GET['course_id'] ?? 0;
if (!$course_id) {
    http_response_code(400); // Bad Request
    die("<div style='color:red; padding:2rem; background:#fee; border:1px solid #fdd; margin:2rem; border-radius:8px; text-align:center;'>
        <h2>Bad Request</h2>
        <p>Course ID is required.</p>
        <a href='courses.php' style='color:#3498db; text-decoration:none; font-weight:bold;'>← Back to All Courses</a>
    </div>");
}

// Prepare statement to get course details along with instructor name
try {
    $stmt = $pdo->prepare("
        SELECT c.*, u.name AS instructor_name
        FROM courses c
        LEFT JOIN users u ON c.instructor_id = u.user_id
        WHERE c.course_id = ?
    ");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch();

    if (!$course) {
        // Handle case where course ID is invalid
        http_response_code(404); // Set HTTP status code to 404 Not Found
        die("<div style='color:red; padding:2rem; background:#fee; border:1px solid #fdd; margin:2rem; border-radius:8px; text-align:center;'>
            <h2>Course Not Found</h2>
            <p>The course you are looking for does not exist or has been removed.</p>
            <a href='courses.php' style='color:#3498db; text-decoration:none; font-weight:bold;'>← Back to All Courses</a>
        </div>");
    }
} catch (Exception $e) {
    error_log("Error fetching course details (ID: $course_id): " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    die("<div style='color:red; padding:2rem; background:#fee; border:1px solid #fdd; margin:2rem; border-radius:8px; text-align:center;'>
        <h2>Database Error</h2>
        <p>An error occurred while fetching course details. Please try again later.</p>
        <a href='courses.php' style='color:#3498db; text-decoration:none; font-weight:bold;'>← Back to All Courses</a>
    </div>");
}
// --- End Fetch Course Details ---
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($course['title']) ?> - SKILL SWAP</title>
    <!-- Include Font Awesome for icons -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="navigation.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family:  'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        :root {
    --primary-color: #2c3e50;
    --light-bg: #f5f7fa;
    --transition: all 0.3s ease;    
}
        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 2rem;
            padding-top: 5rem;
        }
        .course-header {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 2.5rem;
            border-radius: 12px 12px 0 0;
            text-align: center;
        }
        .course-header h1 {
            margin: 0;
            font-size: 2.2rem;
        }
        .course-body {
            background: white;
            padding: 2.5rem;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .course-info {
            margin-bottom: 2rem;
        }
        .course-info p {
            margin: 1rem 0;
            font-size: 1.05rem;
        }
        .course-info strong {
            color: #2c3e50;
        }
        .btn-primary {
            background: #e74c3c;
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 1.1rem;
            font-weight: 600;
            display: inline-block;
            transition: background-color 0.3s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-top: 1rem;
        }
        .btn-primary:hover {
            background: #c0392b;
        }
        .back-link {
            display: block;
            margin-top: 1.5rem;
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        .back-link:hover {
            color: #2980b9;
            text-decoration: underline;
        }
        .description-content {
            white-space: pre-line; /* Preserves line breaks from textarea */
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo-placeholder">SKILL SWAP</div>
        <div class="navbar-links">
            <a href="index.php">Home</a>
            <a href="courses.php">Courses</a>
            <a href="about.php">About Us</a>
            <a href="contact.php">Contact</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($_SESSION['role'] === 'student'): ?>
                    <a href="student_dashboard.php">Dashboard</a>
                <?php elseif ($_SESSION['role'] === 'admin'): ?>
                    <a href="admin_dashboard.php">Dashboard</a>
                <?php endif; ?>
                <a href="logout.php" class="btn-logout">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">
        <div class="course-header">
            <h1><?= htmlspecialchars($course['title']) ?></h1>
        </div>
        <div class="course-body">
            <div class="course-info">
                <p><strong>Category:</strong> <?= htmlspecialchars($course['category']) ?></p>
                <p><strong>Instructor:</strong> <?= htmlspecialchars($course['instructor_name'] ?? 'Not Assigned') ?></p>
                <p><strong>Fees:</strong> Rs. <?= number_format($course['fees']) ?></p>
                <p><strong>Description:</strong></p>
                <div class="description-content">
                    <?= nl2br(htmlspecialchars($course['description'])) ?>
                </div>
            </div>
            <!-- Check if user is logged in and is a student before showing enroll button -->
            <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'student'): ?>
                <a href="enroll.php?course_id=<?= urlencode($course['course_id']) ?>" class="btn-primary">Enroll Now</a>
            <?php elseif (!isset($_SESSION['user_id'])): ?>
                <p style="color: #7f8c8d; margin-top: 1rem;"><em>Please <a href="login.php" style="color: #3498db;">login</a> as a student to enroll in this course.</em></p>
            <?php endif; ?>
            <a href="courses.php" class="back-link">← Back to All Courses</a>
        </div>
    </div>

</body>
</html>