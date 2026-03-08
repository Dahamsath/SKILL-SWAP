<?php
session_start();
include 'config.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: student_dashboard.php");
    exit();
}

$error = '';
$success = '';

// Handle form submission
if ($_POST) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "An account with this email already exists.";
} else {
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Calculate trial end date (7 days from now)
    $trial_end = date('Y-m-d H:i:s', strtotime('+7 days'));
    
    // Insert new user with trial status 
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, trial_status, trial_start, trial_end, created_at) VALUES (?, ?, ?, 'student', 'active', NOW(), ?, NOW())");
    if ($stmt->execute([$name, $email, $hashed_password, $trial_end])) {
        // Get the inserted user ID
        $user_id = $pdo->lastInsertId();
        
        // Generate unique student ID
        $student_id = 'STU' . time() . rand(100, 999);
        
        // Update the user record with student_id
        $update_stmt = $pdo->prepare("UPDATE users SET student_id = ? WHERE user_id = ?");
        $update_stmt->execute([$student_id, $user_id]);
        
        // Automatically log in the user
        $_SESSION['user_id'] = $user_id;
        $_SESSION['name'] = $name;
        $_SESSION['role'] = 'student';
        $_SESSION['student_id'] = $student_id; 
        
        // Redirect to dashboard
        header("Location: student_dashboard.php");
        exit();
    } else {
        $error = "Registration failed. Please try again.";
    }
}
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>7-Day Free Trial - SKILL SWAP</title>
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
            background-color: #f5f7fa;
            padding-top: 80px;
        }

.navbar {
    background-color: #2c3e50;
    padding: 15px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.logo-container {
    display: flex;
    align-items: center;
}

.logo-placeholder {
    color: white;
    font-size: 20px;
    font-weight: bold;
    white-space: nowrap;
    overflow: visible;
    text-overflow: clip;
}

.navbar-links {
    display: flex;
    gap: 30px;
}

.navbar-links a {
    color: white;
    text-decoration: none;
    font-size: 16px;
    transition: color 0.3s;
}

.navbar-links a:hover {
    color: #3498db;
}

/* Add this media query for mobile responsiveness */
@media (max-width: 768px) {
    .navbar {
        padding: 15px 20px;
    }
    
    .navbar-links {
        gap: 15px;
        font-size: 14px;
    }
    
    .logo-placeholder {
        font-size: 18px;
    }
}
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .trial-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .trial-header h1 {
            color: #3498db;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .trial-header p {
            color: #666;
            line-height: 1.6;
        }
        
        .trial-benefits {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .benefits-list {
            list-style: none;
            margin-top: 15px;
        }
        
        .benefits-list li {
            padding: 8px 0;
            display: flex;
            align-items: center;
        }
        
        .benefits-list li::before {
            content: "✓";
            color: #27ae60;
            font-weight: bold;
            margin-right: 10px;
            font-size: 18px;
        }
        
        .trial-form {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .btn {
            background: #3498db;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            width: 100%;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #2980b9;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        
        .login-link a {
            color: #3498db;
            text-decoration: none;
            font-weight: bold;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .trial-footer {
            text-align: center;
            margin-top: 20px;
            color: #7f8c8d;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
<nav class="navbar">
    <div class="logo-container">
        <div class="logo-placeholder">SKILL SWAP</div>
    </div>
    <div class="navbar-links">
        <a href="index.php">Home</a>
        <a href="courses.php">Courses</a>
        <a href="about.php">About Us</a>
        <a href="contact.php">Contact</a>
        <a href="login.php">Login</a>
        <a href="register.php">Register</a>
    </div>
</nav>

    <div class="container">
        <div class="trial-header">
            <h1>Start Your 7-Day Free Trial</h1>
            <p>Get unlimited access to all courses for 7 days. No credit card required to start.</p>
        </div>
        
        <div class="trial-benefits">
            <h3>What you get with your free trial:</h3>
            <ul class="benefits-list">
                <li>Unlimited access to all courses</li>
                <li>Downloadable course materials</li>
                <li>Interactive quizzes and assignments</li>
                <li>Certificate of completion</li>
                <li>Cancel anytime before trial ends</li>
            </ul>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <div class="trial-form">
            <form method="POST">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn">Start My Free Trial</button>
            </form>
            
            <div class="login-link">
                Already have an account? <a href="login.php">Log in here</a>
            </div>
        </div>
        
        <div class="trial-footer">
            <p>Your trial will automatically expire after 7 days. You can upgrade to a paid plan anytime during your trial.</p>
            <p>We respect your privacy. Read our <a href="privacy.php" style="color:#3498db;">Privacy Policy</a>.</p>
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
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>