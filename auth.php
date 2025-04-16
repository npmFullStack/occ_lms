<?php
session_start();
require_once "db.php";

$action = isset($_GET["action"]) ? $_GET["action"] : "login";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  if (isset($_POST["login"])) {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    if (empty($email) || empty($password)) {
      $_SESSION["error"] = "Please fill in all fields";
      header("Location: auth.php?action=login");
      exit();
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user["password"])) {
      $_SESSION["user_id"] = $user["id"];
      $_SESSION["email"] = $user["email"];
      $_SESSION["id_number"] = $user["id_number"];
      $_SESSION["role"] = $user["role"];
      $_SESSION["first_name"] = $user["first_name"];
      $_SESSION["last_name"] = $user["last_name"];

      header("Location: dashboard.php");
      exit();
    } else {
      $_SESSION["error"] = "Invalid email or password";
      header("Location: auth.php?action=login");
      exit();
    }
  } elseif (isset($_POST["register"])) {
    $email = trim($_POST["email"]);
    $id_number = trim($_POST["id_number"]);
    $password = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm_password"]);
    $first_name = trim($_POST["first_name"]);
    $last_name = trim($_POST["last_name"]);

    $errors = [];
    if (
      empty($email) ||
      empty($id_number) ||
      empty($password) ||
      empty($confirm_password) ||
      empty($first_name) ||
      empty($last_name)
    ) {
      $errors[] = "All fields are required";
    }
    if ($password !== $confirm_password) {
      $errors[] = "Passwords do not match";
    }
    if (strlen($password) < 8) {
      $errors[] = "Password must be at least 8 characters";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $errors[] = "Invalid email format";
    }

    $stmt = $pdo->prepare(
      "SELECT id FROM users WHERE email = ? OR id_number = ?"
    );
    $stmt->execute([$email, $id_number]);
    if ($stmt->rowCount() > 0) {
      $errors[] = "Email or ID number already exists";
    }

    if (empty($errors)) {
      $hashed_password = password_hash($password, PASSWORD_DEFAULT);
      $role = "student";

      $stmt = $pdo->prepare(
        "INSERT INTO users (email, id_number, password, role, first_name, last_name) VALUES (?, ?, ?, ?, ?, ?)"
      );
      $stmt->execute([
        $email,
        $id_number,
        $hashed_password,
        $role,
        $first_name,
        $last_name,
      ]);

      if ($stmt->rowCount() > 0) {
        $_SESSION["success"] = "Registration successful! Please login.";
        header("Location: auth.php?action=login");
        exit();
      } else {
        $_SESSION["error"] = "Registration failed. Please try again.";
      }
    } else {
      $_SESSION["error"] = implode("<br>", $errors);
    }
  }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ucfirst($action); ?> | OCC LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body class="auth-container">
    <!-- Header (same as index.php) -->
    <header id="main-header" class="header-default">
        <div class="container mx-auto px-1 py-4 flex justify-between items-center">
            <div class="flex items-center">
                <img src="assets/images/occ-logo.png" alt="OCC Logo" class="h-12 mr-3">
                <div>
                    <h1 class="text-xl font-bold text-blue-800">OPOL COMMUNITY COLLEGE</h1>
                    <p class="text-sm font-900 text-gray-900">LIBRARY MANAGEMENT SYSTEM</p>
                </div>
            </div>

            <nav class="desktop-nav">
                <a href="index.php#home" class="nav-links">Home</a>
                <a href="index.php#about" class="nav-links">About</a>
                <a href="index.php#services" class="nav-links">Services</a>
                <a href="index.php#contact" class="nav-links">Contact</a>
                <?php if ($action === "login"): ?>
                    <a href="auth.php?action=register" class="signup-btn">Sign Up Now</a>
                <?php else: ?>
                    <a href="auth.php?action=login" class="signup-btn">Login</a>
                <?php endif; ?>
            </nav>

            <button id="mobile-menu-button" class="mobile-menu-btn">
                <i class="fas fa-bars"></i>
            </button>
        </div>

        <!-- Mobile Navigation -->
        <div id="mobile-menu" class="mobile-menu">
            <div class="container mx-auto px-4 py-2 flex flex-col">
                <a href="index.php#home" class="mobile-nav-link">Home</a>
                <a href="index.php#about" class="mobile-nav-link">About</a>
                <a href="index.php#services" class="mobile-nav-link">Services</a>
                <a href="index.php#contact" class="mobile-nav-link">Contact</a>
                <?php if ($action === "login"): ?>
                    <a href="auth.php?action=register" class="mobile-signup-btn">Sign Up Now</a>
                <?php else: ?>
                    <a href="auth.php?action=login" class="mobile-signup-btn">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Main Auth Content -->
    <main class="auth-main">
        <div class="auth-card">
            <?php if ($action === "login"): ?>
                <!-- Login Image -->
