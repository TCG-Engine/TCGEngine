/**
 * Card image zoom functionality for mobile
 * Allows users to tap card images to see a larger view
 */

document.addEventListener('DOMContentLoaded', function() {
  // Only run on mobile devices
  if (window.innerWidth <= 768) {
    // Find all card images
    const cardImages = document.querySelectorAll('img[src*="concat"]');
    
    // REMOVE: Do not add click handler for zooming images in deck list
    // cardImages.forEach(img => {
    //   img.addEventListener('click', function(e) {
    //     e.preventDefault();
    //     e.stopPropagation();
        
    //     // If we already have a zoom view open, close it
    //     const existingZoom = document.querySelector('.card-zoom-container');
    //     if (existingZoom) {
    //       existingZoom.remove();
    //       return;
    //     }
        
    //     // Create zoom container
    //     const zoomContainer = document.createElement('div');
    //     zoomContainer.className = 'card-zoom-container';
        
    //     // Create zoomed image
    //     const zoomedImage = document.createElement('img');
    //     zoomedImage.src = this.src;
    //     zoomedImage.className = 'card-zoomed-image';
        
    //     // Create close button
    //     const closeButton = document.createElement('div');
    //     closeButton.className = 'card-zoom-close';
    //     closeButton.innerHTML = '×';
        
    //     // Add elements to container
    //     zoomContainer.appendChild(zoomedImage);
    //     zoomContainer.appendChild(closeButton);
        
    //     // Add to body
    //     document.body.appendChild(zoomContainer);
        
    //     // Prevent scrolling while zoom is open
    //     document.body.style.overflow = 'hidden';
        
    //     // Handle closing
    //     zoomContainer.addEventListener('click', function() {
    //       this.remove();
    //       document.body.style.overflow = '';
    //     });
        
    //     // Handle swipe to close
    //     let touchStartY = 0;
    //     zoomContainer.addEventListener('touchstart', function(e) {
    //       touchStartY = e.touches[0].clientY;
    //     });
        
    //     zoomContainer.addEventListener('touchmove', function(e) {
    //       const touchY = e.touches[0].clientY;
    //       const diff = touchY - touchStartY;
          
    //       // If swiping down significantly, close the zoom
    //       if (diff > 50) {
    //         this.remove();
    //         document.body.style.overflow = '';
    //       }
    //     });
    //   });
    // });
  }
});
