<?php
include 'config.php';
$error = '';
$success = '';

if ($_POST) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($name) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "Email already registered.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            
            // Generate unique student ID
            $student_id = 'STU' . time() . rand(100, 999);
            
            // Insert new user with student_id
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, student_id) VALUES (?, ?, ?, 'student', ?)");
            if ($stmt->execute([$name, $email, $hashed, $student_id])) {
                $success = "Registration successful! Please login.";
            } else {
                $error = "Registration failed. Please try again.";
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
    <title>Register - SKILL SWAP</title>
    <style>
         *{
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family:  'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    overflow: hidden;
}

.background {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: url('https://i.pinimg.com/1200x/c8/df/02/c8df02b87f7aa286afcdca8ab344a133.jpg  ');
    background-size: cover;
    background-position:center;
    filter: brightness(0.7);
    z-index: -1;
}

.container {
    width: 100%;
    max-width: 400px;
    padding: 20px;
}

.glass-card{
    backdrop-filter: blur(15px);
    background: rgba(255, 255, 255,0.1);
    border: 1px solid rgba(255, 255, 255,0.3);
    padding: 30px 25px;
    border-radius: 16px;
    box-shadow: 0  8px 32px rgba(0, 0, 0,0.2);
    color: white;
    animation: fadeInUp 0.6s ease-out;
}

.glass-card h2 {
    font-size:24px;
    font-weight: 700;
    margin-bottom: 10px;

}

.glass-card p {
    font-size: 14px;
    margin-bottom: 25px;
    opacity: 0.8;
}

.glass-card label {
    display: block;
    margin-bottom: 6px;
    font-size: 14px;
}

.glass-card input{
    width: 100%;
    padding: 12px 14px;
    margin-bottom: 20px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    outline: none
}

.glass-card input::placeholder{
    color: rgba(255, 255, 255, 0.6);
}

.glass-card button {
    width: 100%;
    background-color: rgba(255, 255, 255,0.2);
    color: white;
    padding: 12px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.3s ease;
}

.glass-card button:hover {
    background-color: rgba(255, 255, 255,0.3);
}

.footer {
    margin-top: 20px;
    text-align: center;
    font-size: 14px;
}

.footer a {
    color: #fff;
    text-decoration: underline;
    font-weight: 500;
}

.footer a:hover{
    opacity: 0.8;
}

@keyframes fadeInUp {
    from{
        opacity: 0;
        transform: translateY(20px);
    }
    to{
        opacity: 1;
        transform: translateY(0);
    }
}
        .success { color: #236f23ff; margin-bottom: 1rem; text-align: center; }
        .error { color: #e74c3c; margin-bottom: 1rem; text-align: center; }
    
    </style>
</head>
<body>
    <div class="background"></div>
        <div class="container">
            <div class="glass-card">
        <h2>Student Registration</h2>
        <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
        <?php if ($success): ?><div class="success"><?= $success ?></div><?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <br>
            <button type="submit" class="btn">Register</button>
            <div class="footer">
            Already have an account? <a href="login.php">Login here</a>
        </div>
        </form>
    </div>
</body>
</html>