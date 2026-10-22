// "Export SWUPGN" on the end-game overlay: a seated player downloads THEIR OWN view of the finished game
// (SWUSim/SwuPgnExport.php) and the file is kept in this browser's Replays tab (the same IndexedDB store
// SwuPgnReplays.js lists). Loaded on every SWUSim board page; spectators, replays and viewer games get no button.
(function () {
  const params = new URLSearchParams(location.search);
  if (params.get('swupgnViewer') === '1' || params.get('replay') === '1') return;
  const DB = 'petranaki-swupgn', STORE = 'files';
  const api = location.pathname.replace(/[^/]*$/, '') + 'SWUSim/SwuPgnExport.php';
  const val = (id) => { const e = document.getElementById(id); return e ? String(e.value || '') : ''; };

  function db() {
    return new Promise((res, rej) => {
      const r = indexedDB.open(DB, 1);
      r.onupgradeneeded = () => { const s = r.result.createObjectStore(STORE, { keyPath: 'id' }); s.createIndex('openedAt', 'openedAt'); };
      r.onsuccess = () => res(r.result);
      r.onerror = () => rej(r.error);
    });
  }
  async function keep(rec) {
    const d = await db();
    await new Promise((res, rej) => {
      const t = d.transaction(STORE, 'readwrite');
      t.objectStore(STORE).put(rec);
      t.oncomplete = () => { d.close(); res(); };
      t.onerror = () => { d.close(); rej(t.error); };
    });
  }
  function download(text, filename) {
    const url = URL.createObjectURL(new Blob([text], { type: 'text/plain' }));
    const a = document.createElement('a');
    a.href = url; a.download = filename; a.rel = 'noopener'; a.style.display = 'none';
    document.body.appendChild(a); a.click(); a.remove();
    setTimeout(() => URL.revokeObjectURL(url), 30000);
  }

  async function exportNow(btn) {
    if (btn.disabled) return;
    btn.disabled = true;
    const label = btn.textContent;
    btn.textContent = 'Exporting…';
    let j = null;
    try {
      const q = new URLSearchParams({ gameName: val('gameName'), playerID: val('playerID'), authKey: val('authKey') });
      j = await (await fetch(`${api}?${q}`, { credentials: 'same-origin' })).json();
    } catch (e) { j = null; }
    if (!j || !j.success) { btn.textContent = 'Replay file unavailable'; return; }   // stays disabled
    download(j.pgn, j.filename);
    try {
      await keep({ id: `export-${val('gameName')}-${val('playerID')}`, text: j.pgn, summary: j.summary, openedAt: Date.now() });
    } catch (e) { /* storage blocked (private window): the download still happened */ }
    btn.textContent = label;
    btn.disabled = false;
  }

  function addButton(overlay) {
    if (!overlay || !/^[12]$/.test(val('playerID')) || document.getElementById('swupgn-export-btn')) return;
    let wrap = overlay.querySelector('.match-replay-stats-actions');
    if (!wrap) {   // no Save Replay row (it needs IndexedDB + a downloadable game): make the row ourselves
      const target = document.getElementById('game-over-stats') || overlay;
      if (target.style && target.style.display === 'none') target.style.display = '';
      wrap = document.createElement('div');
      wrap.className = 'match-replay-stats-actions';
      target.insertBefore(wrap, target.firstChild);
    }
    const btn = document.createElement('button');
    btn.id = 'swupgn-export-btn';
    btn.type = 'button';
    btn.className = 'btn btn-secondary';
    btn.textContent = 'Export SWUPGN';
    btn.title = 'Download your view of this game as a .swupgn file';
    btn.addEventListener('click', () => exportNow(btn));
    wrap.appendChild(btn);
  }

  // The overlay builder calls window.MatchReplayAddGameOverButton(overlay) (Core/MatchReplayClient.js, loaded first).
  const orig = window.MatchReplayAddGameOverButton;
  window.MatchReplayAddGameOverButton = function (overlay) {
    if (typeof orig === 'function') orig(overlay);
    try { addButton(overlay); } catch (e) { /* never break the end-game screen */ }
  };
  window.SwuPgnExport = { addButton, exportNow };
})();
