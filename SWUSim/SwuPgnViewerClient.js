// SWU-PGN replay viewer controls. Loaded on every SWUSim board page; does nothing unless the URL
// carries swupgnViewer=1. Every string from the file is set with textContent (untrusted input).
(function () {
  const params = new URLSearchParams(location.search);
  if (params.get('swupgnViewer') !== '1') return;
  const gameName = params.get('gameName');
  const key = params.get('authKey');
  const api = location.pathname.replace(/[^/]*$/, '') + 'SWUSim/SwuPgnViewer.php';
  const state = { step: 0, round: 0, busy: false, meta: null, playing: null };
  let wanted = null;
  let requested = 0;   // the last step asked for — Next/Prev step from it, not from the step on screen

  const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const el = (tag, attrs = {}, text) => { const e = document.createElement(tag); for (const [k, v] of Object.entries(attrs)) e.setAttribute(k, v); if (text !== undefined) e.textContent = text; return e; };

  // Unknown cards: a card back with the card's name over it.
  function wrapRenderer() {
    const orig = window.RenderCardHTML;
    if (typeof orig !== 'function' || orig.__swupgn) return;
    const wrapped = function (cardID, ...rest) {
      const name = state.meta && state.meta.names && state.meta.names[cardID];
      if (!name) return orig.call(this, cardID, ...rest);
      return `<span class="swupgn-ph" style="position:relative;display:inline-block">${orig.call(this, 'CardBack', ...rest)}`
        + `<span class="swupgn-ph-name">${esc(name)}</span></span>`;
    };
    wrapped.__swupgn = true;
    window.RenderCardHTML = wrapped;
  }

  function injectStyles() {
    const css = `#swupgnViewerBar{position:fixed;left:50%;bottom:12px;transform:translateX(-50%);z-index:30000;display:flex;flex-wrap:wrap;gap:6px;align-items:center;
      max-width:min(960px,calc(100vw - 24px));padding:8px 10px;background:rgba(10,14,22,.92);border:1px solid rgba(255,255,255,.18);border-radius:10px;color:#e8eef6;font:13px/1.3 system-ui,sans-serif;box-sizing:border-box}
      #swupgnViewerBar button,#swupgnViewerBar select{background:#1d2735;color:#e8eef6;border:1px solid rgba(255,255,255,.25);border-radius:6px;padding:4px 8px;font:inherit;cursor:pointer}
      #swupgnScrub{flex:1 1 160px;min-width:120px}
      #swupgnCaption{flex:1 1 100%;min-height:1.3em;overflow-wrap:anywhere}
      .swupgn-round[aria-current="true"]{border-color:#f5c542!important}
      #swupgnInfoPanel{position:fixed;right:12px;bottom:110px;z-index:30001;max-width:min(420px,calc(100vw - 24px));max-height:60vh;overflow:auto;background:rgba(10,14,22,.96);color:#e8eef6;border:1px solid rgba(255,255,255,.2);border-radius:10px;padding:10px;font:13px/1.4 system-ui,sans-serif}
      .swupgn-ph-name{position:absolute;left:4%;right:4%;top:40%;background:rgba(0,0,0,.75);color:#fff;font:600 11px/1.2 system-ui,sans-serif;text-align:center;padding:2px;border-radius:4px;overflow-wrap:anywhere}
      @media (max-width:700px){#swupgnViewerBar{left:8px;right:8px;transform:none;max-width:none;bottom:8px}}
      /* Desktop: dock in the right sidebar (a viewer game's log is empty), clear of the board and both hands. */
      #swupgnViewerBar.swupgn-dock{left:auto;right:6px;transform:none;bottom:auto;top:170px;width:calc(var(--swu-sidebar-w, 200px) - 12px);max-width:none;max-height:calc(100vh - 190px);overflow:auto}
      #swupgnViewerBar.swupgn-dock #swupgnScrub{flex-basis:100%}
      #swupgnViewerBar.swupgn-dock #swupgnInfoPanel{right:calc(var(--swu-sidebar-w, 200px) + 6px)}
      /* A replay has no turn to wait on: no "Waiting for the other player" pill, no dimming. */
      #turn-miasma-overlay,#turn-miasma-message{display:none!important}`;
    document.head.appendChild(el('style', { id: 'swupgnViewerStyles' }, css));
  }

  async function call(action, body) {
    const r = body ? await fetch(`${api}?action=${action}`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) })
      : await fetch(`${api}?action=${action}&gameName=${encodeURIComponent(gameName)}&key=${encodeURIComponent(key)}`);
    const j = await r.json();
    if (!j.success) throw new Error(j.message || 'Replay error');
    return j.meta;
  }

  // One seek in flight; a newer request replaces any queued one, so fast clicks land on the latest.
  async function seek(step, view) {
    wanted = { step, view };
    requested = step;
    if (state.busy) return;
    state.busy = true;
    try {
      while (wanted) {
        const w = wanted; wanted = null;
        const meta = await call('seek', { gameName, key, step: w.step, view: w.view });
        apply(meta);
        if (w.view) {
          const seat = w.view === 'p2' ? '2' : '1';
          if (params.get('viewerPerspective') !== seat) { params.set('viewerPerspective', seat); location.replace(`${location.pathname}?${params}`); return; }
        }
        if (typeof window.QueueGameUpdate === 'function') window.QueueGameUpdate();
      }
    } catch (e) { setCaption('⚠ ' + e.message, []); }
    finally { state.busy = false; }
  }

  function setCaption(main, lines) {
    document.getElementById('swupgnCaption').textContent = lines && lines.length ? `${main} → ${lines.join(' · ')}` : main;
  }

  function apply(meta) {
    state.meta = meta; state.step = meta.step;
    const st = meta.steps[meta.step];
    state.round = st.round;
    setCaption(st.caption, st.lines);
    document.getElementById('swupgnScrub').value = String(meta.step);
    document.getElementById('swupgnPos').textContent = `${meta.step + 1}/${meta.steps.length}`;
    document.querySelectorAll('.swupgn-round').forEach((b) => b.setAttribute('aria-current', String(Number(b.dataset.round) === st.round)));
    const hands = document.getElementById('swupgnHands'); if (hands) hands.value = meta.view;
  }

  function stopPlay() { if (state.playing) { clearInterval(state.playing); state.playing = null; document.querySelector('[data-swupgn="play"]').textContent = '▶ Play'; } }
  function togglePlay() {
    if (state.playing) return stopPlay();
    const speed = Number(document.getElementById('swupgnSpeed').value);
    document.querySelector('[data-swupgn="play"]').textContent = '⏸ Pause';
    state.playing = setInterval(() => {
      if (state.busy) return;
      if (state.step >= state.meta.steps.length - 1) return stopPlay();
      seek(state.step + 1);
    }, speed);
  }

  function build(meta) {
    const bar = el('div', { id: 'swupgnViewerBar', role: 'toolbar', 'aria-label': 'Replay controls' });
    const btn = (k, label, title) => { const b = el('button', { type: 'button', 'data-swupgn': k, title }, label); bar.appendChild(b); return b; };
    const target = () => requested;
    btn('first', '⏮', 'First step').onclick = () => seek(0);
    btn('prev', '◀', 'Previous step (←)').onclick = () => seek(Math.max(0, target() - 1));
    btn('next', '▶', 'Next step (→)').onclick = () => seek(Math.min(meta.steps.length - 1, target() + 1));
    btn('last', '⏭', 'Last step').onclick = () => seek(meta.steps.length - 1);
    btn('play', '▶ Play', 'Play / pause').onclick = togglePlay;
    const speed = el('select', { id: 'swupgnSpeed', 'aria-label': 'Speed' });
    [['500', '0.5s'], ['1000', '1s'], ['2000', '2s']].forEach(([v, t]) => { const o = el('option', { value: v }, t); if (v === '1000') o.selected = true; speed.appendChild(o); });
    bar.appendChild(speed);
    const scrub = el('input', { id: 'swupgnScrub', type: 'range', min: '0', max: String(meta.steps.length - 1), value: '0', 'aria-label': 'Step' });
    scrub.oninput = () => seek(Number(scrub.value));
    bar.appendChild(scrub);
    bar.appendChild(el('span', { id: 'swupgnPos' }, ''));
    Object.keys(meta.rounds).forEach((r) => {
      const b = el('button', { type: 'button', class: 'swupgn-round', 'data-round': r }, Number(r) === 0 ? 'Setup' : `R${r}`);
      b.onclick = () => seek(meta.rounds[r]);
      bar.appendChild(b);
    });
    if (meta.perspective === null) {
      const hands = el('select', { id: 'swupgnHands', 'aria-label': 'Hands' });
      [['both', 'Both hands'], ['p1', "P1's view"], ['p2', "P2's view"]].forEach(([v, t]) => hands.appendChild(el('option', { value: v }, t)));
      hands.onchange = () => seek(state.step, hands.value);
      bar.appendChild(hands);
    }
    const info = btn('info', meta.warnings.length ? `ⓘ ⚠ ${meta.warnings.length}` : 'ⓘ', meta.warnings.length ? `About this file — ${meta.warnings.length} warning(s)` : 'About this file');
    info.id = 'swupgnInfo';
    info.onclick = () => {
      const old = document.getElementById('swupgnInfoPanel'); if (old) return old.remove();
      const p = el('div', { id: 'swupgnInfoPanel' }); const s = meta.summary;
      [`${s.p1} (${s.p1Leader}) vs ${s.p2} (${s.p2Leader})`, `Bases: ${s.p1Base} / ${s.p2Base}`, `Result: ${s.result} — ${s.reason} · ${s.rounds} rounds`,
        `Recorded by: ${s.engine}`, s.perspective ? `Recorded from ${s.perspective}'s view` : 'All-seeing file'].forEach((t) => p.appendChild(el('div', {}, t)));
      if (meta.warnings.length) { p.appendChild(el('div', { style: 'margin-top:8px;font-weight:600' }, `${meta.warnings.length} warning(s)`)); meta.warnings.forEach((w) => p.appendChild(el('div', {}, '• ' + w))); }
      document.body.appendChild(p);
    };
    bar.appendChild(el('div', { id: 'swupgnCaption', 'aria-live': 'polite' }, ''));
    document.body.appendChild(bar);
    placeBar(bar);
    const spectator = document.getElementById('spectatorControls'); if (spectator) spectator.style.display = 'none';
    document.addEventListener('keydown', (e) => {
      if (e.target && /input|select|textarea/i.test(e.target.tagName)) return;
      if (e.key === 'ArrowRight') { e.preventDefault(); document.querySelector('[data-swupgn="next"]').click(); }
      if (e.key === 'ArrowLeft') { e.preventDefault(); document.querySelector('[data-swupgn="prev"]').click(); }
    });
  }

  // Desktop with a sidebar → dock in it; phones → bottom bar, with the board padded so nothing hides under it.
  function placeBar(bar) {
    const mobileRoot = document.getElementById('swuMobileRoot');
    if (mobileRoot) {
      const pad = () => { mobileRoot.style.paddingBottom = (bar.offsetHeight + 16) + 'px'; };
      pad();
      if (window.ResizeObserver) new ResizeObserver(pad).observe(bar);
      return;
    }
    const dock = () => bar.classList.toggle('swupgn-dock', innerWidth >= 800);
    dock();
    addEventListener('resize', dock);
  }

  async function start() {
    injectStyles();
    try {
      const meta = await call('info');
      state.meta = meta;
      requested = meta.step;
      wrapRenderer();
      build(meta);
      apply(meta);
      // The board may have painted before the renderer was wrapped (unknown cards as broken images):
      // rewriting the current step bumps the update number, so the board draws again, wrapped.
      seek(meta.step);
    } catch (e) { document.body.appendChild(el('div', { id: 'swupgnViewerBar' }, '⚠ ' + e.message)); }
  }
  window.SwuPgnViewer = { state, seek, setView: (v) => seek(state.step, v) };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start); else start();
})();
