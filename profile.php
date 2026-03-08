<?php
// profile.php - Profile for SKILL SWAP
session_start();
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user data from database
$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if ($user) {
        $student_name = $user['name'];
        $email = $user['email'];
        $country = $user['country'] ?? 'Not specified';
        $city = $user['city'] ?? 'Not specified';
        $profile_pic = !empty($user['profile_pic']) ? $user['profile_pic'] : 'images/default-profile.png';
    } else {
        // Fallback if user not found
        $student_name = "User";
        $email = "user@example.com";
        $country = "Not specified";
        $city = "Not specified";
        $profile_pic = "images/default-profile.png";
    }
} catch (PDOException $e) {
    // Fallback on error
    $student_name = "User";
    $email = "user@example.com";
    $country = "Not specified";
    $city = "Not specified";
    $profile_pic = "images/default-profile.png";
}

// Fetch enrolled courses with progress from database
try {
    $stmt = $pdo->prepare("
        SELECT 
            c.course_id,
            c.title,
            e.progress,
            e.status,
            e.enrolled_at,
            e.videos_completed,
            e.quizzes_completed, 
            e.assignments_completed,
            e.total_videos,
            e.total_quizzes,
            e.total_assignments
        FROM enrollments e
        JOIN courses c ON e.course_id = c.course_id
        WHERE e.user_id = ?
        ORDER BY e.enrolled_at DESC
    ");
    $stmt->execute([$user_id]);
    $courses = $stmt->fetchAll();
    
    // If no courses found, show a message
    if (empty($courses)) {
        $courses = [];
    } else {
        // Recalculate progress for each course to ensure accuracy
        foreach ($courses as &$course) {
            // Only recalculate if the function exists
            if (function_exists('calculateCourseProgress')) {
                $course['progress'] = calculateCourseProgress($pdo, $user_id, $course['course_id']);
            }
        }
    }
} catch (PDOException $e) {
    // Fallback to empty array on error
    $courses = [];
    error_log("Error fetching courses: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile - SKILL SWAP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css  ">
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="footer.css">
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

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--secondary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            cursor: pointer;
            position: relative;
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

        .dropdown-menu.show {
            display: block;
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

        .btn-login:hover, .btn-register:hover {
            background: var(--secondary-color);
            color: white;
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
            background: var(--primary-color);
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

        /* Dashboard Container */
        .dashboard-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .dashboard-title {
            color: var(--primary-color);
            font-size: 2rem;
            font-weight: 600;
        }

        /* Main Content Layout */
        .main-content {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
        }

        /* Sidebar */
        .sidebar {
            background: var(--white);
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .profile-section {
            text-align: center;
            margin-bottom: 2rem;
        }

        .profile-pic {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 1rem;
            border: 3px solid var(--secondary-color);
            background: var(--gray-light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray-dark);
            font-size: 2.5rem;
        }

        .profile-name {
            color: var(--primary-color);
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .edit-profile {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--secondary-color);
            text-decoration: none;
            font-size: 0.9rem;
            margin-top: 0.5rem;
            transition: color 0.2s;
        }

        .edit-profile:hover {
            color: var(--accent-color);
        }

        .section-divider {
            height: 1px;
            background: var(--gray-light);
            margin: 2rem 0;
        }

        .personal-info {
            margin-top: 1rem;
        }

        .info-item {
            margin-bottom: 1rem;
        }

        .info-label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.25rem;
            display: block;
        }

        .info-value {
            color: var(--gray-dark);
            font-size: 0.95rem;
        }

        /* Main Content Area */
        .content-area {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .card {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--gray-light);
        }

        .card-title {
            color: var(--primary-color);
            font-size: 1.2rem;
            font-weight: 600;
        }

        .card-icon {
            color: var(--secondary-color);
            font-size: 1.2rem;
        }

        .course-list {
            list-style: none;
        }

        .course-item {
            padding: 1rem 0;
            border-bottom: 1px solid var(--gray-light);
        }

        .course-item:last-child {
            border-bottom: none;
        }

        .course-title {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .course-progress {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .progress-bar {
            flex: 1;
            height: 6px;
            background: var(--gray-light);
            border-radius: 3px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: var(--secondary-color);
            border-radius: 3px;
        }

        .progress-text {
            font-size: 0.85rem;
            color: var(--gray-dark);
        }

        .course-status {
            font-size: 0.85rem;
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-weight: 600;
        }

        .status-in-progress {
            background: rgba(52, 152, 219, 0.2);
            color: var(--secondary-color);
        }

        .status-completed {
            background: rgba(39, 174, 96, 0.2);
            color: var(--accent-color);
        }

        /* Progress details */
        .progress-details {
            font-size: 0.8rem;
            color: var(--gray-dark);
            margin-top: 0.5rem;
            display: flex;
            justify-content: space-between;
        }

        .progress-item {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .progress-icon {
            color: var(--secondary-color);
            font-size: 0.9rem;
        }

        .report-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--gray-light);
        }

        .report-item:last-child {
            border-bottom: none;
        }

        .report-label {
            color: var(--primary-color);
            font-weight: 600;
        }

        .report-value {
            color: var(--gray-dark);
        }

        .miscellaneous-item {
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--gray-light);
        }

        .miscellaneous-item:last-child {
            border-bottom: none;
        }

        .miscellaneous-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }

        .miscellaneous-link:hover {
            color: var(--secondary-color);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .dashboard-container { padding: 0 1rem; }
            .main-content { grid-template-columns: 1fr; }
            .dashboard-title { font-size: 1.5rem; }
            .profile-pic { width: 100px; height: 100px; }
            .profile-name { font-size: 1.2rem; }
            .content-area { grid-template-columns: 1fr; }
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
            <a href="student_inquiries.php">Inquiries</a>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="student_dashboard.php" class="active">Dashboard</a>
                <div class="user-menu">
                    <div class="user-avatar" id="userAvatar">
                        <?php echo substr($student_name, 0, 1); ?>
                    </div>
                    <div class="dropdown-menu" id="dropdownMenu">
                        <a href="student_dashboard.php">My Dashboard</a>
                        <a href="edit_profile.php">Edit Profile</a>
                        <a href="logout.php">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php" class="btn-login">Login</a>
                <a href="register.php" class="btn-register">Register</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Student Dashboard</h1>
        </div>

        <div class="main-content">
            <!-- Sidebar -->
            <div class="sidebar">
                <div class="profile-section">
                    <img src="<?php echo $profile_pic; ?>" alt="Profile Picture" class="profile-pic">
                    <h3 class="profile-name"><?php echo htmlspecialchars($student_name); ?></h3>
                    <a href="edit_profile.php" class="edit-profile">
                        <i class="fas fa-edit"></i> Edit profile
                    </a>
                </div>

                <div class="section-divider"></div>

                <div class="personal-info">
                    <h4>Personal Information</h4>
                    <div class="info-item">
                        <span class="info-label">Email address:</span>
                        <span class="info-value"><?php echo htmlspecialchars($email); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Country:</span>
                        <span class="info-value"><?php echo htmlspecialchars($country); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">City/town:</span>
                        <span class="info-value"><?php echo htmlspecialchars($city); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Student ID:</span>
                        <span class="info-value"><?php echo htmlspecialchars($_SESSION['student_id']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="content-area">
                <!-- Course Details -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Course Progress</h3>
                        <!-- Removed manual update link since it's now automatic -->
                    </div>
                    <?php if (empty($courses)): ?>
                        <div style="padding: 2rem; text-align: center; color: #7f8c8d;">
                            <i class="fas fa-book-open" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                            <p style="font-size: 1.1rem; margin-bottom: 1rem;">No courses enrolled yet</p>
                            <a href="courses.php" style="color: #3498db; text-decoration: none; font-weight: 600;">Browse Courses</a>
                        </div>
                    <?php else: ?>
                        <ul class="course-list">
                            <?php foreach ($courses as $course): ?>
                                <li class="course-item">
                                    <div class="course-title">
                                        <a href="course_video.php?id=<?php echo $course['course_id']; ?>" style="color: var(--primary-color); text-decoration: none;">
                                            <?php echo htmlspecialchars($course['title']); ?>
                                        </a>
                                    </div>
                                    <div class="course-progress">
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $course['progress']; ?>%"></div>
                                        </div>
                                        <span class="progress-text"><?php echo $course['progress']; ?>%</span>
                                    </div>
                                    <span class="course-status <?php echo $course['status'] === 'Completed' ? 'status-completed' : 'status-in-progress'; ?>">
                                        <?php echo $course['status']; ?>
                                    </span>
                                    <div class="progress-details">
                                        <div class="progress-item">
                                            <i class="fas fa-video progress-icon"></i>
                                            <span><?php echo $course['videos_completed']; ?>/<?php echo $course['total_videos']; ?> videos</span>
                                        </div>
                                        <div class="progress-item">
                                            <i class="fas fa-clipboard-list progress-icon"></i>
                                            <span><?php echo $course['quizzes_completed']; ?>/<?php echo $course['total_quizzes']; ?> quizzes</span>
                                        </div>
                                        <div class="progress-item">
                                            <i class="fas fa-file-alt progress-icon"></i>
                                            <span><?php echo $course['assignments_completed']; ?>/<?php echo $course['total_assignments']; ?> assignments</span>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <!-- Reports -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Reports</h3>
                        <i class="fas fa-chart-line card-icon"></i>
                    </div>
                    <div class="report-item">
                        <span class="report-label">Browser sessions</span>
                        <span class="report-value">12</span>
                    </div>
                    <div class="report-item">
                        <span class="report-label">Grades overview</span>
                        <span class="report-value">A, B+, A-</span>
                    </div>
                </div>

                <!-- Miscellaneous -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Miscellaneous</h3>
                        <i class="fas fa-cogs card-icon"></i>
                    </div>
                    <div class="miscellaneous-item">
                        <a href="certificates.php" class="miscellaneous-link">My certificates</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

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

    <!-- JavaScript -->
    <script>
        // Mobile Menu Toggle
        document.getElementById('mobileToggle').addEventListener('click', function() {
            this.classList.toggle('active');
            document.getElementById('navbarLinks').classList.toggle('active');
        });

        // User Menu Toggle
        document.getElementById('userAvatar').addEventListener('click', function() {
            document.getElementById('dropdownMenu').classList.toggle('show');
        });

        // Close dropdown when clicking outside
        window.addEventListener('click', function(e) {
            const userAvatar = document.getElementById('userAvatar');
            const dropdownMenu = document.getElementById('dropdownMenu');
            
            if (!userAvatar.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.classList.remove('show');
            }
        });

        // Smooth Scroll for Anchor Links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>

</body>
</html>