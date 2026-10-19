'use strict';
// Warm the same URLs used by CSS, without putting 36 competing requests ahead
// of the menu or blocking a player who starts immediately.
window.DFGAssetPreloader = (() => {
  const known = new Set();
  const queue = [];
  const media = new Map();
  const motion = matchMedia('(prefers-reduced-motion: reduce)');
  let active = 0, scheduled = false;
  const connection = navigator.connection;
  const constrained = () => connection?.saveData || ['slow-2g', '2g'].includes(connection?.effectiveType);

  function schedule() {
    if (scheduled || document.hidden || constrained() || active >= 2 || !queue.length) return;
    scheduled = true;
    const run = () => { scheduled = false; pump(); };
    if ('requestIdleCallback' in window) window.requestIdleCallback(run, {timeout:1000});
    else window.setTimeout(run, 100);
  }
  function pump() {
    if (document.hidden || constrained()) return;
    while (active < 2 && queue.length) {
      const job = queue.shift();
      const {url} = job;
      if (job.motion && motion.matches) { known.delete(url); continue; }
      if (job.kind === 'media') {
        active++;
        fetch(url, {priority:'low'}).then(response => {
          if (!response.ok) throw new Error('Asset unavailable');
          return response.blob();
        }).then(blob => media.set(url, blob)).catch(() => {
          // Playback retains its normal network fallback if warming fails.
        }).finally(() => { active--; schedule(); });
        continue;
      }
      const image = new Image();
      image.decoding = 'async';
      image.fetchPriority = 'low';
      active++;
      // Errors free the slot too; a missing image must not stall the queue.
      const finish = () => {
        image.onload = image.onerror = null;
        active--;
        schedule();
      };
      image.onload = image.onerror = finish;
      image.src = url;
    }
  }
  function warmCards(cards, colors = ['Red', 'Purple']) {
    for (const card of cards) {
      const url = DFGCardRenderer.artURL(card);
      if (!url || known.has(url)) continue;
      known.add(url);
      queue.push({url, color:card.color, priority:3});
    }
    const rank = color => { const index = colors.indexOf(color); return index < 0 ? colors.length : index; };
    for (const job of queue) if (job.color) job.priority = colors.includes(job.color) ? 1 : 3;
    queue.sort((a,b) => a.priority-b.priority || rank(a.color)-rank(b.color));
    schedule();
  }
  function warmScene() {
    const enqueue = (url, priority, kind = 'image', animated = false) => {
      if (!url || known.has(url) || (animated && motion.matches)) return;
      known.add(url);
      queue.push({url, priority, kind, motion:animated});
    };
    // Read the actual rendered URLs, including deployment version parameters.
    const arena = document.querySelector('.arena-art');
    const background = arena && getComputedStyle(arena).backgroundImage.match(/url\(["']?(.*?)["']?\)/);
    if (background) enqueue(background[1], 0);
    const reactions = JSON.parse(document.getElementById('fighterReactionAssets').textContent);
    for (const clips of Object.values(reactions)) for (const [type, clip] of Object.entries(clips)) {
      enqueue(clip.src, type === 'knockout' ? 4 : 2, 'media', true);
      enqueue(clip.finalSrc, 4, 'media');
    }
    enqueue(document.getElementById('arenaBackdrop')?.dataset.src, 4, 'media', true);
    queue.sort((a,b) => a.priority-b.priority);
    schedule();
  }
  // A fresh object URL restarts an animation without changing the network URL.
  // The caller releases it when playback finishes or is interrupted.
  function playbackSource(url) {
    const blob = media.get(url);
    if (!blob) return null;
    const src = URL.createObjectURL(blob);
    return {src, release:() => URL.revokeObjectURL(src)};
  }
  document.addEventListener('visibilitychange', schedule);
  connection?.addEventListener('change', schedule);
  motion.addEventListener('change', warmScene);
  warmScene();
  return {warmCards, playbackSource};
})();
