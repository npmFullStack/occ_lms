<?php
session_start();
require "db.php";

// Redirect to login if not authenticated
if (!isset($_SESSION["user_id"])) {
  header("Location: auth.php");
  exit();
}

// Get user information
$user_id = $_SESSION["user_id"];
$role = $_SESSION["role"];

// Get books for student dashboard
if ($role == "student") {
  $stmt = $pdo->prepare("SELECT b.* FROM books b 
                          JOIN borrow_records br ON b.id = br.book_id 
                          WHERE br.user_id = ? AND br.status = 'borrowed'");
  $stmt->execute([$user_id]);
  $borrowed_books = $stmt->fetchAll();

  // Get available books
  $stmt = $pdo->query(
    "SELECT * FROM books WHERE available_quantity > 0 AND status = 'available' LIMIT 5"
  );
  $available_books = $stmt->fetchAll();
}

// Get stats for librarian dashboard
if ($role == "librarian") {
  // Total books
  $stmt = $pdo->query("SELECT COUNT(*) as total_books FROM books");
  $total_books = $stmt->fetch()["total_books"];

  // Available books
  $stmt = $pdo->query(
    "SELECT SUM(available_quantity) as available_books FROM books"
  );
  $available_books = $stmt->fetch()["available_books"];

  // Total students
  $stmt = $pdo->query(
    "SELECT COUNT(*) as total_students FROM users WHERE role = 'student'"
  );
  $total_students = $stmt->fetch()["total_students"];

  // Pending requests
  $stmt = $pdo->query(
    "SELECT COUNT(*) as pending_requests FROM book_requests WHERE status = 'pending'"
  );
  $pending_requests = $stmt->fetch()["pending_requests"];

  // Overdue books
  $stmt = $pdo->query("SELECT COUNT(*) as overdue_books FROM borrow_records 
                        WHERE status = 'overdue' OR (status = 'borrowed' AND due_date < NOW())");
  $overdue_books = $stmt->fetch()["overdue_books"];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library System - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/stylesheet.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-brand">
            <h2>OPOL COMMUNITY COLLEGE</h2>
            <p>LIBRARY MANAGEMENT SYSTEM</p>
        </div>
        <nav class="sidebar-menu">

            <a href="dashboard.php" class="active">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
            <a href="books.php">
                <i class="fas fa-book-open"></i>
                <span>Browse Catalog</span>
            </a>
            <?php if ($role == "librarian"): ?>
                <a href="manage_books.php">
                    <i class="fas fa-book-medical"></i>
                    <span>Manage Books</span>
                </a>
                <a href="manage_requests.php">
                    <i class="fas fa-clipboard-check"></i>
                    <span>Book Requests</span>
                </a>
                <a href="report.php">
                    <i class="fas fa-chart-pie"></i>
                    <span>Reports</span>
                </a>
            <?php else: ?>

                <a href="view_borrow_history.php">
                    <i class="fas fa-history"></i>
                    <span>Borrowing History</span>
                </a>
            <?php endif; ?>
            
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
                <h1>Dashboard</h1>
            </div>
        </header>

        <main class="content-wrapper">
            <?php if ($role == "librarian"): ?>
                <section class="section">
                    <h2 class="section-title"><i class="fas fa-chart-line"></i> Library Overview</h2>
                    <div class="info-cards-grid">
                        <div class="info-card card-primary">
                            <div class="card-icon">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="card-content">
                                <h3>Total Books</h3>
                                <div class="card-value"><?php echo $total_books; ?></div>
                                <a href="manage_books.php" class="card-link">View All Books</a>
                            </div>
                        </div>
                        
                        <div class="info-card card-success">
                            <div class="card-icon">
                                <i class="fas fa-book-open"></i>
                            </div>
                            <div class="card-content">
                                <h3>Available Books</h3>
                                <div class="card-value"><?php echo $available_books; ?></div>
                                <a href="books.php" class="card-link">Browse Catalog</a>
                            </div>
                        </div>
                        
                        <div class="info-card card-warning">
                            <div class="card-icon">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                            <div class="card-content">
                                <h3>Overdue Books</h3>
                                <div class="card-value"><?php echo $overdue_books; ?></div>
                                <a href="manage_requests.php" class="card-link">Manage Overdues</a>
                            </div>
                        </div>
                        

                        
                        <div class="info-card card-danger">
                            <div class="card-icon">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                            <div class="card-content">
                                <h3>Pending Requests</h3>
                                <div class="card-value"><?php echo $pending_requests; ?></div>
                                <a href="manage_requests.php" class="card-link">Process Requests</a>
                            </div>
                        </div>
                    </div>
                </section>
            <?php else: ?>
                <section class="section">
                    <h2 class="section-title"><i class="fas fa-info-circle"></i> Your Library Status</h2>
                    <div class="info-cards-grid">
                        <div class="info-card card-primary">
                            <div class="card-icon">
                                <i class="fas fa-bookmark"></i>
                            </div>
                            <div class="card-content">
                                <h3>Borrowed Books</h3>
                                <div class="card-value"><?php echo count(
                                  $borrowed_books
                                ); ?></div>
                                <a href="view_borrow_history.php" class="card-link">View My Books</a>
                            </div>
                        </div>
                        
                        <div class="info-card card-success">
                            <div class="card-icon">
                                <i class="fas fa-book-open"></i>
                            </div>
                            <div class="card-content">
                                <h3>Available Books</h3>
                                <div class="card-value"><?php echo count(
                                  $available_books
                                ); ?></div>
                                <a href="books.php" class="card-link">Browse Catalog</a>
                            </div>
                        </div>
                    </div>
                </section>
                
                <section class="section">
                    <h2 class="section-title"><i class="fas fa-bookmark"></i> Your Borrowed Books</h2>
                    <?php if (!empty($borrowed_books)): ?>
                        <div class="card-grid">
                            <?php foreach ($borrowed_books as $book): ?>
                                <div class="book-card">
<div class="book-cover">
    <?php if (!empty($book["cover_image"])): ?>
        <?php $cover_image = basename($book["cover_image"]); ?>
        <img src="uploads/book_covers/<?php echo htmlspecialchars(
          $cover_image
        ); ?>" 
             alt="<?php echo htmlspecialchars($book["title"]); ?>">
    <?php else: ?>
        <i class="fas fa-book"></i>
    <?php endif; ?>
</div>
                                    </div>
                                    <div class="book-info">
                                        <h3><?php echo htmlspecialchars(
                                          $book["title"]
                                        ); ?></h3>
                                        <p class="book-author"><?php echo htmlspecialchars(
                                          $book["author"]
                                        ); ?></p>
                                        <div class="book-meta">
                                            <span class="book-status status-borrowed">
                                                <i class="fas fa-clock"></i> Borrowed
                                            </span>
                                        </div>
                                        <a href="return_book.php?id=<?php echo $book[
                                          "id"
                                        ]; ?>" class="btn btn-primary">
                                            <i class="fas fa-undo"></i> Return
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-book-open"></i>
                            <p>You currently have no borrowed books</p>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </main>
    </div>

    <script src="assets/js/scriptsheet.js"></script>
</body>
</html>