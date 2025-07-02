/**
 * Mobile detection and class application for TCGEngine
 */

(function() {
  // Execute immediately
  function detectMobile() {
    // Check for mobile device using screen width as primary indicator
    const isMobileWidth = window.innerWidth <= 768;
    
    // Additional checks via user agent (less reliable but helpful as secondary check)
    const userAgent = navigator.userAgent || navigator.vendor || window.opera;
    const mobileRegex = /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i;
    const isMobileAgent = mobileRegex.test(userAgent.toLowerCase());
    
    // Check for touch capability (most mobile devices have touch)
    const isTouchDevice = 'ontouchstart' in window || 
                         navigator.maxTouchPoints > 0 ||
                         navigator.msMaxTouchPoints > 0;
    
    // If any two conditions are true, consider it a mobile device
    const conditionsMet = [isMobileWidth, isMobileAgent, isTouchDevice].filter(Boolean).length >= 2;
    
    return conditionsMet || isMobileWidth; // Prioritize screen width
  }
  
  // Add appropriate class to body
  function applyMobileClass() {
    if (detectMobile()) {
      document.documentElement.classList.add('mobile-device');
      
      // Check orientation immediately
      const isLandscape = window.innerWidth > window.innerHeight;
      document.documentElement.classList.add(isLandscape ? 'landscape' : 'portrait');
      
      // Apply data attribute for CSS targeting
      document.documentElement.setAttribute('data-device-type', 'mobile');
    } else {
      // Only apply data attribute for desktop, don't add unnecessary class
      document.documentElement.setAttribute('data-device-type', 'desktop');
    }
  }
  
  // Apply immediately
  applyMobileClass();
  
  // Re-apply on resize (in case of rotation or window resizing)
  window.addEventListener('resize', function() {
    // Remove existing classes
    document.documentElement.classList.remove('mobile-device', 'portrait', 'landscape');
    
    // Re-apply detection
    applyMobileClass();
  });
}());
