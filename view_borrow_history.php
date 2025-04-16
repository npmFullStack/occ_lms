<?php
session_start();
require "db.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: auth.php");
  exit();
}

$user_id = $_SESSION["user_id"];
$is_librarian = $_SESSION["role"] == "librarian";

// Check if viewing another user's history (for librarians)
$view_user_id = $user_id;
if ($is_librarian && isset($_GET["user_id"])) {
  $view_user_id = (int) $_GET["user_id"];

  // Verify the requested user exists
  $stmt = $pdo->prepare(
    "SELECT id, CONCAT(first_name, ' ', last_name) as full_name FROM users WHERE id = ?"
  );
  $stmt->execute([$view_user_id]);
  $view_user = $stmt->fetch();

  if (!$view_user) {
    $_SESSION["error"] = "User not found!";
    header("Location: dashboard.php");
    exit();
  }
}

// Build base query
$query = "SELECT br.*, b.title as book_title, b.author,
          CONCAT(u.first_name, ' ', u.last_name) as user_name,
          DATEDIFF(IFNULL(br.return_date, NOW()), br.borrow_date) as days_borrowed,
          CASE 
            WHEN br.status = 'borrowed' AND br.due_date < NOW() THEN 'overdue'
            ELSE br.status
          END as display_status
          FROM borrow_records br
          JOIN books b ON br.book_id = b.id
          JOIN users u ON br.user_id = u.id
          WHERE br.user_id = ?";

// Add filters if present
$filters = [];
$params = [$view_user_id];

if (
  isset($_GET["status"]) &&
  in_array($_GET["status"], ["borrowed", "returned", "overdue", "lost"])
) {
  if ($_GET["status"] == "overdue") {
    $query .= " AND (br.status = 'borrowed' AND br.due_date < NOW())";
  } else {
    $query .= " AND br.status = ?";
    $params[] = $_GET["status"];
  }
  $filters["status"] = $_GET["status"];
}

if (isset($_GET["book"]) && !empty($_GET["book"])) {
  $query .= " AND (b.title LIKE ? OR b.author LIKE ?)";
  $params[] = "%{$_GET["book"]}%";
  $params[] = "%{$_GET["book"]}%";
  $filters["book"] = $_GET["book"];
}

if (isset($_GET["from_date"]) && !empty($_GET["from_date"])) {
  $query .= " AND br.borrow_date >= ?";
  $params[] = $_GET["from_date"];
  $filters["from_date"] = $_GET["from_date"];
}

if (isset($_GET["to_date"]) && !empty($_GET["to_date"])) {
  $query .= " AND br.borrow_date <= ?";
  $params[] = $_GET["to_date"] . " 23:59:59";
  $filters["to_date"] = $_GET["to_date"];
}

// Complete query
$query .= " ORDER BY br.borrow_date DESC";

// Prepare and execute
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$history = $stmt->fetchAll();

