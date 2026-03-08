<?php
// index.php - Homepage for SKILL SWAP
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start(); 
include 'config.php';

// --- FETCH COURSES FOR HOMEPAGE ---
try {
    $search_term_index = trim($_GET['search'] ?? '');

    if (!empty($search_term_index)) {
        $stmt = $pdo->prepare("
            SELECT c.*, u.name AS instructor_name
            FROM courses c
            LEFT JOIN users u ON c.instructor_id = u.user_id
            WHERE c.title LIKE ? OR c.description LIKE ? OR c.category LIKE ?
            ORDER BY c.title
            LIMIT 3
        ");
        $search_param_index = '%' . $search_term_index . '%';
        $stmt->execute([$search_param_index, $search_param_index, $search_param_index]);
    } else {
        // If no search term, show the first 3 courses 
        $stmt = $pdo->query("SELECT c.*, u.name AS instructor_name FROM courses c LEFT JOIN users u ON c.instructor_id = u.user_id ORDER BY c.course_id DESC LIMIT 3");
    }  $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Error fetching courses for index.php: " . $e->getMessage());
    $courses = [];
}
// --- END FETCH COURSES ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKILLSWAP</title>
    <meta name="description" content="Welcome to SKILL SWAP Online Courses hosted platform.">
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="footer.css"> 
    <style>
        .hero {
            background:  url('images/eee.jpg') no-repeat center center/cover;
            color: white;
            text-align: center;
            padding: 11.5rem 2rem;
            position: relative;
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
                <a href="logout.php" class="btn1">Logout</a>
            <?php else: ?>
                <!-- User is NOT logged in -->
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
            <?php endif; ?>
        </div>
    </nav>
    <!--Course Search Bar-->
    <div class="search-bar-container">
        <form action="courses.php" method="GET" class="course-search-form">
            <input
                type="text"
                name="search"
                class="course-search-input"
                placeholder="Search for courses"
                aria-label="Search courses"
                value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
            <button type="submit" class="course-search-button">
                <i class="fas fa-search"></i> Search Courses
            </button>
        </form>
    </div>
    <!-- Hero Section -->
    <header class="hero">
        <div class="hero-content">
            <h1>Learn What Matters, From People Who Know</h1>
            <p>Welcome to SKILL SWAP Online Courses hosted platform.</p>
            <div class="hero-buttons">
                <a href="courses.php" class="btn btn-primary">Explore Courses</a>
            </div>
        </div>
    </header>
        <!-- Why Choose SKILLSWAP Section -->
        <section class="section">
            <div class="section-header">
                <h2>Why Choose SKILL SWAP Courses?</h2>
                <p>We are committed to bridging the skills gap and preparing you for success.</p>
            </div>
            <div class="features">
                <div class="feature-card">
                    <div class="feature-icon"> <i class="fas fa-graduation-cap"></i> 🎓</div>
                    <h3>Industry-Aligned Curriculum</h3>
                    <p>Our courses are co-developed with leading employers to ensure you learn the most relevant skills.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"> <i class="fas fa-map-marked-alt"></i> 🏅</div>
                    <h3>Recognized Course Certificates</h3>
                    <p>Receive a professional certificate after completing the program to showcase your skills and achievements.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">  <i class="fas fa-briefcase"></i> 💼</div>
                    <h3>Strong Job Placement Support</h3>
                    <p>Gain access to exclusive job fairs, internships, and our extensive network of hiring partners.</p>
                </div>
            </div>
        </section>
        <!-- Featured Courses Preview Section -->
        <section class="section courses-preview">
            <div class="section-header">
                <h2>Popular Courses Programs</h2>
                <p>Discover our most sought-after courses designed for high employability.</p>
            </div>
            <div class="courses-grid">
                <!-- Course Card 1 -->
                <div class="course-card">
                    <img src="images/python.jpg" alt="Python for Beginners" class="course-image">
                    <div class="course-content">
                        <h3>Python for Beginners</h3>
                        <p>Programming in Python - 1. Python for Beginners</p>
                        <a href="courses.php" class="btn btn-primary">Learn More</a>
                    </div>
                </div>
                <!-- Course Card 2 -->
                <div class="course-card">
                    <img src="images/web design.jpg" alt="Web Design for Beginners" class="course-image">
                    <div class="course-content">   
                        <h3>Web Design for Beginners</h3>
                        <p>Web Development - 1. Web Design for Beginners</p>
                        <a href="courses.php" class="btn btn-primary">Learn More</a>
                    </div>
                </div>
                <!-- Course Card 3 -->
                <div class="course-card">
                    <img src="images/mobile-development.jpg" alt="Mobile Developer" class="course-image">
                    <div class="course-content">
                        <h3>Mobile Developing for Beginners</h3>
                        <p>Mobile Development - 1. Mobile Development for Beginners</p>
                        <a href="courses.php" class="btn btn-primary">Learn More</a>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- New Plans Section -->
        <section class="section plans-section">
            <div class="section-header">
                <h2>Plans for you </h2>
            </div>
            
            <div class="toggle-container">
                <button class="toggle-btn active">For Individuals</button>
            </div>
            
            <div class="plans-container">
                <!-- Single Learning Program -->
<div class="plan-card">
    <div class="plan-title">Basic Access</div>
    <div class="plan-description">Learn at your own pace</div>
    <div class="features">
        <div class="feature">
            <span class="feature-check">✓</span>
            <span>Access to selected courses</span>
        </div>
        <div class="feature">
            <span class="feature-check">✓</span>
            <span>Learn a course at a time</span>
        </div>
        <div class="feature">
            <span class="feature-check">✓</span>
            <span>Community support</span>
        </div>
        <div class="feature">
            <span class="feature-check">✓</span>
            <span>Cancel anytime</span>
        </div>
        <div class="price">LKR 6000<span class="monthly-price">/month</span></div>
    </div>
    <a href="payment.php?plan=basic" class="cta-button">Purchase Basic Access</a>
    <div class="cancel-policy">Cancel anytime</div>
</div>
                
                
                <!-- Coursera Plus Monthly -->
                <div class="plan-card">
                    <div class="popular-tag">Most popular</div>
                    <div class="plan-title">Pro Learnning Pass</div>
                    <div class="plan-description">Unlimited learning, maximum value</div>
                    <a href="free_trial.php" class="cta-button">Start 7-day free trial</a>
                    <div class="cancel-policy">Cancel anytime</div>
                    <div class="features">
                        <div class="feature">
                            <span class="feature-check">✓</span>
                            <span>Unlimited access to all courses</span>
                        </div>
                        <div class="feature">
                            <span class="feature-check">✓</span>
                            <span>Priority admin support</span>
                        </div>
                        <div class="feature">
                            <span class="feature-check">✓</span>
                            <span>Cancel anytime</span>
                        </div>
                        <div class="price">LKR 7500<span class="monthly-price">/month</span></div>
                    </div>
                </div>
                
                <!-- Coursera Plus Annual -->
                <div class="plan-card">
                    <div class="plan-title">Annual Learning Pass</div>
                    <div class="plan-description">Save more with long-term access</div>
                    <a href="payment.php?plan=annual" class="outline-button">Try SKILL SWAP Annual</a>
                    <div class="features">
                        <div class="feature">
                            <span>Everything in Pro, plus:</span>
                        </div>
                        <div class="feature">
                            <span class="feature-check">✓</span>
                            <span>Save when you pay up front for the year</span>
                            </div>
                            <div class="feature">
                            <span class="feature-check">✓</span>
                            <span>Best value for money</span>
                            </div>
                            <div class="feature">
                            <span class="feature-check">✓</span>
                            <span>Learn at your own pace</span>
                            </div>
                            <div class="feature">
                            <span class="feature-check">✓</span>
                            <span>Ideal for serious learners</span>
                            </div>
                            <div class="price">
                        LKR 25000<span class="annual-price">/year</span>
                    </div>
                    <div class="guarantee">14-day money-back guarantee</div>
                    </div>
                </div>
            </div>
        </section>

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

<style>
/* Plans section styles */
.plans-section {
    padding: 60px 20px;
    background-color: #f0f5ff;
}

.toggle-container {
    display: flex;
    justify-content: center;
    background-color: white;
    border-radius: 50px;
    padding: 5px;
    margin-bottom: 40px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    max-width: 500px;
    margin-left: auto;
    margin-right: auto;
}

.toggle-btn {
    padding: 12px 25px;
    border: none;
    border-radius: 50px;
    cursor: pointer;
    font-weight: bold;
    transition: all 0.3s ease;
}

.toggle-btn.active {
    background-color: #0056b3;
    color: white;
}

.toggle-btn.inactive {
    background-color: transparent;
    color: #666;
}

.plans-container {
    display: flex;
    justify-content: center;
    gap: 20px;
    flex-wrap: wrap;
}

.plan-card {
    background-color: white;
    border-radius: 8px;
    padding: 25px;
    width: 300px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    transition: transform 0.3s ease;
    position: relative;
}

.plan-card:hover {
    transform: translateY(-5px);
}

.popular-tag {
    background-color: #0056b3;
    color: white;
    text-align: center;
    padding: 5px;
    font-size: 14px;
    margin-bottom: 15px;
}

.plan-title {
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 10px;
    text-align: center;
}

.plan-description {
    font-size: 14px;
    color: #666;
    margin-bottom: 20px;
    text-align: center;
}

.price {
    font-size: 32px;
    font-weight: bold;
    margin: 10px 0;
    text-align: center;
}

.monthly-price, .annual-price {
    font-size: 18px;
    color: #666;
}

.strike-through {
    text-decoration: line-through;
    color: #999;
    margin-right: 10px;
}

.cta-button {
    display: block;
    width: 100%;
    padding: 12px;
    background-color: #0056b3;
    color: white;
    border: none;
    border-radius: 5px;
    text-align: center;
    font-weight: bold;
    cursor: pointer;
    margin: 20px 0;
    text-decoration: none;
    transition: background-color 0.3s ease;
}

.cta-button:hover {
    background-color: #004494;
}

.outline-button {
    display: block;
    width: 100%;
    padding: 12px;
    background-color: transparent;
    color: #0056b3;
    border: 2px solid #0056b3;
    border-radius: 5px;
    text-align: center;
    font-weight: bold;
    cursor: pointer;
    margin: 20px 0;
    text-decoration: none;
    transition: all 0.3s ease;
}

.outline-button:hover {
    background-color: #e6f0ff;
}

.cancel-policy, .guarantee {
    text-align: center;
    font-size: 14px;
    color: #666;
    margin: 15px 0;
}

.features {
    margin-top: 10px;
    border-top: 1px solid #eee;
    padding-top: 20px;
}

.feature {
    display: flex;
    align-items: flex-start;
    margin: 5px 0;
    font-size: 14px;
}

.feature-check {
    color: #0056b3;
    margin-right: 10px;
    font-weight: bold;
}

@media (max-width: 768px) {
    .plans-container {
        flex-direction: column;
        align-items: center;
    }
    .plan-card {
        width: 100%;
        max-width: 400px;
    }
}
</style>

</body>
</html>