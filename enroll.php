<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

include 'config.php';

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

$course_id = $_GET['course_id'] ?? 0;

// Get course details
$stmt = $pdo->prepare("SELECT c.* FROM courses c WHERE c.course_id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch();
if (!$course) {
    die("Course not found.");
}

// Handle enrollment + payment
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
        // Check if already enrolled
        $stmt = $pdo->prepare("SELECT * FROM enrollments WHERE user_id = ? AND course_id = ?");
        $stmt->execute([$user_id, $course_id]);
        if ($stmt->fetch()) {
            $error = "You are already enrolled in this course.";
        } else {
            // Enroll student in course
            $stmt = $pdo->prepare("INSERT INTO enrollments (user_id, course_id, mode, progress, last_accessed) VALUES (?, ?, 'online', 0, NOW())");
            if ($stmt->execute([$user_id, $course_id])) {
                $success = "Enrollment successful! You can now access this course.";
                
                // Optional: Simulate payment processing (you can replace this with real gateway later)
                // For now, just log or simulate success
                error_log("Payment processed for user $user_id, course $course_id, amount: Rs. " . number_format($course['fees']));
                
                // Redirect to course video page after 2 seconds
                header("refresh:2;url=course_video.php?id=$course_id&lesson=1&content=video");
            } else {
                $error = "Enrollment failed. Please try again.";
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
    <title>Enroll - <?= htmlspecialchars($course['title']) ?></title>
    <link rel="stylesheet" href="index.css">
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
        
        .enroll-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .enroll-header h1 {
            color: #3498db;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .enroll-header p {
            color: #666;
            line-height: 1.6;
        }
        
        .course-info {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .course-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }
        
        .course-fee {
            font-size: 24px;
            font-weight: bold;
            color: #27ae60;
            margin: 15px 0;
            text-align: center;
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
        
        /* Card visual */
        .card-preview {
            background: #1a1f2b;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .card-preview::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #ff6b6b, #4ecdc4, #45b7d1, #96ceb4);
        }
        
        .card-number-display {
            font-family: monospace;
            font-size: 20px;
            letter-spacing: 4px;
            margin: 15px 0;
            text-align: center;
            min-height: 24px;
        }
        
        .card-holder {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            font-size: 14px;
        }
        
        .card-expiry {
            color: #aaa;
        }
        
        .card-logo {
            position: absolute;
            bottom: 15px;
            right: 15px;
            font-size: 24px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="enroll-header">
            <h1>Enroll in: <?= htmlspecialchars($course['title']) ?></h1>
            <p>Complete your enrollment to gain full access to all lessons, quizzes, and assignments.</p>
        </div>
        
        <div class="course-info">
            <div class="course-title"><?= htmlspecialchars($course['title']) ?></div>
            <div class="course-fee">Course Fee: Rs. <?= number_format($course['fees']) ?></div>
            <p><strong>Category:</strong> <?= htmlspecialchars($course['category'] ?? 'General') ?></p>
            <p><strong>Instructor:</strong> 
                <?php 
                // If instructor_id exists and users table exists, show name
                if (isset($course['instructor_id'])) {
                    $stmt_instructor = $pdo->prepare("SELECT name FROM users WHERE user_id = ?");
                    $stmt_instructor->execute([$course['instructor_id']]);
                    $instructor = $stmt_instructor->fetch();
                    echo $instructor ? htmlspecialchars($instructor['name']) : "Not Assigned";
                } else {
                    echo "Not Assigned";
                }
                ?>
            </p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <div class="payment-form">
            <h2 style="text-align: center; margin-bottom: 20px; color: #333;">Payment Details</h2>
            
            <!-- Card Preview -->
            <div class="card-preview">
                <div class="card-number-display" id="cardNumberDisplay">•••• •••• •••• ••••</div>
                <div class="card-holder">
                    <span id="cardHolderName">CARDHOLDER NAME</span>
                    <span class="card-expiry" id="cardExpiry">MM/YY</span>
                </div>
                <div class="card-logo">💳</div>
            </div>
            
            <form method="POST">
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
                
                <button type="submit" class="btn">Confirm Enrollment & Pay</button>
            </form>
            
            <div class="security-note">
                <p>Your payment details are securely encrypted</p>
                <div class="security-icons">
                    <span class="security-icon">SSL Secured</span>
                    <span class="security-icon">PCI Compliant</span>
                </div>
            </div>
        </div>
        
        <a href="courses.php" class="back-link" style="display: inline-block; margin-top: 20px; color: #3498db; text-decoration: none;">
            ← Back to Courses
        </a>
    </div>

    <script>
        // Auto-format card number and update preview
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
            document.getElementById('cardNumberDisplay').textContent = 
                formattedValue || '•••• •••• •••• ••••';
        });
        
        // Update cardholder name preview
        document.getElementById('cardholder_name').addEventListener('input', function(e) {
            let name = e.target.value.trim() || 'CARDHOLDER NAME';
            document.getElementById('cardHolderName').textContent = name.toUpperCase();
        });
        
        // Update expiry preview
        document.getElementById('expiry_month').addEventListener('change', function() {
            const month = this.value.padStart(2, '0');
            const year = document.getElementById('expiry_year').value;
            document.getElementById('cardExpiry').textContent = month + '/' + (year ? year.toString().substr(-2) : 'YY');
        });
        document.getElementById('expiry_year').addEventListener('change', function() {
            const month = document.getElementById('expiry_month').value.padStart(2, '0');
            const year = this.value;
            document.getElementById('cardExpiry').textContent = month + '/' + (year ? year.toString().substr(-2) : 'YY');
        });
        
        // Auto-focus first field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('card_number').focus();
        });
    </script>
</body>
</html>