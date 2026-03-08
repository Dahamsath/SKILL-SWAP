<?php
session_start();
include 'config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle quiz deletion
if (isset($_GET['delete_id'])) {
    $quiz_id = (int)$_GET['delete_id'];
    
    try {
        // Delete quiz responses first 
        $stmt = $pdo->prepare("DELETE FROM quiz_responses WHERE quiz_id = ?");
        $stmt->execute([$quiz_id]);
        
        // Delete quiz options
        $stmt = $pdo->prepare("DELETE qo FROM quiz_options qo JOIN quiz_questions qq ON qo.question_id = qq.id WHERE qq.quiz_id = ?");
        $stmt->execute([$quiz_id]);
        
        // Delete quiz questions
        $stmt = $pdo->prepare("DELETE FROM quiz_questions WHERE quiz_id = ?");
        $stmt->execute([$quiz_id]);
        
        // Delete the quiz itself
        $stmt = $pdo->prepare("DELETE FROM quizzes WHERE id = ?");
        $stmt->execute([$quiz_id]);
        
        header("Location: admin_manage_quizzes.php?message=Quiz deleted successfully");
        exit();
    } catch (Exception $e) {
        $error = "Error deleting quiz: " . $e->getMessage();
    }
}

// Handle viewing quiz details/answers
$show_quiz_details = false;
$quiz_details = null;
$questions_with_answers = [];
$student_responses = [];

