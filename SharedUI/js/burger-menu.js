/**
 * Burger menu functionality for mobile navigation
 * Improved version to fix menu display and interaction issues
 * Now preserves desktop styling while enabling mobile menu
 */

// Run initialization as soon as possible, don't wait for full DOM content
function initBurgerMenu() {
  // Only run this code on mobile devices
  if (window.innerWidth <= 768) {
    // Remove any existing burger menu to prevent duplicates
    const existingBurger = document.querySelector('.burger-menu');
    const existingOverlay = document.querySelector('.menu-overlay');
    if (existingBurger) existingBurger.remove();
    if (existingOverlay) existingOverlay.remove();
    
    // Create burger menu button
    const burgerMenu = document.createElement('div');
    burgerMenu.className = 'burger-menu';
    burgerMenu.innerHTML = '<span></span><span></span><span></span>';
    document.body.insertBefore(burgerMenu, document.body.firstChild);
    
    // Create overlay element
    const menuOverlay = document.createElement('div');
    menuOverlay.className = 'menu-overlay';
    menuOverlay.style.display = 'none';
    document.body.appendChild(menuOverlay);
    
    // Get the nav-bar element - don't modify its content
    const navBar = document.querySelector('.nav-bar');
    
    if (!navBar) return;
      // Ensure correct initial state but don't modify content
    navBar.style.right = '-100%';
    
    // Make sure nav items are visible in mobile view
    const navBarUser = navBar.querySelector('.nav-bar-user');
    const navBarLinks = navBar.querySelector('.nav-bar-links');
    
    if (navBarUser) navBarUser.style.display = 'block';
    if (navBarLinks) navBarLinks.style.display = 'block';
    
    // Toggle menu on burger click
    burgerMenu.addEventListener('click', function(e) {
      e.stopPropagation(); // Prevent event bubbling
      burgerMenu.classList.toggle('active');
      navBar.classList.toggle('active');
      menuOverlay.style.display = navBar.classList.contains('active') ? 'block' : 'none';
      document.body.classList.toggle('menu-open');
    });
    
    // Close menu when clicking on overlay
    menuOverlay.addEventListener('click', function(e) {
      e.stopPropagation(); // Prevent event bubbling
      burgerMenu.classList.remove('active');
      navBar.classList.remove('active');
      menuOverlay.style.display = 'none';
      document.body.classList.remove('menu-open');
    });
    
    // Close menu when clicking a nav item
    const navItems = navBar.querySelectorAll('a');
    navItems.forEach(item => {
      item.addEventListener('click', function() {
        burgerMenu.classList.remove('active');
        navBar.classList.remove('active');
        menuOverlay.style.display = 'none';
        document.body.classList.remove('menu-open');
      });
    });
    
    // Close menu on resize (if user switches to desktop view)
    window.addEventListener('resize', function() {
      if (window.innerWidth > 768) {
        burgerMenu.classList.remove('active');
        navBar.classList.remove('active');
        menuOverlay.style.display = 'none';
        document.body.classList.remove('menu-open');
      }
    });
  }
}

// Initialize immediately
initBurgerMenu();

// Also run on DOMContentLoaded to ensure it works after page is fully loaded
document.addEventListener('DOMContentLoaded', initBurgerMenu);
