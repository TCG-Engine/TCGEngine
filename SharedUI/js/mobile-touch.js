/**
 * Mobile touch handling for TCGEngine
 * This script improves touch interactions on mobile devices
 * Fixed to work better with the burger menu
 */

document.addEventListener('DOMContentLoaded', function() {
  // Only run this code on mobile devices
  if (window.innerWidth <= 768) {
    
    // Improve tap response time
    const allClickables = document.querySelectorAll('a, button, [onclick], .tab-button');
    allClickables.forEach(element => {
      // Skip the burger menu button and its children to prevent conflicts
      if (element.closest('.burger-menu')) return;
      
      // Add touch highlight effect
      element.addEventListener('touchstart', function(e) {
        this.classList.add('touch-active');
      });
      
      element.addEventListener('touchend', function(e) {
        this.classList.remove('touch-active');
      });
      
      // Handle links and buttons - but don't interfere with the menu functionality
      element.addEventListener('click', function(e) {
        // Don't prevent default for menu-related elements
        if (this.closest('.nav-bar') || this.classList.contains('burger-menu')) {
          return; // Let natural behavior happen for menu items
        }
        
        // For everything else, enhance the touch experience
        const href = this.getAttribute('href');
        
        // If it's a link with an href, handle navigation
        if (href && href !== '#' && !href.startsWith('javascript:')) {
          e.preventDefault(); // Prevent default only for actual navigation links
          setTimeout(() => {
            window.location.href = href;
          }, 50);
        }
      });
    });
    
    // Add swipe detection for tabs
    const tabContainers = document.querySelectorAll('.tabs');
    tabContainers.forEach(container => {
      let touchStartX = 0;
      let touchEndX = 0;
      
      container.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
      });
      
      container.addEventListener('touchend', function(e) {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
      });
      
      function handleSwipe() {
        const tabButtons = container.querySelectorAll('.tab-button');
        const activeIndex = Array.from(tabButtons).findIndex(button => 
          button.classList.contains('active') || button.classList.contains('selected'));
        
        if (activeIndex === -1) return;
        
        // Swipe right to go to previous tab
        if (touchEndX - touchStartX > 50 && activeIndex > 0) {
          tabButtons[activeIndex - 1].click();
        }
        
        // Swipe left to go to next tab
        if (touchStartX - touchEndX > 50 && activeIndex < tabButtons.length - 1) {
          tabButtons[activeIndex + 1].click();
        }
      }
    });
    
    // Improve card zoom on mobile
    // DISABLED: Remove card image enlarge popup for deck images
    // const cardImages = document.querySelectorAll('img[src*="concat"]');
    // cardImages.forEach(card => {
    //   card.addEventListener('click', function(e) {
    //     e.stopPropagation();
    //     // Check if we already have an enlarged view
    //     if (document.querySelector('.mobile-card-enlarge')) {
    //       document.querySelector('.mobile-card-enlarge').remove();
    //       return;
    //     }
    //     // Create enlarged view
    //     const enlargedView = document.createElement('div');
    //     enlargedView.className = 'mobile-card-enlarge';
    //     const enlargedImg = document.createElement('img');
    //     enlargedImg.src = this.src;
    //     enlargedView.appendChild(enlargedImg);
    //     document.body.appendChild(enlargedView);
    //     // Close when clicked
    //     enlargedView.addEventListener('click', function() {
    //       this.remove();
    //     });
    //   });
    // }
  }
});
