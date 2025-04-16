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
    <title>Return Book - Library System</title>
    <link rel="stylesheet" href="assets/css/stylesheet.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .return-card {
            background: #fff;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .book-info p {
            margin: 10px 0;
            font-size: 16px;
        }
        .due-date {
            font-weight: bold;
            color: #2c3e50;
        }
        .late-warning {
            color: #e74c3c;
            font-weight: bold;
            padding: 8px;
            background-color: #fde8e8;
            border-radius: 4px;
            margin-top: 10px;
        }
        .btn-return {
            background-color: #2ecc71;
            color: white;
        }
        .btn-return:hover {
            background-color: #27ae60;
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
                <h1>Return Book</h1>
            </div>
        </header>

        <main class="content-wrapper">
            <section class="section">
                <h2 class="section-title"><i class="fas fa-book-return"></i> Return Book Confirmation</h2>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="error-message"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                
                <div class="return-card">
                    <div class="book-info">
                        <p><strong>Book Title:</strong> <?php echo htmlspecialchars($borrow_record['book_title']); ?></p>
                        <p><strong>Borrow Date:</strong> <?php echo htmlspecialchars($borrow_record['borrow_date']); ?></p>
                        <p class="due-date"><strong>Due Date:</strong> <?php echo htmlspecialchars($borrow_record['due_date']); ?></p>
                        
                        <?php 
                        $is_late = strtotime($borrow_record['due_date']) < time();
                        if ($is_late): ?>
                            <p class="late-warning"><i class="fas fa-exclamation-triangle"></i> This book is late! You may incur a fine.</p>
                        <?php endif; ?>
                    </div>
                    
                    <form method="POST" action="return_book.php?id=<?php echo $book_id; ?>" class="book-form">
                        <div class="form-group">
                            <p>Are you sure you want to return this book?</p>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="confirm_return" value="1" class="btn btn-return">
                                <i class="fas fa-check-circle"></i> Yes, Return Book
                            </button>
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </section>
        </main>
    </div>

    <script src="assets/js/scriptsheet.js"></script>
</body>
</html>