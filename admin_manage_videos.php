<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle video deletion
if (isset($_GET['delete_id'])) {
    $video_id = (int)$_GET['delete_id'];
    
    try {
        // Get video filepath before deletion
        $stmt = $pdo->prepare("SELECT filepath FROM videos WHERE id = ?");
        $stmt->execute([$video_id]);
        $video = $stmt->fetch();
        
        if ($video) {
            // Delete file from server
            if (file_exists($video['filepath'])) {
                unlink($video['filepath']);
            }
            
            // Delete from database
            $stmt = $pdo->prepare("DELETE FROM videos WHERE id = ?");
            $stmt->execute([$video_id]);
            
            header("Location: admin_manage_videos.php?message=Video deleted successfully");
            exit();
        }
    } catch (Exception $e) {
        $error = "Error deleting video: " . $e->getMessage();
    }
}

// Fetch all videos with course information
try {
    $stmt = $pdo->query("
        SELECT v.*, c.title as course_title 
        FROM videos v 
        JOIN courses c ON v.course_id = c.course_id 
        ORDER BY v.created_at DESC
    ");
    $videos = $stmt->fetchAll();
} catch (Exception $e) {
    $error = "Error loading videos: " . $e->getMessage();
    $videos = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Videos - Admin Dashboard</title>
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
            max-width: 1200px; 
            margin: 2rem auto; 
            padding: 0 2rem;
        }
        .card {
            background: white;
            padding: 3rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        h2 {
            color: #3498db;
            margin-bottom: 1.5rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }
        th {
            background: #3498db;
            color: white;
        }
        tr:last-child td {
            border-bottom: none;
        }
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
            margin: 0.2rem;
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        .btn:hover {
            opacity: 0.9;
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
            <h2>Manage Course Videos</h2>
            
            <?php if (isset($_GET['message'])): ?>
                <div class="message success"><?= htmlspecialchars($_GET['message']) ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="message error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <a href="admin_upload_video.php" class="btn btn-primary">Upload New Video</a>
            
            <?php if (empty($videos)): ?>
                <p style="margin-top: 1rem;">No videos found.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Video Title</th>
                            <th>Course</th>
                            <th>File</th>
                            <th>Uploaded</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($videos as $video): ?>
                            <tr>
                                <td><?= htmlspecialchars($video['title']) ?></td>
                                <td><?= htmlspecialchars($video['course_title']) ?></td>
                                <td><?= htmlspecialchars($video['filename']) ?></td>
                                <td><?= date('M j, Y', strtotime($video['created_at'])) ?></td>
                                <td>
                                    <a href="admin_manage_videos.php?delete_id=<?= $video['id'] ?>" 
                                    class="btn btn-danger" 
                                    onclick="return confirm('Are you sure you want to delete this video? This action cannot be undone.')">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
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
    </script>
</body>
</html>