<?php
// contact.php - inquiry

error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'config.php';

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $subject = trim($_POST['subject'] ?? '');
    $message_text = trim($_POST['message'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    // Basic validation
    if (empty($name) || empty($email) || empty($subject) || empty($message_text)) {
        $message = "Please fill in all required fields.";
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $message_type = 'error';
    } else {
        try {
            $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

            $stmt = $pdo->prepare("INSERT INTO inquiries (user_id, subject, message) VALUES (?, ?, ?)");
            
            // Include name and email in the message for context, as they aren't separate columns
            $full_message = "Name: " . $name . "\nEmail: " . $email . "\n\nMessage:\n" . $message_text;
            
            $result = $stmt->execute([$user_id, $subject, $full_message]);

            if ($result) {
                $message = "Thank you for your inquiry! We will get back to you soon.";
                $message_type = 'success';
                // Clear form variables after successful submission 
                $name = $email = $subject = $message_text = '';
            } else {
                // Get detailed error info if execute fails
                $errorInfo = $stmt->errorInfo();
                error_log("Database Error Info (Contact Form): " . print_r($errorInfo, true));
                $message = "Sorry, there was an error submitting your inquiry. Please try again.";
                $message_type = 'error';
            }
        } catch (Exception $e) {
            // Catch any other unexpected exceptions
            error_log("Unexpected Error submitting inquiry: " . $e->getMessage());
            $message = "An unexpected error occurred. Please try again later.";
            $message_type = 'error';
        }
    }
}

// Pre-fill name/email if user is logged in (for the form display)
$prefill_name = '';
$prefill_email = '';
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT name, email FROM users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $prefill_name = $user['name'];
            $prefill_email = $user['email'];
        }
    } catch (Exception $e) {
        error_log("Error fetching user details for contact form: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - SKILL SWAP </title>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="navigation.css">
    <link rel="stylesheet" href="footer.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: #f5f7fa; }
        .container { max-width: 800px; margin: 2rem auto; padding: 0 2rem; }
        .filters { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .filters label { margin-right: 1rem; font-weight: bold; }
        .filters select { padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; }
        .course-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 2rem; }
        .course-card { background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .course-header { background: #3498db; color: white; padding: 1rem; }
        .course-body { padding: 1.5rem; }
        .course-footer { padding: 1rem; background: #ecf0f1; display: flex; justify-content: space-between; }
        .btn { background: #27ae60; color: white; padding: 0.5rem 1rem; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; }
        .btn1 { background: #ef1212ff; color: white; padding: 0.2rem 0.297rem; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; }
        .btn:hover { background: #229954; }
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
            <a href="login.php" class="btn-login">Login</a>
            <a href="register.php" class="btn-register">Register</a>
        <?php endif; ?>
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
        <h2 style="margin-bottom: 1rem; padding-top: 5rem ">Contact Us</h2>
        
        <div class="filters">
            <p><strong>Email:</strong> info@skillswap.lk</p>
            <p>Have questions about our courses, certifications, or job opportunities? Send us a message and we'll get back to you as soon as possible.</p>
        </div>

        <?php if ($message): ?>
            <div style="padding: 1rem; margin-bottom: 1.5rem; border-radius: 8px; text-align: center; background: <?= $message_type === 'success' ? '#d4edda; color: #155724; border: 1px solid #c3e6cb;' : '#f8d7da; color: #721c24; border: 1px solid #f5c6cb;'; ?>; ">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="filters">
                <div style="margin-bottom: 1.5rem;">
                    <label for="name">Full Name *</label>
                    <input type="text" name="name" id="name" value="<?= htmlspecialchars($prefill_name) ?>" required style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label for="email">Email Address *</label>
                    <input type="email" name="email" id="email" value="<?= htmlspecialchars($prefill_email) ?>" required style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label for="subject">Subject *</label>
                    <select name="subject" id="subject" required style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
                        <option value="">-- Please select a subject --</option>
                        <option value="Course Information" <?= (isset($_POST['subject']) && $_POST['subject'] == 'Course Information') ? 'selected' : '' ?>>Course Information</option>
                        <option value="Certification Details" <?= (isset($_POST['subject']) && $_POST['subject'] == 'Certification Details') ? 'selected' : '' ?>>Certification Details</option>
                        <option value="Job Opportunities" <?= (isset($_POST['subject']) && $_POST['subject'] == 'Job Opportunities') ? 'selected' : '' ?>>Job Opportunities</option>
                        <option value="Enrollment Process" <?= (isset($_POST['subject']) && $_POST['subject'] == 'Enrollment Process') ? 'selected' : '' ?>>Enrollment Process</option>
                        <option value="General Inquiry" <?= (isset($_POST['subject']) && $_POST['subject'] == 'General Inquiry') ? 'selected' : '' ?>>General Inquiry</option>
                    </select>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label for="message">Your Message *</label>
                    <textarea name="message" id="message" rows="6" placeholder="Please provide as much detail as possible..." required style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;"><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                </div>

                <button type="submit" style="width: 100%; background: #3498db; color: white; padding: 0.9rem; border: none; border-radius: 6px; cursor: pointer; font-size: 1.1rem; font-weight: 600; transition: background-color 0.3s; margin-top: 1rem;"> Send Message</button>
            </div>
        </form>
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
    <!-- End Footer -->
    <script>
        // JavaScript for the back-to-top button (Footer functionality)
        window.onscroll = function() { scrollFunction() };

        function scrollFunction() {
            const backToTopButton = document.getElementById("backToTop");
            // Show button when scrolled down 300px
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