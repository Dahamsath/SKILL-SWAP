<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Fetch courses for dropdown
$stmt = $pdo->query("SELECT course_id, title FROM courses ORDER BY title");
$courses = $stmt->fetchAll();

$message = '';
$error = '';

if ($_POST) {
    $course_id = (int)$_POST['course_id'];
    $lesson_id = (int)$_POST['lesson_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $questions = $_POST['questions'] ?? [];
    
    if (empty($course_id) || empty($lesson_id) || empty($title)) {
        $error = "Course, lesson, and title are required.";
    } else {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Insert quiz
            $stmt = $pdo->prepare("INSERT INTO quizzes (course_id, lesson_id, title, description) VALUES (?, ?, ?, ?)");
            $stmt->execute([$course_id, $lesson_id, $title, $description]);
            $quiz_id = $pdo->lastInsertId();
            
            // Insert questions and options
            foreach ($questions as $question_data) {
                if (!empty($question_data['text'])) {
                    $question_text = trim($question_data['text']);
                    $question_type = 'multiple_choice'; 
                    $points = (int)($question_data['points'] ?? 10);
                    
                    // Get the correct answer option text
                    $correct_option_index = $question_data['correct_option'] ?? 0;
                    $options = $question_data['options'] ?? [];
                    
                    if (empty($options) || !isset($options[$correct_option_index])) {
                        throw new Exception("Please provide at least one option and select a correct answer for question: '" . substr($question_text, 0, 30) . "...'");
                    }
                    
                    $correct_answer = trim($options[$correct_option_index]);
                    
                    // Insert question
                    $stmt = $pdo->prepare("INSERT INTO quiz_questions (quiz_id, question_text, question_type, correct_answer, points) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$quiz_id, $question_text, $question_type, $correct_answer, $points]);
                    $question_id = $pdo->lastInsertId();
                    
                    // Insert all options for multiple choice
                    foreach ($options as $option_text) {
                        if (!empty(trim($option_text))) {
                            $is_correct = (trim($option_text) === trim($correct_answer));
                            $stmt = $pdo->prepare("INSERT INTO quiz_options (question_id, option_text, is_correct) VALUES (?, ?, ?)");
                            $stmt->execute([$question_id, trim($option_text), $is_correct]);
                        }
                    }
                }
            }
            
            $pdo->commit();
            $message = "Quiz created successfully!";
        } catch (Exception $e) {
            $pdo->rollback();
            $error = "Error creating quiz: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Quiz - Admin Dashboard</title>
    <link rel="stylesheet" href="navigation.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: #f5f7fa; }
        .container { max-width: 800px; margin: 2rem auto; padding: 0 2rem; padding-top: 3rem; }
        .card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        h2 { color: #3498db; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: bold; color: #333; }
        select, input[type="text"], textarea { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem; }
        .btn { background: #3498db; color: white; padding: 0.75rem 2rem; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; font-weight: bold; text-decoration: none; display: inline-block; transition: background 0.3s; }
        .btn:hover { background: #2980b9; }
        .btn-secondary { background: #95a5a6; margin-left: 1rem; }
        .btn-secondary:hover { background: #7f8c8d; }
        .message { padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .back-link { display: inline-block; margin-top: 1rem; color: #3498db; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
        
        /* Quiz question styles */
        .question-item { background: #f8f9fa; padding: 1rem; margin-bottom: 1rem; border-radius: 4px; border-left: 4px solid #3498db; }
        .question-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; }
        .question-text { margin-bottom: 0.5rem; }
        .options-list { margin-bottom: 0.5rem; }
        .option-item { margin-bottom: 0.25rem; display: flex; align-items: center; }
        .remove-question { background: #e74c3c; color: white; border: none; padding: 0.25rem 0.5rem; border-radius: 4px; cursor: pointer; font-size: 0.8rem; }
        .add-question-btn { background: #27ae60; margin-top: 1rem; }
        
        /* Correct answer selector */
        .correct-answer-section {
            margin-top: 1rem;
            padding: 1rem;
            background: #e8f4ea;
            border-radius: 4px;
        }
        .correct-answer-label {
            font-weight: bold;
            color: #27ae60;
            margin-bottom: 0.5rem;
        }
        .correct-answer-select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
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
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <h2>Add New Quiz</h2>
            
            <?php if ($message): ?>
                <div class="message success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" id="quizForm">
                <div class="form-group">
                    <label>Course *</label>
                    <select name="course_id" required>
                        <option value="">-- Select Course --</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= $course['course_id'] ?>" <?= (isset($_POST['course_id']) && $_POST['course_id'] == $course['course_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($course['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Lesson *</label>
                    <select name="lesson_id" required>
                        <option value="">-- Select Lesson --</option>
                        <option value="1" <?= (isset($_POST['lesson_id']) && $_POST['lesson_id'] == 1) ? 'selected' : '' ?>>1.1 Introduction</option>
                        <option value="2" <?= (isset($_POST['lesson_id']) && $_POST['lesson_id'] == 2) ? 'selected' : '' ?>>1.2 Lesson 1</option>
                        <option value="3" <?= (isset($_POST['lesson_id']) && $_POST['lesson_id'] == 3) ? 'selected' : '' ?>>1.3 Lesson 2</option>
                        <option value="4" <?= (isset($_POST['lesson_id']) && $_POST['lesson_id'] == 4) ? 'selected' : '' ?>>1.4 Lesson 3</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Quiz Title *</label>
                    <input type="text" name="title" required placeholder="Enter quiz title" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3" placeholder="Enter quiz description (optional)"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>
                
                <h3 style="margin: 2rem 0 1rem 0; color: #333;">Questions</h3>
                <div id="questionsContainer">
                    <!-- Questions will be added here dynamically -->
                    <div class="question-item">
                        <div class="question-header">
                            <h4>Question 1</h4>
                            <button type="button" class="remove-question" onclick="removeQuestion(this)">Remove</button>
                        </div>
                        <div class="form-group">
                            <label>Question Text *</label>
                            <textarea name="questions[0][text]" required placeholder="Enter question text" rows="2"><?= htmlspecialchars($_POST['questions'][0]['text'] ?? '') ?></textarea>
                        </div>
                        
                        <!-- Options Section -->
                        <div class="form-group">
                            <label>Answer Options *</label>
                            <div class="options-list" id="options-list-0">
                                <div class="option-item">
                                    <input type="text" name="questions[0][options][]" placeholder="Option A" value="<?= htmlspecialchars($_POST['questions'][0]['options'][0] ?? '') ?>" style="flex: 1; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;" required>
                                </div>
                                <div class="option-item">
                                    <input type="text" name="questions[0][options][]" placeholder="Option B" value="<?= htmlspecialchars($_POST['questions'][0]['options'][1] ?? '') ?>" style="flex: 1; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;" required>
                                </div>
                            </div>
                            <button type="button" class="btn" style="background: #f39c12; margin-top: 0.5rem;" onclick="addOption(0)">+ Add Option</button>
                        </div>
                        
                        <!-- Correct Answer Selection -->
                        <div class="correct-answer-section">
                            <div class="correct-answer-label">Select Correct Answer:</div>
                            <select name="questions[0][correct_option]" class="correct-answer-select" required>
                                <option value="">-- Select Correct Option --</option>
                                <option value="0" <?= (isset($_POST['questions'][0]['correct_option']) && $_POST['questions'][0]['correct_option'] == '0') ? 'selected' : '' ?>>Option A</option>
                                <option value="1" <?= (isset($_POST['questions'][0]['correct_option']) && $_POST['questions'][0]['correct_option'] == '1') ? 'selected' : '' ?>>Option B</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Points</label>
                            <input type="number" name="questions[0][points]" value="<?= $_POST['questions'][0]['points'] ?? 10 ?>" min="1" max="100">
                        </div>
                    </div>
                </div>
                
                <button type="button" class="btn add-question-btn" onclick="addQuestion()">+ Add Question</button>
                <br><br>
                <button type="submit" class="btn">Create Quiz</button>
                <a href="admin_dashboard.php" class="btn btn-secondary">Cancel</a>
            </form>
            
            <a href="admin_dashboard.php" class="back-link">← Back to Admin Dashboard</a>
        </div>
    </div>

    <script>
        let questionCount = 1;
        let optionCounts = [2]; // Track number of options per question
        
        function addQuestion() {
            const container = document.getElementById('questionsContainer');
            const newQuestion = document.createElement('div');
            newQuestion.className = 'question-item';
            newQuestion.innerHTML = `
                <div class="question-header">
                    <h4>Question ${questionCount + 1}</h4>
                    <button type="button" class="remove-question" onclick="removeQuestion(this)">Remove</button>
                </div>
                <div class="form-group">
                    <label>Question Text *</label>
                    <textarea name="questions[${questionCount}][text]" required placeholder="Enter question text" rows="2"></textarea>
                </div>
                
                <!-- Options Section -->
                <div class="form-group">
                    <label>Answer Options *</label>
                    <div class="options-list" id="options-list-${questionCount}">
                        <div class="option-item">
                            <input type="text" name="questions[${questionCount}][options][]" placeholder="Option A" style="flex: 1; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;" required>
                        </div>
                        <div class="option-item">
                            <input type="text" name="questions[${questionCount}][options][]" placeholder="Option B" style="flex: 1; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;" required>
                        </div>
                    </div>
                    <button type="button" class="btn" style="background: #f39c12; margin-top: 0.5rem;" onclick="addOption(${questionCount})">+ Add Option</button>
                </div>
                
                <!-- Correct Answer Selection -->
                <div class="correct-answer-section">
                    <div class="correct-answer-label">Select Correct Answer:</div>
                    <select name="questions[${questionCount}][correct_option]" class="correct-answer-select" required>
                        <option value="">-- Select Correct Option --</option>
                        <option value="0">Option A</option>
                        <option value="1">Option B</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Points</label>
                    <input type="number" name="questions[${questionCount}][points]" value="10" min="1" max="100">
                </div>
            `;
            container.appendChild(newQuestion);
            optionCounts.push(2); 
            questionCount++;
            
            // Update correct answer dropdown for new question
            updateCorrectAnswerDropdown(questionCount - 1);
        }
        
        function removeQuestion(button) {
            if (document.querySelectorAll('.question-item').length > 1) {
                button.closest('.question-item').remove();
            } else {
                alert('At least one question is required.');
            }
        }
        
        function addOption(questionIndex) {
            const optionsList = document.getElementById(`options-list-${questionIndex}`);
            const optionIndex = optionCounts[questionIndex];
            const optionLetter = String.fromCharCode(65 + optionIndex); // A, B, C, D...
            
            const optionItem = document.createElement('div');
            optionItem.className = 'option-item';
            optionItem.innerHTML = `
                <input type="text" name="questions[${questionIndex}][options][]" placeholder="Option ${optionLetter}" style="flex: 1; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;" required>
            `;
            
            optionsList.appendChild(optionItem);
            optionCounts[questionIndex]++;
            
            // correct answer dropdown
            updateCorrectAnswerDropdown(questionIndex);
        }
        
        function updateCorrectAnswerDropdown(questionIndex) {
            const select = document.querySelector(`select[name="questions[${questionIndex}][correct_option]"]`);
            if (select) {
                while (select.options.length > 1) {
                    select.remove(1);
                }
                
                // Add options based on current option count
                for (let i = 0; i < optionCounts[questionIndex]; i++) {
                    const optionLetter = String.fromCharCode(65 + i);
                    const option = document.createElement('option');
                    option.value = i;
                    option.textContent = `Option ${optionLetter}`;
                    select.appendChild(option);
                }
            }
        }
        
        // Initialize correct answer dropdowns on page load
        document.addEventListener('DOMContentLoaded', function() {
            // dropdown for the first question
            updateCorrectAnswerDropdown(0);
            
            // Add event listeners to add/remove buttons
            document.querySelectorAll('.add-option-btn').forEach((btn, index) => {
                btn.addEventListener('click', () => addOption(index));
            });
        });
    </script>
</body>
</html>