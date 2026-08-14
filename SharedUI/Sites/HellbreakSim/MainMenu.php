<?php
include_once __DIR__ . '/MenuBar.php';
include_once __DIR__ . '/../../../AccountFiles/AccountSessionAPI.php';
include_once __DIR__ . '/../../../Database/ConnectionManager.php';
include_once __DIR__ . '/Header.php';

$decks = [];
if (IsUserLoggedIn()) {
  $conn = GetLocalMySQLConnection();
  if ($conn) {
    $userID = (string)LoggedInUser();
    $stmt = $conn->prepare('SELECT assetIdentifier, assetName, keyIndicator1, keyIndicator2, lastUpdated FROM ownership WHERE assetType = 1 AND assetOwner = ? AND assetStatus = 1 ORDER BY assetIdentifier DESC');
    if ($stmt) {
      $stmt->bind_param('s', $userID);
      $stmt->execute();
      $result = $stmt->get_result();
      while ($row = $result->fetch_assoc()) {
        $deckID = (string)$row['assetIdentifier'];
        if (is_file(__DIR__ . '/../../../HellbreakDeck/Games/' . $deckID . '/Gamestate.txt')) $decks[] = $row;
      }
      $stmt->close();
    }
    $conn->close();
  }
}
?>
<main class="hellbreak-shell">
  <div class="hellbreak-menu-grid">
    <section class="hellbreak-panel hellbreak-active-panel">
      <div class="hellbreak-panel-heading">
        <h3><span class="hellbreak-heading-icon" aria-hidden="true">&#9813;</span> Active Games <strong id="active-game-count">0</strong></h3>
        <button class="hellbreak-icon-button" type="button" onclick="refreshActiveGames(this)" aria-label="Refresh active games">&#8635;</button>
      </div>
      <button id="rejoin-last-game" class="hellbreak-rejoin" type="button" onclick="rejoinLastGame()" hidden>
        <span aria-hidden="true">&#8617;</span><span><strong>Rejoin recent game</strong><small id="rejoin-last-game-note"></small></span>
      </button>
      <div id="active-games-list" class="hellbreak-active-list">
        <div class="hellbreak-empty hellbreak-active-empty"><span aria-hidden="true">&#9876;</span><strong>No active games right now.</strong><small>Start one or refresh again in a moment.</small></div>
      </div>
    </section>

    <section class="hellbreak-panel hellbreak-play-panel">
      <div class="hellbreak-workspace-heading">
        <h2>Prepare a Deck</h2>
        <span>Build or choose a deck to begin your match</span>
      </div>

      <div class="hellbreak-library-heading">
        <h3><span class="hellbreak-heading-icon" aria-hidden="true">&#9638;</span> Deck Library</h3>
        <?php if (IsUserLoggedIn()): ?><a class="hellbreak-create-deck" href="/TCGEngine/HellbreakDeck/CreateDeck.php"><span aria-hidden="true">+</span> Create Deck</a><?php endif; ?>
      </div>

      <div class="hellbreak-library-tabs" role="tablist" aria-label="Deck sources">
        <span class="is-active">My Decks</span>
        <span>Starter Decks</span>
      </div>

      <section class="hellbreak-selected-section" aria-labelledby="hellbreak-selected-title">
        <div class="hellbreak-selected-heading">
          <span id="hellbreak-selected-title"><span aria-hidden="true">&#10022;</span> Selected Deck</span>
          <label for="hellbreak-match-deck">Change Deck</label>
        </div>
        <div class="hellbreak-selected-deck">
          <div class="hellbreak-deck-art" aria-hidden="true">
            <img src="/TCGEngine/HellbreakSim/crops/DOT_001_cropped.png" alt="">
            <img src="/TCGEngine/HellbreakSim/crops/DOT_006_cropped.png" alt="">
          </div>
          <div class="hellbreak-selected-copy">
            <strong id="hellbreak-selected-name">GAMA Demo</strong>
            <span id="hellbreak-selected-details">Dracula vs. Jaws</span>
            <small><b id="hellbreak-selected-count">40 cards</b><b>Standard</b></small>
          </div>
          <select id="hellbreak-match-deck" aria-label="Deck for this match">
            <option value="preset:HellbreakGamaDemo" data-name="GAMA Demo" data-details="Dracula vs. Jaws" data-count="40 cards">GAMA Demo - Dracula vs. Jaws (40 cards)</option>
            <option value="preset:HellbreakFixture" data-name="Engine Fixture" data-details="Development decks" data-count="24 cards">Engine fixture (24 cards)</option>
            <?php foreach ($decks as $deck):
              $choiceID = (string)$deck['assetIdentifier'];
              $choiceName = trim((string)($deck['assetName'] ?? '')) ?: 'Hellbreak Deck #' . $choiceID;
            ?>
              <option value="hellbreakdeck:<?php echo htmlspecialchars($choiceID, ENT_QUOTES); ?>" data-name="<?php echo htmlspecialchars($choiceName, ENT_QUOTES); ?>" data-details="Saved Hellbreak deck" data-count="Custom list"><?php echo htmlspecialchars($choiceName, ENT_QUOTES); ?></option>
            <?php endforeach; ?>
          </select>
          <span class="hellbreak-selected-check" aria-label="Selected">&#10003;</span>
        </div>
      </section>

      <div class="hellbreak-ready-label"><span aria-hidden="true">&#10022;</span> Ready to Play?</div>

      <div class="hellbreak-game-actions">
        <button id="join-queue-btn" class="hellbreak-game-action primary" type="button" onclick="joinQueue()">
          <span class="hellbreak-action-icon" aria-hidden="true">&#9813;</span><span><strong>Join Queue</strong><small>Find an opponent</small></span>
        </button>
        <button id="start-tutorial-btn" class="hellbreak-game-action" type="button" onclick="startTutorial()">
          <span class="hellbreak-action-icon" aria-hidden="true">?</span><span><strong>Learn to Play</strong><small>Guided quick-start</small></span>
        </button>
        <button id="start-fixture-match-btn" class="hellbreak-game-action" type="button" onclick="startFixtureMatch()">
          <span class="hellbreak-action-icon" aria-hidden="true">&#9654;</span><span><strong>Play vs. Bot</strong><small>Practice mode</small></span>
        </button>
        <button id="create-private-game-btn" class="hellbreak-game-action" type="button" onclick="createPrivateGame()">
          <span class="hellbreak-action-icon" aria-hidden="true">&#128274;</span><span><strong>Private Game</strong><small>Invite your friends</small></span>
        </button>
        <button id="join-private-invite-btn" class="hellbreak-game-action invite" type="button" onclick="joinPrivateInvite()">
          <span class="hellbreak-action-icon" aria-hidden="true">&#10148;</span><span><strong>Join Private Invite</strong><small>Enter your friend's lobby</small></span>
        </button>
      </div>
      <div id="private-invite-notice" class="hellbreak-inline-notice" style="display:none"></div>
      <div id="queue-inline-error" class="hellbreak-inline-error" role="alert" hidden></div>
    </section>

    <section class="hellbreak-panel hellbreak-info-panel">
      <div class="hellbreak-info-tabs"><span class="is-active">Welcome</span></div>
      <div class="hellbreak-welcome">
        <h2>Welcome to Hellbreak</h2>
        <p>A fan-made online simulator for the Hellbreak TCG.</p>
      </div>
      <div class="hellbreak-tip">
        <span class="hellbreak-eyebrow">&#128161; Did you know?</span>
        <p>The GAMA Demo puts Dracula against Jaws and is ready to play immediately.</p>
      </div>
      <div class="hellbreak-quick-reference">
        <h3>Quick Reference</h3>
        <div><kbd>?</kbd><span>Open the guided tutorial</span></div>
        <div><kbd>Esc</kbd><span>Cancel matchmaking</span></div>
      </div>
    </section>
  </div>
