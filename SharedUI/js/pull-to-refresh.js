/**
 * Pull-to-refresh is disabled for this application
 * This file exists only to maintain compatibility with existing includes
 */

document.addEventListener('DOMContentLoaded', function() {
  // Disabled functionality
  const pullIndicator = document.querySelector('.pull-indicator');
  if (pullIndicator) {
    pullIndicator.style.display = 'none';
  }
});
