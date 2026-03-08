<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'student'");
    $total_students = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM courses");
    $total_courses = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM inquiries WHERE status = 'Pending'");
    $pending_inquiries = $stmt->fetch()['total'];
    
} catch (Exception $e) {
    die("<div style='color:red; padding:2rem; background:#fee; border:1px solid #fdd; margin:2rem; border-radius:8px;'>
        <h3>Database Error</h3>
        <p>" . htmlspecialchars($e->getMessage()) . "</p>
        <p>Check your database tables: users, courses, inquiries</p>
    </div>");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SKILL SWAP</title>
    <link rel="stylesheet" href="navigation.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: #f5f7fa; }
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 2rem;}
        .welcome { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 2rem; padding-top: 4rem; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: #3498db; color: white; padding: 1.5rem; border-radius: 8px; text-align: center; }
        .stat-card h3 { font-size: 2rem; margin: 0.5rem 0; }
        .quick-actions { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .action-btn { background: #27ae60; color: white; padding: 1rem; border: none; border-radius: 8px; cursor: pointer; text-align: center; text-decoration: none; font-weight: bold; }
        .action-btn:hover { background: #229954; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #ecf0f1; }
        th { background: #3498db; color: white; }
        tr:last-child td { border-bottom: none; }
        .btn2 { background: #2182e4ff; color: white; padding: 0.5rem ; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; }
        .btn { background: #e74c3c; color: white; padding: 0.5rem 1rem; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin: 0.2rem; }
        .btn1 { background: #ef1212ff; color: white; padding: 0.2rem 0.297rem; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; }
        .btn-edit { background: #3498db; } /* Blue for Edit */
        .btn2 { background: #2182e4ff; color: white; padding: 0.5rem ; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; }
        .btn-view { background: #9b59b6; } 
        .btn-delete { background: #e74c3c; } 
        .btn:hover { opacity: 0.9; }
        .action-cell { white-space: nowrap; }
        .content-management {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .content-section {
            margin-bottom: 2rem;
        }
        .content-section h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #3498db;
        }
        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        .content-card {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .content-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        .content-card i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #3498db;
        }
        .content-card h4 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        .content-card p {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        .content-btn {
            background: #3498db;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            margin-top: 1rem;
            display: inline-block;
        }
        .content-btn:hover {
            background: #2980b9;
        }
        .video-icon { color: #e74c3c; }
        .quiz-icon { color: #f39c12; }
        .assignment-icon { color: #27ae60; }
    </style>
    <script src="https://kit.fontawesome.com/a076d05399.js  " crossorigin="anonymous"></script>
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
            <a href="index.php">Home</a>
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
        <div class="welcome">
            <h2>Admin Dashboard</h2>
            <p>Manage courses and students for SKILL SWAP.</p>
        </div>

        <div class="stats">
            <div class="stat-card">
                <h3><?= $total_students ?></h3>
                <p>Total Students</p>
            </div>
            <div class="stat-card">
                <h3><?= $total_courses ?></h3>
                <p>Total Courses</p>
            </div>
            <div class="stat-card">
                <h3><?= $pending_inquiries ?></h3>
                <p>Pending Inquiries</p>
            </div>
        </div>
        
        <!-- Content Management Section -->
        <div class="content-management">
            <h2>Content Management</h2>
            <p>Upload and manage course materials including videos, quizzes, and assignments.</p>
            
            <div class="content-section">
                <h3><i class="fas fa-video video-icon"></i> Video Management</h3>
                <div class="content-grid">
                    <div class="content-card">
                        <i class="fas fa-upload"></i>
                        <h4>Upload Videos</h4>
                        <p>Add new video lectures for courses</p>
                        <a href="admin_upload_video.php" class="content-btn">Upload Video</a>
                    </div>
                    <div class="content-card">
                        <i class="fas fa-list"></i>
                        <h4>Manage Videos</h4>
                        <p>Edit or delete existing videos</p>
                        <a href="admin_manage_videos.php" class="content-btn">Manage Videos</a>
                    </div>
                </div>
            </div>
            
            <div class="content-section">
                <h3><i class="fas fa-clipboard-list quiz-icon"></i> Quiz Management</h3>
                <div class="content-grid">
                    <div class="content-card">
                        <i class="fas fa-plus-circle"></i>
                        <h4>Create Quizzes</h4>
                        <p>Add new quizzes for lessons</p>
                        <a href="admin_add_quiz.php" class="content-btn">Create Quiz</a>
                    </div>
                    <div class="content-card">
                        <i class="fas fa-edit"></i>
                        <h4>Manage Quizzes</h4>
                        <p>Edit or delete existing quizzes</p>
                        <a href="admin_manage_quizzes.php" class="content-btn">Manage Quizzes</a>
                    </div>
                </div>
            </div>
            
            <div class="content-section">
                <h3><i class="fas fa-file-alt assignment-icon"></i> Assignment Management</h3>
                <div class="content-grid">
                    <div class="content-card">
                        <i class="fas fa-plus"></i>
                        <h4>Create Assignments</h4>
                        <p>Add new assignments for lessons</p>
                        <a href="admin_add_assignment.php" class="content-btn">Create Assignment</a>
                    </div>
                    <div class="content-card">
                        <i class="fas fa-tasks"></i>
                        <h4>Manage Assignments</h4>
                        <p>View and grade student submissions</p>
                        <a href="admin_manage_assignments.php" class="content-btn">Manage Assignments</a>
                    </div>
                </div>
            </div>
        </div>

        <h3>Quick Actions</h3>
        <br>
        <div class="quick-actions">
            <a href="admin_add_course.php" class="action-btn">Add New Course</a>
            <a href="admin_add_instructor.php" class="action-btn">Add New Instructor</a>
            <a href="admin_view_inquiries.php" class="action-btn">View Inquiries</a>
            <a href="admin_upload_video.php" class="action-btn">Upload Course Video</a>
            <a href="admin_upload_certificate.php" class="action-btn">Upload Certificates</a>
        </div>

        <h3>Recent Courses</h3>
        <br>
        <?php
        try {
            $stmt = $pdo->query("SELECT c.course_id, c.title, c.category, c.fees
                                FROM courses c
                                ORDER BY c.course_id DESC LIMIT 5");
            $courses = $stmt->fetchAll();
        } catch (Exception $e) {
            echo "<p style='color:red;'>Error loading courses: " . $e->getMessage() . "</p>";
            $courses = [];
        }
        ?>
        <table>
            <tr>
                <th>Course Title</th>
                <th>Category</th>
                <th>Fees</th>
                <th>Actions</th>
            </tr>
            <?php if (empty($courses)): ?>
                <tr>
                    <td colspan="5" style="text-align: center;">No courses found.</td>
                </tr>
            <?php else: ?>
            <?php foreach ($courses as $course): ?>
            <tr>
                <td><?= htmlspecialchars($course['title']) ?></td>
                <td><?= htmlspecialchars($course['category'] ?? 'No Category') ?></td>
                <td>Rs. <?= number_format($course['fees']) ?></td>
                <td class="action-cell">
                    <!-- Pass course_id to edit and delete pages -->
                    <a href="edit_course.php?course_id=<?= urlencode($course['course_id']) ?>" class="btn btn-edit">Edit</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </table>

        <h3>Recent Students</h3>
        <br>
        <?php
        try {
            $stmt = $pdo->query("SELECT user_id, name, email, student_id, created_at FROM users WHERE role = 'student' ORDER BY user_id DESC LIMIT 5");
            $students = $stmt->fetchAll();
        } catch (Exception $e) {
            echo "<p style='color:red;'>Error loading students: " . $e->getMessage() . "</p>";
            $students = [];
        }
        ?>
        <table>
            <tr>
                <th>Student ID</th>
                <th>Student Name</th>
                <th>Email</th>
                <th>Registered On</th>
                <th>Actions</th>
            </tr>
            <?php if (empty($students)): ?>
                <tr>
                    <td colspan="5" style="text-align: center;">No students found.</td>
                </tr>
            <?php else: ?>
            <?php foreach ($students as $student): ?>
            <tr>
                <td><?= htmlspecialchars($student['student_id']) ?></td>
                <td><?= htmlspecialchars($student['name']) ?></td>
                <td><?= htmlspecialchars($student['email']) ?></td>
                <td><?= htmlspecialchars($student['created_at']) ?></td>
                <td class="action-cell">
                    <!-- Pass user_id to view/edit and delete pages -->
                    <a href="view_student.php?student_id=<?= urlencode($student['user_id']) ?>" class="btn btn-view">View</a>
                    <a href="delete_student.php?student_id=<?= urlencode($student['user_id']) ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this student? This action cannot be undone.')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </table>
    </div>
</body>
</html>