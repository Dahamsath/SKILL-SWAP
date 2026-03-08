<?php
session_start();
include 'config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Fetch all courses for dropdown
try {
    $stmt = $pdo->query("SELECT course_id, title FROM courses ORDER BY title");
    $courses = $stmt->fetchAll();
} catch (Exception $e) {
    die("Error fetching courses: " . $e->getMessage());
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_video'])) {
    $course_id = (int)$_POST['course_id'];
    $lesson_id = (int)$_POST['lesson_id'];
    $video_title = trim($_POST['video_title']);
    
    // Validate inputs
    if (empty($course_id) || empty($lesson_id) || empty($video_title)) {
        $error = "Please select a course, lesson, and enter a video title.";
    } elseif (!isset($_FILES['video_file']) || $_FILES['video_file']['error'] !== UPLOAD_ERR_OK) {
        $error = "Please select a valid video file.";
    } else {
        // Get file info
        $file_name = $_FILES['video_file']['name'];
        $file_tmp = $_FILES['video_file']['tmp_name'];
        $file_size = $_FILES['video_file']['size'];
        $file_type = $_FILES['video_file']['type'];
        
        // Allowed video types
        $allowed_types = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'];
        $max_size = 500 * 1024 * 1024; // 500MB
        
        // Validate file type and size
        if (!in_array($file_type, $allowed_types)) {
            $error = "Only MP4, WebM, OGG, and MOV video files are allowed.";
        } elseif ($file_size > $max_size) {
            $error = "File size must be less than 500MB.";
        } else {
            // Create videos directory if it doesn't exist
            if (!is_dir('videos')) {
                mkdir('videos', 0755, true);
            }
            
            // Generate unique filename
            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
            $unique_filename = 'course_' . $course_id . '_lesson_' . $lesson_id . '_' . time() . '.' . $file_extension;
            $target_path = 'videos/' . $unique_filename;
            
            // Move uploaded file
            if (move_uploaded_file($file_tmp, $target_path)) {
                // Insert into database
                $stmt = $pdo->prepare("INSERT INTO videos (course_id, lesson_id, title, filename, filepath, duration) VALUES (?, ?, ?, ?, ?, ?)");
                $duration = 0;
                
                if ($stmt->execute([$course_id, $lesson_id, $video_title, $file_name, $target_path, $duration])) {
                    $message = "Video uploaded successfully!";
                } else {
                    $error = "Error saving video information to database.";
                    unlink($target_path); // Remove file if DB insert fails
                }
            } else {
                $error = "Error uploading video file.";
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
    <title>Upload Video - Admin Dashboard</title>
    <link rel="stylesheet" href="navigation.css">
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
        }
        body { 
            background: #f5f7fa; 
        }
        .container { 
            max-width: 800px; 
            margin: 2rem auto; 
            padding: 0 2rem;
            padding-top: 2rem;
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
        select, input[type="text"], input[type="file"] {
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
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #2980b9;
        }
        .btn-secondary {
            background: #95a5a6;
            margin-left: 1rem;
        }
        .btn-secondary:hover {
            background: #7f8c8d;
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
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <h2>Upload Course Video</h2>
            
            <?php if ($message): ?>
                <div class="message success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="course_id">Select Course:</label>
                    <select name="course_id" id="course_id" required onchange="updateLessons(this.value)">
                        <option value="">-- Select a Course --</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= $course['course_id'] ?>"><?= htmlspecialchars($course['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="lesson_id">Select Lesson:</label>
                    <select name="lesson_id" id="lesson_id" required>
                        <option value="">-- Select a Lesson --</option>
                        <option value="1">1.1 Introduction</option>
                        <option value="2">1.2 Lesson 1</option>
                        <option value="3">1.3 Lesson 2</option>
                        <option value="4">1.4 Lesson 3</option>
                        <option value="5">1.5 Assignment</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="video_title">Video Title:</label>
                    <input type="text" name="video_title" id="video_title" required placeholder="Enter video title (e.g., Introduction to Python)">
                </div>
                
                <div class="form-group">
                    <label for="video_file">Video File:</label>
                    <input type="file" name="video_file" id="video_file" accept="video/*" required>
                    <small style="color: #666; display: block; margin-top: 0.5rem;">
                        Supported formats: MP4, WebM, OGG, MOV (Max size: 500MB)
                    </small>
                </div>
                
                <button type="submit" name="upload_video" class="btn">Upload Video</button>
                <a href="admin_dashboard.php" class="btn btn-secondary">Cancel</a>
            </form>
            
            <a href="admin_dashboard.php" class="back-link">← Back to Admin Dashboard</a>
        </div>
    </div>

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
        
        // Function to update lessons based on course (optional enhancement)
        function updateLessons(courseId) {
            // You can enhance this to load course-specific lessons from database
            console.log('Selected course:', courseId);
        }
    </script>
</body>
</html>