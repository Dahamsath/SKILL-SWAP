<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';
$message_type = '';

if ($_POST) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($name) || empty($email) || empty($password)) {
        $message = "All fields are required.";
        $message_type = 'error';
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $message = "Email already exists.";
            $message_type = 'error';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'instructor')");
            if ($stmt->execute([$name, $email, $hashed])) {
                $message = "Instructor added successfully!";
                $message_type = 'success';
            } else {
                $message = "Error adding instructor. Please try again.";
                $message_type = 'error';
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
    <title>Add New Instructor - SKILL SWAP</title>
    <link rel="stylesheet" href="navigation.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: #f5f7fa; }
        .container { max-width: 600px; margin: 2rem auto; padding: 0 2rem; }
        .form-container { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #2c3e50; margin-bottom: 1.5rem; text-align: center; }
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: bold; color: #2c3e50; }
        input, select { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem; }
        .btn { background: #27ae60; color: white; padding: 0.75rem; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; font-weight: 600; width: 100%; margin-top: 1rem; }
        .btn:hover { background: #229954; }
        .message { padding: 1rem; margin-bottom: 1.5rem; border-radius: 4px; text-align: center; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .back-link { display: block; text-align: center; margin-top: 1rem; color: #3498db; text-decoration: none; }
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
        <div class="form-container">
            <h2 style=padding-top:1rem>Add New Instructor</h2>
            
            <?php if ($message): ?>
                <div class="message <?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn">Add Instructor</button>
            </form>
            
            <a href="admin_dashboard.php" class="back-link">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>