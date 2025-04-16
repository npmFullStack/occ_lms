<?php
session_start();
require "db.php";

// Redirect if not librarian
if (!isset($_SESSION["user_id"])) {
  header("Location: auth.php");
  exit();
}

$role = $_SESSION["role"]; // Added this line to match dashboard.php
$librarian_id = $_SESSION["user_id"];
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $title = trim($_POST["title"]);
  $author = trim($_POST["author"]);
  $isbn = trim($_POST["isbn"] ?? "");
  $publisher = trim($_POST["publisher"] ?? "");
  $publication_year = trim($_POST["publication_year"] ?? null);
  $quantity = (int) $_POST["quantity"];
  $description = trim($_POST["description"] ?? "");
  $category = trim($_POST["category"] ?? "");
  $location = trim($_POST["location"] ?? "");
  $new_category = trim($_POST["new_category"] ?? "");

  // Handle category selection
  if (!empty($new_category)) {
    $category = $new_category;

    // Check if category exists, if not add it
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
    $stmt->execute([$category]);

    if ($stmt->rowCount() == 0) {
      $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
      $stmt->execute([$category]);
    }
  }

  // Handle file upload
  $cover_image = null;
  if (
    isset($_FILES["cover_image"]) &&
    $_FILES["cover_image"]["error"] == UPLOAD_ERR_OK
  ) {
    $upload_dir = "uploads/book_covers/";
    if (!file_exists($upload_dir)) {
      mkdir($upload_dir, 0777, true);
    }

    $file_ext = pathinfo($_FILES["cover_image"]["name"], PATHINFO_EXTENSION);
    $file_name = uniqid("book_cover_", true) . "." . $file_ext;
    $file_path = $upload_dir . $file_name;

    // Validate image
    $allowed_types = ["jpg", "jpeg", "png", "gif"];
    if (in_array(strtolower($file_ext), $allowed_types)) {
      if (move_uploaded_file($_FILES["cover_image"]["tmp_name"], $file_path)) {
        $cover_image = $file_path;
      } else {
        $error = "Failed to upload cover image.";
      }
    } else {
      $error = "Invalid file type. Only JPG, JPEG, PNG, GIF are allowed.";
    }
  }

  // Basic validation
  if (empty($title) || empty($author) || $quantity <= 0) {
    $error = "Title, author, and quantity are required!";
  } elseif (empty($error)) {
    $stmt = $pdo->prepare("INSERT INTO books 
                              (title, author, isbn, publisher, publication_year, quantity, 
                               available_quantity, description, category, location, cover_image, created_by) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
      $title,
      $author,
      $isbn,
      $publisher,
      $publication_year,
      $quantity,
      $quantity,
      $description,
      $category,
      $location,
      $cover_image,
      $librarian_id,
    ]);

    log_activity($librarian_id, "add_book", "Added new book: $title");
    $_SESSION["success"] = "Book added successfully!";
    header("Location: manage_books.php");
    exit();
  }
}

