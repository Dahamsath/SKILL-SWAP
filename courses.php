<?php
// courses.php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'config.php';

$message = '';
$message_type = '';

// Function to check if user has active trial
function hasActiveTrial($pdo, $user_id) {
    if (!$user_id) return false;
    
    $stmt = $pdo->prepare("SELECT trial_status, trial_end FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if ($user && $user['trial_status'] === 'active' && $user['trial_end']) {
        return strtotime($user['trial_end']) > time();
    }
    return false;
}

// --- FETCH COURSES LOGIC ---
try {
    // 1. Get the search term from the URL (if any)
    $search_term = trim($_GET['search'] ?? '');

    // First, check if the courses table has an instructor_id column
    $has_instructor = false;
    try {
        $check_stmt = $pdo->query("DESCRIBE courses");
        $columns = $check_stmt->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('instructor_id', $columns)) {
            $has_instructor = true;
        }
    } catch (Exception $e) {
        // If we can't check columns, assume no instructor
        $has_instructor = false;
    }

    $has_users_table = false;
    if ($has_instructor) {
        try {
            $pdo->query("SELECT 1 FROM users LIMIT 1");
            $has_users_table = true;
        } catch (Exception $e) {
            $has_users_table = false;
        }
    }

    if (!empty($search_term)) {
        // --- SEARCH MODE ---
        if ($has_instructor && $has_users_table) {
            $stmt = $pdo->prepare("
                SELECT c.*, u.name AS instructor_name
                FROM courses c
                LEFT JOIN users u ON c.instructor_id = u.user_id
                WHERE c.title LIKE ? OR c.description LIKE ? OR c.category LIKE ?
                ORDER BY c.title
            ");
            $search_param = '%' . $search_term . '%';
            $stmt->execute([$search_param, $search_param, $search_param]);
        } else {
            $stmt = $pdo->prepare("
                SELECT c.*
                FROM courses c
                WHERE c.title LIKE ? OR c.description LIKE ? OR c.category LIKE ?
                ORDER BY c.title
            ");
            $search_param = '%' . $search_term . '%';
            $stmt->execute([$search_param, $search_param, $search_param]);
        }
    } else {
        // --- DEFAULT MODE (SHOW ALL) ---
        if ($has_instructor && $has_users_table) {
            $stmt = $pdo->query("
                SELECT c.*, u.name AS instructor_name 
                FROM courses c 
                LEFT JOIN users u ON c.instructor_id = u.user_id 
                ORDER BY c.title
            ");
        } else {
            $stmt = $pdo->query("SELECT c.* FROM courses c ORDER BY c.title");
        }
    }

    // 4. Fetch all matching courses
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($courses)) {
        if (!empty($search_term)) {
            $message = "No courses found matching your search for: \"" . htmlspecialchars($search_term) . "\"";
        } else {
            $message = "No courses are currently available.";
        }
        $message_type = 'info';
    }

} catch (Exception $e) {
    error_log("Error fetching courses: " . $e->getMessage());
    $message = "An error occurred while fetching courses. Please try again later.";
    $message_type = 'error';
    $courses = [];
}
// --- END FETCH COURSES LOGIC ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courses - SKILL SWAP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="index.css">  
    <link rel="stylesheet" href="footer.css">  
    <style>
        .user-greeting {
                width: 100%;
                text-align: center;
                margin-bottom: 0.5rem;
            }
        .filters {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
        }
        .filters label {
            font-weight: bold;
            color: #2c3e50;
        }
        .filters select {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .course-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .course-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .course-header {
            background: #3498db;
            color: white;
            padding: 1rem;
            text-align: center;
        }
        .course-body {
            padding: 1.5rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .course-body h3 {
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }
        .course-meta {
            display: flex;
            justify-content: space-between;
            color: #7f8c8d;
            font-size: 0.9rem;
            margin: 0.5rem 0;
        }
        .course-description {
            color: #555;
            margin: 1rem 0;
            flex-grow: 1;
        }
        .course-footer {
            padding: 1rem;
            background: #ecf0f1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn {
            background: #27ae60;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background: #229954;
        }
        .btn-logout {
    background: #e74c3c !important;
    color: white !important;
    border: 2px solid #e74c3c;
    font-weight: bold;
}

.btn-logout:hover {
    background: #c0392b !important;
    border-color: #c0392b;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(231, 76, 60, 0.3);
}
        .message {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            text-align: center;
        }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .no-courses {
            text-align: center;
            padding: 3rem;
            color: #7f8c8d;
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        } 
        /* Course Reviews Styles */
.course-reviews {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.rating-section {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.stars {
    color: #ddd;
    font-size: 18px;
}

.star.filled {
    color: #f39c12;
}

.rating-text {
    color: #7f8c8d;
    font-size: 14px;
}

.btn-review {
    background: #9b59b6;
    color: white;
    padding: 6px 12px;
    font-size: 12px;
    border-radius: 4px;
    text-decoration: none;
    display: inline-block;
}

.btn-review:hover {
    background: #8e44ad;
}

.btn-review.disabled {
    background: #95a5a6;
    cursor: not-allowed;
}

.user-review {
    background: #f8f9fa;
    padding: 8px;
    border-radius: 4px;
    font-size: 12px;
}

/* Review Form Styles */
.review-form {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.review-form h3 {
    margin-bottom: 15px;
    color: #333;
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
}

.submit-review-btn {
    background: #9b59b6;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
}

/* Reviews List */
.reviews-list {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
}
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
        <h2> Available Courses</h2>
        <style>
            .container{
                text-align: center;
                padding-top: 1rem;
            }
        </style>
        
        <!-- Course Search Bar -->
        <div class="search-bar-container">
            <form action="courses.php" method="GET" class="course-search-form">
                <input
                    type="text"
                    name="search"
                    class="course-search-input"
                    placeholder="Search for courses"
                    aria-label="Search courses"
                    value="<?= htmlspecialchars($search_term) ?>"  
                >
                <button type="submit" class="course-search-button">
                    <i class="fas fa-search"></i> Search
                </button>
            </form>
        </div>
        <!-- End Course Search Bar -->


        <?php if ($message): ?>
            <div class="message <?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
        <?php elseif (empty($courses)): ?>
            <div class="no-courses">
                <p>No courses found matching your criteria.</p>
            </div>
        <?php else: ?>
            <div class="course-grid">
                <?php foreach ($courses as $course): ?>
                <div class="course-card">
                    <div class="course-header">
                        <h3><?= htmlspecialchars($course['title']) ?></h3>
                    </div>
                    <div class="course-body">
                        <p class="course-meta">
                            <span><i class="fas fa-tag"></i> <?= htmlspecialchars($course['category'] ?? 'General') ?></span>
                            <span><i class="fas fa-chalkboard-teacher"></i> 
                                <?php 
                                // Display instructor name if available, otherwise show "Not Assigned"
                                if (isset($course['instructor_name']) && !empty($course['instructor_name'])) {
                                    echo htmlspecialchars($course['instructor_name']);
                                } else {
                                    echo "Not Assigned";
                                }
                                ?>
                            </span>
                        </p>
                        <p class="course-description"><?= nl2br(htmlspecialchars(substr($course['description'] ?? '', 0, 150))) ?>...</p>
                    </div>
                    <!-- Add this inside each course card, after the course description -->
<div class="course-reviews">
    <?php
    // Calculate average rating for this course
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM course_reviews WHERE course_id = ?");
    $stmt->execute([$course['course_id']]);
    $review_stats = $stmt->fetch();
    
    $avg_rating = $review_stats['avg_rating'] ? round($review_stats['avg_rating'], 1) : 0;
    $total_reviews = $review_stats['total_reviews'];
    ?>
    
    <div class="rating-section">
        <div class="stars">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <span class="star <?= $i <= $avg_rating ? 'filled' : '' ?>">★</span>
            <?php endfor; ?>
        </div>
        <span class="rating-text"><?= $avg_rating ?>/5 (<?= $total_reviews ?> reviews)</span>
    </div>
    
    <?php if (isset($_SESSION['user_id'])): ?>
        <!-- Check if user has already reviewed this course -->
        <?php
        $stmt = $pdo->prepare("SELECT * FROM course_reviews WHERE user_id = ? AND course_id = ?");
        $stmt->execute([$_SESSION['user_id'], $course['course_id']]);
        $user_review = $stmt->fetch();
        ?>
        
        <?php if (!$user_review): ?>
            <!-- Check if user is enrolled in this course -->
            <?php
            $stmt_check = $pdo->prepare("SELECT * FROM enrollments WHERE user_id = ? AND course_id = ?");
            $stmt_check->execute([$_SESSION['user_id'], $course['course_id']]);
            $is_enrolled = $stmt_check->fetch();
            ?>
            
            <?php if ($is_enrolled): ?>
                <a href="course_video.php?id=<?= $course['course_id'] ?>&lesson=1&content=reviews" class="btn btn-review">Write a Review</a>
            <?php else: ?>
                <span class="btn btn-review disabled" title="Enroll in course first">Write a Review</span>
            <?php endif; ?>
        <?php else: ?>
            <!-- Show user's review -->
            <div class="user-review">
                <p>Your rating: 
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="star <?= $i <= $user_review['rating'] ? 'filled' : '' ?>">★</span>
                    <?php endfor; ?>
                </p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
                    <!-- Single price display with 7-day trial support -->
                    <?php
                    // Check if user has active trial or annual subscription
                    $has_active_trial = isset($_SESSION['user_id']) ? hasActiveTrial($pdo, $_SESSION['user_id']) : false;
                    
                    // Check if user has active annual subscription
                    $has_annual_subscription = false;
                    if (isset($_SESSION['user_id'])) {
                        $stmt = $pdo->prepare("SELECT subscription_status, plan_type, subscription_end FROM users WHERE user_id = ?");
                        $stmt->execute([$_SESSION['user_id']]);
                        $user = $stmt->fetch();
                        
                        if ($user && 
                            $user['subscription_status'] === 'active' && 
                            $user['plan_type'] === 'annual' && 
                            $user['subscription_end'] && 
                            strtotime($user['subscription_end']) > time()) {
                            $has_annual_subscription = true;
                        }
                    }

                    // Auto-enroll trial users in all courses
                    if ($has_active_trial || $has_annual_subscription) {
                        $course_price = 0;
                        $price_text = "FREE";
                        $button_text = "Continue Course";
                        
                        // Auto-enroll if not already enrolled
                        if (isset($_SESSION['user_id'])) {
                            $stmt_check = $pdo->prepare("SELECT * FROM enrollments WHERE user_id = ? AND course_id = ?");
                            $stmt_check->execute([$_SESSION['user_id'], $course['course_id']]);
                            $is_enrolled = $stmt_check->fetch();
                            
                            if (!$is_enrolled) {
                                // Auto-enroll the user
                                $stmt_enroll = $pdo->prepare("INSERT INTO enrollments (user_id, course_id, mode, progress, last_accessed) VALUES (?, ?, 'online', 0, NOW())");
                                $stmt_enroll->execute([$_SESSION['user_id'], $course['course_id']]);
                            }
                        }
                    } else {
                        $course_price = $course['fees'] ?? 0;
                        $price_text = "Rs. " . number_format($course_price);
                        $button_text = "View Details";
                    }
                    ?>
                    <div class="course-footer">
                        <?php if ($has_active_trial): ?>
                            <strong style="color: #27ae60;">FREE (7-day trial)</strong>
                        <?php elseif ($has_annual_subscription): ?>
                            <strong style="color: #27ae60;">FREE</strong>
                        <?php else: ?>
                            <strong><?= $price_text ?></strong>
                        <?php endif; ?>
                        <?php if ($has_active_trial || $has_annual_subscription): ?>
                            <a href="course_video.php?id=<?= $course['course_id'] ?>&lesson=1&content=video" class="btn"><?= $button_text ?></a>
                        <?php else: ?>
                            <a href="enroll.php?course_id=<?= $course['course_id'] ?>" class="btn"><?= $button_text ?></a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
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
<!-- JavaScript for Back to Top Button -->
<script>
// JavaScript for the back-to-top button
window.onscroll = function() { scrollFunction() };

function scrollFunction() {
    const backToTopButton = document.getElementById("backToTop");
    if (document.body.scrollTop > 300 || document.documentElement.scrollTop > 300) {
        backToTopButton.classList.add("show");
    } else {
        backToTopButton.classList.remove("show");
    }
}
function topFunction() {
    document.body.scrollTop = 0; // For Safari
    document.documentElement.scrollTop = 0; // For Chrome, Firefox, IE and Opera
}
</script>
</body>
</html>