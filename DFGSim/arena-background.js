'use strict';
(() => {
  const video = document.getElementById('arenaBackdrop');
  if (!video) return;
  const game = document.getElementById('gameView');
  const preference = matchMedia('(prefers-reduced-motion: reduce)');
  let warmedSource = null;
  const shouldPlay = () => !preference.matches && !document.hidden && !game.hidden;
  const update = () => {
    if (!shouldPlay()) {
      video.pause();
      video.hidden = true;
      video.classList.remove('is-playing');
      return;
    }
    video.hidden = false;
    if (!video.getAttribute('src')) {
      // One source lives with this video for the page's lifetime.
      warmedSource = window.DFGAssetPreloader?.playbackSource(video.dataset.src);
      video.src = warmedSource?.src ?? video.dataset.src;
    }
    video.play().then(() => {
      if (!shouldPlay()) update();
    }).catch(() => {
      // Keep the static courtyard if autoplay is unavailable or loading fails.
      video.classList.remove('is-playing');
    });
  };
  video.addEventListener('playing', () => {
    if (shouldPlay()) video.classList.add('is-playing');
  });
  video.addEventListener('error', () => {
    // Some WebKit media pipelines reject blob-backed MP4s. Retry the normal
    // versioned URL before falling back to the static courtyard.
    if (warmedSource) {
      warmedSource.release();
      warmedSource = null;
      video.src = video.dataset.src;
      update();
      return;
    }
    video.classList.remove('is-playing');
    video.hidden = true;
  });
  preference.addEventListener('change', update);
  document.addEventListener('visibilitychange', update);
  new MutationObserver(update).observe(game, { attributes: true, attributeFilter: ['hidden'] });
  update();
})();
