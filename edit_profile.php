<?php
session_start();
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Fetch current user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        header("Location: login.php");
        exit();
    }
    
    $student_name = $user['name'] ?? 'John Doe';
    $email = $user['email'] ?? 'john.doe@example.com';
    $country = $user['country'] ?? 'Sri Lanka';
    $city = $user['city'] ?? 'Colombo';
    $profile_pic = !empty($user['profile_pic']) ? $user['profile_pic'] : 'images/default-profile.png';
} catch (PDOException $e) {
    // Fallback to placeholder data if database query fails
    $student_name = "John Doe";
    $email = "john.doe@example.com";
    $country = "Sri Lanka";
    $city = "Colombo";
    $profile_pic = "images/default-profile.png";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_name = trim($_POST['name']);
    $new_email = trim($_POST['email']);
    $new_country = trim($_POST['country']);
    $new_city = trim($_POST['city']);
    
    // Validate input
    if (empty($new_name) || empty($new_email)) {
        $error_message = "Name and email are required fields.";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        try {
            // profile picture upload
            if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $file_type = $_FILES['profile_pic']['type'];
                
                if (in_array($file_type, $allowed_types)) {
                    $upload_dir = 'uploads/profiles/';
                    
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $file_extension = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
                    $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_path)) {
                        $profile_pic = $upload_path;
                    }
                }
            }
            
            // Update user information in database
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, country = ?, city = ?, profile_pic = ? WHERE user_id = ?");
            $stmt->execute([$new_name, $new_email, $new_country, $new_city, $profile_pic, $user_id]);
            
            $success_message = "Profile updated successfully!";
            
            // Update session variables
            $student_name = $new_name;
            $email = $new_email;
            $country = $new_country;
            $city = $new_city;
            
        } catch (PDOException $e) {
            $error_message = "Error updating profile: " . $e->getMessage();
            // For debugging - remove in production
            error_log("Profile update error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - SKILL SWAP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="footer.css">
    <style>
        :root {
            --primary-color: rgb(12, 12, 30);
            --secondary-color: #3498db;
            --accent-color: #27ae60;
            --light-bg: #f8f9fa;
            --white: #ffffff;
            --gray-light: #e9ecef;
            --gray-dark: #495057;
            --error-color: #e74c3c;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: var(--light-bg);
            padding-top: 80px;
        }

        /* Navigation */
        .navbar {
            background: rgb(44, 62, 80);
            padding: 1rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--primary-color);
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .logo-placeholder {
            font-weight: bold;
            font-size: 1.3rem;
            letter-spacing: 1px;
            color: var(--white);
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--white);
            font-weight: 500;
            transition: var(--transition);
        }

        .nav-links a:hover {
            color: var(--secondary-color);
        }

        /* Main Container */
        .edit-profile-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .page-title {
            color: var(--primary-color);
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 2rem;
            text-align: center;
        }

        /* Alert Messages */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-success {
            background: rgba(39, 174, 96, 0.1);
            color: var(--accent-color);
            border: 1px solid var(--accent-color);
        }

        .alert-error {
            background: rgba(231, 76, 60, 0.1);
            color: var(--error-color);
            border: 1px solid var(--error-color);
        }

        /* Form Card */
        .form-card {
            background: var(--white);
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }

        /* Profile Picture Section */
        .profile-pic-section {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid var(--gray-light);
        }

        .current-profile-pic {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 1rem;
            border: 4px solid var(--secondary-color);
            display: block;
        }

        .upload-label {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: var(--secondary-color);
            color: var(--white);
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 500;
        }

        .upload-label:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .upload-label i {
            margin-right: 0.5rem;
        }

        #profile_pic {
            display: none;
        }

        .file-name {
            margin-top: 0.5rem;
            color: var(--gray-dark);
            font-size: 0.9rem;
        }

        /* Form Groups */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .form-input {
            width: 100%;
            padding: 0.875rem;
            border: 2px solid var(--gray-light);
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
            background: var(--white);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .form-input:disabled {
            background: var(--gray-light);
            cursor: not-allowed;
        }

        /* Button Group */
        .button-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            justify-content: center;
        }

        .btn {
            padding: 0.875rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: var(--secondary-color);
            color: var(--white);
        }

        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        }

        .btn-secondary {
            background: var(--gray-light);
            color: var(--gray-dark);
        }

        .btn-secondary:hover {
            background: #dee2e6;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .edit-profile-container {
                padding: 0 1rem;
            }

            .form-card {
                padding: 1.5rem;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .button-group {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>

    <!-- Navigation -->
    <nav class="navbar">
        <a href="index.php" class="logo-placeholder">SKILL SWAP</a>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="courses.php">Courses</a>
            <a href="profile.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="edit-profile-container">
        <h1 class="page-title">Edit Profile</h1>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST" enctype="multipart/form-data">
                <!-- Profile Picture Section -->
                <div class="profile-pic-section">
                    <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile Picture" class="current-profile-pic" id="profilePreview">
                    <label for="profile_pic" class="upload-label">
                        <i class="fas fa-camera"></i> Change Profile Picture
                    </label>
                    <input type="file" id="profile_pic" name="profile_pic" accept="image/*">
                    <div class="file-name" id="fileName"></div>
                </div>

                <!-- Personal Information Fields -->
                <div class="form-group">
                    <label for="name" class="form-label">Full Name *</label>
                    <input type="text" id="name" name="name" class="form-input" value="<?php echo htmlspecialchars($student_name); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email Address *</label>
                    <input type="email" id="email" name="email" class="form-input" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>

                <div class="form-group">
                    <label for="country" class="form-label">Country</label>
                    <input type="text" id="country" name="country" class="form-input" value="<?php echo htmlspecialchars($country); ?>">
                </div>

                <div class="form-group">
                    <label for="city" class="form-label">City/Town</label>
                    <input type="text" id="city" name="city" class="form-input" value="<?php echo htmlspecialchars($city); ?>">
                </div>

                <!-- Buttons -->
                <div class="button-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a href="profile.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Preview profile picture before upload
        document.getElementById('profile_pic').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profilePreview').src = e.target.result;
                };
                reader.readAsDataURL(file);
                document.getElementById('fileName').textContent = file.name;
            }
        });
    </script>

</body>
</html>
