<?php
session_start();
require "db.php";

// Redirect to login if not authenticated
if (!isset($_SESSION["user_id"])) {
  header("Location: auth.php");
  exit();
}

$role = $_SESSION["role"] ?? "student";

// Search functionality
$search = $_GET["search"] ?? "";
$category = $_GET["category"] ?? "";

$query =
  "SELECT * FROM books WHERE available_quantity > 0 AND status = 'available'";
$params = [];

if (!empty($search)) {
  $query .= " AND (title LIKE ? OR author LIKE ? OR isbn LIKE ?)";
  $search_term = "%$search%";
  $params = array_merge($params, [$search_term, $search_term, $search_term]);
}

if (!empty($category)) {
  $query .= " AND category = ?";
  $params[] = $category;
}

$query .= " ORDER BY title ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$books = $stmt->fetchAll();

// Get categories for filter
$stmt = $pdo->query(
  "SELECT DISTINCT category FROM books WHERE category IS NOT NULL ORDER BY category"
);
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library System - Browse Catalog</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/stylesheet.css">
    <style>
/* BOOKS PAGE SPECIFIC STYLES */
.search-section {
    background: white;
    padding: 25px;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    margin-bottom: 30px;
}

.search-form {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.form-group {
    flex: 1;
    min-width: 200px;
}

.search-form input,
.search-form select {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #e0e0e0;
    border-radius: var(--border-radius);
    font-size: 0.95rem;
    transition: var(--transition);
}

.search-form input:focus,
.search-form select:focus {
    outline: none;
    border-color: var(--sidebar-color);
    box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.1);
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 20px;
    border-radius: var(--border-radius);
    font-size: 0.95rem;
    font-weight: 500;
    cursor: pointer;
    transition: var(--transition);
}

.btn-primary {
    background: var(--sidebar-color);
    color: white;
    border: none;
}

.btn-primary:hover {
    background: var(--sidebar-dark);
    transform: translateY(-2px);
}

.btn-secondary {
    background: #f1f3f4;
    color: var(--text-color);
    border: none;
}

.btn-secondary:hover {
    background: #e0e3e7;
}

/* Book Cards */
.book-card {
  width: 100%;
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    overflow: hidden;
    transition: var(--transition);
}

.book-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
}

.book-cover {
    height: 100%;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
}

.book-cover img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.book-card:hover .book-cover img {
    transform: scale(1.05);
}

.book-cover i {
    font-size: 3rem;
    color: #d1d5db;
}

.book-info {
    padding: 20px;
}

.book-info h3 {
    font-size: 1.1rem;
    margin-bottom: 8px;
    color: var(--text-color);
    line-height: 1.4;
}

.book-author {
    color: var(--text-light);
    font-size: 0.9rem;
    margin-bottom: 15px;
    font-style: italic;
}

.book-meta {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 15px;
    font-size: 0.85rem;
}

.book-meta span {
    display: flex;
    align-items: center;
    gap: 8px;
}

.book-meta i {
    width: 18px;
    color: var(--text-light);
}

.status-available {
    color: var(--card-success);
    font-weight: 500;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .search-form {
        flex-direction: column;
        gap: 12px;
    }
    
    .book-cover {
        height: 100%;
    }
}

@media (max-width: 576px) {
    .book-cover {
        height: 100%;
    }
    
    .btn {
        width: 100%;
    }
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
            <a href="books.php" class="active">
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
                <h1>Browse Catalog</h1>
            </div>
        </header>

        <main class="content-wrapper">
            <section class="section">
                <div class="search-section">
                    <form method="get" class="search-form">
                        <div class="form-group">
                            <input type="text" name="search" placeholder="Search by title, author or ISBN" 
                                   value="<?php echo htmlspecialchars(
                                     $search
                                   ); ?>">
                        </div>
                        <div class="form-group">
                            <select name="category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars(
                                      $cat
                                    ); ?>" 
                                        <?php echo $category == $cat
                                          ? "selected"
                                          : ""; ?>>
                                        <?php echo htmlspecialchars($cat); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <a href="books.php" class="btn btn-secondary">
                            <i class="fas fa-sync-alt"></i> Reset
                        </a>
                    </form>
                </div>
                
                <div class="card-grid">
                    <?php if ($books): ?>
                        <?php foreach ($books as $book): ?>
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
                                <div class="book-info">
                                    <h3><?php echo htmlspecialchars(
                                      $book["title"]
                                    ); ?></h3>
                                    <p class="book-author">by <?php echo htmlspecialchars(
                                      $book["author"]
                                    ); ?></p>
                                    <div class="book-meta">
                                        <span class="book-status status-available">
                                            <i class="fas fa-check-circle"></i> 
                                            Available (<?php echo $book[
                                              "available_quantity"
                                            ]; ?>)
                                        </span>
                                        <span class="book-isbn">
                                            <i class="fas fa-barcode"></i> <?php echo htmlspecialchars(
                                              $book["isbn"] ?? "N/A"
                                            ); ?>
                                        </span>
                                        <span class="book-category">
                                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars(
                                              $book["category"] ?? "General"
                                            ); ?>
                                        </span>
                                    </div>
                                    
                                    <?php if ($role == "student"): ?>
                                        <a href="request_book.php?id=<?php echo $book[
                                          "id"
                                        ]; ?>" class="btn btn-primary">
                                            <i class="fas fa-hand-paper"></i> Request Book
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-book-open"></i>
                            <p>No books found matching your criteria</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <script src="assets/js/scriptsheet.js"></script>
</body>
</html>