'use strict';
// Each seat owns its playback, so simultaneous combat reactions cannot cancel each other.
window.DFGFighterReactions = (() => {
  const preference = matchMedia('(prefers-reduced-motion: reduce)');
  const assets = JSON.parse(document.getElementById('fighterReactionAssets').textContent);
  const playing = [null, null];
  const defeated = [false, false];
  let serial = 0;
  const kind = damage => damage === 0 ? 'block' : damage <= 2 ? 'light' : damage <= 4 ? 'medium' : 'heavy';
  function stop(seat) {
    const active = playing[seat];
    if (!active) return;
    clearTimeout(active.timer);
    active.image.onload = active.image.onerror = null;
    active.image.replaceWith(active.host);
    active.release?.();
    // Re-evaluate picture media sources after restoring a detached idle image.
    const idle = active.host.tagName === 'PICTURE' ? active.host.querySelector('img') : active.host;
    idle.src = idle.getAttribute('src');
    playing[seat] = null;
  }
  function reset() {
    stop(0); stop(1);
    defeated.fill(false);
  }
  function holdFinal(seat) {
    const active = playing[seat];
    if (!active?.finalSrc) return;
    clearTimeout(active.timer);
    active.image.onload = null;
    active.image.dataset.reactionState = 'held';
    const warmed = window.DFGAssetPreloader?.playbackSource(active.finalSrc);
    active.image.src = warmed?.src ?? active.finalSrc;
    active.release?.();
    active.release = warmed?.release;
  }
  function start(seat, type, animate = true) {
    stop(seat);
    const knockout = type === 'knockout';
    if (preference.matches && !knockout) return;
    const color = seat === 0 ? 'blue' : 'red';
    const clip = assets[color]?.[type];
    const idle = document.querySelector(`.arena-fighter-${seat === 0 ? 'player' : 'opponent'}`);
    if (!idle || !clip?.src || !(clip.durationMs > 0) || (knockout && !clip.finalSrc)) return;
    const image = idle.cloneNode(false);
    image.dataset.reaction = type;
    const host = idle.parentElement.tagName === 'PICTURE' ? idle.parentElement : idle;
    const active = { host, image, timer: null, finalSrc: knockout ? clip.finalSrc : null };
    playing[seat] = active;
    if (knockout && (preference.matches || !animate) && active.finalSrc) {
      host.replaceWith(image);
      holdFinal(seat);
      image.onerror = () => { if (playing[seat] === active) stop(seat); };
      return;
    }
    image.onload = () => {
      if (playing[seat] !== active) return;
      clearTimeout(active.timer);
      active.timer = setTimeout(() => knockout ? holdFinal(seat) : stop(seat), clip.durationMs);
    };
    image.onerror = () => {
      if (playing[seat] !== active) return;
      if (knockout && image.dataset.reactionState !== 'held') holdFinal(seat);
      else stop(seat);
    };
    const warmed = window.DFGAssetPreloader?.playbackSource(clip.src);
    active.release = warmed?.release;
    // Distinct object URLs restart warmed clips with no download. Keep the
    // original fallback for a hit that arrives before background loading ends.
    image.src = warmed?.src ?? `${clip.src}${clip.src.includes('?') ? '&' : '?'}play=${++serial}`;
    host.replaceWith(image);
    // Recover if the request stalls; normal playback timing starts on load.
    active.timer = setTimeout(() => knockout ? holdFinal(seat) : stop(seat), 10000);
  }
  function play(seat, damage) {
    if (![0, 1].includes(seat) || !Number.isFinite(damage) || damage < 0 || defeated[seat]) return;
    start(seat, kind(damage));
  }
  function syncOutcome(winner, { animate = false } = {}) {
    if (![0, 1, 'draw'].includes(winner)) return;
    for (const seat of [0, 1]) {
      const lost = winner === 'draw' || winner !== seat;
      if (lost && !defeated[seat]) {
        defeated[seat] = true;
        start(seat, 'knockout', animate);
      } else if (!lost && defeated[seat]) {
        stop(seat);
        defeated[seat] = false;
      }
    }
  }
  preference.addEventListener('change', () => {
    for (const seat of [0, 1]) {
      if (defeated[seat]) holdFinal(seat);
      else stop(seat);
    }
  });
  return { play, reset, kind, syncOutcome };
})();
