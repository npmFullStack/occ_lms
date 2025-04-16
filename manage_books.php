<?php
session_start();
require "db.php";

// Redirect to login if not authenticated or not a librarian
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "librarian") {
  header("Location: auth.php");
  exit();
}

$librarian_id = $_SESSION["user_id"];

// Handle book deletion
if (isset($_GET["delete"])) {
  $book_id = $_GET["id"] ?? 0;

  $stmt = $pdo->prepare("SELECT title FROM books WHERE id = ?");
  $stmt->execute([$book_id]);
  $book = $stmt->fetch();

  if ($book) {
    // Check if book has active borrows
    $stmt = $pdo->prepare(
      "SELECT COUNT(*) FROM borrow_records WHERE book_id = ? AND status = 'borrowed'"
    );
    $stmt->execute([$book_id]);
    $active_borrows = $stmt->fetchColumn();

    if ($active_borrows > 0) {
      $_SESSION["error"] = "Cannot delete book with active borrows.";
    } else {
      $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
      $stmt->execute([$book_id]);

      log_activity(
        $librarian_id,
        "delete_book",
        "Deleted book: {$book["title"]}"
      );
      $_SESSION["success"] = "Book deleted successfully!";
    }
  }

  header("Location: manage_books.php");
  exit();
}

// Get all books - updated to use email instead of username
$stmt = $pdo->query("SELECT b.*, u.email as created_by_email FROM books b 
                    LEFT JOIN users u ON b.created_by = u.id 
                    ORDER BY b.title");
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
    <title>Manage Books - Library System</title>
    <link rel="stylesheet" href="assets/css/stylesheet.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Additional styles specific to manage_books.php */
        .content-wrapper {
            padding: 20px;
        }
        
        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 15px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn:hover {
            opacity: 0.9;
        }
        
        .btn-add {
            background-color: #28a745;
        }
        
        .btn-danger {
            background-color: #dc3545;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        tr:hover {
            background-color: #f5f5f5;
        }
        
        .status-available {
            color: #28a745;
        }
        
        .status-unavailable {
            color: #dc3545;
        }
        
        .status-lost {
            color: #ffc107;
        }
        
        .alert {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 4px;
        }
        
        .alert-error {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
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

            <a href="manage_books.php" class="active">
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
                <h1>Manage Books</h1>
            </div>
        </header>

        <main class="content-wrapper">
            <div class="action-bar">
                <h2 class="section-title"><i class="fas fa-book-medical"></i> Book Management</h2>
                <a href="add_book.php" class="btn btn-add"><i class="fas fa-plus"></i> Add New Book</a>
            </div>
            
            <?php if (isset($_SESSION["error"])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php
                    echo $_SESSION["error"];
                    unset($_SESSION["error"]);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION["success"])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php
                    echo $_SESSION["success"];
                    unset($_SESSION["success"]);
                    ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Author</th>
                            <th>ISBN</th>
                            <th>Category</th>
                            <th>Quantity</th>
                            <th>Available</th>

                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($books): ?>
                            <?php foreach ($books as $book): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(
                                      $book["title"]
                                    ); ?></td>
                                    <td><?php echo htmlspecialchars(
                                      $book["author"]
                                    ); ?></td>
                                    <td><?php echo htmlspecialchars(
                                      $book["isbn"] ?? "N/A"
                                    ); ?></td>
                                    <td><?php echo htmlspecialchars(
                                      $book["category"] ?? "N/A"
                                    ); ?></td>
                                    <td><?php echo $book["quantity"]; ?></td>
                                    <td><?php echo $book[
                                      "available_quantity"
                                    ]; ?></td>

                                    <td class="action-buttons">
                                        <a href="edit_book.php?id=<?php echo $book[
                                          "id"
                                        ]; ?>" class="btn">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="manage_books.php?delete=1&id=<?php echo $book[
                                          "id"
                                        ]; ?>" 
                                           class="btn btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this book?')">
                                           <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" style="text-align: center;">No books found in the library.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script src="assets/js/scriptsheet.js"></script>
</body>
</html>