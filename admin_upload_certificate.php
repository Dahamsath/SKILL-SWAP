<?php
session_start();
include 'config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';

// Handle certificate upload
if ($_POST) {
    $user_id = (int)$_POST['user_id'];
    $course_id = (int)$_POST['course_id'];
    
    // Validate user and course exist
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    if (!$stmt->fetch()) {
        $error = "Invalid user selected.";
    } else {
        $stmt = $pdo->prepare("SELECT course_id FROM courses WHERE course_id = ?");
        $stmt->execute([$course_id]);
        if (!$stmt->fetch()) {
            $error = "Invalid course selected.";
        } else {
            // Handle file upload
            if (isset($_FILES['certificate_file']) && $_FILES['certificate_file']['error'] === UPLOAD_ERR_OK) {
                $file_name = $_FILES['certificate_file']['name'];
                $file_tmp = $_FILES['certificate_file']['tmp_name'];
                $file_size = $_FILES['certificate_file']['size'];
                
                // Allowed extensions
                $allowed_exts = ['pdf', 'jpg', 'jpeg', 'png'];
                $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                if (!in_array($ext, $allowed_exts)) {
                    $error = "Only PDF, JPG, JPEG, PNG files are allowed.";
                } elseif ($file_size > 5 * 1024 * 1024) { // 5MB max
                    $error = "File size must be less than 5MB.";
                } else {
                    // Create certificates directory
                    $upload_dir = 'certificates/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                    
                    // Generate unique filename
                    $unique_name = 'cert_' . $user_id . '_' . $course_id . '_' . time() . '.' . $ext;
                    $target_path = $upload_dir . $unique_name;
                    
                    if (move_uploaded_file($file_tmp, $target_path)) {
                        // Insert into database
                        $stmt = $pdo->prepare("INSERT INTO certificates (user_id, course_id, certificate_path, status) VALUES (?, ?, ?, 'issued')");
                        if ($stmt->execute([$user_id, $course_id, $target_path])) {
                            $message = "Certificate uploaded successfully!";
                        } else {
                            unlink($target_path);
                            $error = "Database error occurred.";
                        }
                    } else {
                        $error = "Failed to save certificate file.";
                    }
                }
            } else {
                $error = "Please select a certificate file to upload.";
            }
        }
    }
}

// Fetch users and courses for dropdowns
$users = $pdo->query("SELECT user_id, name, student_id FROM users WHERE role = 'student' ORDER BY name")->fetchAll();
$courses = $pdo->query("SELECT course_id, title FROM courses ORDER BY title")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Certificate - Admin Dashboard</title>
    <link rel="stylesheet" href="index.css">
    <style>
        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        .card {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        h2 {
            color: #3498db;
            margin-bottom: 1.5rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #333;
        }
        select, input[type="file"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        .btn {
            background: #3498db;
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: bold;
        }
        .btn:hover {
            background: #2980b9;
        }
        .message {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .back-link {
            display: inline-block;
            margin-top: 1rem;
            color: #3498db;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="logo-placeholder">SKILL SWAP</div>
        <div class="navbar-links">
            <span>Hey, <?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?>!</span>
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="logout.php" class="btn1">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <h2>Upload Certificate</h2>
            
            <?php if ($message): ?>
                <div class="message success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="user_id">Select Student *</label>
                    <select name="user_id" required>
                        <option value="">-- Select Student --</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user['user_id'] ?>"><?= htmlspecialchars($user['student_id']) ?> - <?= htmlspecialchars($user['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="course_id">Select Course *</label>
                    <select name="course_id" required>
                        <option value="">-- Select Course --</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= $course['course_id'] ?>"><?= htmlspecialchars($course['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="certificate_file">Certificate File (PDF/JPG/PNG) *</label>
                    <input type="file" name="certificate_file" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
                
                <button type="submit" class="btn">Upload Certificate</button>
            </form>
            
            <a href="admin_dashboard.php" class="back-link">← Back to Admin Dashboard</a>
        </div>
    </div>
</body>
</html>