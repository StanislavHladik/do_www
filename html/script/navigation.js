/**
 * Navigation helper functions
 */

// Function to highlight the current page button
function setActiveNavButton() {
    // Get current page filename
    var currentPage = window.location.pathname.split('/').pop().split('.')[0];
    
    // Remove active class from all buttons
    var navButtons = document.querySelectorAll('.nav-btn');
    navButtons.forEach(function(btn) {
        btn.classList.remove('active');
    });
    
    // Add active class to current page button
    var activeButton = null;
    switch(currentPage) {
        case 'Detekce':
            activeButton = document.querySelector('.nav-btn[href*="nahledy.php"]');
            break;
        case 'Výběr modelů':
            activeButton = document.querySelector('.nav-btn[href*="detection.php"]');
            break;
    }
    
    if (activeButton) {
        activeButton.classList.add('active');
    }
}

// Function to handle button click animations
function addButtonAnimations() {
    var navButtons = document.querySelectorAll('.nav-btn');
    
    navButtons.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            // Add ripple effect
            var ripple = document.createElement('span');
            ripple.className = 'ripple';
            this.appendChild(ripple);
            
            setTimeout(function() {
                ripple.remove();
            }, 600);
        });
    });
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    setActiveNavButton();
    addButtonAnimations();
});

// Function to update navigation URLs (useful for dynamic updates)
function updateNavigationUrls(cisloStroj, nazevStroj, popisStroj) {
    var navButtons = document.querySelectorAll('.nav-btn');
    var urlParams = `cisloStroj=${encodeURIComponent(cisloStroj)}&nazevStroj=${encodeURIComponent(nazevStroj)}&popisStroj=${encodeURIComponent(popisStroj)}`;
    
    navButtons.forEach(function(btn) {
        var href = btn.getAttribute('href');
        var basePage = href.split('?')[0];
        btn.setAttribute('href', basePage + '?' + urlParams);
    });
}

// Initialize navigation when page loads
document.addEventListener('DOMContentLoaded', function() {
    setActiveNavButton();
    addButtonAnimations();
});
