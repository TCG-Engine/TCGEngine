// Replays tab: open a .swupgn, keep it in this browser (IndexedDB), list it, Play SWUPGN, Delete.
// Every string from a file is set with textContent (untrusted input).
(function () {
  const DB = 'petranaki-swupgn', STORE = 'files';
  const base = (() => { const p = location.pathname, i = p.indexOf('/TCGEngine/'); return i >= 0 ? p.slice(0, i + 11) : '/TCGEngine/'; })();
  const api = base + 'SWUSim/SwuPgnViewer.php?action=open';

  function db() {
    return new Promise((res, rej) => {
      const r = indexedDB.open(DB, 1);
      r.onupgradeneeded = () => { const s = r.result.createObjectStore(STORE, { keyPath: 'id' }); s.createIndex('openedAt', 'openedAt'); };
      r.onsuccess = () => res(r.result);
      r.onerror = () => rej(r.error);
    });
  }
  async function tx(mode, fn) {
    const d = await db();
    return new Promise((res, rej) => {
      const t = d.transaction(STORE, mode);
      const req = fn(t.objectStore(STORE));
      t.oncomplete = () => { d.close(); res(req && 'result' in req ? req.result : undefined); };
      t.onerror = () => { d.close(); rej(t.error); };
    });
  }
  const all = () => tx('readonly', (s) => s.getAll());
  const get = (id) => tx('readonly', (s) => s.get(id));
  const put = (rec) => tx('readwrite', (s) => s.put(rec));
  const del = (id) => tx('readwrite', (s) => s.delete(id));

  async function openText(text, existingId) {
    let j;
    try {
      const r = await fetch(api, { method: 'POST', headers: { 'Content-Type': 'text/plain' }, body: text });
      j = await r.json();
    } catch (e) { j = { success: false, message: 'The server could not read that file.' }; }
    if (!j.success) { (window.StyledAlert || window.alert)(j.message || 'That file could not be opened.'); return; }
    const id = existingId || `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
    try {
      let openedAt = Date.now();
      if (existingId) { const old = await get(existingId); if (old && old.openedAt) openedAt = old.openedAt; }
      await put({ id, text, summary: j.meta.summary, openedAt, live: { gameName: j.gameName, key: j.key, viewUrl: j.viewUrl } });
    } catch (e) { /* storage blocked (private window): still play */ }
    location.href = j.viewUrl;
  }

  // Reuse the viewer game this browser already opened for the file while it lives (24 h); else upload again.
  async function playStored(rec) {
    const live = rec.live;
    if (live && live.gameName && live.key && live.viewUrl) {
      try {
        const r = await fetch(`${base}SWUSim/SwuPgnViewer.php?action=info&gameName=${encodeURIComponent(live.gameName)}&key=${encodeURIComponent(live.key)}`);
        if ((await r.json()).success) { location.href = live.viewUrl; return; }
      } catch (e) { /* expired or offline: upload again */ }
    }
    openText(rec.text, rec.id);
  }

  async function render() {
    const list = document.getElementById('swupgnReplayList');
    const empty = document.getElementById('swupgnReplayEmpty');
    if (!list) return;
    let recs = [];
    try { recs = await all(); } catch (e) { recs = []; }
    recs.sort((a, b) => (b.openedAt || 0) - (a.openedAt || 0));
    list.textContent = '';
    if (empty) empty.hidden = recs.length > 0;
    for (const rec of recs) {
      const s = rec.summary || {};
      const li = document.createElement('li'); li.className = 'match ch';
      const seats = document.createElement('div'); seats.className = 'match__seats';
      seats.textContent = `${s.p1Leader || '?'} vs ${s.p2Leader || '?'}`;
      const sub = document.createElement('div'); sub.className = 'match__meta';
      const date = rec.openedAt ? new Date(rec.openedAt).toLocaleDateString() : '';
      sub.textContent = [s.result ? `Result: ${s.result}` : '', date, s.engine ? `from ${String(s.engine).split('@')[0]}` : ''].filter(Boolean).join(' · ');
      const play = document.createElement('button'); play.type = 'button'; play.className = 'swu2-btn swu2-btn--chip ch'; play.textContent = 'Play SWUPGN';
      play.setAttribute('data-swupgn-play', rec.id);
      play.onclick = () => playStored(rec);
      const rm = document.createElement('button'); rm.type = 'button'; rm.className = 'swu2-btn swu2-btn--chip swu2-btn--quiet ch'; rm.textContent = 'Delete';
      rm.setAttribute('data-swupgn-delete', rec.id);
      rm.onclick = async () => {
        const yes = window.StyledConfirm ? await window.StyledConfirm('Delete this replay from this browser?') : window.confirm('Delete this replay?');
        if (yes) { await del(rec.id); render(); }
      };
      li.append(seats, sub, play, rm);
      list.appendChild(li);
    }
  }

  function wire() {
    const btn = document.getElementById('swupgnOpenBtn'), input = document.getElementById('swupgnFileInput'), drop = document.getElementById('swupgnDrop');
    if (!btn || !input || !drop) return;
    btn.onclick = () => input.click();
    input.onchange = async () => { const f = input.files && input.files[0]; input.value = ''; if (f) openText(await f.text()); };
    drop.addEventListener('dragover', (e) => { e.preventDefault(); drop.classList.add('is-drag'); });
    drop.addEventListener('dragleave', () => drop.classList.remove('is-drag'));
    drop.addEventListener('drop', async (e) => {
      e.preventDefault(); drop.classList.remove('is-drag');
      const f = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
      if (f) openText(await f.text());
    });
    render();
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', wire); else wire();
})();
