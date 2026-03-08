<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}
include 'config.php';

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT e.*, c.title, c.fees 
    FROM enrollments e 
    JOIN courses c ON e.course_id = c.course_id 
    WHERE e.user_id = ?
");
$stmt->execute([$user_id]);
$enrollments = $stmt->fetchAll();

// Recalculate progress for each course
if (!empty($enrollments)) {
    foreach ($enrollments as &$course) {
        if (function_exists('calculateCourseProgress')) {
            $course['progress'] = calculateCourseProgress($pdo, $user_id, $course['course_id']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - SKILL SWAP</title>
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

        .welcome {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .welcome h1 {
            font-size: 28px;
            margin-bottom: 30px;
            color: #333;
        }

        .welcome h2 {
            font-size: 18px;
            margin-bottom: 15px;
            color: #333;
        }

        .welcome p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .course-card {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 30px;
        }

        .course-image {
            flex: 0 0 300px;
        }

        .course-image img {
            width: 100%;
            height: auto;
        }

        .badge {
            display: inline-block;
            background-color: #c44;
            color: white;
            padding: 5px 15px;
            border-radius: 3px;
            font-size: 11px;
            margin-top: 10px;
            text-transform: uppercase;
        }

        .timeline {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .timeline h3 {
            font-size: 18px;
            margin-bottom: 15px;
        }

        .timeline-controls {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .timeline-controls select {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: white;
        }

        .search-box {
            flex: 1;
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .no-activities {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .recent-courses {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .recent-courses h3 {
            font-size: 18px;
            margin-bottom: 20px;
        }

        .course-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .course-item {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .course-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .course-thumbnail {
            width: 100%;
            height: 150px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }

        .course-info {
            padding: 15px;
        }

        .course-tag {
            display: inline-block;
            background-color: #c44;
            color: white;
            padding: 3px 10px;
            border-radius: 3px;
            font-size: 10px;
            margin-bottom: 10px;
        }

        .course-title {
            font-size: 14px;
            color: #c44;
            margin-top: 10px;
        }
        .course-info {
            margin-bottom: 30px;
        }

        .course-info p {
            font-size: 14px;
            color: #333;
            margin-bottom: 15px;
        }

        .course-placeholder {
            width: 100%;
            height: 400px;
            background-color: #e8e8e8;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
            overflow: hidden;
        }

        .course-placeholder img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .placeholder-icon {
            width: 120px;
            height: 80px;
            background-color: #d0d0d0;
            position: relative;
            border-radius: 4px;
        }

        .placeholder-icon::before {
            content: '';
            position: absolute;
            width: 30px;
            height: 30px;
            background-color: #c0c0c0;
            border-radius: 50%;
            top: -15px;
            right: 20px;
        }
        .image-placeholder-small {
            width: 80px;
            height: 60px;
            background-color: #d0d0d0;
            position: relative;
            border-radius: 3px;
        }

        .image-placeholder-small::before {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            background-color: #c0c0c0;
            border-radius: 50%;
            top: -10px;
            right: 15px;
        }

        .icon {
            width: 35px;
            height: 35px;
            background-color: #c44;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .app-buttons {
            display: flex;
            gap: 10px;
        }

        .app-btn {
            background-color: #444;
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 12px;
            cursor: pointer;
        }
        
        .course-progress {
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .progress-bar {
            flex: 1;
            height: 6px;
            background-color: #e0e0e0;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background-color: #c44;
            border-radius: 3px;
        }
        
        .progress-text {
            font-size: 12px;
            color: #666;
            min-width: 40px;
        }
        
        .last-accessed {
            font-size: 11px;
            color: #888;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="logo-placeholder">SKILL SWAP</div>
        <div class="navbar-links">
            <span>Hey, <?= htmlspecialchars($_SESSION['name'] ?? 'User') ?>!</span>
            <a href="index.php" class="active">Home</a>
            <a href="courses.php" >Courses</a>
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
        <div class="welcome-section">
            <h1>Hi, <?= htmlspecialchars($_SESSION['name'] ?? 'Student') ?>!</h1>
            <p><strong>Student ID:</strong> <?= htmlspecialchars($_SESSION['student_id']) ?></p>
            <p>Welcome to the SKILL SWAP Online Courses hosted platform.</p>

            <div class="course-info">
                <p>Our <strong>"Web Design For Beginners"</strong> programme is now available after completing the "Getting Started" course.</p>
                <div class="course-placeholder">
                    <!-- Replace the src with your image URL -->
                    <img src="images/web design.jpg" alt="Full Stack Developer Course">
                    <!-- If no image, it will show the gray placeholder -->
                </div>
            </div>

            <div class="course-info">
                <p>Our <strong>"Python"</strong> programme is now available after completing the "Registration for Python" course.</p>
                <div class="course-placeholder">
                    <!-- Replace the src with your image URL -->
                    <img src="images/python.jpg" alt="Python Course">
                    <!-- If no image, it will show the gray placeholder -->
                </div>
            </div>
        </div>
        <div class="recent-courses">
            <h3>Recently accessed courses</h3>
            <div class="course-grid">
                <?php if (!empty($enrollments)): ?>
                    <?php foreach ($enrollments as $course): ?>
                        <div class="course-item">
                            <div class="course-thumbnail">
                                <!-- Using a simple text icon instead of Font Awesome -->
                                📚
                            </div>
                            <div class="course-info">
                                <a href="course_video.php?id=<?php echo $course['course_id']; ?>" class="course-link" style="text-decoration: none;">
                                    <span class="course-tag"><?= htmlspecialchars($course['title']) ?></span>
                                </a>
                                <div class="course-progress">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?= (int)$course['progress'] ?>%"></div>
                                    </div>
                                    <span class="progress-text"><?= (int)$course['progress'] ?>%</span>
                                </div>
                                <?php if (!empty($course['last_accessed'])): ?>
                                <div class="last-accessed">
                                    Last accessed: <?= date('M j, Y', strtotime($course['last_accessed'])) ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 20px; color: #888;">
                        You haven't enrolled in any courses yet.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
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
    <script src="https://kit.fontawesome.com/a076d05399.js  " crossorigin="anonymous"></script>
</body>
</html>