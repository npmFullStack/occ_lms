<?php
session_start();
require_once 'config/db.php'; // Database connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php?action=login");
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];

// Logout logic
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | OCC LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .sidebar {
            transition: all 0.3s ease;
        }
        .sidebar-collapsed {
            width: 80px;
        }
        .sidebar-collapsed .sidebar-text {
            display: none;
        }
        .main-content {
            transition: all 0.3s ease;
        }
        .main-content-expanded {
            margin-left: 80px;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex">
        <!-- Sidebar -->
        <div id="sidebar" class="sidebar bg-blue-800 text-white h-screen fixed top-0 left-0 w-64 shadow-lg">
            <div class="p-4 flex items-center justify-between border-b border-blue-700">
                <div class="flex items-center">
                    <img src="./assets/images/occ-logo.png" alt="OCC Logo" class="h-10 mr-2">
                    <span class="sidebar-text font-bold">OCC LMS</span>
                </div>
                <button id="toggle-sidebar" class="text-white focus:outline-none">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <div class="p-4 border-b border-blue-700 flex items-center">
                <div class="bg-blue-600 rounded-full h-10 w-10 flex items-center justify-center">
                    <i class="fas fa-user"></i>
                </div>
                <div class="ml-3 sidebar-text">
                    <p class="font-medium"><?php echo $first_name . ' ' . $last_name; ?></p>
                    <p class="text-xs text-blue-200"><?php echo ucfirst($role); ?></p>
                </div>
            </div>
            
            <nav class="p-2">
                <ul>
                    <li class="mb-1">
                        <a href="dashboard.php" class="flex items-center p-3 rounded-lg hover:bg-blue-700 text-blue-100 hover:text-white">
                            <i class="fas fa-home mr-3"></i>
                            <span class="sidebar-text">Dashboard</span>
                        </a>
                    </li>
                    
                    <?php if ($role === 'student'): ?>
                        <li class="mb-1">
                            <a href="books.php" class="flex items-center p-3 rounded-lg hover:bg-blue-700 text-blue-100 hover:text-white">
                                <i class="fas fa-book mr-3"></i>
                                <span class="sidebar-text">Browse Books</span>
                            </a>
                        </li>
                        <li class="mb-1">
                            <a href="borrowed.php" class="flex items-center p-3 rounded-lg hover:bg-blue-700 text-blue-100 hover:text-white">
                                <i class="fas fa-list-ul mr-3"></i>
                                <span class="sidebar-text">My Borrowings</span>
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="mb-1">
                            <a href="manage_books.php" class="flex items-center p-3 rounded-lg hover:bg-blue-700 text-blue-100 hover:text-white">
                                <i class="fas fa-book mr-3"></i>
                                <span class="sidebar-text">Manage Books</span>
                            </a>
                        </li>
                        <li class="mb-1">
                            <a href="manage_users.php" class="flex items-center p-3 rounded-lg hover:bg-blue-700 text-blue-100 hover:text-white">
                                <i class="fas fa-users mr-3"></i>
                                <span class="sidebar-text">Manage Users</span>
                            </a>
                        </li>
                        <li class="mb-1">
                            <a href="transactions.php" class="flex items-center p-3 rounded-lg hover:bg-blue-700 text-blue-100 hover:text-white">
                                <i class="fas fa-exchange-alt mr-3"></i>
                                <span class="sidebar-text">Transactions</span>
                            </a>
                        </li>
                        <li class="mb-1">
                            <a href="reports.php" class="flex items-center p-3 rounded-lg hover:bg-blue-700 text-blue-100 hover:text-white">
                                <i class="fas fa-chart-bar mr-3"></i>
                                <span class="sidebar-text">Reports</span>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <li class="mb-1">
                        <a href="profile.php" class="flex items-center p-3 rounded-lg hover:bg-blue-700 text-blue-100 hover:text-white">
                            <i class="fas fa-user-cog mr-3"></i>
                            <span class="sidebar-text">Profile</span>
                        </a>
                    </li>
                    <li class="mb-1">
                        <a href="?logout" class="flex items-center p-3 rounded-lg hover:bg-blue-700 text-blue-100 hover:text-white">
                            <i class="fas fa-sign-out-alt mr-3"></i>
                            <span class="sidebar-text">Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div id="main-content" class="main-content flex-1 ml-64 min-h-screen">
            <!-- Top Navigation -->
            <header class="bg-white shadow-sm">
                <div class="flex justify-between items-center p-4">
                    <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <button class="text-gray-600 hover:text-gray-900 focus:outline-none">
                                <i class="fas fa-bell text-xl"></i>
                                <span class="absolute top-0 right-0 h-2 w-2 rounded-full bg-red-500"></span>
                            </button>
                        </div>
                        <div class="relative">
                            <button class="text-gray-600 hover:text-gray-900 focus:outline-none">
                                <i class="fas fa-envelope text-xl"></i>
                                <span class="absolute top-0 right-0 h-2 w-2 rounded-full bg-blue-500"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Dashboard Content -->
            <main class="p-6">
                <!-- Welcome Banner -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white rounded-xl p-6 mb-6 shadow-lg">
                    <h2 class="text-2xl font-bold mb-2">Welcome back, <?php echo $first_name; ?>!</h2>
                    <p class="opacity-90"><?php echo date('l, F j, Y'); ?></p>
                </div>
                
                <!-- Dashboard Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                    <?php if ($role === 'student'): ?>
                        <!-- Student Dashboard -->
                        <div class="bg-white rounded-xl shadow-md p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-gray-500 uppercase text-sm font-medium">Borrowed Books</h3>
                                    <p class="text-3xl font-bold mt-2">5</p>
                                </div>
                                <div class="bg-blue-100 p-3 rounded-full text-blue-600">
                                    <i class="fas fa-book-open text-xl"></i>
                                </div>
                            </div>
                            <a href="borrowed.php" class="mt-4 inline-block text-blue-600 hover:underline text-sm font-medium">View all</a>
                        </div>
                        
                        <div class="bg-white rounded-xl shadow-md p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-gray-500 uppercase text-sm font-medium">Overdue Books</h3>
                                    <p class="text-3xl font-bold mt-2">1</p>
                                </div>
                                <div class="bg-red-100 p-3 rounded-full text-red-600">
                                    <i class="fas fa-exclamation-triangle text-xl"></i>
                                </div>
                            </div>
                            <a href="borrowed.php" class="mt-4 inline-block text-blue-600 hover:underline text-sm font-medium">View details</a>
                        </div>
                        
                        <div class="bg-white rounded-xl shadow-md p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-gray-500 uppercase text-sm font-medium">Available Books</h3>
                                    <p class="text-3xl font-bold mt-2">1,245</p>
                                </div>
                                <div class="bg-green-100 p-3 rounded-full text-green-600">
                                    <i class="fas fa-book text-xl"></i>
                                </div>
                            </div>
                            <a href="books.php" class="mt-4 inline-block text-blue-600 hover:underline text-sm font-medium">Browse catalog</a>
                        </div>
                    <?php else: ?>
                        <!-- Librarian Dashboard -->
                        <div class="bg-white rounded-xl shadow-md p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-gray-500 uppercase text-sm font-medium">Total Books</h3>
                                    <p class="text-3xl font-bold mt-2">1,245</p>
                                </div>
                                <div class="bg-blue-100 p-3 rounded-full text-blue-600">
                                    <i class="fas fa-book text-xl"></i>
                                </div>
                            </div>
                            <a href="manage_books.php" class="mt-4 inline-block text-blue-600 hover:underline text-sm font-medium">Manage books</a>
                        </div>
                        
                        <div class="bg-white rounded-xl shadow-md p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-gray-500 uppercase text-sm font-medium">Active Borrowings</h3>
                                    <p class="text-3xl font-bold mt-2">87</p>
                                </div>
                                <div class="bg-green-100 p-3 rounded-full text-green-600">
                                    <i class="fas fa-exchange-alt text-xl"></i>
                                </div>
                            </div>
                            <a href="transactions.php" class="mt-4 inline-block text-blue-600 hover:underline text-sm font-medium">View transactions</a>
                        </div>
                        
                        <div class="bg-white rounded-xl shadow-md p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-gray-500 uppercase text-sm font-medium">Overdue Books</h3>
                                    <p class="text-3xl font-bold mt-2">12</p>
                                </div>
                                <div class="bg-red-100 p-3 rounded-full text-red-600">
                                    <i class="fas fa-exclamation-triangle text-xl"></i>
                                </div>
                            </div>
                            <a href="transactions.php?filter=overdue" class="mt-4 inline-block text-blue-600 hover:underline text-sm font-medium">View overdue</a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Recent Activity -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold text-gray-800">Recent Activity</h3>
                        <a href="#" class="text-blue-600 hover:underline text-sm">View all</a>
                    </div>
                    
                    <div class="space-y-4">
                        <?php if ($role === 'student'): ?>
                            <div class="flex items-start pb-4 border-b border-gray-100">
                                <div class="bg-blue-100 p-2 rounded-full mr-3 text-blue-600">
                                    <i class="fas fa-book"></i>
                                </div>
                                <div>
                                    <p class="font-medium">You borrowed "Introduction to Computer Science"</p>
                                    <p class="text-sm text-gray-500">2 days ago</p>
                                </div>
                            </div>
                            <div class="flex items-start pb-4 border-b border-gray-100">
                                <div class="bg-green-100 p-2 rounded-full mr-3 text-green-600">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div>
                                    <p class="font-medium">You returned "Database Systems"</p>
                                    <p class="text-sm text-gray-500">1 week ago</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="flex items-start pb-4 border-b border-gray-100">
                                <div class="bg-blue-100 p-2 rounded-full mr-3 text-blue-600">
                                    <i class="fas fa-user-plus"></i>
                                </div>
                                <div>
                                    <p class="font-medium">You added a new book "Advanced Web Development"</p>
                                    <p class="text-sm text-gray-500">3 hours ago</p>
                                </div>
                            </div>
                            <div class="flex items-start pb-4 border-b border-gray-100">
                                <div class="bg-purple-100 p-2 rounded-full mr-3 text-purple-600">
                                    <i class="fas fa-exchange-alt"></i>
                                </div>
                                <div>
                                    <p class="font-medium">Processed borrowing for Juan Dela Cruz</p>
                                    <p class="text-sm text-gray-500">Yesterday</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Toggle sidebar
        const toggleSidebar = document.getElementById('toggle-sidebar');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('main-content');
        
        toggleSidebar.addEventListener('click', () => {
            sidebar.classList.toggle('sidebar-collapsed');
            mainContent.classList.toggle('main-content-expanded');
        });
    </script>
</body>
</html>