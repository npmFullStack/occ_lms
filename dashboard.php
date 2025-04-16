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
    <style>
      /* Book Section Styles */
.book-section {
    margin: 2rem 0;
    padding: 1rem;
}

.section-title {
    font-size: 1.5rem;
    margin-bottom: 1.5rem;
    color: #333;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Card Grid Layout */
.card-grid-1 {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

/* Book Card Styles */
.book-card-1 {
    display: flex;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: transform 0.3s ease;
}

.book-card-1:hover {
    transform: translateY(-5px);
}

/* Image Container */
.book-image-container-1 {
    width: 120px;
    height: 180px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f5f5f5;
    flex-shrink: 0;
}

.book-image-1 {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.book-icon-1 {
    font-size: 3rem;
    color: #777;
}

/* Book Details */
.book-details-1 {
    padding: 1rem;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}

.book-title-1 {
    font-size: 1.1rem;
    margin: 0 0 0.5rem 0;
    color: #333;
}

.book-author-1 {
    margin: 0 0 0.5rem 0;
    color: #666;
    font-size: 0.9rem;
}

.book-meta-1 {
    margin-top: auto;
}

.book-status-1 {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    font-size: 0.8rem;
    padding: 0.3rem 0.6rem;
    border-radius: 4px;
    margin-bottom: 1rem;
}

.status-borrowed-1 {
    background-color: #fff3cd;
    color: #856404;
}

.btn-primary-1 {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.5rem 1rem;
    background-color: #007bff;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-size: 0.9rem;
    transition: background-color 0.3s;
    margin-top: auto;
    width: fit-content;
}

.btn-primary-1:hover {
    background-color: #0069d9;
}

/* No Data Styles */
.no-data-1 {
    text-align: center;
    padding: 2rem;
    color: #666;
}

.no-data-1 i {
    font-size: 2rem;
    margin-bottom: 1rem;
    color: #ddd;
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

            <a href="dashboard.php" class="active">
                <i class="fas fa-home"></i>
                <span>Home</span>
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
            <a href="books.php">
                <i class="fas fa-book-open"></i>
                <span>Browse Catalog</span>
            </a>
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
                
<section class="book-section">
    <h2 class="section-title"><i class="fas fa-bookmark"></i> Your Borrowed Books</h2>
    <?php if (!empty($borrowed_books)): ?>
        <div class="card-grid-1">
            <?php foreach ($borrowed_books as $book): ?>
                <div class="book-card-1">
                    <div class="book-image-container-1">
                        <?php if (!empty($book["cover_image"])): ?>
                            <?php $cover_image = basename($book["cover_image"]); ?>
                            <img class="book-image-1" src="uploads/book_covers/<?php echo htmlspecialchars($cover_image); ?>" 
                                 alt="<?php echo htmlspecialchars($book["title"]); ?>">
                        <?php else: ?>
                            <i class="fas fa-book book-icon-1"></i>
                        <?php endif; ?>
                    </div>
                    <div class="book-details-1">
                        <h3 class="book-title-1"><?php echo htmlspecialchars($book["title"]); ?></h3>
                        <p class="book-author-1"><?php echo htmlspecialchars($book["author"]); ?></p>
                        <div class="book-meta-1">
                            <span class="book-status-1 status-borrowed-1">
                                <i class="fas fa-clock"></i> Borrowed
                            </span>
                        </div>
                        <a href="return_book.php?id=<?php echo $book["id"]; ?>" class="btn btn-primary-1">
                            <i class="fas fa-undo"></i> Return
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-data-1">
            <i class="fas fa-book-open"></i>
            <p>You currently have no borrowed books</p>
        </div>
    <?php endif; ?>
</section>      <?php endif; ?>
        </main>
    </div>

    <script src="assets/js/scriptsheet.js"></script>
</body>
</html>