if (isset($_GET['view_id'])) {
    $quiz_id = (int)$_GET['view_id'];
    $show_quiz_details = true;
    
    try {
        // Get quiz details
        $stmt = $pdo->prepare("
            SELECT q.*, c.title as course_title 
            FROM quizzes q 
            JOIN courses c ON q.course_id = c.course_id 
            WHERE q.id = ?
        ");
        $stmt->execute([$quiz_id]);
        $quiz_details = $stmt->fetch();
        
        if ($quiz_details) {
            // Get questions with correct answers
            $stmt = $pdo->prepare("
                SELECT qq.*, 
                    GROUP_CONCAT(qo.option_text ORDER BY qo.id SEPARATOR '|') as options_list,
                    GROUP_CONCAT(qo.is_correct ORDER BY qo.id SEPARATOR '|') as correct_flags
                FROM quiz_questions qq
                LEFT JOIN quiz_options qo ON qq.id = qo.question_id
                WHERE qq.quiz_id = ?
                GROUP BY qq.id
                ORDER BY qq.id
            ");
            $stmt->execute([$quiz_id]);
            $questions_with_answers = $stmt->fetchAll();
            
            // Get student responses with user info
            $stmt = $pdo->prepare("
                SELECT qr.*, u.name as student_name, u.email as student_email
                FROM quiz_responses qr
                JOIN users u ON qr.user_id = u.user_id
                WHERE qr.quiz_id = ?
                ORDER BY qr.submitted_at DESC
            ");
            $stmt->execute([$quiz_id]);
            $student_responses = $stmt->fetchAll();
        }
    } catch (Exception $e) {
        $error = "Error loading quiz details: " . $e->getMessage();
    }
}

// Fetch all quizzes with course information (for main list)
try {
    $stmt = $pdo->query("
        SELECT q.*, c.title as course_title 
        FROM quizzes q 
        JOIN courses c ON q.course_id = c.course_id 
        ORDER BY q.created_at DESC
    ");
    $quizzes = $stmt->fetchAll();
} catch (Exception $e) {
    $error = "Error loading quizzes: " . $e->getMessage();
    $quizzes = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Quizzes - Admin Dashboard</title>
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
            padding-top: 1rem;
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
        .btn-info {
            background: #3498db;
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
        
        /* Lesson mapping */
        .lesson-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
            background: #3498db;
            color: white;
        }
        
        /* Quiz details section */
        .quiz-details {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .quiz-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #3498db;
        }
        .quiz-title {
            color: #2c3e50;
            font-size: 1.5rem;
        }
        .quiz-info {
            margin-bottom: 1.5rem;
        }
        .quiz-info p {
            margin: 0.5rem 0;
            color: #555;
        }
        .questions-section, .responses-section {
            margin: 2rem 0;
        }
        .section-title {
            color: #2c3e50;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #ddd;
        }
        .question-item {
            background: white;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }
        .question-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        .question-text {
            font-size: 1.1rem;
            margin-bottom: 1rem;
            line-height: 1.5;
        }
        .options-list {
            list-style: none;
            padding: 0;
        }
        .option-item {
            padding: 0.5rem;
            margin: 0.25rem 0;
            background: #f8f9fa;
            border-radius: 4px;
            display: flex;
            align-items: center;
        }
        .correct-option {
            background: #d4edda;
            border-left: 4px solid #28a745;
        }
        .option-marker {
            width: 20px;
            height: 20px;
            border: 2px solid #3498db;
            border-radius: 50%;
            margin-right: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        .correct-marker {
            background: #28a745;
            color: white;
            border-color: #28a745;
        }
        .response-item {
            background: white;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            border-left: 4px solid #9b59b6;
        }
        .response-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        .student-info {
            color: #555;
        }
        .response-details {
            margin-top: 1rem;
        }
        .answer-row {
            display: flex;
            margin: 0.5rem 0;
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .answer-question {
            flex: 2;
            font-weight: bold;
        }
        .answer-response {
            flex: 1;
            color: #e74c3c;
        }
        .answer-correct {
            color: #27ae60;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }
            
            table {
                font-size: 0.9rem;
            }
            
            th, td {
                padding: 0.75rem;
            }
            
            .btn {
                padding: 0.4rem 0.8rem;
                font-size: 0.9rem;
            }
            
            .quiz-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .answer-row {
                flex-direction: column;
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
            <a href="index.php">Home</a>
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>

    <div class="container">
        <?php if ($show_quiz_details && $quiz_details): ?>
            <!-- Quiz Details View -->
            <div class="quiz-details">
                <div class="quiz-header">
                    <h2 class="quiz-title"><?= htmlspecialchars($quiz_details['title']) ?></h2>
                    <a href="admin_manage_quizzes.php" class="btn btn-primary">Back to Quizzes</a>
                </div>
                
                <div class="quiz-info">
                    <p><strong>Course:</strong> <?= htmlspecialchars($quiz_details['course_title']) ?></p>
                    <p><strong>Lesson:</strong> 
                        <?php 
                        $lesson_map = [
                            1 => '1.1 Introduction',
                            2 => '1.2 Lesson 1', 
                            3 => '1.3 Lesson 2',
                            4 => '1.4 Lesson 3'
                        ];
                        echo isset($lesson_map[$quiz_details['lesson_id']]) ? $lesson_map[$quiz_details['lesson_id']] : 'Lesson ' . $quiz_details['lesson_id'];
                        ?>
                    </p>
                    <p><strong>Description:</strong> <?= htmlspecialchars($quiz_details['description'] ?? 'No description') ?></p>
                    <p><strong>Created:</strong> <?= date('M j, Y', strtotime($quiz_details['created_at'])) ?></p>
                </div>
                
                <!-- Questions with Answers -->
                <div class="questions-section">
                    <h3 class="section-title">Questions & Correct Answers</h3>
                    <?php if (empty($questions_with_answers)): ?>
                        <p>No questions found for this quiz.</p>
                    <?php else: ?>
                        <?php foreach ($questions_with_answers as $index => $question): ?>
                            <div class="question-item">
                                <div class="question-header">
                                    <span><strong>Question <?= $index + 1 ?>:</strong></span>
                                    <span><?= $question['points'] ?> points</span>
                                </div>
                                <div class="question-text"><?= htmlspecialchars($question['question_text']) ?></div>
                                
                                <?php if ($question['question_type'] === 'multiple_choice' && !empty($question['options_list'])): ?>
                                    <ul class="options-list">
                                        <?php 
                                        $options = explode('|', $question['options_list']);
                                        $correct_flags = explode('|', $question['correct_flags']);
                                        $letters = ['A', 'B', 'C', 'D', 'E'];
                                        
                                        foreach ($options as $i => $option_text):
                                            $is_correct = isset($correct_flags[$i]) && $correct_flags[$i] == '1';
                                            $marker_class = $is_correct ? 'correct-marker' : '';
                                        ?>
                                            <li class="option-item <?= $is_correct ? 'correct-option' : '' ?>">
                                                <div class="option-marker <?= $marker_class ?>"><?= $letters[$i] ?? chr(65 + $i) ?></div>
                                                <span><?= htmlspecialchars($option_text) ?></span>
                                                <?php if ($is_correct): ?>
                                                    <span style="color: #27ae60; margin-left: 10px;">✓ (Correct Answer)</span>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php elseif ($question['question_type'] === 'true_false'): ?>
                                    <ul class="options-list">
                                        <li class="option-item <?= $question['correct_answer'] === 'True' ? 'correct-option' : '' ?>">
                                            <div class="option-marker <?= $question['correct_answer'] === 'True' ? 'correct-marker' : '' ?>">A</div>
                                            <span>True</span>
                                            <?php if ($question['correct_answer'] === 'True'): ?>
                                                <span style="color: #27ae60; margin-left: 10px;">✓ (Correct Answer)</span>
                                            <?php endif; ?>
                                        </li>
                                        <li class="option-item <?= $question['correct_answer'] === 'False' ? 'correct-option' : '' ?>">
                                            <div class="option-marker <?= $question['correct_answer'] === 'False' ? 'correct-marker' : '' ?>">B</div>
                                            <span>False</span>
                                            <?php if ($question['correct_answer'] === 'False'): ?>
                                                <span style="color: #27ae60; margin-left: 10px;">✓ (Correct Answer)</span>
                                            <?php endif; ?>
                                        </li>
                                    </ul>
                                <?php else: ?>
                                    <div class="option-item">
                                        <strong>Correct Answer:</strong> <?= htmlspecialchars($question['correct_answer']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Student Responses -->
                <div class="responses-section">
                    <h3 class="section-title">Student Responses</h3>
                    <?php if (empty($student_responses)): ?>
                        <p>No student responses found for this quiz.</p>
                    <?php else: ?>
                        <?php foreach ($student_responses as $response): ?>
                            <div class="response-item">
                                <div class="response-header">
                                    <div>
                                        <strong><?= htmlspecialchars($response['student_name']) ?></strong>
                                    </div>
                                    <div class="student-info">
                                        <span><?= htmlspecialchars($response['student_email']) ?></span><br>
                                        <small><?= date('M j, Y g:i A', strtotime($response['submitted_at'])) ?></small>
                                    </div>
                                </div>
                                
                                <div class="response-details">
                                    <?php
                                    // Get the specific question for this response
                                    $stmt = $pdo->prepare("SELECT question_text, question_type FROM quiz_questions WHERE id = ?");
                                    $stmt->execute([$response['question_id']]);
                                    $question_data = $stmt->fetch();
                                    
                                    if ($question_data):
                                    ?>
                                        <div class="answer-row">
                                            <div class="answer-question"><?= htmlspecialchars($question_data['question_text']) ?></div>
                                            <?php if ($question_data['question_type'] === 'multiple_choice'): ?>
                                                <?php
                                                // Get the selected option text
                                                $stmt = $pdo->prepare("SELECT option_text FROM quiz_options WHERE id = ?");
                                                $stmt->execute([$response['selected_option_id']]);
                                                $selected_option = $stmt->fetch();
                                                $selected_text = $selected_option ? $selected_option['option_text'] : 'No answer selected';
                                                ?>
                                                <div class="answer-response <?= $response['is_correct'] ? 'answer-correct' : '' ?>">
                                                    <?= htmlspecialchars($selected_text) ?>
                                                    <?= $response['is_correct'] ? '✓' : '✗' ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="answer-response <?= $response['is_correct'] ? 'answer-correct' : '' ?>">
                                                    <?= htmlspecialchars($response['answer_text']) ?>
                                                    <?= $response['is_correct'] ? '✓' : '✗' ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <a href="admin_manage_quizzes.php" class="back-link">← Back to Quizzes List</a>
            
        <?php else: ?>
            <!-- Main Quizzes List -->
            <div class="card">
                <h2>Manage Quizzes</h2>
                
                <?php if (isset($_GET['message'])): ?>
                    <div class="message success"><?= htmlspecialchars($_GET['message']) ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="message error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                
                
                <?php if (empty($quizzes)): ?>
                    <p style="margin-top: 1rem;">No quizzes found.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Quiz Title</th>
                                <th>Course</th>
                                <th>Lesson</th>
                                <th>Questions</th>
                                <th>Responses</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($quizzes as $quiz): ?>
                                <tr>
                                    <td><?= htmlspecialchars($quiz['title']) ?></td>
                                    <td><?= htmlspecialchars($quiz['course_title']) ?></td>
                                    <td>
                                        <?php 
                                        $lesson_map = [
                                            1 => '1.1 Introduction',
                                            2 => '1.2 Lesson 1', 
                                            3 => '1.3 Lesson 2',
                                            4 => '1.4 Lesson 3'
                                        ];
                                        echo isset($lesson_map[$quiz['lesson_id']]) ? '<span class="lesson-badge">' . $lesson_map[$quiz['lesson_id']] . '</span>' : 'Lesson ' . $quiz['lesson_id'];
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        // Count questions for this quiz
                                        $stmt = $pdo->prepare("SELECT COUNT(*) as question_count FROM quiz_questions WHERE quiz_id = ?");
                                        $stmt->execute([$quiz['id']]);
                                        $question_count = $stmt->fetch()['question_count'];
                                        echo $question_count;
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        // Count student responses for this quiz
                                        $stmt = $pdo->prepare("SELECT COUNT(*) as response_count FROM quiz_responses WHERE quiz_id = ?");
                                        $stmt->execute([$quiz['id']]);
                                        $response_count = $stmt->fetch()['response_count'];
                                        echo $response_count;
                                        ?>
                                    </td>
                                    <td><?= date('M j, Y', strtotime($quiz['created_at'])) ?></td>
                                    <td>
                                        <a href="admin_manage_quizzes.php?view_id=<?= $quiz['id'] ?>" 
                                        class="btn btn-info">
                                            View Answers
                                        </a>
                                        
                                        </a>
                                        <a href="admin_manage_quizzes.php?delete_id=<?= $quiz['id'] ?>" 
                                        class="btn btn-danger" 
                                        onclick="return confirm('Are you sure you want to delete this quiz? This action cannot be undone and will also delete all associated questions and student responses.')">
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
        <?php endif; ?>
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