// Get current borrowed books count
$current_borrowed = $pdo->prepare("SELECT COUNT(*) FROM borrow_records 
                                  WHERE user_id = ? AND status = 'borrowed'");
$current_borrowed->execute([$view_user_id]);
$current_count = $current_borrowed->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library System - <?php echo $is_librarian && isset($view_user)
      ? "Borrow History for {$view_user["full_name"]}"
      : "My Borrow History"; ?></title>
    <link rel="stylesheet" href="assets/css/stylesheet.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Internal styles specific to this page */
        .content-wrapper {
            padding: 20px;
        }
        
        .section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .filters {
            margin-bottom: 20px;
        }
        
        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 10px;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .filter-group input, 
        .filter-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .stats {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 15px;
            flex: 1;
        }
        
        .stat-card h3 {
            margin-top: 0;
            color: #555;
            font-size: 1em;
        }
        
        .stat-card .value {
            font-size: 1.5em;
            font-weight: bold;
        }
        
        .btn-reset {
            background-color: #6c757d;
        }
        
        .btn-reset:hover {
            background-color: #5a6268;
        }
        
        .history-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .history-table th, 
        .history-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .history-table th {
            background-color: #f8f9fa;
            position: sticky;
            top: 0;
        }
        
        .history-table tr:hover {
            background-color: #f5f5f5;
        }
        
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8em;
        }
        
        .status-borrowed {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-returned {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-overdue {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-lost {
            background-color: #d6d8d9;
            color: #1b1e21;
        }
        
        .error {
            color: #dc3545;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
        }
        
        .success {
            color: #28a745;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
        }
        
        .no-data {
            text-align: center;
            padding: 20px;
            color: #6c757d;
        }
        
        .no-data i {
            font-size: 2em;
            margin-bottom: 10px;
            display: block;
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
            <?php if ($is_librarian): ?>
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
                <a href="view_borrow_history.php" class="active">>
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
                <h1><?php echo $is_librarian && isset($view_user)
                  ? "Borrow History for {$view_user["full_name"]}"
                  : "My Borrow History"; ?></h1>
            </div>
        </header>

<main class="content-wrapper">
    <section class="section">
        <?php if (isset($_SESSION["error"])): ?>
            <div class="error"><?php
            echo htmlspecialchars($_SESSION["error"]);
            unset($_SESSION["error"]);
            ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION["success"])): ?>
            <div class="success"><?php
            echo htmlspecialchars($_SESSION["success"]);
            unset($_SESSION["success"]);
            ?></div>
        <?php endif; ?>
        
        <!-- Stats Cards - Simplified for regular users -->
        <div class="stats">
            <div class="stat-card">
                <h3>Total Borrowed</h3>
                <div class="value"><?php echo count($history); ?></div>
            </div>
            <div class="stat-card">
                <h3>Currently Borrowed</h3>
                <div class="value"><?php echo $current_count; ?></div>
            </div>
            <div class="stat-card">
                <h3>Overdue Books</h3>
                <div class="value">
                    <?php
                    $overdue = array_reduce(
                      $history,
                      function ($carry, $item) {
                        return $carry +
                          ($item["display_status"] == "overdue" ? 1 : 0);
                      },
                      0
                    );
                    echo $overdue;
                    ?>
                </div>
            </div>
        </div>
        
        <!-- Simplified Filters -->
        <div class="filters">
            <form method="GET" action="view_borrow_history.php">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="status">Status:</label>
                        <select id="status" name="status">
                            <option value="">All Statuses</option>
                            <option value="borrowed" <?php echo isset(
                              $filters["status"]
                            ) && $filters["status"] == "borrowed"
                              ? "selected"
                              : ""; ?>>Borrowed</option>
                            <option value="returned" <?php echo isset(
                              $filters["status"]
                            ) && $filters["status"] == "returned"
                              ? "selected"
                              : ""; ?>>Returned</option>
                            <option value="overdue" <?php echo isset(
                              $filters["status"]
                            ) && $filters["status"] == "overdue"
                              ? "selected"
                              : ""; ?>>Overdue</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="book">Book/Author:</label>
                        <input type="text" id="book" name="book" value="<?php echo isset(
                          $filters["book"]
                        )
                          ? htmlspecialchars($filters["book"])
                          : ""; ?>" placeholder="Title or author">
                    </div>
                </div>
                
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="from_date">From Date:</label>
                        <input type="date" id="from_date" name="from_date" value="<?php echo isset(
                          $filters["from_date"]
                        )
                          ? htmlspecialchars($filters["from_date"])
                          : ""; ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="to_date">To Date:</label>
                        <input type="date" id="to_date" name="to_date" value="<?php echo isset(
                          $filters["to_date"]
                        )
                          ? htmlspecialchars($filters["to_date"])
                          : ""; ?>">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="view_borrow_history.php" class="btn btn-reset">Reset</a>
            </form>
        </div>
        
        <!-- History Table - Simplified -->
        <div style="overflow-x: auto;">
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Book</th>
                        <th>Author</th>
                        <th>Borrow Date</th>
                        <th>Due Date</th>
                        <th>Return Date</th>
                        <th>Days Borrowed</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($history)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">
                                <div class="no-data">
                                    <i class="fas fa-book-open"></i>
                                    <p>No borrow records found</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($history as $record): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(
                                  $record["book_title"]
                                ); ?></td>
                                <td><?php echo htmlspecialchars(
                                  $record["author"]
                                ); ?></td>
                                <td><?php echo htmlspecialchars(
                                  $record["borrow_date"]
                                ); ?></td>
                                <td><?php echo htmlspecialchars(
                                  $record["due_date"]
                                ); ?></td>
                                <td><?php echo $record["return_date"]
                                  ? htmlspecialchars($record["return_date"])
                                  : "--"; ?></td>
                                <td><?php echo $record["days_borrowed"]; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo htmlspecialchars(
                                      $record["display_status"]
                                    ); ?>">
                                        <?php echo ucfirst(
                                          htmlspecialchars(
                                            $record["display_status"]
                                          )
                                        ); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
    </div>

    <script src="assets/js/scriptsheet.js"></script>
</body>
</html>