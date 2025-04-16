<?php
session_start();
require "db.php";

// Redirect if not librarian
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "librarian") {
  header("Location: auth.php");
  exit();
}

$librarian_id = $_SESSION["user_id"];

// Process approve/reject actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $request_id = $_POST["request_id"] ?? 0;
  $action = $_POST["action"] ?? "";
  $notes = trim($_POST["notes"] ?? "");

  // Validate action
  if (!in_array($action, ["approve", "reject"])) {
    $_SESSION["error"] = "Invalid action!";
    header("Location: manage_requests.php");
    exit();
  }

  // Get request details
  $stmt = $pdo->prepare("SELECT br.*, b.title as book_title, b.available_quantity, 
                          u.first_name, u.last_name, u.id as student_id
                          FROM book_requests br
                          JOIN books b ON br.book_id = b.id
                          JOIN users u ON br.user_id = u.id
                          WHERE br.id = ? AND br.status = 'pending'");
  $stmt->execute([$request_id]);
  $request = $stmt->fetch();

  if (!$request) {
    $_SESSION["error"] = "Request not found or already processed!";
    header("Location: manage_requests.php");
    exit();
  }

  // Process approval
  if ($action == "approve") {
    // Check book availability
    if ($request["available_quantity"] < 1) {
      $_SESSION["error"] = "Book is no longer available!";
      header("Location: manage_requests.php");
      exit();
    }

    // Calculate due date (2 weeks from now)
    $due_date = date("Y-m-d H:i:s", strtotime("+2 weeks"));

    // Start transaction
    $pdo->beginTransaction();

    try {
      // Update request status
      $stmt = $pdo->prepare("UPDATE book_requests SET 
                                  status = 'approved', processed_by = ?, 
                                  processed_date = NOW(), notes = ?
                                  WHERE id = ?");
      $stmt->execute([$librarian_id, $notes, $request_id]);

      // Create borrow record
      $stmt = $pdo->prepare("INSERT INTO borrow_records 
                                  (book_id, user_id, borrow_date, due_date, approved_by)
                                  VALUES (?, ?, NOW(), ?, ?)");
      $stmt->execute([
        $request["book_id"],
        $request["user_id"],
        $due_date,
        $librarian_id,
      ]);

      // Update book available quantity
      $stmt = $pdo->prepare("UPDATE books SET 
                                  available_quantity = available_quantity - 1 
                                  WHERE id = ?");
      $stmt->execute([$request["book_id"]]);

      $pdo->commit();

      log_activity(
        $librarian_id,
        "approve_request",
        "Approved request #$request_id for book: {$request["book_title"]}"
      );
      $_SESSION["success"] = "Request approved successfully!";
    } catch (Exception $e) {
      $pdo->rollBack();
      $_SESSION["error"] = "Error processing request: " . $e->getMessage();
    }
  }
  // Process rejection
  else {
    $stmt = $pdo->prepare("UPDATE book_requests SET 
                              status = 'rejected', processed_by = ?, 
                              processed_date = NOW(), notes = ?
                              WHERE id = ?");
    $stmt->execute([$librarian_id, $notes, $request_id]);

    log_activity(
      $librarian_id,
      "reject_request",
      "Rejected request #$request_id for book: {$request["book_title"]}"
    );
    $_SESSION["success"] = "Request rejected successfully!";
  }

  header("Location: manage_requests.php");
  exit();
}

// Fetch pending requests with book and user info
$stmt = $pdo->query("SELECT br.*, b.title as book_title, b.available_quantity, 
                    u.first_name, u.last_name
                    FROM book_requests br
                    JOIN books b ON br.book_id = b.id
                    JOIN users u ON br.user_id = u.id
                    WHERE br.status = 'pending'
                    ORDER BY br.request_date DESC");
$requests = $stmt->fetchAll();

// Fetch recently processed requests (last 10)
$processed_requests = $pdo
  ->query(
    "SELECT br.*, b.title as book_title, 
                                  CONCAT(u.first_name, ' ', u.last_name) as student_name, 
                                  CONCAT(ub.first_name, ' ', ub.last_name) as processed_by_name
                                  FROM book_requests br
                                  JOIN books b ON br.book_id = b.id
                                  JOIN users u ON br.user_id = u.id
                                  LEFT JOIN users ub ON br.processed_by = ub.id
                                  WHERE br.status != 'pending'
                                  ORDER BY br.processed_date DESC LIMIT 10"
  )
  ->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library System - Manage Requests</title>
    <link rel="stylesheet" href="assets/css/stylesheet.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Internal styling for manage_requests.php specific elements */
        .request-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .request-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .request-card h3 {
            margin-top: 0;
            color: #2c3e50;
            font-size: 1.2rem;
        }
        
        .request-meta {
            color: #7f8c8d;
            font-size: 0.9em;
            margin-bottom: 15px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .request-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .request-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .notes-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-top: 10px;
            font-family: inherit;
            resize: vertical;
            min-height: 80px;
        }
        
        .notes-input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52,152,219,0.2);
        }
        
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 500;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .processed-requests-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .processed-requests-table th,
        .processed-requests-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .processed-requests-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .processed-requests-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .no-requests {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .no-requests i {
            font-size: 2rem;
            margin-bottom: 15px;
            color: #bdc3c7;
        }
        
        #success {
          border: none;
          background-color: #34a853;
          color: white;
        }
        
        #danger {
          border: none;
          color: white;
          background-color: #ea4335;
        }
        @media (max-width: 768px) {
            .request-meta {
                flex-direction: column;
                gap: 5px;
            }
            
            .request-actions {
                flex-direction: column;
            }
            
            .processed-requests-table {
                display: block;
                overflow-x: auto;
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

            <a href="manage_books.php">
                <i class="fas fa-book-medical"></i>
                <span>Manage Books</span>
            </a>
            <a href="manage_requests.php" class="active">
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
                <h1>Manage Requests</h1>
            </div>
        </header>

        <main class="content-wrapper">
            <section class="section">
                <h2 class="section-title"><i class="fas fa-clipboard-list"></i> Pending Book Requests</h2>
                
                <?php if (isset($_SESSION["error"])): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php
                        echo htmlspecialchars($_SESSION["error"]);
                        unset($_SESSION["error"]);
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION["success"])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php
                        echo htmlspecialchars($_SESSION["success"]);
                        unset($_SESSION["success"]);
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if (empty($requests)): ?>
                    <div class="no-requests">
                        <i class="fas fa-inbox"></i>
                        <p>No pending book requests found</p>
                    </div>
                <?php else: ?>
                    <div class="request-list">
                        <?php foreach ($requests as $request): ?>
                            <div class="request-card">
                                <h3><?php echo htmlspecialchars(
                                  $request["book_title"]
                                ); ?></h3>
                                <div class="request-meta">
                                    <span>
                                        <i class="fas fa-user"></i>
                                        <?php echo htmlspecialchars(
                                          $request["first_name"] .
                                            " " .
                                            $request["last_name"]
                                        ); ?>
                                    </span>
                                    <span>
                                        <i class="fas fa-calendar-alt"></i>
                                        <?php echo date(
                                          "M j, Y g:i A",
                                          strtotime($request["request_date"])
                                        ); ?>
                                    </span>
                                    <span>
                                        <i class="fas fa-copy"></i>
                                        Available: <?php echo htmlspecialchars(
                                          $request["available_quantity"]
                                        ); ?>
                                    </span>
                                </div>
                                
                                <form method="POST" action="manage_requests.php">
                                    <input type="hidden" name="request_id" value="<?php echo $request[
                                      "id"
                                    ]; ?>">
                                    <textarea name="notes" class="notes-input" placeholder="Add notes (optional)..."></textarea>
                                    <div class="request-actions">
                                        <button type="submit" name="action" value="approve" id="success" class="btn btn-success">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                        <button type="submit" name="action" value="reject" id="danger" class="btn btn-danger">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
            
            <section class="section">
                <h2 class="section-title"><i class="fas fa-history"></i> Recently Processed Requests</h2>
                
                <?php if (empty($processed_requests)): ?>
                    <div class="no-requests">
                        <i class="fas fa-inbox"></i>
                        <p>No processed requests found</p>
                    </div>
                <?php else: ?>
                    <table class="processed-requests-table">
                        <thead>
                            <tr>
                                <th>Book</th>
                                <th>Student</th>
                                <th>Status</th>
                                <th>Processed By</th>
                                <th>Date</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($processed_requests as $request): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(
                                      $request["book_title"]
                                    ); ?></td>
                                    <td><?php echo htmlspecialchars(
                                      $request["student_name"]
                                    ); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo htmlspecialchars(
                                          $request["status"]
                                        ); ?>">
                                            <?php echo ucfirst(
                                              htmlspecialchars(
                                                $request["status"]
                                              )
                                            ); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars(
                                      $request["processed_by_name"] ?? "System"
                                    ); ?></td>
                                    <td><?php echo date(
                                      "M j, Y g:i A",
                                      strtotime($request["processed_date"])
                                    ); ?></td>
                                    <td><?php echo htmlspecialchars(
                                      $request["notes"] ?? "N/A"
                                    ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <script src="assets/js/scriptsheet.js"></script>
</body>
</html>