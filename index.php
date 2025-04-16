<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OPOL COMMUNITY COLLEGE - LIBRARY MANAGEMENT SYSTEM</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Header -->
    <header id="main-header" class="header-default">
        <div class="container mx-auto px-1 py-4 flex justify-between items-center">
            <!-- Logo and College Name -->
            <div class="flex items-center">
                <img src="assets/images/occ-logo.png" alt="OCC Logo" class="h-12 mr-3">
                <div>
                    <h1 class="text-xl font-bold text-blue-800">OPOL COMMUNITY COLLEGE</h1>
                    <p class="text-sm font-900 text-gray-900">LIBRARY MANAGEMENT SYSTEM</p>
                </div>
            </div>

            <!-- Desktop Navigation -->
            <nav class="desktop-nav">
                <a href="#home" class="nav-links">Home</a>
                <a href="#about" class="nav-links">About</a>
                <a href="#services" class="nav-links">Services</a>
                <a href="#contact" class="nav-links">Contact</a>
                <a href="auth.php?action=register" class="signup-btn">Sign Up Now</a>
            </nav>

            <!-- Mobile Menu Button -->
            <button id="mobile-menu-button" class="mobile-menu-btn">
                <i class="fas fa-bars"></i>
            </button>
        </div>

        <!-- Mobile Navigation -->
        <div id="mobile-menu" class="mobile-menu">
            <div class="container mx-auto px-4 py-2 flex flex-col">
                <a href="#home" class="mobile-nav-link">Home</a>
                <a href="#about" class="mobile-nav-link">About</a>
                <a href="#services" class="mobile-nav-link">Services</a>
                <a href="#contact" class="mobile-nav-link">Contact</a>
                <a href="auth.php?action=register" class="mobile-signup-btn">Sign Up Now</a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main>
        <!-- Home Section -->
        <section id="home" class="section-home">
            <div class="container mx-auto max-w-6xl">
                <div class="content-box">
                    <div class="text-content">
                        <h2 class="section-title">Welcome to OCC Library</h2>
                        <p class="section-description">A modern digital gateway to knowledge and resources for all OCC students and faculty.</p>
                        <div class="btn-group">
                            <a href="auth.php?action=register" class="primary-btn">Get Started</a>
                            <a href="#services" class="secondary-btn">Explore Services</a>
                        </div>
                    </div>
                    <div class="image-content">
                        <img src="assets/images/home.png" alt="Modern Library">
                    </div>
                </div>
            </div>
        </section>

        <!-- About Section -->
        <section id="about" class="section-about">
            <div class="container mx-auto max-w-6xl">
                <div class="content-box">
                    <div class="image-content">
                        <img src="assets/images/about.png" alt="About OCC Library">
                    </div>
                    <div class="text-content">
                        <h2 class="section-title">About Our Library</h2>
                        <div class="prose-content">
                            <p>The OPOL COMMUNITY COLLEGE Library is a state-of-the-art facility dedicated to supporting the academic needs of our students and faculty.</p>
                            <p>Established in 2005, our library has grown to house over 50,000 physical volumes and provides access to more than 100,000 digital resources.</p>
                            <ul class="features-list">
                                <li>Open 7 days a week during academic terms</li>
                                <li>Dedicated study spaces for individuals and groups</li>
                                <li>Computer workstations with academic software</li>
                                <li>Professional librarians available for research assistance</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Services Section -->
        <section id="services" class="section-services">
            <div class="container mx-auto max-w-6xl">
   
                
                <div class="content-box">
                    <div class="text-content">
                        <div class="prose-content">
                            <h3 class="section-title">WHAT WE OFFER</h3>
                            <ul class="services-list">
                                <li class="service-item">
                                    <i class="fas fa-book service-icon"></i>
                                    <span>Book Borrowing: Access our extensive collection of academic and leisure reading materials</span>
                                </li>
                                <li class="service-item">
                                    <i class="fas fa-search service-icon"></i>
                                    <span>Research Assistance: Get help from our expert librarians for your academic projects</span>
                                </li>
                                <li class="service-item">
                                    <i class="fas fa-laptop service-icon"></i>
                                    <span>Online Catalog: Search and reserve books through our user-friendly digital system</span>
                                </li>
                                <li class="service-item">
                                    <i class="fas fa-database service-icon"></i>
                                    <span>Digital Resources: Access e-books, journals, and databases from anywhere</span>
                                </li>
                                <li class="service-item">
                                    <i class="fas fa-users service-icon"></i>
                                    <span>Study Spaces: Comfortable and quiet areas for individual and group study</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="image-content">
                        <img src="assets/images/services.png" alt="Library Services">
                    </div>
                </div>
            </div>
        </section>

        <!-- Contact Section -->
        <section id="contact" class="section-contact">
            <div class="container mx-auto max-w-6xl">
                <div class="content-box">
                    <div class="image-content">
                        <img src="assets/images/contact.png" alt="Contact OCC Library">
                    </div>
                    <div class="text-content">
                        <h2 class="section-title">Contact Us</h2>
                        <div class="contact-info">
                            <div class="info-group">
                                <h3>Library Hours</h3>
                                <p>Monday-Friday: 8:00 AM - 8:00 PM<br>
                                Saturday: 9:00 AM - 5:00 PM<br>
                                Sunday: 10:00 AM - 4:00 PM</p>
                            </div>
                            <div class="info-group">
                                <h3>Location</h3>
                                <p>Main Campus Building, 2nd Floor<br>
                                OPOL, Misamis Oriental</p>
                            </div>
                            <div class="info-group">
                                <h3>Contact Information</h3>
                                <p>Email: library@occ.edu.ph<br>
                                Phone: (088) 123-4567</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container mx-auto px-4">
            <div class="footer-content">
                <div class="footer-logo">
                    <div class="flex items-center">
                        <img src="assets/images/occ-logo.png" alt="OCC Logo" class="h-10 mr-3">
                        <div>
                            <h3>OPOL COMMUNITY COLLEGE</h3>
                            <p>Library Management System</p>
                        </div>
                    </div>
                </div>
                <div class="footer-copyright">
                    <p>&copy; 2023 OPOL COMMUNITY COLLEGE. All rights reserved.</p>
                    <p>Developed by Norway P. Mangorangca, 2nd Year BSIT Student</p>

                </div>
            </div>
        </div>
    </footer>

    <script src="assets/js/script.js"></script>
</body>
</html>