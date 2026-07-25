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
    await new Promise(function (resolve, reject) {
      writeTx.oncomplete = resolve;
      writeTx.onerror = function () { reject(writeTx.error || new Error('Could not prune old game logs.')); };
      writeTx.onabort = function () { reject(writeTx.error || new Error('Game-log pruning was aborted.')); };
    });
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
    await new Promise(function (resolve, reject) {
      tx.oncomplete = resolve;
      tx.onerror = function () { reject(tx.error || new Error('Could not store the game log.')); };
      tx.onabort = function () { reject(tx.error || new Error('Game-log storage was aborted.')); };
    });
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

  async function loadCurrentGame() {
    var db = await openDb();
    var id = gameKey(config.gameName, config.viewer);
    var tx = db.transaction([GAME_STORE, EVENT_STORE], 'readonly');
    var game = await requestAsPromise(tx.objectStore(GAME_STORE).get(id));
    if (!game) throw new Error('No local game log was found.');
    var rows = await requestAsPromise(tx.objectStore(EVENT_STORE).index('by_game').getAll(id));
    return { game: game, rows: rows || [] };
  }

  async function exportCurrentGame() {
    try {
      await storeChain;
      var loaded = await loadCurrentGame();
      var markdown = renderMarkdown(loaded.game, loaded.rows);
      var blob = new Blob([markdown], { type: 'text/markdown;charset=utf-8' });
      var url = URL.createObjectURL(blob);
      var link = document.createElement('a');
      link.href = url;
      link.download = 'azuki-game-' + loaded.game.gameId + '-p' + loaded.game.viewer + '.md';
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
    } catch (error) {
      if (typeof window.StyledAlert === 'function') window.StyledAlert(error.message || 'Could not export the game log.');
      else window.alert(error.message || 'Could not export the game log.');
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
    exportCurrentGame: exportCurrentGame
  };
})();