</main>

<div id="hellbreak-lobby-modal" class="hellbreak-modal" hidden>
  <div class="hellbreak-modal-backdrop" onclick="cancelWaitingLobby()"></div>
  <section class="hellbreak-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="hellbreak-modal-title">
    <button class="hellbreak-modal-close" type="button" onclick="cancelWaitingLobby()" aria-label="Cancel">&times;</button>
    <span class="hellbreak-modal-mark" aria-hidden="true">H</span>
    <h2 id="hellbreak-modal-title">Waiting for an opponent</h2>
    <p id="hellbreak-modal-message">Your lobby is ready.</p>
    <div id="hellbreak-invite-wrap" class="hellbreak-invite-wrap" hidden>
      <input id="hellbreak-invite-link" readonly aria-label="Private invite link">
      <button type="button" onclick="copyInviteLink()">Copy</button>
    </div>
    <button class="hellbreak-button secondary" type="button" onclick="cancelWaitingLobby()">Cancel</button>
  </section>
</div>

<script src="/TCGEngine/SharedUI/js/private-invite.js"></script>
<script>
(function() {
  'use strict';
  var rootName = 'HellbreakSim';
  var lobbyID = '';
  var lobbyPlayerID = '';
  var lobbyAuthKey = '';
  var privateInviteCode = '';
  var pollTimer = null;
  var lastGameKey = 'tcgengine:lastSimGame:' + rootName;

  function appBase() {
    var path = location.pathname;
    var index = path.indexOf('/TCGEngine/');
    return index >= 0 ? path.slice(0, index + 11) : '/TCGEngine/';
  }

  function encodeForm(values) {
    return Object.keys(values).filter(function(key) { return values[key] !== undefined && values[key] !== null; })
      .map(function(key) { return encodeURIComponent(key) + '=' + encodeURIComponent(values[key]); }).join('&');
  }

  function showError(message) {
    var error = document.getElementById('queue-inline-error');
    error.textContent = message || 'Unable to start the game.';
    error.hidden = false;
  }

  function clearError() {
    var error = document.getElementById('queue-inline-error');
    error.textContent = '';
    error.hidden = true;
  }

  function gameUrl(playerID, gameName, authKey, perspective) {
    var url = new URL(appBase() + 'NextTurn.php', location.href);
    url.searchParams.set('playerID', String(playerID));
    url.searchParams.set('gameName', String(gameName));
    url.searchParams.set('folderPath', rootName);
    if (authKey) url.searchParams.set('authKey', String(authKey));
    if (perspective) url.searchParams.set('viewerPerspective', String(perspective));
    return url.toString();
  }

  function rememberAndOpen(playerID, gameName, authKey) {
    try {
      localStorage.setItem(lastGameKey, JSON.stringify({
        rootName: rootName, playerID: String(playerID), gameName: String(gameName),
        authKey: String(authKey || ''), updatedAt: Date.now()
      }));
    } catch (e) {}
    location.href = gameUrl(playerID, gameName, authKey);
  }

  function getLastGame() {
    try { return JSON.parse(localStorage.getItem(lastGameKey) || 'null'); } catch (e) { return null; }
  }

  window.rejoinLastGame = function() {
    var game = getLastGame();
    if (game && game.gameName) location.href = gameUrl(game.playerID, game.gameName, game.authKey);
  };

  function updateRejoin() {
    var game = getLastGame();
    var button = document.getElementById('rejoin-last-game');
    var note = document.getElementById('rejoin-last-game-note');
    if (!game || !game.gameName || Date.now() - Number(game.updatedAt || 0) > 30 * 60 * 1000) {
      button.hidden = true;
      return;
    }
    note.textContent = 'Game ' + game.gameName + ' / Player ' + game.playerID;
    button.hidden = false;
  }

  function updateSelectedDeck() {
    var select = document.getElementById('hellbreak-match-deck');
    if (!select || !select.options.length) return;
    var option = select.options[select.selectedIndex];
    document.getElementById('hellbreak-selected-name').textContent = option.dataset.name || option.textContent;
    document.getElementById('hellbreak-selected-details').textContent = option.dataset.details || 'Ready for setup';
    document.getElementById('hellbreak-selected-count').textContent = option.dataset.count || 'Custom list';
  }

  function showWaiting(message, inviteLink) {
    document.getElementById('hellbreak-modal-message').textContent = message || 'Waiting for an opponent...';
    var wrap = document.getElementById('hellbreak-invite-wrap');
    var input = document.getElementById('hellbreak-invite-link');
    if (inviteLink) {
      input.value = inviteLink;
      wrap.hidden = false;
    } else {
      input.value = '';
      wrap.hidden = true;
    }
    document.getElementById('hellbreak-lobby-modal').hidden = false;
  }

  function privateInviteLink(code) {
    var url = new URL(location.href);
    url.searchParams.set('privateInvite', code);
    return url.toString();
  }

  function submitLobby(options) {
    options = options || {};
    clearError();
    var request = new XMLHttpRequest();
    request.open('POST', appBase() + 'APIs/Lobbies/JoinQueue.php', true);
    request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    request.onload = function() {
      var response = null;
      try { response = JSON.parse(request.responseText || '{}'); } catch (e) {}
      if (request.status < 200 || request.status >= 300 || !response || !response.success) {
        showError(response && response.message ? response.message : 'Failed to create or join the lobby.');
        return;
      }
      if (response.ready) {
        rememberAndOpen(response.playerID, response.gameName, response.authKey);
        return;
      }
      lobbyID = response.lobbyID || '';
      lobbyPlayerID = String(response.playerID || '');
      lobbyAuthKey = response.authKey || '';
      var inviteLink = response.inviteCode ? privateInviteLink(response.inviteCode) : '';
      showWaiting(options.private ? 'Share this invite link with your opponent.' : 'Waiting for another player in the queue...', inviteLink);
      pollLobby();
    };
    request.onerror = function() { showError('The lobby service could not be reached.'); };

    var deckChoice = document.getElementById('hellbreak-match-deck');
    var selectedDeck = deckChoice ? String(deckChoice.value || '') : 'preset:HellbreakGamaDemo';
    var presetPrefix = 'preset:';
    var preconstructedDeck = selectedDeck.indexOf(presetPrefix) === 0 ? selectedDeck.slice(presetPrefix.length) : '';
    var deckLink = preconstructedDeck ? '' : selectedDeck;
    var values = {
      rootName: rootName,
      preconstructedDeck: preconstructedDeck || (deckLink ? '' : 'HellbreakGamaDemo'),
      deckLink: deckLink,
      game_type: 'casual',
      format: options.immediate ? 'goldfish' : 'standard'
    };
    if (options.immediate) values.createGoldfish = '1';
    if (options.tutorial) {
      values.createTutorial = '1';
      values.format = 'tutorial';
      delete values.createGoldfish;
    }
    if (options.private) values.createPrivate = '1';
    if (options.inviteCode) values.privateInviteCode = options.inviteCode;
    request.send(encodeForm(values));
  }

  window.startFixtureMatch = function() { submitLobby({ immediate: true }); };
  window.startTutorial = function() { submitLobby({ immediate: true, tutorial: true }); };
  window.joinQueue = function() { submitLobby({}); };
  window.createPrivateGame = function() { submitLobby({ private: true }); };
  window.joinPrivateInvite = function() {
    if (!privateInviteCode) { showError('This page does not contain a private invite code.'); return; }
    submitLobby({ inviteCode: privateInviteCode });
  };

  function pollLobby() {
    if (!lobbyID) return;
    var request = new XMLHttpRequest();
    request.open('POST', appBase() + 'APIs/Lobbies/PollLobbyUpdates.php', true);
    request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    request.onload = function() {
      var response = null;
      try { response = JSON.parse(request.responseText || '{}'); } catch (e) {}
      if (request.status >= 200 && request.status < 300 && response && response.ready) {
        rememberAndOpen(response.playerID, response.gameName, lobbyAuthKey);
        return;
      }
      pollTimer = window.setTimeout(pollLobby, 2500);
    };
    request.onerror = function() { pollTimer = window.setTimeout(pollLobby, 4000); };
    request.send(encodeForm({ rootName: rootName, playerID: lobbyPlayerID, lobbyID: lobbyID, authKey: lobbyAuthKey }));
  }

  window.cancelWaitingLobby = function() {
    if (pollTimer) window.clearTimeout(pollTimer);
    document.getElementById('hellbreak-lobby-modal').hidden = true;
    if (!lobbyID) return;
    var request = new XMLHttpRequest();
    request.open('POST', appBase() + 'APIs/Lobbies/LeaveQueue.php', true);
    request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    request.send(encodeForm({ rootName: rootName, playerID: lobbyPlayerID, lobbyID: lobbyID, authKey: lobbyAuthKey }));
    lobbyID = '';
  };

  window.copyInviteLink = function() {
    var input = document.getElementById('hellbreak-invite-link');
    if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(input.value);
    else { input.select(); document.execCommand('copy'); }
  };

  function escapeHtml(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, function(character) {
      return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[character];
    });
  }

  window.refreshActiveGames = function(button) {
    if (button) button.classList.add('loading');
    fetch(appBase() + 'APIs/Lobbies/GetActiveGames.php?rootName=' + encodeURIComponent(rootName))
      .then(function(response) { return response.json(); })
      .then(function(response) {
        var games = response && Array.isArray(response.data) ? response.data : [];
        document.getElementById('active-game-count').textContent = String(games.length);
        var list = document.getElementById('active-games-list');
        if (!games.length) {
          list.innerHTML = '<div class="hellbreak-empty hellbreak-active-empty"><span aria-hidden="true">&#9876;</span><strong>No active games right now.</strong><small>Start one or refresh again in a moment.</small></div>';
          return;
        }
        list.innerHTML = games.map(function(game) {
          var name = String(game.gameName || '');
          return '<article class="hellbreak-active-game"><span><strong>Game ' + escapeHtml(name) + '</strong><small>Public fixture match</small></span>' +
            '<span class="hellbreak-spectate-actions"><button onclick="spectateGame(' + JSON.stringify(name).replace(/"/g, '&quot;') + ',1)">P1 view</button>' +
            '<button onclick="spectateGame(' + JSON.stringify(name).replace(/"/g, '&quot;') + ',2)">P2 view</button></span></article>';
        }).join('');
      })
      .catch(function() { showError('Active games could not be refreshed.'); })
      .finally(function() { if (button) button.classList.remove('loading'); });
  };

  window.spectateGame = function(gameName, perspective) {
    location.href = gameUrl('S', gameName, '', perspective);
  };

  document.addEventListener('DOMContentLoaded', function() {
    updateRejoin();
    updateSelectedDeck();
    document.getElementById('hellbreak-match-deck').addEventListener('change', updateSelectedDeck);
    refreshActiveGames();
    privateInviteCode = window.PrivateInviteUI ? window.PrivateInviteUI.init({
      rootName: rootName,
      createBtn: '#create-private-game-btn, #start-fixture-match-btn',
      joinBtnVisibleClass: 'is-visible',
      noticeText: 'Private Hellbreak invite detected. Join the host with the current fixture decks.'
    }) : '';
  });
})();
</script>
<?php include_once __DIR__ . '/Disclaimer.php'; ?>
</body></html>
