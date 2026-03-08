<?php
session_start();
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Determine plan type from URL parameter
$plan_type = isset($_GET['plan']) ? $_GET['plan'] : 'monthly';

// Define plan details based on plan type
switch($plan_type) {
    case 'basic':
        $plan_name = 'Basic Access';
        $price = 6000;
        $period = 'month';
        break;
    case 'trial':
        $plan_name = '7-Day Free Trial';
        $price = 0;
        $period = 'trial';
        break;
    case 'annual':
        $plan_name = 'Annual Learning Pass';
        $price = 25000;
        $period = 'year';
        break;
    case 'monthly':
    default:
        $plan_name = 'Pro Learning Pass';
        $price = 7500;
        $period = 'month';
        break;
}

$currency = 'LKR';

// Handle payment form submission
if ($_POST) {
    $card_number = preg_replace('/\s+/', '', $_POST['card_number']); // Remove spaces
    $expiry_month = $_POST['expiry_month'];
    $expiry_year = $_POST['expiry_year'];
    $cvv = $_POST['cvv'];
    $cardholder_name = trim($_POST['cardholder_name']);
    
    // Validate inputs
    if (empty($card_number) || empty($expiry_month) || empty($expiry_year) || empty($cvv) || empty($cardholder_name)) {
        $error = "All fields are required.";
    } elseif (!ctype_digit($card_number) || strlen($card_number) < 13 || strlen($card_number) > 19) {
        $error = "Please enter a valid card number.";
    } elseif (!ctype_digit($cvv) || strlen($cvv) < 3 || strlen($cvv) > 4) {
        $error = "Please enter a valid CVV.";
    } elseif (!checkdate($expiry_month, 1, $expiry_year)) {
        $error = "Please enter a valid expiry date.";
    } else {
        
        try {
            $stmt = $pdo->prepare("ALTER TABLE users ADD COLUMN subscription_status VARCHAR(20) DEFAULT 'inactive'");
            $stmt->execute();
        } catch (Exception $e) {
            
        }
        
        try {
            $stmt = $pdo->prepare("ALTER TABLE users ADD COLUMN subscription_start DATETIME NULL");
            $stmt->execute();
        } catch (Exception $e) {
            
        }
        
        try {
            $stmt = $pdo->prepare("ALTER TABLE users ADD COLUMN subscription_end DATETIME NULL");
            $stmt->execute();
        } catch (Exception $e) {
            
        }
        
        try {
            $stmt = $pdo->prepare("ALTER TABLE users ADD COLUMN plan_type VARCHAR(10) DEFAULT 'monthly'");
            $stmt->execute();
        } catch (Exception $e) {
            
        }
        
        // Update user's subscription status
        if ($plan_type === 'annual') {
            $end_date_query = 'DATE_ADD(NOW(), INTERVAL 1 YEAR)';
        } elseif ($plan_type === 'trial') {
            $end_date_query = 'DATE_ADD(NOW(), INTERVAL 7 DAY)';
        } elseif ($plan_type === 'basic') {
            $end_date_query = 'DATE_ADD(NOW(), INTERVAL 1 MONTH)';
        } else {
            $end_date_query = 'DATE_ADD(NOW(), INTERVAL 1 MONTH)';
        }
        
        $stmt = $pdo->prepare("UPDATE users SET subscription_status = 'active', subscription_start = NOW(), subscription_end = $end_date_query, plan_type = ? WHERE user_id = ?");
        if ($stmt->execute([$plan_type, $user_id])) {
            $success = "Your $plan_name has been activated! You now have full access to all courses.";
            
            // Redirect to dashboard after 3 seconds
            header("refresh:3;url=student_dashboard.php");
        } else {
            $error = "Failed to activate your subscription. Please contact support.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Your Purchase - SKILL SWAP</title>
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
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .payment-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .payment-header h1 {
            color: #3498db;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .payment-header p {
            color: #666;
            line-height: 1.6;
        }
        
        .payment-info {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .plan-details {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            font-weight: bold;
            font-size: 18px;
            color: #3498db;
        }
        
        .payment-form {
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
        
        .card-number {
            letter-spacing: 2px;
        }
        
        .expiry-cvv {
            display: flex;
            gap: 15px;
        }
        
        .expiry, .cvv {
            flex: 1;
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
        
        .security-note {
            text-align: center;
            margin-top: 20px;
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .security-icons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 10px;
        }
        
        .security-icon {
            background: #e8f4ea;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            color: #27ae60;
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
        }

        .logo-placeholder {
            color: white;
            font-size: 20px;
            font-weight: bold;
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

        .btn1 {
            background: #e74c3c;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="logo-placeholder">SKILL SWAP</div>
        <div class="navbar-links">
            <span>Hey, <?= htmlspecialchars($_SESSION['name'] ?? 'User') ?>!</span>
            <a href="index.php">Home</a>
            <a href="courses.php">Courses</a>
            <a href="about.php">About Us</a>
            <a href="contact.php">Contact</a>
            <a href="student_dashboard.php">Dashboard</a>
            <a href="logout.php" class="btn1">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="payment-header">
            <h1>Complete Your Purchase</h1>
            <p>Enter your payment details to activate your subscription.</p>
        </div>
        
        <div class="payment-info">
            <div class="plan-details">
                <span><?= htmlspecialchars($plan_name) ?></span>
                <span><?= $currency ?> <?= number_format($price) ?>/<?= $period ?></span>
            </div>
            <div class="total-row">
                <span>Total Today</span>
                <span><?= $currency ?> <?= number_format($price) ?></span>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <div class="payment-form">
            <form method="POST">
                <input type="hidden" name="plan_type" value="<?= htmlspecialchars($plan_type) ?>">
                <div class="form-group">
                    <label for="card_number">Card Number</label>
                    <input type="text" id="card_number" name="card_number" class="card-number" placeholder="1234 5678 9012 3456" maxlength="19" required>
                </div>
                
                <div class="form-group">
                    <label for="cardholder_name">Cardholder Name</label>
                    <input type="text" id="cardholder_name" name="cardholder_name" placeholder="John Doe" required>
                </div>
                
                <div class="expiry-cvv">
                    <div class="expiry form-group">
                        <label for="expiry_month">Expiry Date</label>
                        <select id="expiry_month" name="expiry_month" required>
                            <option value="">Month</option>
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="expiry form-group">
                        <label>&nbsp;</label>
                        <select id="expiry_year" name="expiry_year" required>
                            <option value="">Year</option>
                            <?php for ($i = date('Y'); $i <= date('Y') + 10; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="cvv form-group">
                        <label for="cvv">CVV</label>
                        <input type="text" id="cvv" name="cvv" placeholder="123" maxlength="4" required>
                    </div>
                </div>
                
                <button type="submit" class="btn">Complete Purchase</button>
            </form>
            
            <div class="security-note">
                <p>Your payment details are securely encrypted</p>
                <div class="security-icons">
                    <span class="security-icon">SSL Secured</span>
                    <span class="security-icon">PCI Compliant</span>
                </div>
            </div>
        </div>
    </div>

    <!--Footer -->
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
    
    <!-- Auto-format card number -->
    <script>
        document.getElementById('card_number').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            let formattedValue = '';
            
            for (let i = 0; i < value.length; i++) {
                if (i > 0 && i % 4 === 0) {
                    formattedValue += ' ';
                }
                formattedValue += value[i];
            }
            
            e.target.value = formattedValue.substring(0, 19);
        });
        
        document.getElementById('cvv').addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '').substring(0, 4);
        });
    </script>
</body>
</html>