<div class="auth-image" style="background-image: url('assets/images/login.png')">
    <div class="auth-message-container">
        <h2 class="auth-image-title">Welcome Back!</h2>
        <p class="auth-image-subtitle">Login to access your library account and explore our resources</p>
    </div>
</div>
                
                <!-- Login Form -->
                <div class="auth-form">
                    <div class="auth-tabs">
                        <a href="?action=login" class="auth-tab active">Login</a>
                        <a href="?action=register" class="auth-tab">Register</a>
                    </div>
                    
                    <?php if (isset($_SESSION["error"])): ?>
                        <div class="alert alert-error">
                            <?php
                            echo $_SESSION["error"];
                            unset($_SESSION["error"]);
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION["success"])): ?>
                        <div class="alert alert-success">
                            <?php
                            echo $_SESSION["success"];
                            unset($_SESSION["success"]);
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="auth.php?action=login" method="POST">
                        <div class="form-group">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email" class="form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" id="password" name="password" class="form-input" required>
                        </div>
                        
                        <button type="submit" name="login" class="btn-primary">Login</button>
                        
                        <div class="auth-switch">
                            <p>Don't have an account? <a href="?action=register">Sign up</a></p>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <!-- Register Form -->
                <div class="auth-form">
                    <div class="auth-tabs">
                        <a href="?action=login" class="auth-tab">Login</a>
                        <a href="?action=register" class="auth-tab active">Register</a>
                    </div>
                    
                    <?php if (isset($_SESSION["error"])): ?>
                        <div class="alert alert-error">
                            <?php
                            echo $_SESSION["error"];
                            unset($_SESSION["error"]);
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="auth.php?action=register" method="POST">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" id="first_name" name="first_name" class="form-input" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" id="last_name" name="last_name" class="form-input" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email" class="form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="id_number" class="form-label">ID Number</label>
                            <input type="text" id="id_number" name="id_number" class="form-input" required>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" id="password" name="password" class="form-input" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                            </div>
                        </div>
                        
                        <button type="submit" name="register" class="btn-primary">Register as Student</button>
                        
                        <div class="auth-switch">
                            <p>Already have an account? <a href="?action=login">Login here</a></p>
                        </div>
                    </form>
                </div>
                
                <!-- Register Image -->
<div class="auth-image" style="background-image: url('assets/images/register.png')">
    <div class="auth-message-container">
        <h2 class="auth-image-title">Join Our Library</h2>
        <p class="auth-image-subtitle">Register now to access thousands of books and resources</p>
    </div>
</div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer (same as index.php) -->
    <footer class="main-footer">
        <div class="container mx-auto px-4">
            <div class="footer-content">
                <div class="footer-logo">
                    <div class="flex items-center">
                        <img src="assets/images/occ-logo.png" alt="OCC Logo" class="h-10 mr-3">
                        <div>
                            <h3>OPOL COMMUNITY COLLEGE</h3>
                            <p>Library Management System</p>
                        </div>
                    </div>
                </div>
                <div class="footer-copyright">
                    <p>&copy; 2023 OPOL COMMUNITY COLLEGE. All rights reserved.</p>
                    <p>Developed by Norway P. Mangorangca, 2nd Year BSIT Student</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="assets/js/script.js"></script>
</body>
</html>