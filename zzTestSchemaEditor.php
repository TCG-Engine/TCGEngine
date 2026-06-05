<?php
// Admin-only Test Schema Editor
include_once './AccountFiles/AccountSessionAPI.php';
include_once './Core/HTTPLibraries.php';

$error = CheckLoggedInUserMod();
if ($error !== '') {
    echo htmlspecialchars($error);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Test Schema Editor</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      display: flex;
      height: 100vh;
      overflow: hidden;
      background: #111;
      color: #ddd;
      font-family: 'Courier New', monospace;
      font-size: 13px;
    }

    /* ── Sidebar ──────────────────────────────────────────────────── */
    #sidebar {
      width: 300px;
      min-width: 300px;
      height: 100vh;
      overflow-y: auto;
      background: #1e1e1e;
      border-right: 1px solid #333;
      display: flex;
      flex-direction: column;
      gap: 0;
    }

    .panel {
      padding: 12px 14px;
      border-bottom: 1px solid #2a2a2a;
    }

    .panel-title {
      font-size: 11px;
      font-weight: bold;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: #888;
      margin-bottom: 8px;
    }

    h1 {
      font-size: 14px;
      font-weight: bold;
      color: #e2c07a;
      padding: 14px;
      border-bottom: 1px solid #2a2a2a;
    }

    /* ── Inputs ───────────────────────────────────────────────────── */
    select, input[type="file"] {
      width: 100%;
      background: #2a2a2a;
      border: 1px solid #3a3a3a;
      border-radius: 3px;
      color: #ddd;
      padding: 6px 8px;
      font-family: inherit;
      font-size: 13px;
      margin-bottom: 8px;
    }

    input[type="file"] { cursor: pointer; }
    input[type="file"]::file-selector-button {
      background: #3a3a3a;
      border: none;
      color: #ccc;
      padding: 4px 8px;
      cursor: pointer;
      font-family: inherit;
      font-size: 12px;
      border-radius: 2px;
      margin-right: 6px;
    }

    /* ── Buttons ──────────────────────────────────────────────────── */
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 6px 12px;
      border: 1px solid #444;
      border-radius: 3px;
      background: #2d2d2d;
      color: #ccc;
      cursor: pointer;
      font-family: inherit;
      font-size: 12px;
      transition: background 0.15s, color 0.15s;
      user-select: none;
    }
    .btn:hover:not(:disabled) { background: #3a3a3a; color: #fff; }
    .btn:disabled { opacity: 0.4; cursor: not-allowed; }

    .btn-primary { background: #2a4a2a; border-color: #4a7a4a; color: #7ecb7e; }
    .btn-primary:hover:not(:disabled) { background: #3a6a3a; }
    .btn-danger  { background: #4a2a2a; border-color: #7a4a4a; color: #cb7e7e; }
    .btn-danger:hover:not(:disabled) { background: #6a3a3a; }

    .btn-row {
      display: flex;
      gap: 6px;
      flex-wrap: wrap;
    }

    /* ── Load section ─────────────────────────────────────────────── */
    #load-btn { width: 100%; justify-content: center; }

    /* ── Status ───────────────────────────────────────────────────── */
    #status-msg {
      font-size: 11px;
      color: #aaa;
      min-height: 18px;
      padding: 4px 14px;
      border-bottom: 1px solid #2a2a2a;
    }
    #status-msg.error { color: #e07070; }
    #status-msg.ok    { color: #70c070; }

    /* ── Step counter ─────────────────────────────────────────────── */
    #step-counter {
      font-size: 12px;
      color: #aaa;
      margin-bottom: 8px;
    }
    #step-counter span { color: #e2c07a; font-weight: bold; }

    /* ── WHEN steps list ──────────────────────────────────────────── */
    #when-list {
      display: flex;
      flex-direction: column;
      gap: 2px;
      max-height: 260px;
      overflow-y: auto;
      margin-bottom: 10px;
    }

    .step-item {
      padding: 5px 8px;
      border-radius: 3px;
      font-size: 11px;
      color: #666;
      background: #1a1a1a;
      border: 1px solid transparent;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      cursor: default;
    }
    .step-item.done    { color: #555; text-decoration: line-through; }
    .step-item.current { color: #e2c07a; background: #2a2a1a; border-color: #5a5020; font-weight: bold; }
    .step-item.pending { color: #888; }

    /* ── Controls panel ───────────────────────────────────────────── */
    #controls-panel { display: none; }

    /* ── Game iframe ──────────────────────────────────────────────── */
    #game-area {
      flex: 1;
      display: flex;
      flex-direction: column;
      background: #111;
      position: relative;
    }

    #game-frame {
      flex: 1;
      border: none;
      width: 100%;
      display: block;
    }

    /* Blocks interaction with the iframe while step-through is active */
    #game-overlay {
      position: absolute;
      inset: 0;
      z-index: 10;
      cursor: not-allowed;
      display: none;
    }
    #game-overlay.is-blocking { display: block; }

    #placeholder {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #333;
      font-size: 16px;
    }
  </style>
</head>
<body>

<!-- ── Sidebar ──────────────────────────────────────────────────────── -->
<div id="sidebar">
  <h1>Test Schema Editor</h1>

  <!-- Load panel -->
  <div class="panel">
    <div class="panel-title">Simulator</div>
    <select id="sim-select">
      <option value="SWUSim">SWUSim</option>
    </select>

    <div class="panel-title">Schema File</div>
    <input type="file" id="schema-file" accept=".md">

    <button id="load-btn" class="btn btn-primary" disabled>Load Schema</button>
  </div>

  <!-- Status bar -->
  <div id="status-msg">Pick a .md file to begin.</div>

  <!-- Controls (shown after load) -->
  <div class="panel" id="controls-panel">
    <div class="panel-title">Loaded: <span id="schema-filename" style="color:#ddd;text-transform:none;"></span></div>
    <div id="step-counter">Step <span id="step-current">0</span> / <span id="step-total">0</span></div>

    <div id="when-list"></div>

    <div class="btn-row">
      <button id="step-btn" class="btn btn-primary" disabled title="Execute next WHEN step">▶ Step</button>
      <button id="run-btn"  class="btn" disabled title="Execute all remaining steps">▶▶ Run All</button>
    </div>
    <br>
    <div class="btn-row">
      <button id="stop-btn"  class="btn btn-danger" disabled title="Stop step-through; play freely">⏹ Stop</button>
      <button id="reset-btn" class="btn" title="Reset game to initial GIVEN state">↩ Reset</button>
    </div>
    <br>
    <div class="btn-row">
      <button id="swap-player-btn"    class="btn" disabled title="Reload the board viewing as the other player">⇄ Swap Player</button>
      <button id="refresh-iframe-btn" class="btn" disabled title="Force-reload the game iframe (reloads its JS)">⟳ Hard Refresh iFrame</button>
    </div>
  </div>
</div>

<!-- ── Game area ────────────────────────────────────────────────────── -->
<div id="game-area">
  <div id="placeholder">Load a schema to see the game board.</div>
  <iframe id="game-frame" src="about:blank" style="display:none;"></iframe>
  <div id="game-overlay"></div>
</div>

<script>
(function () {
  // ── State ──────────────────────────────────────────────────────────
  let schemaContent = '';
  let whenSteps     = [];   // [{raw, player, cmd, args}]
  let stepIndex     = 0;
  let gameName      = null;
  let steppingMode  = false;
  let busy          = false;
  let currentSim    = null;
  let currentPlayerID = 1;

  const simSelect    = document.getElementById('sim-select');
  const fileInput    = document.getElementById('schema-file');
  const loadBtn      = document.getElementById('load-btn');
  const statusMsg    = document.getElementById('status-msg');
  const controlsPanel= document.getElementById('controls-panel');
  const schemaFilename = document.getElementById('schema-filename');
  const stepCurrent  = document.getElementById('step-current');
  const stepTotal    = document.getElementById('step-total');
  const whenList     = document.getElementById('when-list');
  const stepBtn      = document.getElementById('step-btn');
  const runBtn       = document.getElementById('run-btn');
  const stopBtn      = document.getElementById('stop-btn');
  const resetBtn     = document.getElementById('reset-btn');
  const swapPlayerBtn   = document.getElementById('swap-player-btn');
  const refreshIframeBtn= document.getElementById('refresh-iframe-btn');
  const gameFrame    = document.getElementById('game-frame');
  const placeholder  = document.getElementById('placeholder');
  const gameOverlay  = document.getElementById('game-overlay');

  // ── File picker ────────────────────────────────────────────────────
  fileInput.addEventListener('change', () => {
    const file = fileInput.files[0];
    if (!file) { loadBtn.disabled = true; return; }
    const reader = new FileReader();
    reader.onload = e => {
      schemaContent = e.target.result;
      loadBtn.disabled = false;
    };
    reader.readAsText(file);
  });

  // ── Load ───────────────────────────────────────────────────────────
  loadBtn.addEventListener('click', () => loadSchema());
  resetBtn.addEventListener('click', () => loadSchema());

  // ── Iframe tools ───────────────────────────────────────────────────
  swapPlayerBtn.addEventListener('click', () => {
    if (!gameName) return;
    const next = currentPlayerID === 1 ? 2 : 1;
    loadGameFrame(currentSim, gameName, next, true);
    setStatus('Viewing board as Player ' + next + '.', 'ok');
  });

  refreshIframeBtn.addEventListener('click', async () => {
    if (!gameName) return;
    // A plain iframe reload re-fetches the document but serves sub-resources
    // (token images, JS) from the HTTP cache — so updated assets look stale.
    // Re-fetch every asset the iframe loaded with cache:'reload' to refresh
    // the cache entries, THEN reload so the iframe pulls the fresh copies.
    refreshIframeBtn.disabled = true;
    setStatus('Hard-refreshing iframe (revalidating cached assets)…', '');
    try { await purgeIframeCache(); } catch (e) { /* best-effort */ }
    loadGameFrame(currentSim, gameName, currentPlayerID, true);
    setStatus('Hard-refreshed game iframe (Player ' + currentPlayerID + ').', 'ok');
  });

  // Force the browser to re-download (bypassing the HTTP cache) every asset the
  // iframe currently has loaded, updating the cache so a following reload is fresh.
  async function purgeIframeCache() {
    const urls = new Set();
    try {
      gameFrame.contentWindow.performance.getEntriesByType('resource')
        .forEach(e => urls.add(e.name));
    } catch (e) { /* perf API unavailable */ }
    try {
      Array.from(gameFrame.contentDocument.images).forEach(img => urls.add(img.src));
    } catch (e) { /* cross-origin document */ }

    const fetches = [];
    urls.forEach(u => {
      if (!/^https?:/i.test(u)) return;
      const mode = u.startsWith(location.origin) ? 'cors' : 'no-cors';
      fetches.push(fetch(u, { cache: 'reload', mode }).catch(() => {}));
    });
    await Promise.all(fetches);
  }

  async function loadSchema() {
    if (!schemaContent) return;
    setStatus('Loading schema…', '');
    setBusy(true);

    try {
      const sim = simSelect.value;
      const fd  = new FormData();
      fd.append('schema', schemaContent);

      const res  = await fetch('./' + sim + '/TestSchemaSetup.php', { method: 'POST', body: fd });
      const data = await res.json();

      if (data.error) { setStatus('Error: ' + data.error, 'error'); return; }

      gameName    = data.gameName;
      whenSteps   = data.whenSteps;
      stepIndex   = 0;
      steppingMode = false;

      const filename = fileInput.files[0]?.name ?? 'schema.md';
      schemaFilename.textContent = filename;

      renderStepList();
      updateStepCounter();
      updateButtons();
      syncOverlay();

      controlsPanel.style.display = 'block';
      setStatus('Game #' + gameName + ' ready. ' + whenSteps.length + ' WHEN step(s).', 'ok');

      // Load game in iframe
      loadGameFrame(sim, gameName);
    } catch (err) {
      setStatus('Network error: ' + err.message, 'error');
    } finally {
      setBusy(false);
    }
  }

  function loadGameFrame(sim, gn, playerID, bustCache) {
    currentSim      = sim;
    currentPlayerID = playerID || 1;
    let url = './NextTurn.php?folderPath=' + encodeURIComponent(sim)
              + '&gameName=' + encodeURIComponent(gn)
              + '&playerID=' + currentPlayerID + '&authKey=testschema';
    if (bustCache) url += '&_=' + Date.now();
    placeholder.style.display = 'none';
    gameFrame.style.display   = 'block';
    gameFrame.src = url;
    swapPlayerBtn.disabled    = false;
    refreshIframeBtn.disabled = false;
  }

  // ── Step controls ──────────────────────────────────────────────────
  stepBtn.addEventListener('click', () => executeStep());
  runBtn.addEventListener('click',  () => runAll());
  stopBtn.addEventListener('click', () => {
    steppingMode = false;
    setStatus('Stepped out — play freely in the game board.', 'ok');
    updateButtons();
    syncOverlay();
  });

  async function executeStep() {
    if (stepIndex >= whenSteps.length || busy) return;
    steppingMode = true;
    syncOverlay();
    setBusy(true);

    const step = whenSteps[stepIndex];
    setStatus('Executing: ' + step.raw + '…', '');

    try {
      const sim = simSelect.value;
      const fd  = new FormData();
      fd.append('gameName', gameName);
      fd.append('step', step.raw);

      const res  = await fetch('./' + sim + '/TestSchemaStep.php', { method: 'POST', body: fd });
      const data = await res.json();

      if (data.error) {
        setStatus('Step failed: ' + data.error, 'error');
        return;
      }

      stepIndex++;
      renderStepList();
      updateStepCounter();
      updateButtons();
      syncOverlay();

      let statusText = 'Step ' + stepIndex + '/' + whenSteps.length + ' done.';
      if (data.autoResolved > 0) statusText += ' (auto-resolved ' + data.autoResolved + ' decision' + (data.autoResolved > 1 ? 's' : '') + ')';
      if (data.pending && data.pending.length > 0) {
        const pDesc = data.pending.map(d => 'P' + d.player + ':' + (d.tooltip || d.type)).join(', ');
        statusText += ' — awaiting: ' + pDesc;
      }
      setStatus(statusText, 'ok');
    } catch (err) {
      setStatus('Network error: ' + err.message, 'error');
    } finally {
      setBusy(false);
    }
  }

  async function runAll() {
    if (busy) return;
    steppingMode = true;
    syncOverlay();
    while (stepIndex < whenSteps.length) {
      await executeStep();
      if (!steppingMode) break;
      // Small delay so the iframe can poll and update between steps
      await sleep(300);
    }
    if (stepIndex >= whenSteps.length) {
      setStatus('All WHEN steps executed. Game is at post-test state.', 'ok');
    }
  }

  // ── Overlay ────────────────────────────────────────────────────────
  function syncOverlay() {
    gameOverlay.classList.toggle('is-blocking', steppingMode);
  }

  // ── Helpers ────────────────────────────────────────────────────────
  function renderStepList() {
    whenList.innerHTML = '';
    whenSteps.forEach((step, i) => {
      const el = document.createElement('div');
      el.className = 'step-item '
        + (i < stepIndex ? 'done' : i === stepIndex ? 'current' : 'pending');
      el.textContent = (i < stepIndex ? '✓ ' : i === stepIndex ? '▶ ' : '  ') + step.raw;
      el.title = step.raw;
      whenList.appendChild(el);
    });
    // Scroll current step into view
    const current = whenList.querySelector('.current');
    if (current) current.scrollIntoView({ block: 'nearest' });
  }

  function updateStepCounter() {
    stepCurrent.textContent = stepIndex;
    stepTotal.textContent   = whenSteps.length;
  }

  function updateButtons() {
    const hasMore = stepIndex < whenSteps.length;
    stepBtn.disabled = !hasMore || busy;
    runBtn.disabled  = !hasMore || busy;
    stopBtn.disabled = !steppingMode || busy;
  }

  function setStatus(msg, type) {
    statusMsg.textContent = msg;
    statusMsg.className   = type || '';
  }

  function setBusy(val) {
    busy = val;
    updateButtons();
    loadBtn.disabled  = val || !schemaContent;
    resetBtn.disabled = val;
  }

  function sleep(ms) { return new Promise(r => setTimeout(r, ms)); }

  // Forward keydown events to the game iframe so hotkeys (U, S, etc.) work
  // regardless of which element in the outer page has focus.
  document.addEventListener('keydown', function(event) {
    if (gameFrame.style.display === 'none') return;

    // Editor-only hotkey: "W" swaps which player's board is shown. This is a
    // schema-editor convenience and is NOT forwarded to the game iframe.
    if (event.key === 'w' || event.key === 'W') {
      const tag = (event.target.tagName || '').toLowerCase();
      if (tag === 'input' || tag === 'select' || tag === 'textarea') return;
      event.preventDefault();
      if (!swapPlayerBtn.disabled) swapPlayerBtn.click();
      return;
    }

    var iframeWin = gameFrame.contentWindow;
    if (iframeWin && typeof iframeWin.Hotkeys === 'function') {
      iframeWin.Hotkeys(event);
    }
  });
})();
</script>

</body>
</html>
