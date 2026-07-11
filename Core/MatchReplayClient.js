(function() {
  var DB_NAME = 'tcgengine-match-replays';
  var DB_VERSION = 1;
  var STORE_NAME = 'replays';
  var dbPromise = null;
  var replayConfig = window.MatchReplayConfig || { enabled: false };
  var replaySubmitPending = false;
  var replayPlayingAll = false;

  // "Play All" replays action-by-action, each as its OWN ProcessInput request (mode 11101 = Next), with a
  // delay between steps. Every request re-parses the gamestate, which is the boundary that makes stepping
  // faithful — the in-memory server batch (mode 11103) skips it and diverges. The delay is the Speed slider.
  var REPLAY_SPEEDS = { fast: 150, normal: 300, slow: 800 };
  var REPLAY_SPEED_ORDER = ['fast', 'normal', 'slow'];
  var REPLAY_SPEED_STORAGE_KEY = 'tcgengine:matchReplaySpeed';

  function replaySpeedKey() {
    var k = config().replaySpeed;
    if (REPLAY_SPEEDS[k] === undefined) {
      try { k = localStorage.getItem(REPLAY_SPEED_STORAGE_KEY); } catch (e) {}
    }
    return REPLAY_SPEEDS[k] !== undefined ? k : 'normal';
  }

  function replayStepDelay() { return REPLAY_SPEEDS[replaySpeedKey()]; }

  function setReplaySpeed(k) {
    if (REPLAY_SPEEDS[k] === undefined) return;
    configure({ replaySpeed: k });
    try { localStorage.setItem(REPLAY_SPEED_STORAGE_KEY, k); } catch (e) {}
  }

  function byId(id) {
    return document.getElementById(id);
  }

  function pageValue(id) {
    var el = byId(id);
    return el ? el.value : '';
  }

  function config() {
    return window.MatchReplayConfig || replayConfig || { enabled: false };
  }

  function configure(matchReplayConfig) {
    var nextConfig = {};
    var current = config();
    for (var currentKey in current) {
      if (Object.prototype.hasOwnProperty.call(current, currentKey)) nextConfig[currentKey] = current[currentKey];
    }
    matchReplayConfig = matchReplayConfig || {};
    for (var key in matchReplayConfig) {
      if (Object.prototype.hasOwnProperty.call(matchReplayConfig, key)) nextConfig[key] = matchReplayConfig[key];
    }
    replayConfig = nextConfig;
    window.MatchReplayConfig = nextConfig;
    return nextConfig;
  }

  function canUseIndexedDb() {
    return !!window.indexedDB;
  }

  function openDb() {
    if (!canUseIndexedDb()) return Promise.reject(new Error('Replay storage is not available in this browser.'));
    if (dbPromise) return dbPromise;
    dbPromise = new Promise(function(resolve, reject) {
      var request = indexedDB.open(DB_NAME, DB_VERSION);
      request.onupgradeneeded = function(event) {
        var db = event.target.result;
        if (!db.objectStoreNames.contains(STORE_NAME)) {
          var store = db.createObjectStore(STORE_NAME, { keyPath: 'id' });
          store.createIndex('rootName', 'rootName', { unique: false });
          store.createIndex('savedAt', 'savedAt', { unique: false });
        }
      };
      request.onsuccess = function() { resolve(request.result); };
      request.onerror = function() { reject(request.error || new Error('Unable to open replay database.')); };
    });
    return dbPromise;
  }

  function transaction(storeMode, callback) {
    return openDb().then(function(db) {
      return new Promise(function(resolve, reject) {
        var tx = db.transaction(STORE_NAME, storeMode);
        var store = tx.objectStore(STORE_NAME);
        var result;
        try {
          result = callback(store);
        } catch (e) {
          reject(e);
          return;
        }
        tx.oncomplete = function() { resolve(result); };
        tx.onerror = function() { reject(tx.error || new Error('Replay database transaction failed.')); };
      });
    });
  }

  function putReplay(replay) {
    var id = [
      replay.rootName || 'root',
      replay.savedAt || new Date().toISOString(),
      Math.random().toString(36).slice(2)
    ].join(':');
    replay.id = id;
    replay.storedAt = new Date().toISOString();
    return transaction('readwrite', function(store) {
      store.put(replay);
      return replay;
    });
  }

  function getReplay(id) {
    return openDb().then(function(db) {
      return new Promise(function(resolve, reject) {
        var tx = db.transaction(STORE_NAME, 'readonly');
        var request = tx.objectStore(STORE_NAME).get(id);
        request.onsuccess = function() { resolve(request.result || null); };
        request.onerror = function() { reject(request.error || new Error('Unable to load replay.')); };
      });
    });
  }

  function listReplays(rootName) {
    return openDb().then(function(db) {
      return new Promise(function(resolve, reject) {
        var tx = db.transaction(STORE_NAME, 'readonly');
        var request = tx.objectStore(STORE_NAME).getAll();
        request.onsuccess = function() {
          var rows = request.result || [];
          if (rootName) {
            rows = rows.filter(function(row) { return row && row.rootName === rootName; });
          }
          rows.sort(function(a, b) {
            return String(b.savedAt || b.storedAt || '').localeCompare(String(a.savedAt || a.storedAt || ''));
          });
          resolve(rows);
        };
        request.onerror = function() { reject(request.error || new Error('Unable to list replays.')); };
      });
    });
  }

  function deleteReplay(id) {
    return transaction('readwrite', function(store) {
      store.delete(id);
      return true;
    });
  }

  function configuredUrl(configKey, fallback) {
    return config()[configKey] || fallback;
  }

  function apiUrl(action) {
    var url = new URL(configuredUrl('apiBaseUrl', './APIs/MatchReplay.php'), window.location.href);
    url.searchParams.set('action', action);
    return url;
  }

  function processInputUrl() {
    return new URL(configuredUrl('processInputUrl', './ProcessInput.php'), window.location.href);
  }

  function replayControlPlayerID() {
    var raw = String(pageValue('playerID') || '1').toUpperCase();
    return raw === 'S' ? '1' : raw;
  }

  function refreshPlaybackState(playbackState) {
    if (playbackState !== undefined) {
      configure({ playbackState: playbackState });
    }
    ensurePlaybackModal();
    renderPanelList();
  }

  function requestGameUpdate() {
    if (typeof window.QueueGameUpdate === 'function') {
      window.QueueGameUpdate();
    } else if (typeof window.reload === 'function') {
      window.reload();
    }
  }

  // Tear down the end-game overlay so the board re-renders live after Reset (or stepping back from the end)
  // — the game-over overlay + its one-shot flag/poll normally persist until a full page refresh, which is why
  // Reset left "YOU LOST" up over an already-reset board. Safe to call when no overlay exists (all no-ops).
  function clearReplayGameOverUI() {
    var overlay = document.getElementById('game-over-overlay');
    if (overlay && overlay.parentNode) overlay.parentNode.removeChild(overlay);
    var banner = document.getElementById('swuGameOverBanner');
    if (banner && banner.parentNode) banner.parentNode.removeChild(banner);
    if (window._swuEndGamePollTimer) { clearInterval(window._swuEndGamePollTimer); window._swuEndGamePollTimer = null; }
    // Re-arm so a later Play All can show the end-game overlay again.
    window._gameOverShown = false;
  }

  function handleReplaySubmitPayload(payload) {
    if (payload && typeof payload === 'object') {
      refreshPlaybackState(payload.playbackState);
      // Not at the end anymore (e.g. Reset → action 0): drop any lingering end-game overlay so the freshly
      // reloaded board is visible without a page refresh. At the end (completed) we leave it for the overlay.
      if (payload.playbackState && !payload.playbackState.completed) clearReplayGameOverUI();
      if (payload.message) console.log(String(payload.message).trim());
      if (!payload.success) throw new Error(payload.message || 'Replay action failed.');
      return payload;
    }

    var message = String(payload || '').trim();
    if (message) console.log(message);
    return payload;
  }

  function replayRootName() {
    return config().rootName || pageValue('folderPath') || '';
  }

  function buildNextTurnUrl(payload, replay) {
    if (config().nextTurnBaseUrl) {
      var url = new URL(config().nextTurnBaseUrl, window.location.href);
      url.searchParams.set('gameName', String(payload.gameName || ''));
      url.searchParams.set('playerID', '1');
      url.searchParams.set('folderPath', String(payload.rootName || replay.rootName || replayRootName()));
      url.searchParams.set('replay', '1');
      return url.toString();
    }
    if (payload.nextTurnUrl) return new URL(payload.nextTurnUrl, window.location.href).toString();
    return './NextTurn.php?gameName=' + encodeURIComponent(payload.gameName || '')
      + '&playerID=1&folderPath=' + encodeURIComponent(payload.rootName || replay.rootName || replayRootName())
      + '&replay=1';
  }

  function saveCurrentReplay() {
    if (!canUseIndexedDb()) {
      StyledAlert('Replay storage is not available in this browser.');
      return Promise.resolve(null);
    }
    if (!config().canDownload) {
      StyledAlert('Replay can be saved after the match is over.');
      return Promise.resolve(null);
    }
    var url = apiUrl('download');
    url.searchParams.set('gameName', pageValue('gameName'));
    url.searchParams.set('playerID', pageValue('playerID'));
    url.searchParams.set('authKey', pageValue('authKey'));
    url.searchParams.set('folderPath', pageValue('folderPath'));

    return fetch(url.toString(), { method: 'GET' })
      .then(function(response) { return response.json(); })
      .then(function(payload) {
        if (!payload || !payload.success) throw new Error((payload && payload.message) || 'Unable to save replay.');
        return putReplay(payload.replay);
      })
      .then(function(replay) {
        Toast('Replay saved to this browser.', { type: 'success' });
        return replay;
      })
      .catch(function(error) {
        StyledAlert(error.message || String(error));
        return null;
      });
  }

  function playReplay(id) {
    return getReplay(id).then(function(replay) {
      if (!replay) throw new Error('Replay not found.');
      return fetch(apiUrl('import').toString(), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ replay: replay })
      }).then(function(response) {
        return response.json();
      }).then(function(payload) {
        if (!payload || !payload.success) throw new Error((payload && payload.message) || 'Unable to start replay.');
        window.location.href = buildNextTurnUrl(payload, replay);
      });
    }).catch(function(error) {
      StyledAlert(error.message || String(error));
    });
  }

  // Fire a single replay-control action as its own ProcessInput request; resolves with the JSON payload.
  function runReplaySubmit(mode) {
    if (typeof window.SubmitEngineInput === 'function') {
      return window.SubmitEngineInput(mode, '', {
        playerID: replayControlPlayerID(),
        authKey: pageValue('authKey'),
        folderPath: pageValue('folderPath'),
        gameName: pageValue('gameName'),
        responseFormat: 'json',
        afterSubmitReload: true,
        allowSpectator: true
      });
    }
    var url = processInputUrl();
    url.searchParams.set('gameName', pageValue('gameName'));
    url.searchParams.set('playerID', replayControlPlayerID());
    url.searchParams.set('authKey', pageValue('authKey'));
    url.searchParams.set('folderPath', pageValue('folderPath'));
    url.searchParams.set('mode', String(mode));
    url.searchParams.set('responseFormat', 'json');
    return fetch(url.toString(), { method: 'GET' })
      .then(function(response) { return response.json(); })
      .then(function(payload) {
        requestGameUpdate();
        return payload;
      });
  }

  // Single manual control (Reset / Next).
  function submitReplayMode(mode) {
    if (replaySubmitPending || replayPlayingAll) return;
    replaySubmitPending = true;
    renderPanelList();

    runReplaySubmit(mode)
      .then(handleReplaySubmitPayload)
      .catch(function(error) { StyledAlert(error.message || String(error)); })
      .then(function() {
        replaySubmitPending = false;
        renderPanelList();
      });
  }

  function stopPlayAll() {
    replayPlayingAll = false;
    renderPanelList();
  }

  // Auto-advance: submit "Next" (11101) one action at a time, pausing replayStepDelay() between each, until
  // the replay completes / is stopped / errors. Looping Next (not the 11103 batch) keeps every action on its
  // own re-parsed request boundary, so Play All reproduces the game exactly like manual stepping.
  function playAll() {
    if (replaySubmitPending || replayPlayingAll) return;
    var state = config().playbackState;
    if (state && state.completed) return;
    replayPlayingAll = true;
    renderPanelList();

    function step() {
      if (!replayPlayingAll) { renderPanelList(); return; }
      runReplaySubmit(11101)
        .then(handleReplaySubmitPayload)
        .then(function() {
          var st = config().playbackState;
          if (!replayPlayingAll) { renderPanelList(); return; }
          if (st && st.completed) { replayPlayingAll = false; renderPanelList(); return; }
          renderPanelList();
          setTimeout(step, replayStepDelay());
        })
        .catch(function(error) {
          replayPlayingAll = false;
          StyledAlert(error.message || String(error));
          renderPanelList();
        });
    }
    step();
  }

  function ensureStyles() {
    if (byId('match-replay-styles')) return;
    var style = document.createElement('style');
    style.id = 'match-replay-styles';
    style.textContent = ''
      + '.match-replay-button,#matchReplayPlaybackModal button{cursor:pointer;border:1px solid var(--border);background:var(--surface-sunken);color:var(--input-text, var(--text));border-radius:var(--radius);padding:6px 10px;font-weight:700;}'
      + '.match-replay-button:hover,#matchReplayPlaybackModal button:hover{background:var(--surface-raised);}'
      + '.match-replay-button:disabled,#matchReplayPlaybackModal button:disabled{opacity:.55;cursor:not-allowed;}'
      + '#matchReplayPlaybackModal{position:fixed;left:16px;top:16px;width:min(360px,calc(100vw - 28px));z-index:30000;color:#f0e6c8;font-family:Roboto,Arial,sans-serif;border:1px solid rgba(201,168,76,.45);border-radius:8px;background:rgba(7,18,30,.97);box-shadow:0 16px 38px rgba(0,0,0,.45);overflow:hidden;}'
      + '#matchReplayPlaybackModalHeader{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 12px;cursor:move;user-select:none;background:rgba(201,168,76,.12);border-bottom:1px solid rgba(201,168,76,.24);}'
      + '#matchReplayPlaybackModalTitle{font-size:13px;font-weight:900;letter-spacing:.08em;text-transform:uppercase;color:#fff4cf;}'
      + '#matchReplayPlaybackModalBadge{font-size:11px;font-weight:800;text-transform:uppercase;color:#0b1b2d;background:#d6b86d;border-radius:999px;padding:2px 8px;white-space:nowrap;}'
      + '#matchReplayPlaybackModalBody{padding:11px 12px 12px;}'
      + '.match-replay-muted{font-size:12px;color:#bfc8d7;line-height:1.35;}'
      + '.match-replay-heading{font-size:13px;font-weight:800;margin-bottom:8px;}'
      + '.match-replay-actions{display:flex;flex-wrap:wrap;gap:6px;margin:9px 0 0;}'
      + '.match-replay-library{display:flex;flex-direction:column;gap:8px;}'
      + '.match-replay-row{display:grid;grid-template-columns:minmax(0,1fr) auto auto;gap:8px;align-items:center;padding:9px 0;border-top:1px solid rgba(255,255,255,.08);}'
      + '.match-replay-row:first-child{border-top:0;}'
      + '.match-replay-meta{font-size:12px;line-height:1.35;color:#d9d0b8;min-width:0;overflow:hidden;text-overflow:ellipsis;}'
      + '.match-replay-meta strong{display:block;color:#fff4cf;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}'
      + '.match-replay-stats-actions{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin:0 0 14px;padding:10px 12px;border:1px solid rgba(201,168,76,.28);border-radius:8px;background:rgba(10,24,42,.72);}'
      + '.match-replay-stats-actions .match-replay-muted{max-width:620px;}';
    document.head.appendChild(style);
  }

  function formatDate(value) {
    if (!value) return '';
    var date = new Date(value);
    if (Number.isNaN(date.getTime())) return String(value);
    return date.toLocaleString();
  }

  function renderPlaybackControls(container) {
    var state = config().playbackState;
    if (!state) return;

    var progress = document.createElement('div');
    progress.className = 'match-replay-muted';
    progress.textContent = 'Action ' + state.nextActionIndex + ' of ' + state.actionCount;
    container.appendChild(progress);

    if (state.sourceGameName || state.sourceSavedAt) {
      var source = document.createElement('div');
      source.className = 'match-replay-muted';
      source.style.marginTop = '4px';
      source.textContent = 'Source'
        + (state.sourceGameName ? ' game ' + state.sourceGameName : '')
        + (state.sourceSavedAt ? ' - ' + formatDate(state.sourceSavedAt) : '');
      container.appendChild(source);
    }

    var actions = document.createElement('div');
    actions.className = 'match-replay-actions';

    var busy = replaySubmitPending || replayPlayingAll;
    // "Play from Here" branched this replay into free play — you play a different line as P1 (opponent
    // auto-passes). Advancing the recording no longer applies, so Next / Play All / Speed grey out; only
    // Reset (which restores the replay) stays live.
    var interrupted = !!state.interrupted;

    var reset = document.createElement('button');
    reset.type = 'button';
    reset.textContent = 'Reset';
    reset.disabled = busy;
    reset.addEventListener('click', function() { submitReplayMode(11102); });
    actions.appendChild(reset);

    var next = document.createElement('button');
    next.type = 'button';
    next.textContent = 'Next';
    next.disabled = busy || !!state.completed || interrupted;
    next.addEventListener('click', function() { submitReplayMode(11101); });
    actions.appendChild(next);

    // Play All ⇄ Stop toggle (auto-advances one Next per step at the chosen Speed).
    var all = document.createElement('button');
    all.type = 'button';
    if (replayPlayingAll) {
      all.textContent = 'Stop';
      all.addEventListener('click', function() { stopPlayAll(); });
    } else {
      all.textContent = 'Play All';
      all.disabled = replaySubmitPending || !!state.completed || interrupted;
      all.addEventListener('click', function() { playAll(); });
    }
    actions.appendChild(all);

    // Play from Here — pause the replay at this position and take control (mode 11104). One click, no
    // confirm; Reset is the undo. Disabled once already interrupted.
    var branch = document.createElement('button');
    branch.type = 'button';
    branch.textContent = 'Play from Here';
    branch.disabled = busy || interrupted;
    branch.addEventListener('click', function() { submitReplayMode(11104); });
    actions.appendChild(branch);

    container.appendChild(actions);

    // Speed slider — Fast / Normal / Slow → 150 / 300 / 800 ms between steps.
    var speedRow = document.createElement('div');
    speedRow.className = 'match-replay-actions';
    speedRow.style.alignItems = 'center';

    var currentSpeed = replaySpeedKey();
    var speedLabel = document.createElement('span');
    speedLabel.className = 'match-replay-muted';
    speedLabel.style.minWidth = '92px';
    function speedText(k) { return 'Speed: ' + k.charAt(0).toUpperCase() + k.slice(1); }
    speedLabel.textContent = speedText(currentSpeed);

    var slider = document.createElement('input');
    slider.type = 'range';
    slider.min = '0';
    slider.max = String(REPLAY_SPEED_ORDER.length - 1);
    slider.step = '1';
    slider.value = String(Math.max(0, REPLAY_SPEED_ORDER.indexOf(currentSpeed)));
    slider.style.flex = '1';
    slider.disabled = interrupted;
    slider.setAttribute('aria-label', 'Replay speed');
    slider.addEventListener('input', function() {
      var k = REPLAY_SPEED_ORDER[parseInt(slider.value, 10)] || 'normal';
      setReplaySpeed(k);
      speedLabel.textContent = speedText(k);
    });

    speedRow.appendChild(speedLabel);
    speedRow.appendChild(slider);
    container.appendChild(speedRow);

    if (interrupted) {
      var hint = document.createElement('div');
      hint.className = 'match-replay-muted';
      hint.style.marginTop = '6px';
      hint.textContent = 'Playing from here as P1 — Reset to restore the replay.';
      container.appendChild(hint);
    }
  }

  function modalPositionKey() {
    return 'tcgengine:matchReplayPlaybackModalPosition';
  }

  function clampModalPosition(panel, left, top) {
    var maxLeft = Math.max(8, window.innerWidth - panel.offsetWidth - 8);
    var maxTop = Math.max(8, window.innerHeight - panel.offsetHeight - 8);
    return {
      left: Math.max(8, Math.min(maxLeft, left)),
      top: Math.max(8, Math.min(maxTop, top))
    };
  }

  function restoreModalPosition(panel) {
    try {
      var raw = localStorage.getItem(modalPositionKey());
      if (!raw) return;
      var pos = JSON.parse(raw);
      if (!pos || typeof pos.left !== 'number' || typeof pos.top !== 'number') return;
      var clamped = clampModalPosition(panel, pos.left, pos.top);
      panel.style.left = clamped.left + 'px';
      panel.style.top = clamped.top + 'px';
    } catch (e) {}
  }

  function saveModalPosition(panel) {
    try {
      localStorage.setItem(modalPositionKey(), JSON.stringify({
        left: parseInt(panel.style.left || panel.offsetLeft, 10) || panel.offsetLeft,
        top: parseInt(panel.style.top || panel.offsetTop, 10) || panel.offsetTop
      }));
    } catch (e) {}
  }

  function makeDraggable(panel, handle) {
    var dragging = false;
    var offsetX = 0;
    var offsetY = 0;

    function startDrag(event) {
      if (event.button !== undefined && event.button !== 0) return;
      if (event.target && event.target.closest && event.target.closest('button')) return;
      dragging = true;
      offsetX = event.clientX - panel.offsetLeft;
      offsetY = event.clientY - panel.offsetTop;
      document.addEventListener('pointermove', moveDrag);
      document.addEventListener('pointerup', stopDrag);
      event.preventDefault();
    }

    function moveDrag(event) {
      if (!dragging) return;
      var pos = clampModalPosition(panel, event.clientX - offsetX, event.clientY - offsetY);
      panel.style.left = pos.left + 'px';
      panel.style.top = pos.top + 'px';
    }

    function stopDrag() {
      if (!dragging) return;
      dragging = false;
      saveModalPosition(panel);
      document.removeEventListener('pointermove', moveDrag);
      document.removeEventListener('pointerup', stopDrag);
    }

    handle.addEventListener('pointerdown', startDrag);
  }

  function renderPanelList() {
    var body = byId('matchReplayPlaybackModalBody');
    if (!body) return;
    body.innerHTML = '';
    renderPlaybackControls(body);
  }

  function ensurePlaybackModal() {
    if (!config().playbackState) return null;
    ensureStyles();
    var existing = byId('matchReplayPlaybackModal');
    if (existing) {
      renderPanelList();
      return existing;
    }

    var panel = document.createElement('div');
    panel.id = 'matchReplayPlaybackModal';

    var header = document.createElement('div');
    header.id = 'matchReplayPlaybackModalHeader';

    var title = document.createElement('div');
    title.id = 'matchReplayPlaybackModalTitle';
    title.textContent = 'Match Replay';
    header.appendChild(title);

    var badge = document.createElement('div');
    badge.id = 'matchReplayPlaybackModalBadge';
    badge.textContent = 'Replay Game';
    header.appendChild(badge);

    var body = document.createElement('div');
    body.id = 'matchReplayPlaybackModalBody';

    panel.appendChild(header);
    panel.appendChild(body);
    document.body.appendChild(panel);
    restoreModalPosition(panel);
    makeDraggable(panel, header);
    renderPanelList();
    return panel;
  }

  function renderReplayLibrary(containerOrId, options) {
    var container = typeof containerOrId === 'string' ? byId(containerOrId) : containerOrId;
    if (!container) return;
    options = options || {};
    ensureStyles();
    container.classList.add('match-replay-library');
    container.innerHTML = '';

    if (!canUseIndexedDb()) {
      var unavailable = document.createElement('div');
      unavailable.className = 'match-replay-muted';
      unavailable.textContent = 'Replay storage is not available in this browser.';
      container.appendChild(unavailable);
      return;
    }

    var loading = document.createElement('div');
    loading.className = 'match-replay-muted';
    loading.textContent = 'Loading saved replays...';
    container.appendChild(loading);

    var rootFilter = options.rootName !== undefined ? options.rootName : replayRootName();
    listReplays(rootFilter).then(function(rows) {
      container.innerHTML = '';
      if (!rows.length) {
        var empty = document.createElement('div');
        empty.className = 'match-replay-muted';
        empty.textContent = rootFilter
          ? 'No saved replays for this game in this browser.'
          : 'No saved replays in this browser.';
        container.appendChild(empty);
        return;
      }

      rows.forEach(function(replay) {
        var row = document.createElement('div');
        row.className = 'match-replay-row';

        var meta = document.createElement('div');
        meta.className = 'match-replay-meta';
        var title = document.createElement('strong');
        title.textContent = (replay.rootName || 'Replay') + (replay.gameName ? ' game ' + replay.gameName : '');
        meta.appendChild(title);
        var details = document.createElement('span');
        details.textContent = formatDate(replay.savedAt || replay.storedAt) + ' - ' + (replay.actionCount || 0) + ' actions';
        meta.appendChild(details);
        row.appendChild(meta);

        var play = document.createElement('button');
        play.type = 'button';
        play.className = 'match-replay-button';
        play.textContent = 'Open';
        play.addEventListener('click', function() {
          play.disabled = true;
          play.textContent = 'Opening...';
          playReplay(replay.id).then(function() {
            play.disabled = false;
            play.textContent = 'Open';
          });
        });
        row.appendChild(play);

        var del = document.createElement('button');
        del.type = 'button';
        del.className = 'match-replay-button';
        del.textContent = 'Delete';
        del.addEventListener('click', function() {
          StyledConfirm('Delete this saved replay from this browser?', { danger: true, confirmLabel: 'Delete' }).then(function(ok) {
            if (!ok) return;
            deleteReplay(replay.id).then(function() {
              renderReplayLibrary(container, options);
            });
          });
        });
        row.appendChild(del);

        container.appendChild(row);
      });
    }).catch(function(error) {
      container.innerHTML = '';
      var errorEl = document.createElement('div');
      errorEl.className = 'match-replay-muted';
      errorEl.textContent = error.message || String(error);
      container.appendChild(errorEl);
    });
  }

  function addGameOverButton(overlay) {
    if (!overlay || !canUseIndexedDb() || !config().canDownload || byId('match-replay-game-over-save-btn')) return;
    ensureStyles();

    var target = byId('game-over-stats') || overlay;
    if (target.style && target.style.display === 'none') target.style.display = '';

    var wrap = document.createElement('div');
    wrap.className = 'match-replay-stats-actions';

    var copy = document.createElement('div');
    copy.className = 'match-replay-muted';
    copy.textContent = 'Save this completed match replay to this browser.';
    wrap.appendChild(copy);

    var btn = document.createElement('button');
    btn.id = 'match-replay-game-over-save-btn';
    btn.className = 'btn btn-primary';   // standard themed primary button (matches the other game-over actions)
    btn.type = 'button';
    btn.textContent = 'Save Replay';
    btn.addEventListener('click', saveCurrentReplay);
    wrap.appendChild(btn);

    if (target.firstChild) target.insertBefore(wrap, target.firstChild);
    else target.appendChild(wrap);
  }

  function init(matchReplayConfig) {
    configure(matchReplayConfig || window.MatchReplayConfig || { enabled: false });
    if (!config().enabled) return;
    ensureStyles();
    if (config().playbackState) {
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ensurePlaybackModal);
      } else {
        ensurePlaybackModal();
      }
    }
  }

  window.MatchReplayClient = {
    init: init,
    configure: configure,
    saveCurrentReplay: saveCurrentReplay,
    addGameOverButton: addGameOverButton,
    renderReplayLibrary: renderReplayLibrary,
    renderPanelList: renderPanelList,
    listReplays: listReplays,
    playReplay: playReplay,
    deleteReplay: deleteReplay
  };
  window.MatchReplayAddGameOverButton = addGameOverButton;
})();
