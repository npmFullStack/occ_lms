document.addEventListener("DOMContentLoaded", function () {
    // Mobile menu elements
    const mobileMenuButton = document.getElementById("mobile-menu-button");
    const mobileMenu = document.getElementById("mobile-menu");
    const header = document.getElementById("main-header");
    const body = document.body;

    // Create overlay element
    const overlay = document.createElement("div");
    overlay.className = "mobile-menu-overlay";
    document.body.appendChild(overlay);

    // Toggle mobile menu
    mobileMenuButton.addEventListener("click", e => {
        e.stopPropagation(); // Prevent event bubbling
        mobileMenu.classList.toggle("open");
        overlay.classList.toggle("active");
        body.classList.toggle("no-scroll");

        // Toggle menu icon
        const icon = mobileMenuButton.querySelector("i");
        if (mobileMenu.classList.contains("open")) {
            icon.classList.remove("fa-bars");
            icon.classList.add("fa-close");
        } else {
            icon.classList.remove("fa-close");
            icon.classList.add("fa-bars");
        }
    });

    // Close mobile menu when clicking overlay
    overlay.addEventListener("click", () => {
        mobileMenu.classList.remove("open");
        overlay.classList.remove("active");
        body.classList.remove("no-scroll");

        // Reset menu icon
        const icon = mobileMenuButton.querySelector("i");
        icon.classList.remove("fa-close");
        icon.classList.add("fa-bars");
    });

    // Close mobile menu when clicking a link
    const mobileLinks = document.querySelectorAll(
        ".mobile-nav-link, .mobile-signup-btn"
    );
    mobileLinks.forEach(link => {
        link.addEventListener("click", () => {
            mobileMenu.classList.remove("open");
            overlay.classList.remove("active");
            body.classList.remove("no-scroll");

            // Reset menu icon
            const icon = mobileMenuButton.querySelector("i");
            icon.classList.remove("fa-close");
            icon.classList.add("fa-bars");
        });
    });

    // Close menu when clicking outside (on body)
    body.addEventListener("click", e => {
        if (
            mobileMenu.classList.contains("open") &&
            !mobileMenu.contains(e.target) &&
            e.target !== mobileMenuButton
        ) {
            mobileMenu.classList.remove("open");
            overlay.classList.remove("active");
            body.classList.remove("no-scroll");

            // Reset menu icon
            const icon = mobileMenuButton.querySelector("i");
            icon.classList.remove("fa-close");
            icon.classList.add("fa-bars");
        }
    });

    // Header scroll effect
    let lastScrollPosition = window.scrollY;

    window.addEventListener("scroll", () => {
        const currentScrollPosition = window.scrollY;

        if (
            currentScrollPosition > lastScrollPosition &&
            currentScrollPosition > 100
        ) {
            // Scrolling down
            header.classList.remove("header-scrolled");
        } else if (
            currentScrollPosition < lastScrollPosition &&
            currentScrollPosition > 50
        ) {
            // Scrolling up
            header.classList.add("header-scrolled");
        } else {
            // At top of page
            header.classList.remove("header-scrolled");
        }

        lastScrollPosition = currentScrollPosition;
    });

    // Prevent body scroll when menu is open
    document.addEventListener("keydown", function (e) {
        if (mobileMenu.classList.contains("open") && e.key === "Escape") {
            mobileMenu.classList.remove("open");
            overlay.classList.remove("active");
            body.classList.remove("no-scroll");

            // Reset menu icon
            const icon = mobileMenuButton.querySelector("i");
            icon.classList.remove("fa-close");
            icon.classList.add("fa-bars");
        }
    });
});

// Add no-scroll class to body when menu is open
function handleScroll() {
    document.body.classList.toggle(
        "no-scroll",
        document.getElementById("mobile-menu").classList.contains("open")
    );
}