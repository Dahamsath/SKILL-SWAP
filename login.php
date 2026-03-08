<?php
session_start();
include 'config.php';
$error = '';

if ($_POST) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Email and password are required.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['student_id'] = $user['student_id']; // Add student_id to session

            // Redirect based on role
            switch($user['role']) {
                case 'admin':
                    header("Location: admin_dashboard.php");
                    break;
                
                default:
                    header("Location: student_dashboard.php");
            }
            exit();
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SKILL SWAP</title>
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



        .error { color: #e74c3c; margin-bottom: 1rem; text-align: center; }
    </style>
    
</head>
<body>
    <div class="background"></div>
    <div class="container">
        <div class="glass-card">
        <h2>Login</h2>
        <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
        <div class="footer">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
    </div>
</body>
</html>