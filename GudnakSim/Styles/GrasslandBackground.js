/* GudnakSim Grassland Background Initializer
 * Creates dynamic grass blades and floating particles
 */

function initGrasslandBackground() {
  // Create main background container
  const bgContainer = document.createElement('div');
  bgContainer.className = 'gudnak-background';
  bgContainer.id = 'gudnak-grassland-bg';
  
  // Create light rays
  const lightRays = document.createElement('div');
  lightRays.className = 'light-rays';
  bgContainer.appendChild(lightRays);
  
  // Create distant treeline
  const treeline = document.createElement('div');
  treeline.className = 'treeline';
  bgContainer.appendChild(treeline);
  
  // Create grass container
  const grassContainer = document.createElement('div');
  grassContainer.className = 'grass-container';
  
  // Generate grass blades
  const numBlades = Math.min(80, Math.floor(window.innerWidth / 25));
  for (let i = 0; i < numBlades; i++) {
    const blade = document.createElement('div');
    blade.className = 'grass-blade';
    
    // Randomize properties
    const height = 20 + Math.random() * 40;
    const left = (i / numBlades) * 100 + (Math.random() * 2 - 1);
    const swayDuration = 2.5 + Math.random() * 2;
    const swayDelay = Math.random() * -3;
    const opacity = 0.3 + Math.random() * 0.4;
    
    blade.style.cssText = `
      height: ${height}px;
      left: ${left}%;
      --sway-duration: ${swayDuration}s;
      --sway-delay: ${swayDelay}s;
      opacity: ${opacity};
    `;
    
    grassContainer.appendChild(blade);
  }
  
  bgContainer.appendChild(grassContainer);
  
  // Create particle container
  const particleContainer = document.createElement('div');
  particleContainer.className = 'particle-container';
  
  // Generate floating particles
  const numParticles = 15;
  for (let i = 0; i < numParticles; i++) {
    const particle = document.createElement('div');
    particle.className = 'particle';
    
    const left = Math.random() * 100;
    const floatDuration = 12 + Math.random() * 10;
    const floatDelay = Math.random() * -15;
    const size = 2 + Math.random() * 4;
    
    particle.style.cssText = `
      left: ${left}%;
      width: ${size}px;
      height: ${size}px;
      --float-duration: ${floatDuration}s;
      --float-delay: ${floatDelay}s;
    `;
    
    particleContainer.appendChild(particle);
  }
  
  bgContainer.appendChild(particleContainer);
  
  // Create wind waves
  const windWave1 = document.createElement('div');
  windWave1.className = 'wind-wave';
  bgContainer.appendChild(windWave1);
  
  const windWave2 = document.createElement('div');
  windWave2.className = 'wind-wave wind-wave-2';
  bgContainer.appendChild(windWave2);
  
  // Create vignette overlay
  const vignette = document.createElement('div');
  vignette.className = 'vignette';
  bgContainer.appendChild(vignette);
  
  // Insert at the beginning of body
  document.body.insertBefore(bgContainer, document.body.firstChild);
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initGrasslandBackground);
} else {
  initGrasslandBackground();
}

// Handle window resize - regenerate grass blades
let resizeTimeout;
window.addEventListener('resize', function() {
  clearTimeout(resizeTimeout);
  resizeTimeout = setTimeout(function() {
    const existingBg = document.getElementById('gudnak-grassland-bg');
    if (existingBg) {
      existingBg.remove();
      initGrasslandBackground();
    }
  }, 500);
});
