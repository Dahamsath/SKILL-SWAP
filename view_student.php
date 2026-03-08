<?php
// view_student.php - Admin view for a single student's details

error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'config.php';

// --- Authentication Check ---
// Only admins should be able to view detailed student information
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// --- Get Student ID from URL ---
$student_id = $_GET['student_id'] ?? null;
$student = null;
$enrollments = [];
$message = '';
$message_type = '';

if (!$student_id) {
    $message = "Student ID is required.";
    $message_type = 'error';
} else {
    try {
        // --- Fetch Student Details ---
        $stmt = $pdo->prepare("
            SELECT u.user_id, u.name, u.email, u.created_at
            FROM users u
            WHERE u.user_id = ? AND u.role = 'student'
        ");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            $message = "Student not found.";
            $message_type = 'error';
        } else {
            // --- Fetch Student's Enrollments ---
            $stmt = $pdo->prepare("
                SELECT e.enrollment_id, e.enrollment_date, e.mode, c.title AS course_title
                FROM enrollments e
                JOIN courses c ON e.course_id = c.course_id
                WHERE e.user_id = ?
                ORDER BY e.enrollment_date DESC
            ");
            $stmt->execute([$student_id]);
            $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        error_log("Error fetching student details (ID: $student_id): " . $e->getMessage());
        $message = "An error occurred while fetching student details. Please try again later.";
        $message_type = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Student - Skill Pro Institute</title>
    <link rel="stylesheet" href="navigation.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            background: #f5f7fa;
            padding: 2rem;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        h1, h2 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
        }
        .welcome {
            background: #ecf0f1;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: center;
        }
        .message {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            text-align: center;
        }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .student-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .detail-card {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .detail-card h3 {
            margin-top: 0;
            color: #3498db;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
        }
        .detail-card p {
            margin: 0.5rem 0;
        }
        .detail-label {
            font-weight: bold;
            color: #2c3e50;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }
        th {
            background: #3498db;
            color: white;
            font-weight: 600;
        }
        tr:last-child td {
            border-bottom: none;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .no-enrollments {
            text-align: center;
            padding: 2rem;
            color: #7f8c8d;
        }
        .back-link {
            display: inline-block;
            margin-top: 2rem;
            padding: 0.75rem 1.5rem;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        .back-link:hover {
            background: #2980b9;
        }
        @media (max-width: 768px) {
            .student-details {
                grid-template-columns: 1fr;
            }
            .container {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>

        <!-- Navigation Bar -->
<nav class="navbar" id="navbar">
    <div class="logo-placeholder">SKILL PRO INSTITUTE</div>
    
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
        <div class="welcome">
            <h1> Student Profile</h1>
            <p>View detailed information and enrollment history for this student.</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
            <?php if ($message_type === 'error'): ?>
                <a href="admin_dashboard.php" class="back-link">← Back to Dashboard</a>
            <?php endif; ?>
        <?php elseif ($student): ?>
            <div class="student-details">
                <div class="detail-card">
                    <h3> Personal Information</h3>
                    <p><span class="detail-label">Name:</span> <?= htmlspecialchars($student['name']) ?></p>
                    <p><span class="detail-label">Email:</span> <?= htmlspecialchars($student['email']) ?></p>
                    <p><span class="detail-label">Student ID:</span> #<?= htmlspecialchars($student['user_id']) ?></p>
                </div>
                <div class="detail-card">
                    <h3> Registration Details</h3>
                    <p><span class="detail-label">Registered On:</span> <?= date('F j, Y', strtotime($student['created_at'])) ?></p>
                </div>
            </div>

            <h2> Current Enrollments</h2>
            <?php if (empty($enrollments)): ?>
                <div class="no-enrollments">
                    <p>This student is not currently enrolled in any courses.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Course Title</th>
                            <th>Mode</th>
                            <th>Enrolled On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($enrollments as $enrollment): ?>
                        <tr>
                            <td><?= htmlspecialchars($enrollment['course_title']) ?></td>
                            <td>
                                <?php if ($enrollment['mode'] === 'online'): ?>
                                    <span style="color:#27ae60;"> Online</span>
                                <?php else: ?>
                                    <span style="color:#3498db;"> On-site</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('M j, Y', strtotime($enrollment['enrollment_date'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            <a href="admin_dashboard.php" class="back-link">← Back to Dashboard</a>
        <?php endif; ?>
    </div>
</body>
</html>