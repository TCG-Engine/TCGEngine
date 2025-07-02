/**
 * Mobile orientation handler for TCGEngine
 * This script adjusts the UI when the device orientation changes
 */

document.addEventListener('DOMContentLoaded', function() {
  // Check if on mobile
  if (window.innerWidth <= 768) {
    // Initial orientation state
    checkOrientation();
    
    // Listen for orientation changes
    window.addEventListener('orientationchange', function() {
      // Give time for the browser to adjust
      setTimeout(checkOrientation, 100);
    });
    
    // Also listen for resize (handles orientation changes better on some devices)
    window.addEventListener('resize', function() {
      checkOrientation();
    });
  }
  
  // Function to check orientation and make adjustments
  function checkOrientation() {
    const isLandscape = window.innerWidth > window.innerHeight;
    
    if (isLandscape) {
      // Apply landscape-specific adjustments
      document.body.classList.add('landscape');
      document.body.classList.remove('portrait');
      
      // In landscape, we can show more side-by-side content
      adjustLayoutForLandscape();
    } else {
      // Apply portrait-specific adjustments
      document.body.classList.add('portrait');
      document.body.classList.remove('landscape');
      
      // In portrait, we stack content vertically
      adjustLayoutForPortrait();
    }
  }
  
  // Adjust layout for landscape orientation
  function adjustLayoutForLandscape() {
    const searchSection = document.querySelector('.search-section');
    const decksSection = document.querySelector('.decks-section');
    const newsSection = document.querySelector('.news-section');
    
    if (!searchSection || !decksSection || !newsSection) return;
    
    // In landscape, try to show decks and news side by side if there's enough room
    if (window.innerWidth >= 600) {
      // Two-column layout in landscape
      const mobileLayout = document.querySelector('.mobile-layout');
      if (mobileLayout) {
        mobileLayout.style.flexDirection = 'row';
        mobileLayout.style.flexWrap = 'wrap';
      }
      
      // Search bar stays on top full width
      searchSection.style.width = '100%';
      searchSection.style.order = '1';
      
      // Decks and news side by side
      decksSection.style.width = '60%';
      decksSection.style.order = '2';
      
      newsSection.style.width = '40%';
      newsSection.style.order = '3';
    }
  }
  
  // Adjust layout for portrait orientation
  function adjustLayoutForPortrait() {
    const searchSection = document.querySelector('.search-section');
    const decksSection = document.querySelector('.decks-section');
    const newsSection = document.querySelector('.news-section');
    
    if (!searchSection || !decksSection || !newsSection) return;
    
    // In portrait, stack everything vertically
    const mobileLayout = document.querySelector('.mobile-layout');
    if (mobileLayout) {
      mobileLayout.style.flexDirection = 'column';
    }
    
    // Reset to default mobile layout
    searchSection.style.width = '100%';
    searchSection.style.order = '1';
    
    decksSection.style.width = '100%';
    decksSection.style.order = '2';
    
    newsSection.style.width = '100%';
    newsSection.style.order = '3';
  }
});
