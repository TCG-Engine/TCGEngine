(function () {
  'use strict';

  var PREFIX = 'GAMELOG:';
  var DB_NAME = 'azuki-human-game-logs';
  var DB_VERSION = 1;
  var GAME_STORE = 'games';
  var EVENT_STORE = 'events';
  var MAX_STORED_GAMES = 250;
  var INCOMPLETE_RETENTION_MS = 7 * 24 * 60 * 60 * 1000;
  var dbPromise = null;
  var config = {};
  var captureEnabled = false;
  var storeChain = Promise.resolve();

  function openDb() {
    if (!window.indexedDB) return Promise.reject(new Error('IndexedDB is not available.'));
    if (dbPromise) return dbPromise;
    dbPromise = new Promise(function (resolve, reject) {
      var request = indexedDB.open(DB_NAME, DB_VERSION);
      request.onupgradeneeded = function (event) {
        var db = event.target.result;
        if (!db.objectStoreNames.contains(GAME_STORE)) {
          db.createObjectStore(GAME_STORE, { keyPath: 'id' });
        }
        if (!db.objectStoreNames.contains(EVENT_STORE)) {
          var events = db.createObjectStore(EVENT_STORE, { keyPath: ['gameId', 'seq'] });
          events.createIndex('by_game', 'gameId', { unique: false });
        }
      };
      request.onsuccess = function () { resolve(request.result); };
      request.onerror = function () { reject(request.error || new Error('Could not open game-log storage.')); };
    });
    return dbPromise;
  }

  function requestAsPromise(request) {
    return new Promise(function (resolve, reject) {
      request.onsuccess = function () { resolve(request.result); };
      request.onerror = function () { reject(request.error || new Error('IndexedDB request failed.')); };
    });
  }

  function transactionAsPromise(tx, errorMessage) {
    return new Promise(function (resolve, reject) {
      tx.oncomplete = resolve;
      tx.onerror = function () { reject(tx.error || new Error(errorMessage)); };
      tx.onabort = function () { reject(tx.error || new Error(errorMessage)); };
    });
  }

  async function pruneStoredGames(db) {
    var readTx = db.transaction(GAME_STORE, 'readonly');
    var allGames = await requestAsPromise(readTx.objectStore(GAME_STORE).getAll());
    allGames = Array.isArray(allGames) ? allGames : [];
    allGames.sort(function (a, b) {
      return String(b.updatedAt || '').localeCompare(String(a.updatedAt || ''));
    });
    var cutoff = Date.now() - INCOMPLETE_RETENTION_MS;
    var victims = allGames.filter(function (game, index) {
      var staleIncomplete = !game.complete && Date.parse(game.updatedAt || game.startedAt || 0) < cutoff;
      return staleIncomplete || index >= MAX_STORED_GAMES;
    });
    if (!victims.length) return;

    var writeTx = db.transaction([GAME_STORE, EVENT_STORE], 'readwrite');
    var games = writeTx.objectStore(GAME_STORE);
    var eventIndex = writeTx.objectStore(EVENT_STORE).index('by_game');
    victims.forEach(function (game) {
      games.delete(game.id);
      var cursorRequest = eventIndex.openCursor(IDBKeyRange.only(game.id));
      cursorRequest.onsuccess = function () {
        var cursor = cursorRequest.result;
        if (!cursor) return;
        cursor.delete();
        cursor.continue();
      };
    });
    await transactionAsPromise(writeTx, 'Could not prune old game logs.');
  }

  function parsePayload(responseArr) {
    if (!Array.isArray(responseArr)) return null;
    for (var i = responseArr.length - 1; i >= 0; --i) {
      var part = responseArr[i];
      if (typeof part !== 'string' || part.indexOf(PREFIX) !== 0) continue;
      try { return JSON.parse(part.substring(PREFIX.length)); } catch (e) { return null; }
    }
    return null;
  }

  function gameKey(gameId, viewer) {
    return String(gameId) + ':p' + String(viewer);
  }

  async function storePayload(payload) {
    if (!payload || !payload.enabled || !payload.game_id) return;
    var db = await openDb();
    var id = gameKey(payload.game_id, config.viewer);
    var readTx = db.transaction(GAME_STORE, 'readonly');
    var current = await requestAsPromise(readTx.objectStore(GAME_STORE).get(id));
    var tx = db.transaction([GAME_STORE, EVENT_STORE], 'readwrite');
    var games = tx.objectStore(GAME_STORE);
    var events = tx.objectStore(EVENT_STORE);
    var now = new Date().toISOString();
    var gameEnd = (payload.events || []).find(function (event) {
      return event && event.e === 'game_end';
    });
    var game = Object.assign({}, current || {}, {
      id: id,
      gameId: String(payload.game_id),
      viewer: Number(config.viewer),
      schema: payload.schema || 'azuki-gamelog@1.0.0',
      sessionKind: payload.session_kind || 'human_pvp',
      startedAt: (current && current.startedAt) || payload.started_at || now,
      updatedAt: now,
      lastUpdate: Number(payload.update || 0),
      turn: Number(payload.turn || 0),
      leader: payload.leader || (current && current.leader) || {},
      gate: payload.gate || (current && current.gate) || {},
      winner: Number(payload.winner || 0),
      endReason: (gameEnd && gameEnd.reason) || (current && current.endReason) || null,
      complete: Number(payload.winner || 0) > 0,
      finalSnap: payload.final_snap || (current && current.finalSnap) || null
    });
    if (game.complete && !game.completedAt) game.completedAt = now;
    games.put(game);
    (payload.events || []).forEach(function (event) {
      if (!event || !Number.isFinite(Number(event.seq))) return;
      events.put({ gameId: id, seq: Number(event.seq), event: event });
    });
    await transactionAsPromise(tx, 'Could not store the game log.');
    if (!current) {
      pruneStoredGames(db).catch(function (error) {
        console.warn('Could not prune old Azuki game logs:', error);
      });
    }
  }

  function yamlScalar(value) {
    if (value === null || value === undefined) return 'null';
    if (typeof value === 'boolean' || typeof value === 'number') return String(value);
    return JSON.stringify(String(value));
  }

  function yamlFlow(value) {
    if (Array.isArray(value)) return '[' + value.map(yamlFlow).join(', ') + ']';
    if (value && typeof value === 'object') {
      return '{' + Object.keys(value).map(function (key) {
        return key + ': ' + yamlFlow(value[key]);
      }).join(', ') + '}';
    }
    return yamlScalar(value);
  }

  function snapshotYaml(snap) {
    var lines = ['snap:'];
    Object.keys(snap || {}).forEach(function (key) {
      lines.push('  ' + key + ': ' + yamlFlow(snap[key]));
    });
    return lines.join('\n');
  }

  function eventYaml(event) {
    var copy = {};
    Object.keys(event || {}).forEach(function (key) {
      if (key !== 'snap') copy[key] = event[key];
    });
    return '  - ' + yamlFlow(copy);
  }

  function renderMarkdown(game, rows) {
    var allEvents = rows.map(function (row) { return row.event; }).sort(function (a, b) {
      return Number(a.seq || 0) - Number(b.seq || 0);
    });
    var setup = [];
    var turns = [];
    var current = null;
    allEvents.forEach(function (event) {
      if (event.e === 'turn_start') {
        current = { turn: Number(event.turn || 0), by: event.by || '-', snap: event.snap || {}, events: [event] };
        turns.push(current);
      } else if (current) {
        current.events.push(event);
      } else {
        setup.push(event);
      }
    });

    var viewer = 'p' + game.viewer;
    var opponent = game.viewer === 1 ? 'p2' : 'p1';
    var title = (game.leader && game.leader[viewer] ? game.leader[viewer] : viewer)
      + ' vs ' + (game.leader && game.leader[opponent] ? game.leader[opponent] : opponent);
    var lines = [
      '---',
      'schema: ' + yamlScalar(game.schema),
      'game_id: ' + yamlScalar(game.gameId),
      'played_at: ' + yamlScalar(game.startedAt),
      'viewer: ' + viewer,
      'session_kind: ' + yamlScalar(game.sessionKind),
      'players: ' + yamlFlow({
        p1: { leader: game.leader && game.leader.p1, gate: game.gate && game.gate.p1 },
        p2: { leader: game.leader && game.leader.p2, gate: game.gate && game.gate.p2 }
      }),
      'result: ' + yamlFlow({
        winner: game.winner ? 'p' + game.winner : null,
        reason: game.endReason || null,
        turns: game.turn || 0
      }),
      '---',
      '',
      '# ' + title,
      '',
      '## Game Log',
      '',
      '### Setup',
      '```yaml',
      'events:'
    ];
    setup.forEach(function (event) { lines.push(eventYaml(event)); });
    lines.push('```');
    turns.forEach(function (turn) {
      lines.push('', '### Turn ' + turn.turn + ' (' + turn.by + ')', '```yaml', snapshotYaml(turn.snap), '```', '```yaml', 'events:');
      turn.events.forEach(function (event) { lines.push(eventYaml(event)); });
      lines.push('```');
    });
    lines.push('', '## Final State');
    if (game.finalSnap) {
      lines.push('```yaml', snapshotYaml(game.finalSnap), '```');
    }
    lines.push('', 'Winner: ' + (game.winner ? 'p' + game.winner : 'unknown'), '');
    return lines.join('\n');
  }

  async function listGames() {
    await storeChain;
    var db = await openDb();
    var tx = db.transaction(GAME_STORE, 'readonly');
    var games = await requestAsPromise(tx.objectStore(GAME_STORE).getAll());
    return (Array.isArray(games) ? games : []).sort(function (a, b) {
      return String(b.startedAt || b.updatedAt || '').localeCompare(String(a.startedAt || a.updatedAt || ''));
    });
  }

  async function loadGame(id) {
    await storeChain;
    var db = await openDb();
    var tx = db.transaction([GAME_STORE, EVENT_STORE], 'readonly');
    var gameRequest = tx.objectStore(GAME_STORE).get(String(id));
    var rowsRequest = tx.objectStore(EVENT_STORE).index('by_game').getAll(String(id));
    var loaded = await Promise.all([
      requestAsPromise(gameRequest),
      requestAsPromise(rowsRequest)
    ]);
    var game = loaded[0];
    if (!game) throw new Error('No local game log was found.');
    return { game: game, rows: loaded[1] || [] };
  }

  function loadCurrentGame() {
    return loadGame(gameKey(config.gameName, config.viewer));
  }

  async function deleteGame(id) {
    await storeChain;
    var db = await openDb();
    var tx = db.transaction([GAME_STORE, EVENT_STORE], 'readwrite');
    tx.objectStore(GAME_STORE).delete(String(id));
    var eventIndex = tx.objectStore(EVENT_STORE).index('by_game');
    var cursorRequest = eventIndex.openCursor(IDBKeyRange.only(String(id)));
    cursorRequest.onsuccess = function () {
      var cursor = cursorRequest.result;
      if (!cursor) return;
      cursor.delete();
      cursor.continue();
    };
    await transactionAsPromise(tx, 'Could not delete the game log.');
  }

  async function clearAllGames() {
    await storeChain;
    var db = await openDb();
    var tx = db.transaction([GAME_STORE, EVENT_STORE], 'readwrite');
    tx.objectStore(GAME_STORE).clear();
    tx.objectStore(EVENT_STORE).clear();
    await transactionAsPromise(tx, 'Could not clear the saved game logs.');
  }

  function downloadLoadedGame(loaded) {
    var markdown = renderMarkdown(loaded.game, loaded.rows);
    downloadMarkdown(markdown, 'azuki-game-' + loaded.game.gameId + '-p' + loaded.game.viewer + '.md');
  }

  function downloadMarkdown(markdown, filename) {
    var blob = new Blob([markdown], { type: 'text/markdown;charset=utf-8' });
    var url = URL.createObjectURL(blob);
    var link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
  }

  async function exportGame(id) {
    downloadLoadedGame(await loadGame(id));
  }

  function notifyError(error, fallback) {
    var msg = (error && error.message) || fallback;
    if (typeof window.StyledAlert === 'function') window.StyledAlert(msg);
    else if (typeof window.Toast === 'function') window.Toast(msg, { type: 'error' });
    else console.error(msg);
  }

  function formatDate(value) {
    if (!value) return 'Unknown date';
    var parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) return 'Unknown date';
    return parsed.toLocaleString([], {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: 'numeric',
      minute: '2-digit'
    });
  }

  function leaderLabel(game, player) {
    var key = 'p' + player;
    return (game.leader && game.leader[key]) || key.toUpperCase();
  }

  function gameResultLabel(game) {
    if (!game.complete || !game.winner) return 'Incomplete';
    return Number(game.winner) === Number(game.viewer) ? 'Won' : 'Lost';
  }

  function deckKey(game) {
    return String(leaderLabel(game, Number(game.viewer) || 1));
  }

  function deckDisplayName(key) {
    var label = String(key || 'Unknown deck');
    return label.replace(/\s+\([^()]+\)\s*$/, '') || label;
  }

  function safeFilenamePart(value) {
    return String(value || 'deck')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '') || 'deck';
  }

  async function exportCompletedGamesForDeck(games, selectedDeck) {
    var completed = games.filter(function (game) {
      return game.complete && Number(game.winner || 0) > 0 && deckKey(game) === selectedDeck;
    });
    if (!completed.length) throw new Error('No completed game logs were found for this deck.');

    var loadedGames = await Promise.all(completed.map(function (game) {
      return loadGame(game.id);
    }));
    var heading = [
      '# Azuki Completed Game Logs',
      '',
      'Deck: ' + deckDisplayName(selectedDeck),
      'Games: ' + loadedGames.length,
      'Exported: ' + new Date().toISOString(),
      ''
    ].join('\n');
    var separator = '\n\n<!-- azuki-game-log-boundary -->\n\n';
    var markdown = heading + loadedGames.map(function (loaded) {
      return renderMarkdown(loaded.game, loaded.rows);
    }).join(separator);
    var date = new Date().toISOString().slice(0, 10);
    downloadMarkdown(markdown, 'azuki-' + safeFilenamePart(deckDisplayName(selectedDeck)) + '-completed-games-' + date + '.md');
  }

  function ensureViewerStyles() {
    if (document.getElementById('azuki-game-log-client-styles')) return;
    var style = document.createElement('style');
    style.id = 'azuki-game-log-client-styles';
    style.textContent =
      '.azuki-game-log-modal{position:fixed;inset:0;z-index:10050;display:flex;align-items:center;justify-content:center;padding:20px;box-sizing:border-box;}' +
      '.azuki-game-log-modal-backdrop{position:absolute;inset:0;border:0;background:rgba(3,10,18,.82);cursor:pointer;}' +
      '.azuki-game-log-dialog{position:relative;width:min(920px,96vw);height:min(760px,90vh);display:flex;flex-direction:column;overflow:hidden;background:#102238;border:1px solid rgba(214,184,109,.62);border-radius:12px;box-shadow:0 24px 70px rgba(0,0,0,.55);color:#f8f1df;}' +
      '.azuki-game-log-header{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:14px 16px;border-bottom:1px solid rgba(214,184,109,.28);}' +
      '.azuki-game-log-heading{min-width:0;}' +
      '.azuki-game-log-heading strong{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:16px;}' +
      '.azuki-game-log-heading span{display:block;margin-top:2px;color:#bec9d5;font-size:12px;}' +
      '.azuki-game-log-close{flex:0 0 auto;border:1px solid rgba(214,184,109,.45);border-radius:7px;background:rgba(255,255,255,.06);color:#fff4cf;padding:7px 11px;cursor:pointer;font-weight:700;}' +
      '.azuki-game-log-document{flex:1;overflow:auto;margin:0;padding:16px;background:rgba(3,10,18,.46);color:#e5edf5;font:12px/1.55 Consolas,Monaco,monospace;white-space:pre-wrap;word-break:break-word;}' +
      '.azuki-game-log-modal-actions{display:flex;justify-content:flex-end;gap:8px;padding:10px 16px;border-top:1px solid rgba(214,184,109,.22);}' +
      '.azuki-game-log-modal-actions button{border:1px solid rgba(214,184,109,.45);border-radius:7px;background:#d6b86d;color:#102238;padding:8px 12px;cursor:pointer;font-weight:800;}' +
      '.azuki-game-log-toolbar{display:flex;align-items:end;gap:10px;flex-wrap:wrap;padding:10px 12px;border:1px solid rgba(201,168,76,.28);border-radius:8px;background:rgba(10,24,42,.72);}' +
      '.azuki-game-log-deck-label{display:flex;flex:1 1 240px;flex-direction:column;gap:4px;color:#bfc8d7;font-size:12px;font-weight:700;}' +
      '.azuki-game-log-deck-select{width:100%;min-width:180px;border:1px solid var(--border);border-radius:var(--radius);background:var(--surface-sunken);color:var(--input-text,var(--text));padding:7px 9px;}' +
      '.azuki-game-log-row{grid-template-columns:minmax(0,1fr) auto auto auto;}' +
      '@media(max-width:640px){.azuki-game-log-row{grid-template-columns:minmax(0,1fr) auto auto auto;}.azuki-game-log-row .match-replay-meta{grid-column:1/-1;}}' +
      'body.azuki-game-log-modal-open{overflow:hidden;}';
    document.head.appendChild(style);
  }

  async function viewGame(id) {
    var loaded = await loadGame(id);
    ensureViewerStyles();
    var existing = document.getElementById('azuki-game-log-modal');
    if (existing) existing.remove();

    var modal = document.createElement('div');
    modal.id = 'azuki-game-log-modal';
    modal.className = 'azuki-game-log-modal';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute('aria-labelledby', 'azuki-game-log-modal-title');

    var backdrop = document.createElement('button');
    backdrop.type = 'button';
    backdrop.className = 'azuki-game-log-modal-backdrop';
    backdrop.setAttribute('aria-label', 'Close game log');
    modal.appendChild(backdrop);

    var dialog = document.createElement('div');
    dialog.className = 'azuki-game-log-dialog';
    modal.appendChild(dialog);

    var header = document.createElement('div');
    header.className = 'azuki-game-log-header';
    dialog.appendChild(header);

    var heading = document.createElement('div');
    heading.className = 'azuki-game-log-heading';
    var title = document.createElement('strong');
    title.id = 'azuki-game-log-modal-title';
    title.textContent = leaderLabel(loaded.game, 1) + ' vs ' + leaderLabel(loaded.game, 2);
    heading.appendChild(title);
    var subtitle = document.createElement('span');
    subtitle.textContent = formatDate(loaded.game.startedAt) + ' - ' + gameResultLabel(loaded.game);
    heading.appendChild(subtitle);
    header.appendChild(heading);

    var close = document.createElement('button');
    close.type = 'button';
    close.className = 'azuki-game-log-close';
    close.textContent = 'Close';
    header.appendChild(close);

    var documentView = document.createElement('pre');
    documentView.className = 'azuki-game-log-document';
    documentView.textContent = renderMarkdown(loaded.game, loaded.rows);
    dialog.appendChild(documentView);

    var actions = document.createElement('div');
    actions.className = 'azuki-game-log-modal-actions';
    var exportButton = document.createElement('button');
    exportButton.type = 'button';
    exportButton.textContent = 'Export Markdown';
    exportButton.addEventListener('click', function () { downloadLoadedGame(loaded); });
    actions.appendChild(exportButton);
    dialog.appendChild(actions);

    var previousFocus = document.activeElement;
    function closeModal() {
      document.removeEventListener('keydown', onKeyDown);
      document.body.classList.remove('azuki-game-log-modal-open');
      modal.remove();
      if (previousFocus && typeof previousFocus.focus === 'function') previousFocus.focus();
    }
    function onKeyDown(event) {
      if (event.key === 'Escape') closeModal();
    }
    backdrop.addEventListener('click', closeModal);
    close.addEventListener('click', closeModal);
    document.addEventListener('keydown', onKeyDown);
    document.body.appendChild(modal);
    document.body.classList.add('azuki-game-log-modal-open');
    close.focus();
  }

  // StyledDialog.js is loaded by both pages that pull this file in — NextTurn.php directly, and the
  // AzukiSim menu via RenderMenuBar -> RenderHead — so StyledConfirm is always defined here.
  function confirmDelete() {
    return window.StyledConfirm('Delete this saved game log from this browser?', {
      title: 'Delete game log',
      danger: true,
      confirmLabel: 'Delete'
    });
  }

  function confirmClearAll() {
    return window.StyledConfirm(
      'Delete all saved game logs from this browser? Downloaded Markdown exports will not be affected.',
      {
        title: 'Clear saved game logs',
        danger: true,
        confirmLabel: 'Clear all'
      }
    );
  }

  function makeLibraryButton(label, handler) {
    var button = document.createElement('button');
    button.type = 'button';
    button.className = 'match-replay-button';
    button.textContent = label;
    button.addEventListener('click', handler);
    return button;
  }

  function renderGameLibrary(containerOrId) {
    var container = typeof containerOrId === 'string'
      ? document.getElementById(containerOrId)
      : containerOrId;
    if (!container) return;
    ensureViewerStyles();
    container.classList.add('match-replay-library');
    container.innerHTML = '';

    if (!window.indexedDB) {
      var unavailable = document.createElement('div');
      unavailable.className = 'match-replay-muted';
      unavailable.textContent = 'Game-log storage is not available in this browser.';
      container.appendChild(unavailable);
      return;
    }

    var loading = document.createElement('div');
    loading.className = 'match-replay-muted';
    loading.textContent = 'Loading saved game logs...';
    container.appendChild(loading);

    listGames().then(function (games) {
      container.innerHTML = '';
      if (!games.length) {
        var empty = document.createElement('div');
        empty.className = 'match-replay-muted';
        empty.textContent = 'No game logs have been saved in this browser yet.';
        container.appendChild(empty);
        return;
      }

      var deckNames = {};
      games.forEach(function (game) { deckNames[deckKey(game)] = true; });
      var decks = Object.keys(deckNames).sort(function (a, b) {
        return deckDisplayName(a).localeCompare(deckDisplayName(b));
      });
      var selectedDeck = container.dataset.azukiSelectedDeck;
      if (!selectedDeck || !deckNames[selectedDeck]) selectedDeck = decks[0];
      container.dataset.azukiSelectedDeck = selectedDeck;

      var toolbar = document.createElement('div');
      toolbar.className = 'azuki-game-log-toolbar';
      var deckLabel = document.createElement('label');
      deckLabel.className = 'azuki-game-log-deck-label';
      deckLabel.textContent = 'Deck';
      var deckSelect = document.createElement('select');
      deckSelect.className = 'azuki-game-log-deck-select';
      decks.forEach(function (deck) {
        var option = document.createElement('option');
        option.value = deck;
        option.textContent = deckDisplayName(deck);
        option.selected = deck === selectedDeck;
        deckSelect.appendChild(option);
      });
      deckLabel.appendChild(deckSelect);
      toolbar.appendChild(deckLabel);

      var exportAll = makeLibraryButton('', function () {
        exportAll.disabled = true;
        exportCompletedGamesForDeck(games, deckSelect.value).catch(function (error) {
          notifyError(error, 'Could not export the completed game logs.');
        }).finally(function () {
          exportAll.disabled = false;
          updateBulkExportButton();
        });
      });
      toolbar.appendChild(exportAll);

      var clearAll = makeLibraryButton('Clear saved logs', function () {
        confirmClearAll().then(function (confirmed) {
          if (!confirmed) return;
          clearAll.disabled = true;
          clearAllGames().then(function () {
            delete container.dataset.azukiSelectedDeck;
            renderGameLibrary(container);
          }).catch(function (error) {
            clearAll.disabled = false;
            notifyError(error, 'Could not clear the saved game logs.');
          });
        });
      });
      toolbar.appendChild(clearAll);
      container.appendChild(toolbar);

      var list = document.createElement('div');
      list.className = 'match-replay-library';
      container.appendChild(list);

      function selectedGames() {
        return games.filter(function (game) { return deckKey(game) === deckSelect.value; });
      }

      function updateBulkExportButton() {
        var count = selectedGames().filter(function (game) {
          return game.complete && Number(game.winner || 0) > 0;
        }).length;
        exportAll.textContent = 'Export completed (' + count + ')';
        exportAll.disabled = count === 0;
      }

      function renderSelectedGames() {
        list.innerHTML = '';
        selectedGames().forEach(function (game) {
          var row = document.createElement('div');
          row.className = 'match-replay-row azuki-game-log-row';

          var meta = document.createElement('div');
          meta.className = 'match-replay-meta';
          var title = document.createElement('strong');
          title.textContent = leaderLabel(game, 1) + ' vs ' + leaderLabel(game, 2);
          meta.appendChild(title);
          var details = document.createElement('span');
          var turnCount = Number(game.turn || 0);
          details.textContent = formatDate(game.startedAt) + ' - ' + turnCount + ' ' +
            (turnCount === 1 ? 'turn' : 'turns') + ' - ' + gameResultLabel(game);
          meta.appendChild(details);
          row.appendChild(meta);

          row.appendChild(makeLibraryButton('View', function () {
            viewGame(game.id).catch(function (error) {
              notifyError(error, 'Could not open the game log.');
            });
          }));
          row.appendChild(makeLibraryButton('Export', function () {
            exportGame(game.id).catch(function (error) {
              notifyError(error, 'Could not export the game log.');
            });
          }));
          row.appendChild(makeLibraryButton('Delete', function () {
            confirmDelete().then(function (confirmed) {
              if (!confirmed) return;
              deleteGame(game.id).then(function () {
                container.dataset.azukiSelectedDeck = deckSelect.value;
                renderGameLibrary(container);
              }).catch(function (error) {
                notifyError(error, 'Could not delete the game log.');
              });
            });
          }));
          list.appendChild(row);
        });
        updateBulkExportButton();
      }

      deckSelect.addEventListener('change', function () {
        container.dataset.azukiSelectedDeck = deckSelect.value;
        renderSelectedGames();
      });
      renderSelectedGames();
    }).catch(function (error) {
      container.innerHTML = '';
      var errorEl = document.createElement('div');
      errorEl.className = 'match-replay-muted';
      errorEl.textContent = (error && error.message) || 'Could not load saved game logs.';
      container.appendChild(errorEl);
    });
  }

  async function exportCurrentGame() {
    try {
      downloadLoadedGame(await loadCurrentGame());
    } catch (error) {
      notifyError(error, 'Could not export the game log.');
    }
  }

  function addGameOverButton(target) {
    if (!captureEnabled || !target || target.querySelector('#azuki-game-log-export-btn')) return;
    var button = document.createElement('button');
    button.id = 'azuki-game-log-export-btn';
    button.className = 'btn btn-secondary';
    button.type = 'button';
    button.textContent = 'Export Game Log';
    button.addEventListener('click', exportCurrentGame);
    target.appendChild(button);
  }

  window.GameLogClient = {
    init: function (nextConfig) { config = Object.assign({}, config, nextConfig || {}); },
    ingestResponse: function (responseArr) {
      var payload = parsePayload(responseArr);
      if (!payload || !payload.enabled) return;
      captureEnabled = true;
      storeChain = storeChain.then(function () {
        return storePayload(payload);
      }).catch(function (error) {
        console.warn('Azuki game log was not stored:', error);
      });
    },
    addGameOverButton: addGameOverButton,
    exportCurrentGame: exportCurrentGame,
    listGames: listGames,
    loadGame: loadGame,
    viewGame: viewGame,
    exportGame: exportGame,
    deleteGame: deleteGame,
    clearAllGames: clearAllGames,
    renderGameLibrary: renderGameLibrary
  };
})();
