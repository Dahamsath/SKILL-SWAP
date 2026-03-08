<?php
// about.php - About Us page for SKILL SWAP
session_start();
include 'config.php'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - SKILL SWAP</title>
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
            transition: var(--transition);
        }

        .logo-placeholder {
            font-weight: bold;
            font-size: 1.3rem;
            letter-spacing: 1px;
            color: rgb(44,44,44,);
            text-decoration: none;
        }

        .btn-login:hover, .btn-register:hover {
            background: var(--secondary-color);
            color: white;
        }

        .mobile-toggle {
            display: none;
            flex-direction: column;
            cursor: pointer;
            padding: 0.5rem;
        }

        .mobile-toggle span {
            width: 25px;
            height: 3px;
            background: var(--primary-color);
            margin: 3px 0;
            transition: var(--transition);
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 1rem;
                flex-wrap: wrap;
            }

            .mobile-toggle {
                display: flex;
            }

            .navbar-links {
                display: none;
                width: 100%;
                flex-direction: column;
                gap: 0.5rem;
                margin-top: 1rem;
                background: var(--white);
                border-radius: 8px;
                padding: 1rem;
                box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            }

            .navbar-links.active {
                display: flex;
            }

            .navbar-links a {
                width: 100%;
                text-align: center;
                padding: 0.75rem;
            }
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color), #1a2a3a);
            color: var(--white);
            padding: 5rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
            margin-bottom: 3rem;
        }

        .hero-section::before {
            content: "";
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(52, 152, 219, 0.15);
        }

        .hero-section::after {
            content: "";
            position: absolute;
            bottom: -80px;
            left: -80px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(39, 174, 96, 0.15);
        }

        .hero-content {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }

        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }

        .hero-subtitle {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            line-height: 1.6;
        }

        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--secondary-color);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1rem;
            opacity: 0.8;
        }

        /* Page Content */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .section {
            margin-bottom: 0rem;
        }

        .section-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .section-header h2 {
            color: var(--primary-color);
            font-size: 2rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .section-header p {
            color: var(--gray-dark);
            font-size: 1.1rem;
        }

        /* Mission & Vision Cards */
        .mission-vision-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .card {
            background: var(--white);
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card i {
            font-size: 2.5rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }

        .card h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        /* Leadership Team */
        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .team-member {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            text-align: center;
        }

        .team-member img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1rem;
            background: var(--gray-light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray-dark);
            font-size: 2rem;
        }

        .team-member h4 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .team-member p {
            color: var(--gray-dark);
            font-size: 0.9rem;
        }

        /* Testimonials */
        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .testimonial-card {
            background: var(--white);
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            text-align: center;
        }

        .testimonial-card p {
            color: var(--gray-dark);
            font-style: italic;
            margin-bottom: 1rem;
        }

        .testimonial-card .quote-icon {
            font-size: 2rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }

        .testimonial-card .student-name {
            color: var(--primary-color);
            font-weight: 600;
        }

        /* CTA Section */
        .cta-section {
            text-align: center;
            padding: 3rem 2rem;
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .cta-section h2 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-size: 1.8rem;
        }

        .cta-section p {
            color: var(--gray-dark);
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
        }

        .btn {
            display: inline-block;
            background: var(--secondary-color);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            text-decoration: none;
            font-weight: bold;
            font-size: 1rem;
            transition: background-color 0.3s, transform 0.2s;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }

        /* Footer */
        .footer {
            background: var(--primary-color);
            color: var(--white);
            padding: 4rem 0 2rem;
            margin-top: 4rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .footer .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 5%;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-column h4 {
            color: var(--white);
            font-size: 1.1rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            padding-bottom: 0.5rem;
        }

        .footer-column ul {
            list-style: none;
            padding: 0;
        }

        .footer-column ul li {
            margin-bottom: 0.5rem;
        }

        .footer-column ul li a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-column ul li a:hover {
            color: var(--white);
        }

        .social-icons {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .social-icons a {
            color: rgba(255,255,255,0.8);
            font-size: 1.2rem;
            transition: color 0.3s;
        }

        .social-icons a:hover {
            color: var(--white);
        }

        .contact-info p {
            color: rgba(255,255,255,0.8);
            margin: 0.5rem 0;
            line-height: 1.5;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 1rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            font-size: 0.85rem;
            color: rgba(255,255,255,0.6);
        }

        @media (max-width: 768px) {
            .container { padding: 0 1rem; }
            .section { margin-bottom: 2rem; }
            .section-header h2 { font-size: 1.5rem; }
            .footer-content { grid-template-columns: 1fr; }
            
            .hero-section {
                padding: 3rem 1.5rem;
            }
            
            .hero-title {
                font-size: 2.2rem;
            }
            
            .hero-subtitle {
                font-size: 1.1rem;
            }
            
            .hero-stats {
                gap: 1rem;
            }
            
            .stat-number {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>

    <!-- Navigation -->
    <nav class="navbar">
        <a href="index.php" class="logo-placeholder">SKILL SWAP</a>
        
        <div class="mobile-toggle" id="mobileToggle">
            <span></span>
            <span></span>
            <span></span>
        </div>

        <div class="navbar-links" id="navbarLinks">
            <a href="index.php">Home</a>    
            <a href="courses.php">Courses</a>
            <a href="about.php" class="active">About Us</a>
            <a href="contact.php">Contact</a>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($_SESSION['role'] === 'student'): ?>
                    <a href="student_dashboard.php">Dashboard</a>
                <?php elseif ($_SESSION['role'] === 'admin'): ?>
                    <a href="admin_dashboard.php">Dashboard</a>
                <?php endif; ?>
                <a href="logout.php" class="btn1">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn-login">Login</a>
                <a href="register.php" class="btn-register">Register</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title">Empowering Futures Through Skills</h1>
            <p class="hero-subtitle">At SKILL SWAP, we believe that everyone deserves access to quality education that transforms lives and careers. Join our community of learners and professionals shaping the future of Sri Lanka.</p>
            
            <div class="hero-stats">
                <div class="stat-item">
                    <div class="stat-number">10,000+</div>
                    <div class="stat-label">Students Trained</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">50+</div>
                    <div class="stat-label">Industry Experts</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">95%</div>
                    <div class="stat-label">Job Placement</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">

        <!-- Mission & Vision -->
        <div class="section">
            <div class="section-header">
                <h2>Our Mission & Vision</h2>
            </div>
            <div class="mission-vision-grid">
                <div class="card">
                    <i class="fas fa-bullseye"></i>
                    <h3>Our Mission</h3>
                    <p>To empower individuals across Sri Lanka with practical, industry-relevant skills through accessible and innovative online learning.</p>
                </div>
                <div class="card">
                    <i class="far fa-eye"></i>
                    <h3>Our Vision</h3>
                    <p>To become the leading platform for vocational education in South Asia, transforming careers and driving national economic growth.</p>
                </div>
            </div>
        </div>

        <!-- Leadership Team -->
        <div class="section">
            <div class="section-header">
                <h2>Our Leadership Team</h2>
            </div>
            <div class="team-grid">
                <div class="team-member">
                    <div class="avatar-placeholder"><i class="fas fa-user-tie"></i></div>
                    <h4>Founder & CEO</h4>
                    <p>John Doe</p>
                </div>
                <div class="team-member">
                    <div class="avatar-placeholder"><i class="fas fa-user-tie"></i></div>
                    <h4>Director of Academics</h4>
                    <p>Jane Smith</p>
                </div>
                <div class="team-member">
                    <div class="avatar-placeholder"><i class="fas fa-user-tie"></i></div>
                    <h4>Director of Operations</h4>
                    <p>Robert Brown</p>
                </div>
                <div class="team-member">
                    <div class="avatar-placeholder"><i class="fas fa-user-tie"></i></div>
                    <h4>Head of Digital Learning</h4>
                    <p>Emily White</p>
                </div>
            </div>
        </div>

        <!-- Testimonials -->
        <div class="section">
            <div class="section-header">
                <h2>What Our Students Say</h2>
            </div>
            <div class="testimonials-grid">
                <div class="testimonial-card">
                    <div class="quote-icon">❝</div>
                    <p>This course changed my career path. The instructors are knowledgeable and supportive.</p>
                    <div class="student-name">— Priya Fernando</div>
                </div>
                <div class="testimonial-card">
                    <div class="quote-icon">❝</div>
                    <p>The flexible schedule allowed me to learn while working full-time. Highly recommend!</p>
                    <div class="student-name">— Nimal Perera</div>
                </div>
                <div class="testimonial-card">
                    <div class="quote-icon">❝</div>
                    <p>I landed my first job within weeks of completing the program. Worth every minute!</p>
                    <div class="student-name">— Chaminda Silva</div>
                </div>
            </div>
        </div>

        <!-- CTA -->
        <div class="section">
            <div class="cta-section">
                <h2>Ready to Build Your Future?</h2>
                <p>Explore our courses and take the first step towards a rewarding career.</p>
                <a href="register.php" class="btn">Register Now</a>
            </div>
        </div>

    </div>

    <footer class="footer">
    <div class="container">
        <div class="footer-content">
            <!-- Logo and Social Media -->
            <div class="footer-column logo-social">
                <div class="logo-placeholder">SKILL SWAP</div>
                <h4>Follow Us On</h4>
                <div class="social-icons">
                    <a href="#" aria-label="Facebook"><img src="images/facebook-logo.png" alt="Facebook" class="social-icon"></a>
                    <a href="#" aria-label="Instagram"><img src="images/instagram-logo.png" alt="Instagram" class="social-icon"></a>
                    <a href="#" aria-label="LinkedIn"><img src="images/linkedin-logo.png" alt="LinkedIn" class="social-icon"></a>
                    <a href="#" aria-label="YouTube"><img src="images/youtube-logo.png" alt="YouTube" class="social-icon"></a>
                </div>
                <!-- Optional: Remove or adjust the award badge if it's causing issues -->
                <img src="images/award-badge.png" alt="Award Badge" class="award-badge">
            </div>

            <!-- Quick Links -->
            <div class="footer-column">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="courses.php">Courses</a></li>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="contact.php">Contact Us</a></li>
                </ul>
            </div>

            <!-- Important Links -->
            <div class="footer-column">
                <h4>Information</h4>
                <ul>
                    <li><a href="terms.php">Terms & Conditions</a></li>
                    <li><a href="privacy.php">Privacy Policy</a></li>
                    <li><a href="refund_policy.php">Refund Policy</a></li>
                    <li><a href="payment_policy.php">Payment Policy</a></li>
                    <li><a href="quality_policy.php">Quality Policy</a></li>
                </ul>
            </div>

            <!-- Contact Information -->
            <div class="footer-column contact-info">
                <h4>Contact Us</h4>
                <ul>
                    <li><i class="fas fa-envelope"></i> info@skillswap.lk</li>
                </ul>
            </div>
        </div>

        <!-- Bottom Section -->
        <div class="footer-bottom">
            <hr>
            <p>This Site was developed by Team 11- All Rights Reserved</p>
        </div>

        <!-- Back to Top Button -->
        <button class="back-to-top" id="backToTop" aria-label="Back to top">
            <i class="fas fa-arrow-up"></i>
        </button>
    </div>
</footer>
    <!-- JavaScript -->
    <script>
        // Mobile Menu Toggle
        document.getElementById('mobileToggle').addEventListener('click', function() {
            this.classList.toggle('active');
            document.getElementById('navbarLinks').classList.toggle('active');
        });

        // Smooth Scroll for Anchor Links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>

</body>
</html>