// Get existing categories for dropdown
$categories = $pdo
  ->query("SELECT name FROM categories ORDER BY name")
  ->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library System - Add New Book</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/stylesheet.css">
    <style>
        /* Additional styles specific to add_book.php */
        .book-form {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .category-select-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .category-select {
            flex: 1;
        }
        
        .category-or {
            color: #666;
        }
        
        .category-input {
            flex: 1;
        }
        
        .form-actions {
            grid-column: 1 / -1;
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .file-hint {
            display: block;
            color: #666;
            font-size: 0.8em;
            margin-top: 5px;
        }
        
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #c62828;
        }
        
        /* Image upload box styling */
        .image-upload-container {
            grid-column: 1 / -1;
        }
        
        .image-upload-box {
            border: 2px dashed #ccc;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            height: 200px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .image-upload-box:hover {
            border-color: #999;
        }
        
        .image-upload-box i {
            font-size: 48px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .image-upload-box .upload-text {
            color: #666;
        }
        
        .image-upload-box.has-image {
            border-style: solid;
        }
        
        .image-upload-box.has-image i,
        .image-upload-box.has-image .upload-text {
            display: none;
        }
        
        .image-upload-box .preview-image {
            max-width: 100%;
            max-height: 100%;
            display: none;
        }
        
        #cover_image {
            display: none;
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
                <h1>Add New Book</h1>
            </div>

        </header>

        <main class="content-wrapper">
            <section class="section">
                <h2 class="section-title"><i class="fas fa-book-medical"></i> Add New Book</h2>
                
                <?php if (!empty($error)): ?>
                    <div class="error-message"><?php echo htmlspecialchars(
                      $error
                    ); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="add_book.php" enctype="multipart/form-data" class="book-form">
                    <div class="form-group">
                        <label for="title">Title*</label>
                        <input type="text" id="title" name="title" required value="<?php echo isset(
                          $_POST["title"]
                        )
                          ? htmlspecialchars($_POST["title"])
                          : ""; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="author">Author*</label>
                        <input type="text" id="author" name="author" required value="<?php echo isset(
                          $_POST["author"]
                        )
                          ? htmlspecialchars($_POST["author"])
                          : ""; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="isbn">ISBN</label>
                        <input type="text" id="isbn" name="isbn" value="<?php echo isset(
                          $_POST["isbn"]
                        )
                          ? htmlspecialchars($_POST["isbn"])
                          : ""; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="publisher">Publisher</label>
                        <input type="text" id="publisher" name="publisher" value="<?php echo isset(
                          $_POST["publisher"]
                        )
                          ? htmlspecialchars($_POST["publisher"])
                          : ""; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="publication_year">Publication Year</label>
                        <input type="number" id="publication_year" name="publication_year" min="1000" max="<?php echo date(
                          "Y"
                        ); ?>" 
                               value="<?php echo isset(
                                 $_POST["publication_year"]
                               )
                                 ? htmlspecialchars($_POST["publication_year"])
                                 : ""; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="quantity">Quantity*</label>
                        <input type="number" id="quantity" name="quantity" min="1" required 
                               value="<?php echo isset($_POST["quantity"])
                                 ? htmlspecialchars($_POST["quantity"])
                                 : "1"; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Category</label>
                        <div class="category-select-container">
                            <select id="category" name="category" class="category-select">
                                <option value="">-- Select Category --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars(
                                      $cat["name"]
                                    ); ?>" 
                                        <?php if (
                                          isset($_POST["category"]) &&
                                          $_POST["category"] == $cat["name"]
                                        ) {
                                          echo "selected";
                                        } ?>>
                                        <?php echo htmlspecialchars(
                                          $cat["name"]
                                        ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="category-or">or</span>
                            <input type="text" id="new_category" name="new_category" class="category-input" 
                                   placeholder="Add new category" value="<?php echo isset(
                                     $_POST["new_category"]
                                   )
                                     ? htmlspecialchars($_POST["new_category"])
                                     : ""; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" value="<?php echo isset(
                          $_POST["location"]
                        )
                          ? htmlspecialchars($_POST["location"])
                          : ""; ?>">
                    </div>
                    
                    <div class="form-group image-upload-container">
                        <label>Cover Image</label>
                        <div class="image-upload-box" id="imageUploadBox">
                            <i class="fas fa-image"></i>
                            <div class="upload-text">Click to upload book cover</div>
                            <img class="preview-image" id="previewImage" src="#" alt="Preview">
                            <input type="file" id="cover_image" name="cover_image" accept="image/*">
                        </div>
                        <small class="file-hint">Accepted formats: JPG, PNG, GIF (Max 2MB)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description"><?php echo isset(
                          $_POST["description"]
                        )
                          ? htmlspecialchars($_POST["description"])
                          : ""; ?></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i> Add Book
                        </button>
                        <a href="manage_books.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </section>
        </main>
    </div>

    <script src="assets/js/scriptsheet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Category select/new input toggle
            const categorySelect = document.getElementById('category');
            const newCategoryInput = document.getElementById('new_category');
            
            categorySelect.addEventListener('change', function() {
                if (this.value) {
                    newCategoryInput.value = '';
                }
            });
            
            newCategoryInput.addEventListener('input', function() {
                if (this.value) {
                    categorySelect.value = '';
                }
            });
            
            // Image upload box functionality
            const imageUploadBox = document.getElementById('imageUploadBox');
            const coverImageInput = document.getElementById('cover_image');
            const previewImage = document.getElementById('previewImage');
            
            imageUploadBox.addEventListener('click', function() {
                coverImageInput.click();
            });
            
            coverImageInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        previewImage.src = e.target.result;
                        previewImage.style.display = 'block';
                        imageUploadBox.classList.add('has-image');
                    }
                    
                    reader.readAsDataURL(this.files[0]);
                }
            });
        });
    </script>
</body>
</html>