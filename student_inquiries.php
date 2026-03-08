<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'config.php';

// Only logged-in students should access this
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$student_name = $_SESSION['name'] ?? 'Student';

$message = '';
$message_type = '';

$inquiries = [];
try {
    $stmt = $pdo->prepare("
        SELECT inquiry_id, subject, message, status, reply, created_at, replied_at
        FROM inquiries 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user_id]);
    $inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($inquiries)) {
        $message = "You have not submitted any inquiries yet.";
        $message_type = 'info';
    }

} catch (Exception $e) {
    error_log("Error fetching inquiries for student ID $user_id: " . $e->getMessage());
    $message = "An error occurred while fetching your inquiries.";
    $message_type = 'error';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Inquiries - SkillPro Institute</title>
    <link rel="stylesheet" href="navigation.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: #f5f7fa; padding: 2rem; }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
            text-align: center;
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
        .inquiry-card {
            background: white;
            padding: 1.5rem;
            margin: 1rem 0;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid #3498db;
        }
        .inquiry-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .inquiry-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: #2c3e50;
        }
        .status-pending { background: #fff3cd; color: #856404; padding: 0.2rem 0.5rem; border-radius: 12px; font-size: 0.8rem; font-weight: bold; }
        .status-replied { background: #d1ecf1; color: #0c5460; padding: 0.2rem 0.5rem; border-radius: 12px; font-size: 0.8rem; font-weight: bold; }
        .status-resolved { background: #d4edda; color: #155724; padding: 0.2rem 0.5rem; border-radius: 12px; font-size: 0.8rem; font-weight: bold; }
        .inquiry-info {
            color: #666;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        .inquiry-message, .admin-reply {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            white-space: pre-wrap; 
        }
        .admin-reply {
            background: #e8f4fc;
            border-left: 3px solid #3498db;
        }
        .admin-reply-header {
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }
        .no-inquiries {
            text-align: center;
            padding: 3rem;
            color: #7f8c8d;
        }
        .action-link {
            display: inline-block;
            margin-top: 1rem;
            padding: 0.5rem 1rem;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        .action-link:hover {
            background: #2980b9;
        }
        @media (max-width: 768px) {
            .inquiry-header {
                flex-direction: column;
                align-items: flex-start;
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
        <a href="student_dashboard.php">Back</a>
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
            <h1> My Inquiries</h1>
            <p>View the status of your submitted inquiries and read responses from our administration.</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if (empty($inquiries) && $message_type !== 'info'): ?>
            <div class="no-inquiries">
                <p>You haven't submitted any inquiries yet.</p>
                <a href="contact.php" class="action-link">📤 Submit an Inquiry</a>
            </div>
        <?php else: ?>
            <?php foreach ($inquiries as $inquiry): ?>
            <div class="inquiry-card">
                <div class="inquiry-header">
                    <div class="inquiry-title"><?= htmlspecialchars($inquiry['subject']) ?></div>
                    <div>
                        <span class="status-<?= strtolower($inquiry['status']) ?>"><?= htmlspecialchars($inquiry['status']) ?></span>
                    </div>
                </div>
                
                <div class="inquiry-info">
                    <strong>Submitted On:</strong> <?= date('F j, Y g:i A', strtotime($inquiry['created_at'])) ?>
                    <?php if ($inquiry['status'] === 'Replied' && $inquiry['replied_at']): ?>
                        <br><strong>Replied On:</strong> <?= date('F j, Y g:i A', strtotime($inquiry['replied_at'])) ?>
                    <?php endif; ?>
                </div>
                
                <div class="inquiry-message">
                    <strong>Your Message:</strong><br>
                    <?= nl2br(htmlspecialchars($inquiry['message'])) ?>
                </div>
                
                <?php if ($inquiry['status'] === 'Replied' && !empty($inquiry['reply'])): ?>
                <div class="admin-reply">
                    <div class="admin-reply-header">Admin Reply:</div>
                    <?= nl2br(htmlspecialchars($inquiry['reply'])) ?>
                </div>
                <?php elseif ($inquiry['status'] === 'Pending'): ?>
                <div style="font-style: italic; color: #888;">
                    Your inquiry is pending review by our administration. We will respond as soon as possible.
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            
            <div style="text-align: center; margin-top: 2rem;">
                <a href="contact.php" class="action-link"> Submit Another Inquiry</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>