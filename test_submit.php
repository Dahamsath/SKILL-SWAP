<?php
session_start();
include 'config.php';

// Simulate submission
$assignment_id = 1; 
$user_id = $_SESSION['user_id'] ?? 1;

// Manual file upload simulation
$_FILES['submission_file'] = [
    'name' => 'test.pdf',
    'type' => 'application/pdf',
    'tmp_name' => '/tmp/test.pdf',
    'error' => 0,
    'size' => 1024
];

file_put_contents('/tmp/test.pdf', 'TEST ASSIGNMENT FILE');

error_log("TEST: Starting assignment submission...");

$upload_dir = 'submissions/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

$unique_name = 'sub_' . $user_id . '_' . $assignment_id . '_test.pdf';
$target_path = $upload_dir . $unique_name;

if (move_uploaded_file('/tmp/test.pdf', $target_path)) {
    $stmt = $pdo->prepare("INSERT INTO assignment_submissions (assignment_id, user_id, submission_file, submitted_at, status) VALUES (?, ?, ?, NOW(), 'pending')");
    if ($stmt->execute([$assignment_id, $user_id, $target_path])) {
        echo "✅ Success! File saved to: $target_path";
    } else {
        echo "❌ DB Error: " . implode(', ', $stmt->errorInfo());
    }
} else {
    echo "❌ Failed to move file";
}
?>