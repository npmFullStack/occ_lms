document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.querySelector(".sidebar");
    const sidebarToggle = document.getElementById("sidebarToggle");
    const mainContent = document.querySelector(".main-content");
    // Toggle sidebar
    sidebarToggle.addEventListener("click", function (e) {
        e.stopPropagation(); // Prevent event from bubbling to document
        
        if (window.innerWidth <= 768) {
            // Mobile behavior - toggle show class
            sidebar.classList.toggle("show");
        } else {
            // Desktop behavior - toggle collapsed class
            sidebar.classList.toggle("collapsed");

            // Store preference in localStorage
            const isCollapsed = sidebar.classList.contains("collapsed");
            localStorage.setItem("sidebarCollapsed", isCollapsed);
        }
    });
    // Close sidebar when clicking outside
    document.addEventListener("click", function (e) {
        if (window.innerWidth <= 768 && 
            sidebar.classList.contains("show") && 
            !sidebar.contains(e.target) && 
            e.target !== sidebarToggle) {
            sidebar.classList.remove("show");
        }
    });
    // Check for saved preference
    if (localStorage.getItem("sidebarCollapsed") === "true" && window.innerWidth > 768) {
        sidebar.classList.add("collapsed");
    }
    // Responsive behavior
    function handleResponsive() {
        if (window.innerWidth <= 768) {
            // Mobile behavior
            sidebar.classList.remove("collapsed");
            if (!sidebar.classList.contains("show")) {
                sidebar.classList.remove("show");
            }
            document.body.style.overflowX = "hidden";
        } else {
            // Desktop behavior
            sidebar.classList.remove("show");
            // Restore collapsed state if it was collapsed
            if (localStorage.getItem("sidebarCollapsed") === "true") {
                sidebar.classList.add("collapsed");
            } else {
                sidebar.classList.remove("collapsed");
            }
        }
    }
    window.addEventListener("resize", handleResponsive);
    handleResponsive(); // Initial check
});