<?php
session_start();
require "db.php";

// Redirect to login if not authenticated or not a student
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "student") {
  header("Location: auth.php");
  exit();
}

$user_id = $_SESSION["user_id"];
$book_id = $_GET["id"] ?? 0;

// Get book details
$stmt = $pdo->prepare(
  "SELECT * FROM books WHERE id = ? AND available_quantity > 0 AND status = 'available'"
);
$stmt->execute([$book_id]);
$book = $stmt->fetch();

if (!$book) {
  $_SESSION["error"] = "Book not available for request.";
  header("Location: books.php");
  exit();
}

// Check if user already has a pending request for this book
$stmt = $pdo->prepare(
  "SELECT id FROM book_requests WHERE book_id = ? AND user_id = ? AND status = 'pending'"
);
$stmt->execute([$book_id, $user_id]);
if ($stmt->fetch()) {
  $_SESSION["error"] = "You already have a pending request for this book.";
  header("Location: books.php");
  exit();
}

// Check if user has reached max books limit
$stmt = $pdo->prepare(
  "SELECT COUNT(*) as borrowed_count FROM borrow_records WHERE user_id = ? AND status = 'borrowed'"
);
$stmt->execute([$user_id]);
$borrowed_count = $stmt->fetch()["borrowed_count"];

$stmt = $pdo->prepare("SELECT max_books FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$max_books = $stmt->fetch()["max_books"];

if ($borrowed_count >= $max_books) {
  $_SESSION[
    "error"
  ] = "You have reached your maximum borrowing limit ($max_books books).";
  header("Location: books.php");
  exit();
}

// Process request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $stmt = $pdo->prepare(
    "INSERT INTO book_requests (book_id, user_id) VALUES (?, ?)"
  );
  $stmt->execute([$book_id, $user_id]);

  log_activity($user_id, "book_request", "Requested book: {$book["title"]}");

  $_SESSION["success"] = "Book request submitted successfully!";
  header("Location: dashboard.php");
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Book - Library System</title>
    <link rel="stylesheet" href="assets/css/stylesheet.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .book-info-card {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .book-info-card h3 {
            margin-top: 0;
            color: #2c3e50;
        }
        .book-info-card p {
            margin: 8px 0;
        }
        .availability-info {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-brand">
            <h2>OPOL COMMUNITY COLLEGE</h2>
            <p>LIBRARY MANAGEMENT SYSTEM</p>
        </div>
        <nav class="sidebar-menu">
            <a href="dashboard.php">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
            <a href="books.php">
                <i class="fas fa-book-open"></i>
                <span>Browse Catalog</span>
            </a>

                <a href="view_borrow_history.php">
                    <i class="fas fa-history"></i>
                    <span>Borrowing History</span>
                </a>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </nav>
    </div>

    <div class="main-content">
        <header class="main-header">
            <div class="header-left">
                <button class="toggle-sidebar" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <img src="assets/images/occ-logo.png" alt="OCC Logo" class="header-logo">
                <h1>Request Book</h1>
            </div>
        </header>

        <main class="content-wrapper">
            <section class="section">
                <h2 class="section-title"><i class="fas fa-hand-paper"></i> Book Request</h2>
                
                <div class="book-info-card">
                    <h3><?php echo htmlspecialchars($book["title"]); ?></h3>
                    <p><strong>Author:</strong> <?php echo htmlspecialchars(
                      $book["author"]
                    ); ?></p>
                    <p><strong>ISBN:</strong> <?php echo htmlspecialchars(
                      $book["isbn"] ?? "N/A"
                    ); ?></p>
                    <p><strong>Available Copies:</strong> <?php echo $book[
                      "available_quantity"
                    ]; ?></p>
                    
                    <?php if (!empty($book["cover_image"])): ?>
                        <div class="current-image" style="margin-top: 15px;">
                            <img src="<?php echo htmlspecialchars(
                              $book["cover_image"]
                            ); ?>" alt="Book Cover" style="max-width: 200px; border-radius: 4px;">
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="availability-info">
                    <p>You currently have <?php echo $borrowed_count; ?> books borrowed (max: <?php echo $max_books; ?>).</p>
                </div>
                
                <form method="post" class="book-form">
                    <div class="form-group">
                        <p>Are you sure you want to request this book?</p>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check-circle"></i> Confirm Request
                        </button>
                        <a href="books.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </section>
        </main>
    </div>

    <script src="assets/js/scriptsheet.js"></script>
</body>
</html>