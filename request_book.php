<?php
session_start();
require 'db.php';

// Redirect to login if not authenticated or not a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: auth.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$book_id = $_GET['id'] ?? 0;

// Get book details
$stmt = $pdo->prepare("SELECT * FROM books WHERE id = ? AND available_quantity > 0 AND status = 'available'");
$stmt->execute([$book_id]);
$book = $stmt->fetch();

if (!$book) {
    $_SESSION['error'] = "Book not available for request.";
    header("Location: books.php");
    exit();
}

// Check if user already has a pending request for this book
$stmt = $pdo->prepare("SELECT id FROM book_requests WHERE book_id = ? AND user_id = ? AND status = 'pending'");
$stmt->execute([$book_id, $user_id]);
if ($stmt->fetch()) {
    $_SESSION['error'] = "You already have a pending request for this book.";
    header("Location: books.php");
    exit();
}

// Check if user has reached max books limit
$stmt = $pdo->prepare("SELECT COUNT(*) as borrowed_count FROM borrow_records WHERE user_id = ? AND status = 'borrowed'");
$stmt->execute([$user_id]);
$borrowed_count = $stmt->fetch()['borrowed_count'];

$stmt = $pdo->prepare("SELECT max_books FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$max_books = $stmt->fetch()['max_books'];

if ($borrowed_count >= $max_books) {
    $_SESSION['error'] = "You have reached your maximum borrowing limit ($max_books books).";
    header("Location: books.php");
    exit();
}

// Process request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stmt = $pdo->prepare("INSERT INTO book_requests (book_id, user_id) VALUES (?, ?)");
    $stmt->execute([$book_id, $user_id]);
    
    log_activity($user_id, 'book_request', "Requested book: {$book['title']}");
    
    $_SESSION['success'] = "Book request submitted successfully!";
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
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; margin: 0; padding: 0; }
        .header { background-color: #333; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .header a { color: white; text-decoration: none; }
        .container { max-width: 800px; margin: 20px auto; padding: 0 20px; }
        .request-form { background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .book-info { margin-bottom: 20px; }
        .book-info h3 { margin-top: 0; }
        .book-info p { margin: 5px 0; }
        .btn { display: inline-block; padding: 8px 15px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; }
        .btn:hover { background-color: #0056b3; }
        .btn-cancel { background-color: #6c757d; }
        .btn-cancel:hover { background-color: #5a6268; }
        .nav-menu { display: flex; gap: 15px; }
        .nav-menu a { color: white; text-decoration: none; padding: 5px 10px; border-radius: 3px; }
        .nav-menu a:hover { background-color: #555; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Library Management System</h1>
        <div class="nav-menu">
            <a href="dashboard.php">Dashboard</a>
            <a href="books.php">Books</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="request-form">
            <h2>Request Book</h2>
            
            <div class="book-info">
                <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                <p><strong>Author:</strong> <?php echo htmlspecialchars($book['author']); ?></p>
                <p><strong>ISBN:</strong> <?php echo htmlspecialchars($book['isbn'] ?? 'N/A'); ?></p>
                <p><strong>Available:</strong> <?php echo $book['available_quantity']; ?> copies</p>
            </div>
            
            <p>You currently have <?php echo $borrowed_count; ?> books borrowed (max: <?php echo $max_books; ?>).</p>
            
            <form method="post">
                <p>Are you sure you want to request this book?</p>
                <button type="submit" class="btn">Confirm Request</button>
                <a href="books.php" class="btn btn-cancel">Cancel</a>
            </form>
        </div>
    </div>
</body>
</html>