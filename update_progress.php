<?php
session_start();
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle progress update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_progress'])) {
    $enrollment_id = $_POST['enrollment_id'];
    $progress = min(100, max(0, intval($_POST['progress']))); // Ensure between 0-100
    
    // Determine status based on progress
    if ($progress == 0) {
        $status = 'Not Started';
    } elseif ($progress == 100) {
        $status = 'Completed';
    } else {
        $status = 'In Progress';
    }
    
    try {
        // Verify enrollment belongs to user
        $stmt = $pdo->prepare("SELECT * FROM enrollments WHERE enrollment_id = ? AND user_id = ?");
        $stmt->execute([$enrollment_id, $user_id]);
        $enrollment = $stmt->fetch();
        
        if ($enrollment) {
            // Update progress
            $completed_at = ($progress == 100) ? date('Y-m-d H:i:s') : null;
            $stmt = $pdo->prepare("UPDATE enrollments SET progress = ?, status = ?, completed_at = ? WHERE enrollment_id = ?");
            $stmt->execute([$progress, $status, $completed_at, $enrollment_id]);
            
            $message = "Progress updated successfully!";
        } else {
            $error = "Invalid enrollment.";
        }
    } catch (PDOException $e) {
        $error = "Error updating progress: " . $e->getMessage();
    }
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            e.enrollment_id,
            e.progress,
            e.status,
            c.title as course_title,
            c.course_id
        FROM enrollments e
        JOIN courses c ON e.course_id = c.course_id
        WHERE e.user_id = ?
        ORDER BY e.enrolled_at DESC
    ");
    $stmt->execute([$user_id]);
    $enrollments = $stmt->fetchAll();
} catch (PDOException $e) {
    $enrollments = [];
    $error = "Error fetching courses: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Course Progress - SKILL SWAP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="index.css">
    <style>
        :root {
            --primary-color: rgb(12, 12, 30);
            --secondary-color: #3498db;
            --accent-color: #27ae60;
            --light-bg: #f8f9fa;
            --white: #ffffff;
            --gray-light: #e9ecef;
            --gray-dark: #495057;
            --transition: all 0.3s ease;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            background: var(--light-bg);
            padding-top: 80px;
        }
        /* Navigation */
        .navbar {
            background: rgb(44, 62, 80);
            padding: 1rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--primary-color);
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            transition: var(--transition);
        }
        .logo-placeholder {
            color: white;
            font-weight: bold;
            font-size: 1.3rem;
            letter-spacing: 1px;
            color: rgb(44,44,44,);
            text-decoration: none;
        }
        .nav-links {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }
        .nav-links a {
            text-decoration: none;
            color: var(--white);
            font-weight: 500;
            padding: 0.5rem 0;
            transition: var(--transition);
        }
        .nav-links a:hover,
        .nav-links a.active {
            color: var(--secondary-color);
        }
        .nav-links a.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: var(--secondary-color);
            border-radius: 2px;
        }
        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: var(--white);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-radius: 8px;
            min-width: 150px;
            display: none;
            z-index: 1001;
        }
        .dropdown-menu a {
            display: block;
            padding: 0.75rem 1.5rem;
            color: var(--gray-dark);
            text-decoration: none;
            transition: background-color 0.2s;
        }
        .dropdown-menu a:hover {
            background: var(--gray-light);
        }
        .mobile-toggle {
            display: none;
            flex-direction: column;
            cursor: pointer;
            padding: 0.5rem;
        }
        .mobile-toggle span {
            width: 25px;
            height: 3px;
            background: var(--white);
            margin: 3px 0;
            transition: var(--transition);
        }
        @media (max-width: 768px) {
            .navbar {
                padding: 1rem;
                flex-wrap: wrap;
            }
            .mobile-toggle {
                display: flex;
            }
            .navbar-links {
                display: none;
                width: 100%;
                flex-direction: column;
                gap: 0.5rem;
                margin-top: 1rem;
                background: var(--white);
                border-radius: 8px;
                padding: 1rem;
                box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            }
            .navbar-links.active {
                display: flex;
            }
            .navbar-links a {
                width: 100%;
                text-align: center;
                padding: 0.75rem;
            }
        }
        .container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        .page-title {
            color: var(--primary-color);
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 2rem;
            text-align: center;
        }
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .alert-success {
            background: rgba(39, 174, 96, 0.1);
            color: var(--accent-color);
            border: 1px solid var(--accent-color);
        }
        .alert-error {
            background: rgba(231, 76, 60, 0.1);
            color: var(--error-color);
            border: 1px solid var(--error-color);
        }
        .course-card {
            background: var(--white);
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
        }
        .course-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--gray-light);
        }
        .course-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--primary-color);
        }
        .current-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        .status-completed {
            background: rgba(39, 174, 96, 0.2);
            color: var(--accent-color);
        }
        .status-in-progress {
            background: rgba(52, 152, 219, 0.2);
            color: var(--secondary-color);
        }
        .status-not-started {
            background: rgba(149, 165, 166, 0.2);
            color: var(--gray-dark);
        }
        .progress-form {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }
        .progress-input-group {
            flex: 1;
            min-width: 200px;
        }
        .progress-label {
            display: block;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        .progress-input {
            width: 100%;
            padding: 0.875rem;
            border: 2px solid var(--gray-light);
            border-radius: 8px;
            font-size: 1rem;
        }
        .progress-input:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        .btn-update {
            padding: 0.875rem 2rem;
            background: var(--secondary-color);
            color: var(--white);
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1.7rem;
        }
        .btn-update:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        }
        .progress-bar-visual {
            width: 100%;
            height: 10px;
            background: var(--gray-light);
            border-radius: 5px;
            overflow: hidden;
            margin: 1rem 0;
        }
        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--secondary-color), var(--accent-color));
            transition: width 0.3s ease;
        }
        .back-link {
            display: inline-block;
            margin-top: 2rem;
            color: var(--secondary-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        .back-link:hover {
            color: #2980b9;
        }
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--gray-dark);
        }
        .empty-state i {
            font-size: 4rem;
            opacity: 0.3;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <a href="index.php" class="logo-placeholder">SKILL SWAP</a>
        
        <div class="mobile-toggle" id="mobileToggle">
            <span></span>
            <span></span>
            <span></span>
        </div>
        <div class="navbar-links" id="navbarLinks">
            <a href="index.php">Home</a>    
            <a href="courses.php">Courses</a>
            <a href="about.php">About Us</a>
            <a href="contact.php">Contact</a>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="profile.php">Dashboard</a>
                </div>
            <?php else: ?>
                <a href="login.php" class="btn-login">Login</a>
                <a href="register.php" class="btn-register">Register</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">
        <h1 class="page-title">Update Course Progress</h1>

        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($enrollments)): ?>
            <div class="course-card empty-state">
                <i class="fas fa-graduation-cap"></i>
                <h3>No Enrolled Courses</h3>
                <p>You haven't enrolled in any courses yet.</p>
                <a href="courses.php" style="color: var(--secondary-color); text-decoration: none; font-weight: 600; margin-top: 1rem; display: inline-block;">Browse Courses</a>
            </div>
        <?php else: ?>
            <?php foreach ($enrollments as $enrollment): ?>
                <div class="course-card">
                    <div class="course-header">
                        <h3 class="course-title"><?php echo htmlspecialchars($enrollment['course_title']); ?></h3>
                        <span class="current-status status-<?php echo strtolower(str_replace(' ', '-', $enrollment['status'])); ?>">
                            <?php echo htmlspecialchars($enrollment['status']); ?>
                        </span>
                    </div>

                    <div class="progress-bar-visual">
                        <div class="progress-bar-fill" style="width: <?php echo $enrollment['progress']; ?>%"></div>
                    </div>
                    <p style="color: var(--gray-dark); margin-bottom: 1.5rem;">Current Progress: <strong><?php echo $enrollment['progress']; ?>%</strong></p>

                    <form method="POST" class="progress-form">
                        <input type="hidden" name="enrollment_id" value="<?php echo $enrollment['enrollment_id']; ?>">
                        
                        <div class="progress-input-group">
                            <label class="progress-label">Update Progress (0-100%)</label>
                            <input type="number" 
                                name="progress" 
                                class="progress-input" 
                                min="0" 
                                max="100" 
                                value="<?php echo $enrollment['progress']; ?>"
                                required>
                        </div>
                        
                        <button type="submit" name="update_progress" class="btn-update">
                            <i class="fas fa-save"></i> Update Progress
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <a href="profile.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

</body>
</html>
