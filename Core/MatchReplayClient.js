(function() {
  var DB_NAME = 'tcgengine-match-replays';
  var DB_VERSION = 1;
  var STORE_NAME = 'replays';
  var dbPromise = null;

  function byId(id) {
    return document.getElementById(id);
  }

  function pageValue(id) {
    var el = byId(id);
    return el ? el.value : '';
  }

  function config() {
    return window.MatchReplayConfig || { enabled: false };
  }

  function openDb() {
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

  function listReplays() {
    return openDb().then(function(db) {
      return new Promise(function(resolve, reject) {
        var tx = db.transaction(STORE_NAME, 'readonly');
        var request = tx.objectStore(STORE_NAME).getAll();
        request.onsuccess = function() {
          var rows = request.result || [];
          rows.sort(function(a, b) { return String(b.savedAt || '').localeCompare(String(a.savedAt || '')); });
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

  function apiUrl(action) {
    return './APIs/MatchReplay.php?action=' + encodeURIComponent(action);
  }

  function saveCurrentReplay() {
    if (!window.indexedDB) {
      alert('Replay storage is not available in this browser.');
      return Promise.resolve(null);
    }
    if (!config().canDownload) {
      alert('Replay can be saved after the match is over.');
      return Promise.resolve(null);
    }
    var params = new URLSearchParams();
    params.set('action', 'download');
    params.set('gameName', pageValue('gameName'));
    params.set('playerID', pageValue('playerID'));
    params.set('authKey', pageValue('authKey'));
    params.set('folderPath', pageValue('folderPath'));

    return fetch('./APIs/MatchReplay.php?' + params.toString(), { method: 'GET' })
      .then(function(response) { return response.json(); })
      .then(function(payload) {
        if (!payload || !payload.success) throw new Error((payload && payload.message) || 'Unable to save replay.');
        return putReplay(payload.replay);
      })
      .then(function(replay) {
        alert('Replay saved to this browser.');
        renderPanelList();
        return replay;
      })
      .catch(function(error) {
        alert(error.message || String(error));
        return null;
      });
  }

  function playReplay(id) {
    return getReplay(id).then(function(replay) {
      if (!replay) throw new Error('Replay not found.');
      return fetch(apiUrl('import'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ replay: replay })
      });
    }).then(function(response) {
      return response.json();
    }).then(function(payload) {
      if (!payload || !payload.success) throw new Error((payload && payload.message) || 'Unable to start replay.');
      window.location.href = payload.nextTurnUrl;
    }).catch(function(error) {
      alert(error.message || String(error));
    });
  }

  function submitReplayMode(mode) {
    var params = new URLSearchParams();
    params.set('gameName', pageValue('gameName'));
    params.set('playerID', pageValue('playerID') || '1');
    params.set('authKey', pageValue('authKey'));
    params.set('folderPath', pageValue('folderPath'));
    params.set('mode', String(mode));
    fetch('./ProcessInput.php?' + params.toString(), { method: 'GET' })
      .then(function(response) { return response.text(); })
      .then(function(message) {
        if (message && message.trim()) console.log(message.trim());
        window.location.reload();
      })
      .catch(function(error) { alert(error.message || String(error)); });
  }

  function ensureStyles() {
    if (byId('match-replay-styles')) return;
    var style = document.createElement('style');
    style.id = 'match-replay-styles';
    style.textContent = ''
      + '#matchReplayPanel{position:fixed;left:14px;top:14px;z-index:12000;color:#f0e6c8;font-family:Roboto,Arial,sans-serif;}'
      + '#matchReplayPanel button{cursor:pointer;border:1px solid rgba(201,168,76,.35);background:#10243a;color:#f0e6c8;border-radius:6px;padding:6px 10px;font-weight:700;}'
      + '#matchReplayPanel button:hover{background:#1d3a5e;}'
      + '#matchReplayPanelBody{display:none;width:min(390px,calc(100vw - 28px));max-height:72vh;overflow:auto;margin-top:6px;padding:10px;border:1px solid rgba(201,168,76,.35);border-radius:8px;background:rgba(7,18,30,.96);box-shadow:0 10px 26px rgba(0,0,0,.35);}'
      + '#matchReplayPanel.is-open #matchReplayPanelBody{display:block;}'
      + '.match-replay-row{display:grid;grid-template-columns:1fr auto auto;gap:6px;align-items:center;padding:8px 0;border-top:1px solid rgba(255,255,255,.08);}'
      + '.match-replay-row:first-child{border-top:0;}'
      + '.match-replay-meta{font-size:12px;line-height:1.35;color:#d9d0b8;min-width:0;overflow:hidden;text-overflow:ellipsis;}'
      + '.match-replay-heading{font-size:13px;font-weight:800;margin-bottom:8px;}'
      + '.match-replay-muted{font-size:12px;color:#bfc8d7;line-height:1.35;}';
    document.head.appendChild(style);
  }

  function ensurePanel() {
    if (byId('matchReplayPanel')) return byId('matchReplayPanel');
    ensureStyles();
    var panel = document.createElement('div');
    panel.id = 'matchReplayPanel';

    var toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.textContent = 'Replays';
    toggle.addEventListener('click', function() {
      panel.classList.toggle('is-open');
      if (panel.classList.contains('is-open')) renderPanelList();
    });
    panel.appendChild(toggle);

    var body = document.createElement('div');
    body.id = 'matchReplayPanelBody';
    panel.appendChild(body);
    document.body.appendChild(panel);
    renderPanelList();
    return panel;
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

    var heading = document.createElement('div');
    heading.className = 'match-replay-heading';
    heading.textContent = 'Playback';
    container.appendChild(heading);

    var progress = document.createElement('div');
    progress.className = 'match-replay-muted';
    progress.textContent = 'Action ' + state.nextActionIndex + ' of ' + state.actionCount;
    container.appendChild(progress);

    var actions = document.createElement('div');
    actions.style.display = 'flex';
    actions.style.flexWrap = 'wrap';
    actions.style.gap = '6px';
    actions.style.margin = '8px 0 12px';

    var reset = document.createElement('button');
    reset.type = 'button';
    reset.textContent = 'Reset';
    reset.addEventListener('click', function() { submitReplayMode(11102); });
    actions.appendChild(reset);

    var next = document.createElement('button');
    next.type = 'button';
    next.textContent = 'Next';
    next.disabled = !!state.completed;
    next.addEventListener('click', function() { submitReplayMode(11101); });
    actions.appendChild(next);

    var all = document.createElement('button');
    all.type = 'button';
    all.textContent = 'Play All';
    all.disabled = !!state.completed;
    all.addEventListener('click', function() { submitReplayMode(11103); });
    actions.appendChild(all);

    container.appendChild(actions);
  }

  function renderPanelList() {
    var body = byId('matchReplayPanelBody');
    if (!body) return;
    body.innerHTML = '';

    renderPlaybackControls(body);

    if (config().canDownload) {
      var save = document.createElement('button');
      save.type = 'button';
      save.textContent = 'Save Current Replay';
      save.style.marginBottom = '10px';
      save.addEventListener('click', saveCurrentReplay);
      body.appendChild(save);
    }

    var heading = document.createElement('div');
    heading.className = 'match-replay-heading';
    heading.textContent = 'Saved Replays';
    body.appendChild(heading);

    listReplays().then(function(rows) {
      if (!rows.length) {
        var empty = document.createElement('div');
        empty.className = 'match-replay-muted';
        empty.textContent = 'No saved replays in this browser.';
        body.appendChild(empty);
        return;
      }

      rows.forEach(function(replay) {
        var row = document.createElement('div');
        row.className = 'match-replay-row';

        var meta = document.createElement('div');
        meta.className = 'match-replay-meta';
        meta.textContent = (replay.rootName || 'Replay') + ' - ' + formatDate(replay.savedAt || replay.storedAt) + ' - ' + (replay.actionCount || 0) + ' actions';
        row.appendChild(meta);

        var play = document.createElement('button');
        play.type = 'button';
        play.textContent = 'Play';
        play.addEventListener('click', function() { playReplay(replay.id); });
        row.appendChild(play);

        var del = document.createElement('button');
        del.type = 'button';
        del.textContent = 'Delete';
        del.addEventListener('click', function() {
          if (!confirm('Delete this saved replay from this browser?')) return;
          deleteReplay(replay.id).then(renderPanelList);
        });
        row.appendChild(del);

        body.appendChild(row);
      });
    }).catch(function(error) {
      var errorEl = document.createElement('div');
      errorEl.className = 'match-replay-muted';
      errorEl.textContent = error.message || String(error);
      body.appendChild(errorEl);
    });
  }

  function addGameOverButton(overlay) {
    if (!overlay || !window.indexedDB || !config().canDownload || byId('match-replay-game-over-save-btn')) return;
    var btn = document.createElement('button');
    btn.id = 'match-replay-game-over-save-btn';
    btn.textContent = 'Save Replay';
    btn.addEventListener('click', saveCurrentReplay);

    var menuBtn = byId('game-over-menu-btn');
    if (menuBtn && menuBtn.parentNode === overlay) {
      overlay.insertBefore(btn, menuBtn);
    } else {
      overlay.appendChild(btn);
    }
  }

  function init(matchReplayConfig) {
    window.MatchReplayConfig = matchReplayConfig || window.MatchReplayConfig || { enabled: false };
    if (!window.MatchReplayConfig.enabled) return;
    if (!window.indexedDB) return;
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', ensurePanel);
    } else {
      ensurePanel();
    }
  }

  window.MatchReplayClient = {
    init: init,
    saveCurrentReplay: saveCurrentReplay,
    addGameOverButton: addGameOverButton,
    renderPanelList: renderPanelList
  };
  window.MatchReplayAddGameOverButton = addGameOverButton;
})();
