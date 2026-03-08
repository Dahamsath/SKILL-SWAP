<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';
$message_type = '';

if ($_POST && isset($_POST['reply_message']) && isset($_POST['inquiry_id'])) {
    $inquiry_id = (int)$_POST['inquiry_id'];
    $reply = trim($_POST['reply_message']);
    if (empty($reply)) {
        $message = "Please enter a reply message.";
        $message_type = 'error';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE inquiries SET status = 'Replied', reply = ?, replied_at = NOW() WHERE inquiry_id = ?");
            if ($stmt->execute([$reply, $inquiry_id])) {
                $message = "Reply sent successfully!";
                $message_type = 'success';
            } else {
                $message = "Error sending reply.";
                $message_type = 'error';
            }
        } catch (Exception $e) {
            error_log("Error replying to inquiry (ID: $inquiry_id): " . $e->getMessage());
            $message = "Database error occurred.";
            $message_type = 'error';
        }
    }
}
try {
    $stmt = $pdo->query("
        SELECT i.*, u.name as user_name, u.email as user_email 
        FROM inquiries i 
        LEFT JOIN users u ON i.user_id = u.user_id 
        ORDER BY i.created_at DESC
    ");
    $inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching inquiries: " . $e->getMessage());
    $message = "Error loading inquiries.";
    $message_type = 'error';
    $inquiries = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Inquiries - SKILL SWAP</title>
    <link rel="stylesheet" href="navigation.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: #f5f7fa; }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        .header-card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            text-align: center;
        }
        .header-card h1 { color: #2c3e50; margin-bottom: 0.5rem; }
        .message {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            text-align: center;
        }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        th, td {
            padding: 1.2rem;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }
        th {
            background: #3498db;
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        tr:last-child td { border-bottom: none; }
        tr:hover { background-color: #f8f9fa; }
        .status-pending { background: #fff3cd; color: #856404; padding: 0.2rem 0.5rem; border-radius: 12px; font-size: 0.8rem; font-weight: bold; }
        .status-replied { background: #d1ecf1; color: #0c5460; padding: 0.2rem 0.5rem; border-radius: 12px; font-size: 0.8rem; font-weight: bold; }
        .status-resolved { background: #d4edda; color: #155724; padding: 0.2rem 0.5rem; border-radius: 12px; font-size: 0.8rem; font-weight: bold; }
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
        .inquiry-info {
            color: #666;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        .inquiry-message {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            white-space: pre-wrap; /* Preserve line breaks */
        }
        .reply-form {
            background: #e8f4fc;
            padding: 1rem;
            border-radius: 6px;
            margin-top: 1rem;
        }
        .reply-form textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #cbd5e0;
            border-radius: 6px;
            font-size: 1rem;
            margin-bottom: 0.5rem;
            resize: vertical;
        }
        .btn {
            background: #3498db;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 500;
            display: inline-block;
            margin: 0.2rem;
        }
        .btn:hover { opacity: 0.9; }
        .btn-reply { background: #27ae60; }
        .btn-resolve { background: #9b59b6; }
        .btn-toggle { background: #f39c12; }
        .no-inquiries {
            text-align: center;
            padding: 3rem;
            color: #7f8c8d;
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .reply-section {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }
        .admin-reply {
            background: #e8f4fc;
            padding: 1rem;
            border-radius: 6px;
            margin-top: 1rem;
        }
        @media (max-width: 768px) {
            .inquiry-header {
                flex-direction: column;
                align-items: flex-start;
            }
            table, thead, tbody, th, td, tr { display: block; }
            thead tr { position: absolute; top: -9999px; left: -9999px; }
            tr {
                border: 1px solid #ccc;
                padding: 1rem;
                margin-bottom: 1rem;
                border-radius: 8px;
                background: white;
            }
            td {
                border: none;
                position: relative;
                padding-left: 50%;
                text-align: right;
            }
            td:before {
                content: attr(data-label) ": ";
                position: absolute;
                left: 1rem;
                width: 45%;
                text-align: left;
                font-weight: bold;
                color: #2c3e50;
            }
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
        <span class="user-greeting">Hey, <?= htmlspecialchars($_SESSION['name'] ?? 'User') ?>!</span>
        <a href="admin_dashboard.php">Back</a>
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
        <div class="header-card">
            <h1 style=padding-top:3rem> Manage Inquiries</h1>
            <p>View, reply to, and manage visitor/student inquiries.</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if (empty($inquiries)): ?>
            <div class="no-inquiries">
                <p>No inquiries found.</p>
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
                    <strong>From:</strong> <?= htmlspecialchars($inquiry['name'] ?? 'Anonymous') ?> 
                    (<?= htmlspecialchars($inquiry['email'] ?? 'No Email') ?>)
                    <?php if ($inquiry['user_name']): ?>
                        <em>(Registered User: <?= htmlspecialchars($inquiry['user_name']) ?>)</em>
                    <?php endif; ?>
                    <br>
                    <strong>Date:</strong> <?= date('F j, Y g:i A', strtotime($inquiry['created_at'])) ?>
                </div>
                
                <div class="inquiry-message">
                    <strong>Message:</strong><br>
                    <?= nl2br(htmlspecialchars($inquiry['message'])) ?>
                </div>
                
                <?php if ($inquiry['status'] === 'Pending'): ?>
                <div class="reply-form">
                    <form method="POST">
                        <input type="hidden" name="inquiry_id" value="<?= $inquiry['inquiry_id'] ?>">
                        <textarea name="reply_message" rows="3" placeholder="Write your reply here..." required></textarea>
                        <button type="submit" class="btn btn-reply"> Send Reply</button>
                    </form>
                </div>
                <?php elseif ($inquiry['reply']): ?>
                <div class="admin-reply">
                    <strong>Admin Reply (<?= date('F j, Y g:i A', strtotime($inquiry['replied_at'])) ?>):</strong><br>
                    <?= nl2br(htmlspecialchars($inquiry['reply'])) ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>