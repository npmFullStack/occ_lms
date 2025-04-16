<?php
// Start session and check if user is logged in and is a librarian
session_start();
if (!isset($_SESSION["user_id"])) {
  header("Location: login.php");
  exit();
}

if ($_SESSION["role"] !== "librarian") {
  header("Location: dashboard.php");
  exit();
}

// Include database connection
require_once "db.php";

// Fetch data for reports
try {
  // Total books
  $stmt = $pdo->query("SELECT COUNT(*) as total FROM books");
  $total_books = $stmt->fetch()["total"];

  // Available books
  $stmt = $pdo->query(
    "SELECT COUNT(*) as available FROM books WHERE status = 'available'"
  );
  $available_books = $stmt->fetch()["available"];

  // Borrowed books
  $stmt = $pdo->query(
    "SELECT COUNT(*) as borrowed FROM borrow_records WHERE status = 'borrowed'"
  );
  $borrowed_books = $stmt->fetch()["borrowed"];

  // Overdue books
  $stmt = $pdo->query(
    "SELECT COUNT(*) as overdue FROM borrow_records WHERE status = 'overdue'"
  );
  $overdue_books = $stmt->fetch()["overdue"];

  // Total students
  $stmt = $pdo->query(
    "SELECT COUNT(*) as students FROM users WHERE role = 'student' AND status = 'active'"
  );
  $total_students = $stmt->fetch()["students"];

  // Recent borrowings (last 30 days)
  $stmt = $pdo->query("SELECT b.title, b.author, u.first_name, u.last_name, br.borrow_date, br.due_date 
                         FROM borrow_records br
                         JOIN books b ON br.book_id = b.id
                         JOIN users u ON br.user_id = u.id
                         WHERE br.borrow_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                         ORDER BY br.borrow_date DESC
                         LIMIT 10");
  $recent_borrowings = $stmt->fetchAll();

  // Most borrowed books
  $stmt = $pdo->query("SELECT b.title, b.author, COUNT(br.id) as borrow_count
                         FROM borrow_records br
                         JOIN books b ON br.book_id = b.id
                         GROUP BY b.title, b.author
                         ORDER BY borrow_count DESC
                         LIMIT 5");
  $popular_books = $stmt->fetchAll();

  // Fines summary - with COALESCE to handle NULL values
  $stmt = $pdo->query("SELECT 
                            COALESCE(SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END), 0) as pending_fines,
                            COALESCE(SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END), 0) as paid_fines,
                            COALESCE(SUM(amount), 0) as total_fines
                          FROM fines");
  $fines_summary = $stmt->fetch();
} catch (PDOException $e) {
  die("Error fetching report data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Reports - OPOL COMMUNITY COLLEGE</title>
    <link rel="stylesheet" href="assets/css/stylesheet.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        /* Additional styles specific to report page */
        .report-section {
            margin-bottom: 2rem;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 1.5rem;
        }
        
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 1rem;
        }
        
        .report-title {
            font-size: 1.25rem;
            color: #333;
            margin: 0;
        }
        
        .print-btn {
            background: #4a6da7;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background 0.3s;
        }
        
        .print-btn:hover {
            background: #3a5a8a;
        }
        
        .report-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .report-card {
            background: #f9f9f9;
            border-radius: 6px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .report-card h3 {
            margin-top: 0;
            color: #555;
            font-size: 1rem;
            border-bottom: 1px solid #ddd;
            padding-bottom: 0.5rem;
        }
        
        .report-card .value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #2c3e50;
            margin: 0.5rem 0;
        }
        
        .report-card .label {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .report-table th, .report-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .report-table th {
            background: #f5f5f5;
            font-weight: 600;
            color: #333;
        }
        
        .report-table tr:hover {
            background: #f9f9f9;
        }
        
        .chart-container {
            height: 300px;
            margin-top: 1.5rem;
        }
        
        @media print {
            .sidebar, .toggle-sidebar, .print-btn {
                display: none !important;
            }
            
            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
            }
            
            .report-section {
                page-break-inside: avoid;
            }
            
            body {
                background: white !important;
                color: black !important;
                font-size: 12pt !important;
            }
            
            .report-header {
                border-bottom: 2px solid #000 !important;
            }
            
            .report-card {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
            }
            
            .report-table th {
                background: #ddd !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
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
            <a href="manage_requests.php">
                <i class="fas fa-clipboard-check"></i>
                <span>Book Requests</span>
            </a>
            <a href="report.php" class="active">
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
                <h1>Library Reports</h1>
            </div>
        </header>

        <main class="content-wrapper">
            <section class="report-section">
                <div class="report-header">
                    <h2 class="report-title"><i class="fas fa-chart-bar"></i> Library Statistics</h2>
                    <button class="print-btn" onclick="window.print()">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                </div>
                
                <div class="report-grid">
                    <div class="report-card">
                        <h3>Total Books</h3>
                        <div class="value"><?php echo $total_books; ?></div>
                        <div class="label">All books in the library collection</div>
                    </div>
                    
                    <div class="report-card">
                        <h3>Available Books</h3>
                        <div class="value"><?php echo $available_books; ?></div>
                        <div class="label">Books currently available for borrowing</div>
                    </div>
                    
                    <div class="report-card">
                        <h3>Borrowed Books</h3>
                        <div class="value"><?php echo $borrowed_books; ?></div>
                        <div class="label">Books currently checked out</div>
                    </div>
                    
                    <div class="report-card">
                        <h3>Overdue Books</h3>
                        <div class="value"><?php echo $overdue_books; ?></div>
                        <div class="label">Books past their due date</div>
                    </div>
                    
                    <div class="report-card">
                        <h3>Active Students</h3>
                        <div class="value"><?php echo $total_students; ?></div>
                        <div class="label">Students with active accounts</div>
                    </div>
                </div>
            </section>
            
            <section class="report-section">
                <div class="report-header">
                    <h2 class="report-title"><i class="fas fa-exchange-alt"></i> Recent Borrowings</h2>
                </div>
                
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Book Title</th>
                            <th>Author</th>
                            <th>Borrower</th>
                            <th>Borrow Date</th>
                            <th>Due Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_borrowings as $borrowing): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(
                              $borrowing["title"]
                            ); ?></td>
                            <td><?php echo htmlspecialchars(
                              $borrowing["author"]
                            ); ?></td>
                            <td><?php echo htmlspecialchars(
                              $borrowing["first_name"] .
                                " " .
                                $borrowing["last_name"]
                            ); ?></td>
                            <td><?php echo date(
                              "M d, Y",
                              strtotime($borrowing["borrow_date"])
                            ); ?></td>
                            <td><?php echo date(
                              "M d, Y",
                              strtotime($borrowing["due_date"])
                            ); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
            
            <section class="report-section">
                <div class="report-header">
                    <h2 class="report-title"><i class="fas fa-star"></i> Most Popular Books</h2>
                </div>
                
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Book Title</th>
                            <th>Author</th>
                            <th>Borrow Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($popular_books as $index => $book): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars(
                              $book["title"]
                            ); ?></td>
                            <td><?php echo htmlspecialchars(
                              $book["author"]
                            ); ?></td>
                            <td><?php echo $book["borrow_count"]; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
            
            <section class="report-section">
                <div class="report-header">
                    <h2 class="report-title"><i class="fas fa-money-bill-wave"></i> Fines Summary</h2>
                </div>
                
                <div class="report-grid">
                    <div class="report-card">
                        <h3>Pending Fines</h3>
                        <div class="value">₱<?php echo number_format(
                          (float) $fines_summary["pending_fines"],
                          2
                        ); ?></div>
                        <div class="label">Unpaid fines by students</div>
                    </div>
                    
                    <div class="report-card">
                        <h3>Paid Fines</h3>
                        <div class="value">₱<?php echo number_format(
                          (float) $fines_summary["paid_fines"],
                          2
                        ); ?></div>
                        <div class="label">Fines that have been paid</div>
                    </div>
                    
                    <div class="report-card">
                        <h3>Total Fines</h3>
                        <div class="value">₱<?php echo number_format(
                          (float) $fines_summary["total_fines"],
                          2
                        ); ?></div>
                        <div class="label">All fines recorded</div>
                    </div>
                </div>
            </section>
        </main>
    </div>


    <script src="assets/js/scriptsheet.js"></script>


    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize jsPDF (using the global jsPDF instance from the UMD build)
            const { jsPDF } = window.jspdf;
            
            // PDF Generation Function
            function generatePDF() {
                // Show loading indicator
                const loadingIndicator = document.createElement('div');
                loadingIndicator.style.position = 'fixed';
                loadingIndicator.style.top = '0';
                loadingIndicator.style.left = '0';
                loadingIndicator.style.width = '100%';
                loadingIndicator.style.height = '100%';
                loadingIndicator.style.backgroundColor = 'rgba(0,0,0,0.7)';
                loadingIndicator.style.display = 'flex';
                loadingIndicator.style.justifyContent = 'center';
                loadingIndicator.style.alignItems = 'center';
                loadingIndicator.style.zIndex = '9999';
                loadingIndicator.innerHTML = '<div style="color: white; font-size: 1.5rem;">Generating PDF... Please wait</div>';
                document.body.appendChild(loadingIndicator);
                
                // Get the element to print
                const element = document.querySelector('.main-content');
                
                // Options for html2canvas
                const options = {
                    scale: 2, // Higher quality
                    useCORS: true, // Handle cross-origin images
                    allowTaint: true, // Handle tainted canvases
                    scrollY: 0, // Don't include scroll position
                    windowWidth: document.documentElement.scrollWidth,
                    windowHeight: document.documentElement.scrollHeight
                };
                
                // Generate PDF
                html2canvas(element, options).then(canvas => {
                    const imgData = canvas.toDataURL('image/png');
                    const pdf = new jsPDF({
                        orientation: 'portrait',
                        unit: 'mm'
                    });
                    
                    // Calculate PDF page size
                    const imgWidth = pdf.internal.pageSize.getWidth() - 20; // Margin
                    const imgHeight = (canvas.height * imgWidth) / canvas.width;
                    
                    // Add image to PDF
                    pdf.addImage(imgData, 'PNG', 10, 10, imgWidth, imgHeight);
                    
                    // Add header and footer
                    const date = new Date().toLocaleDateString();
                    pdf.setFontSize(10);
                    pdf.setTextColor(150);
                    pdf.text(`Generated on: ${date}`, 10, pdf.internal.pageSize.getHeight() - 10);
                    pdf.text('OPOL Community College Library System', pdf.internal.pageSize.getWidth() / 2, 5, { align: 'center' });
                    
                    // Save the PDF
                    pdf.save('Library_Report_' + new Date().toISOString().slice(0, 10) + '.pdf');
                    
                    // Remove loading indicator
                    document.body.removeChild(loadingIndicator);
                }).catch(err => {
                    console.error('Error generating PDF:', err);
                    alert('Error generating PDF. Please try again.');
                    document.body.removeChild(loadingIndicator);
                });
            }
            
            // Attach PDF generation to print button
            document.querySelector('.print-btn').addEventListener('click', generatePDF);
            
            // Keep the original print functionality as fallback
            document.querySelector('.print-btn').addEventListener('contextmenu', function(e) {
                e.preventDefault();
                window.print();
            });
        });
    </script>
</body>
</html>