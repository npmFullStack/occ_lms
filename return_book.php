<?php
session_start();
require 'db.php';

// Redirect if not student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: auth.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$book_id = $_GET['id'] ?? 0;

// Get borrow record details
$stmt = $pdo->prepare("SELECT br.*, b.title as book_title 
                      FROM borrow_records br
                      JOIN books b ON br.book_id = b.id
                      WHERE br.user_id = ? AND br.book_id = ? AND br.status = 'borrowed'");
$stmt->execute([$user_id, $book_id]);
$borrow_record = $stmt->fetch();

if (!$borrow_record) {
    $_SESSION['error'] = "No active borrow record found for this book!";
    header("Location: dashboard.php");
    exit();
}

// Process return if confirmed
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if return is confirmed
    if (isset($_POST['confirm_return'])) {
        // Start transaction
        $pdo->beginTransaction();

        try {
            // Update borrow record
            $stmt = $pdo->prepare("UPDATE borrow_records SET 
                                  return_date = NOW(), 
                                  status = IF(due_date < NOW(), 'overdue', 'returned'),
                                  updated_at = NOW()
                                  WHERE id = ?");
            $stmt->execute([$borrow_record['id']]);

            // Update book availability
            $stmt = $pdo->prepare("UPDATE books SET 
                                  available_quantity = available_quantity + 1 
                                  WHERE id = ?");
            $stmt->execute([$book_id]);

            // Check if book was returned late
            $is_late = strtotime($borrow_record['due_date']) < time();
            
            if ($is_late) {
                // Calculate fine (example: $1 per day late)
                $days_late = ceil((time() - strtotime($borrow_record['due_date'])) / (60 * 60 * 24));
                $fine_amount = min($days_late * 1.00, 20.00); // Cap at $20

                // Add fine record
                $stmt = $pdo->prepare("INSERT INTO fines 
                                      (borrow_id, user_id, amount, reason, status)
                                      VALUES (?, ?, ?, 'overdue', 'pending')");
                $stmt->execute([
                    $borrow_record['id'], 
                    $user_id, 
                    $fine_amount
                ]);
            }

            $pdo->commit();

            log_activity($user_id, 'return_book', "Returned book: {$borrow_record['book_title']}");
            $_SESSION['success'] = "Book returned successfully!" . ($is_late ? " A late fine of $$fine_amount has been applied." : "");
            header("Location: dashboard.php");
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Error returning book: " . $e->getMessage();
            header("Location: return_book.php?id=" . $book_id);
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library System - Return Book</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; margin: 0; padding: 0; }
        .header { background-color: #333; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .header a { color: white; text-decoration: none; }
        .container { max-width: 600px; margin: 20px auto; padding: 0 20px; }
        .return-card { background: white; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); padding: 20px; margin-bottom: 20px; }
        .return-card h2 { margin-top: 0; color: #333; }
        .book-info { margin-bottom: 15px; }
        .book-info p { margin: 5px 0; }
        .due-date { font-weight: bold; }
        .late-warning { color: #dc3545; font-weight: bold; }
        .btn { 
            display: inline-block; padding: 10px 15px; text-decoration: none; 
            border-radius: 4px; border: none; cursor: pointer; font-size: 16px;
        }
        .btn-return { background-color: #28a745; color: white; }
        .btn-return:hover { background-color: #218838; }
        .btn-cancel { background-color: #6c757d; color: white; }
        .btn-cancel:hover { background-color: #5a6268; }
        .error { color: #dc3545; margin-bottom: 15px; padding: 10px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; }
        .success { color: #28a745; margin-bottom: 15px; padding: 10px; background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; }
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
        <div class="return-card">
            <h2>Return Book Confirmation</h2>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
            <div class="book-info">
                <p><strong>Book Title:</strong> <?php echo htmlspecialchars($borrow_record['book_title']); ?></p>
                <p><strong>Borrow Date:</strong> <?php echo htmlspecialchars($borrow_record['borrow_date']); ?></p>
                <p class="due-date"><strong>Due Date:</strong> <?php echo htmlspecialchars($borrow_record['due_date']); ?></p>
                
                <?php 
                $is_late = strtotime($borrow_record['due_date']) < time();
                if ($is_late): ?>
                    <p class="late-warning">This book is late! You may incur a fine.</p>
                <?php endif; ?>
            </div>
            
            <form method="POST" action="return_book.php?id=<?php echo $book_id; ?>">
                <p>Are you sure you want to return this book?</p>
                
                <button type="submit" name="confirm_return" value="1" class="btn btn-return">
                    Yes, Return Book
                </button>
                <a href="dashboard.php" class="btn btn-cancel">Cancel</a>
            </form>
        </div>
    </div>
</body>
</html>