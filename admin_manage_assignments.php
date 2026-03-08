<?php
// admin_manage_assignments.php - Admin Assignment Grading Page
session_start();
include 'config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

// Handle grading submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grade_assignment'])) {
    $submission_id = (int)$_POST['submission_id'];
    $points = (int)$_POST['points'];
    $feedback = trim($_POST['feedback']);
    $max_points = (int)$_POST['max_points'];

    // Validate points
    if ($points < 0 || $points > $max_points) {
        $error = "Points must be between 0 and {$max_points}";
    } else {
        // Update submission
        $stmt = $pdo->prepare("UPDATE assignment_submissions SET points_awarded = ?, feedback = ?, status = 'graded', graded_at = NOW() WHERE id = ?");
        if ($stmt->execute([$points, $feedback, $submission_id])) {
            $success = "Assignment graded successfully!";
        } else {
            $error = "Failed to grade assignment: " . implode(', ', $stmt->errorInfo());
        }
    }
}

// Get all pending assignments
$stmt = $pdo->prepare("
    SELECT 
        s.id as submission_id,
        s.submission_file,
        s.submitted_at,
        s.points_awarded,
        s.feedback,
        s.status,
        s.graded_at,
        a.title as assignment_title,
        a.max_points,
        u.name as student_name,
        u.email as student_email,
        c.title as course_title
    FROM assignment_submissions s
    JOIN assignments a ON s.assignment_id = a.id
    JOIN users u ON s.user_id = u.user_id
    JOIN courses c ON a.course_id = c.course_id
    WHERE s.status = 'pending'
    ORDER BY s.submitted_at DESC
");
$stmt->execute();
$pending_submissions = $stmt->fetchAll();

// Get all graded assignments
$stmt = $pdo->prepare("
    SELECT 
        s.id as submission_id,
        s.submission_file,
        s.submitted_at,
        s.points_awarded,
        s.feedback,
        s.status,
        s.graded_at,
        a.title as assignment_title,
        a.max_points,
        u.name as student_name,
        u.email as student_email,
        c.title as course_title
    FROM assignment_submissions s
    JOIN assignments a ON s.assignment_id = a.id
    JOIN users u ON s.user_id = u.user_id
    JOIN courses c ON a.course_id = c.course_id
    WHERE s.status = 'graded'
    ORDER BY s.graded_at DESC
");
$stmt->execute();
$graded_submissions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade Assignments - Admin Dashboard | SKILL SWAP</title>
    <link rel="stylesheet" href="navigation.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: #f5f7fa; }
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 2rem; }
        .card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        h2 { color: #3498db; margin-bottom: 1.5rem; }
        .tabs { display: flex; margin-bottom: 2rem; }
        .tab { padding: 0.75rem 1.5rem; cursor: pointer; background: #f0f0f0; border: none; font-weight: bold; }
        .tab.active { background: #3498db; color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .submission-card { 
            border: 1px solid #e0e0e0; 
            border-radius: 6px; 
            padding: 1.5rem; 
            margin-bottom: 1rem;
            transition: all 0.3s;
        }
        .submission-card:hover { box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .submission-header { display: flex; justify-content: space-between; margin-bottom: 1rem; }
        .submission-info { flex: 1; }
        .submission-actions { flex: 0 0 auto; }
        .student-name { font-weight: bold; color: #2c3e50; }
        .course-title { color: #7f8c8d; }
        .assignment-title { font-weight: 600; color: #2980b9; }
        .status-badge {
            padding: 0.2em 0.6em;
            border-radius: 12px;
            font-size: 0.8em;
            text-transform: uppercase;
        }
        .status-pending { background: #f39c12; color: white; }
        .status-graded { background: #27ae60; color: white; }
        .file-link {
            display: inline-flex;
            align-items: center;
            color: #3498db;
            text-decoration: none;
            font-size: 0.9em;
        }
        .file-link:hover { text-decoration: underline; }
        .file-link i { margin-right: 4px; }

        /* Form styling */
        .grade-form {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 6px;
            margin-top: 1rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }
        .btn:hover { background: #2980b9; }
        .btn-secondary {
            background: #95a5a6;
            margin-left: 0.5rem;
        }
        .btn-secondary:hover { background: #7f8c8d; }

        /* Alert messages */
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* Responsive */
        @media (max-width: 768px) {
            .submission-header { flex-direction: column; gap: 1rem; }
            .submission-actions { width: 100%; text-align: right; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Assignment Grading Center</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab active" data-tab="pending">Pending Reviews (<?= count($pending_submissions) ?>)</button>
            <button class="tab" data-tab="graded">Graded (<?= count($graded_submissions) ?>)</button>
        </div>

        <div class="tab-content active" id="tab-pending">
            <?php if (count($pending_submissions) > 0): ?>
                <?php foreach ($pending_submissions as $submission): ?>
                    <div class="submission-card">
                        <div class="submission-header">
                            <div class="submission-info">
                                <div class="student-name"><?= htmlspecialchars($submission['student_name']) ?></div>
                                <div class="course-title"><?= htmlspecialchars($submission['course_title']) ?></div>
                                <div class="assignment-title"><?= htmlspecialchars($submission['assignment_title']) ?></div>
                                <div>
                                    Submitted: <?= date('M j, Y H:i', strtotime($submission['submitted_at'])) ?>
                                    <span class="status-badge status-pending">Pending Review</span>
                                </div>
                            </div>
                            <div class="submission-actions">
                                <a href="<?= htmlspecialchars($submission['submission_file']) ?>" target="_blank" class="file-link">
                                    <i class="fas fa-file-download"></i> Download Submission
                                </a>
                            </div>
                        </div>
                        
                        <div class="grade-form">
                            <form method="POST">
                                <input type="hidden" name="submission_id" value="<?= $submission['submission_id'] ?>">
                                <input type="hidden" name="max_points" value="<?= $submission['max_points'] ?>">
                                
                                <div class="form-group">
                                    <label for="points_<?= $submission['submission_id'] ?>">Score (0–<?= $submission['max_points'] ?>)</label>
                                    <input type="number" id="points_<?= $submission['submission_id'] ?>" 
                                        name="points" 
                                        min="0" 
                                        max="<?= $submission['max_points'] ?>" 
                                        value="0" 
                                        required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="feedback_<?= $submission['submission_id'] ?>">Feedback (Optional)</label>
                                    <textarea id="feedback_<?= $submission['submission_id'] ?>" 
                                            name="feedback" 
                                            placeholder="Provide constructive feedback..."></textarea>
                                </div>
                                
                                <button type="submit" name="grade_assignment" class="btn">Grade Assignment</button>
                                <button type="button" class="btn btn-secondary" onclick="this.closest('form').reset()">Reset</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                    <h3>No Pending Assignments</h3>
                    <p>All assignments have been graded or no students have submitted yet.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="tab-content" id="tab-graded">
            <?php if (count($graded_submissions) > 0): ?>
                <?php foreach ($graded_submissions as $submission): ?>
                    <div class="submission-card">
                        <div class="submission-header">
                            <div class="submission-info">
                                <div class="student-name"><?= htmlspecialchars($submission['student_name']) ?></div>
                                <div class="course-title"><?= htmlspecialchars($submission['course_title']) ?></div>
                                <div class="assignment-title"><?= htmlspecialchars($submission['assignment_title']) ?></div>
                                <div>
                                    Graded: <?= date('M j, Y H:i', strtotime($submission['graded_at'])) ?>
                                    <span class="status-badge status-graded">Graded</span>
                                </div>
                            </div>
                            <div class="submission-actions">
                                <a href="<?= htmlspecialchars($submission['submission_file']) ?>" target="_blank" class="file-link">
                                    <i class="fas fa-file-download"></i> Download Submission
                                </a>
                            </div>
                        </div>
                        
                        <div style="margin-top: 1rem;">
                            <div><strong>Score:</strong> <?= $submission['points_awarded'] ?>/<?= $submission['max_points'] ?> points</div>
                            <?php if (!empty($submission['feedback'])): ?>
                                <div style="margin: 0.5rem 0;"><strong>Feedback:</strong></div>
                                <div style="background: #f8f9fa; padding: 0.75rem; border-radius: 4px; white-space: pre-wrap;">
                                    <?= nl2br(htmlspecialchars($submission['feedback'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <i class="fas fa-check-circle" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                    <h3>No Graded Assignments</h3>
                    <p>You haven't graded any assignments yet.</p>
                </div>
            <?php endif; ?>
        </div>

        <a href="admin_dashboard.php" class="back-link" style="display: inline-flex; align-items: center; gap: 8px; color: #3498db; text-decoration: none; margin-top: 2rem; font-weight: 600;">
            <i class="fas fa-arrow-left"></i> Back to Admin Dashboard
        </a>
    </div>

    <!-- Tabs JavaScript -->
    <script>
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                tab.classList.add('active');
                const tabId = tab.getAttribute('data-tab');
                document.getElementById(`tab-${tabId}`).classList.add('active');
            });
        });
    </script>
    
    <!-- Font Awesome -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>