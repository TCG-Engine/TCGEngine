<?php
require_once __DIR__ . '/../../Render/AssetVersion.php';   // _VersionAsset() — ?v=<filemtime> cache busting
include_once __DIR__ . '/MenuBar.php';
include_once __DIR__ . '/../../../AccountFiles/AccountSessionAPI.php';
include_once __DIR__ . '/../../../Database/ConnectionManager.php';
include_once __DIR__ . '/Header.php';

$decks = [];
if (IsUserLoggedIn()) {
  $conn = GetLocalMySQLConnection();
  if ($conn) {
    $userID = (string)LoggedInUser();
    $stmt = $conn->prepare('SELECT assetIdentifier, assetName, keyIndicator1, keyIndicator2, assetFolder, lastUpdated FROM ownership WHERE assetType = 1 AND assetOwner = ? AND assetStatus = 1 ORDER BY (assetFolder = 1) DESC, assetIdentifier DESC');
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
        <h3><span class="hellbreak-heading-icon" aria-hidden="true">&#9673;</span> Live Signals <strong id="active-game-count">0</strong></h3>
        <button class="hellbreak-icon-button" type="button" onclick="refreshActiveGames(this)" aria-label="Refresh active games">
          <svg class="hellbreak-refresh-icon" viewBox="0 0 16 16" aria-hidden="true">
            <path fill="currentColor" d="M8 3a5 5 0 1 0 4.55 7.07.75.75 0 1 1 1.37.61A6.5 6.5 0 1 1 12.6 3.4V1.75a.75.75 0 0 1 1.5 0V5a.75.75 0 0 1-.75.75H10.1a.75.75 0 0 1 0-1.5h1.45A4.98 4.98 0 0 0 8 3Z"/>
          </svg>
        </button>
      </div>
      <button id="rejoin-last-game" class="hellbreak-rejoin" type="button" onclick="rejoinLastGame()" hidden>
        <span aria-hidden="true">&#8617;</span><span><strong>Return to last signal</strong><small id="rejoin-last-game-note"></small></span>
      </button>
      <div id="active-games-list" class="hellbreak-active-list">
        <div class="hellbreak-empty hellbreak-active-empty"><span class="hellbreak-radar-mark" aria-hidden="true"></span><strong>The shoreline is quiet.</strong><small>No active signals. Check again after the tide turns.</small></div>
      </div>
    </section>

    <section class="hellbreak-panel hellbreak-play-panel">
      <div class="hellbreak-workspace-heading">
        <h2>Choose Your Loadout</h2>
        <span>Pack a deck before you head down to the water</span>
      </div>

      <div class="hellbreak-library-heading">
        <h3><span class="hellbreak-heading-icon" aria-hidden="true">&#9638;</span> Deck Registry</h3>
        <?php if (IsUserLoggedIn()): ?><a class="hellbreak-create-deck" href="/TCGEngine/HellbreakDeck/CreateDeck.php"><span aria-hidden="true">+</span> Build Deck</a><?php endif; ?>
      </div>

      <div class="hellbreak-library-tabs" role="tablist" aria-label="Deck sources">
        <button type="button" class="is-active" role="tab" onclick="openHellbreakDeckPicker('saved')">My Decks</button>
        <button type="button" role="tab" onclick="openHellbreakDeckPicker('starter')">Starter Decks</button>
      </div>

      <section class="hellbreak-selected-section" aria-labelledby="hellbreak-selected-title">
        <div class="hellbreak-selected-heading">
          <span id="hellbreak-selected-title"><span aria-hidden="true">&#9670;</span> Current Loadout</span>
          <button type="button" class="hellbreak-change-deck" onclick="openHellbreakDeckPicker()">Change loadout</button>
        </div>
        <div class="hellbreak-selected-deck">
          <div class="hellbreak-deck-art" aria-hidden="true">
            <img id="hellbreak-selected-art-primary" src="/TCGEngine/HellbreakSim/crops/DOT_001_cropped.png" alt="">
            <img id="hellbreak-selected-art-secondary" src="/TCGEngine/HellbreakSim/crops/DOT_006_cropped.png" alt="">
          </div>
          <div class="hellbreak-selected-copy">
            <strong id="hellbreak-selected-name">GAMA Demo</strong>
            <span id="hellbreak-selected-details">Dracula vs. Jaws</span>
            <small><b id="hellbreak-selected-count">40 cards</b><b>Standard</b></small>
          </div>
          <select id="hellbreak-match-deck" class="hellbreak-native-deck-select" aria-label="Deck for this match">
            <option value="preset:HellbreakGamaDemo" data-kind="starter" data-name="GAMA Demo" data-details="Dracula vs. Jaws" data-count="40 cards" data-primary-art="DOT_001" data-secondary-art="DOT_006">GAMA Demo - Dracula vs. Jaws (40 cards)</option>
            <option value="preset:HellbreakFixture" data-kind="starter" data-name="Engine Fixture" data-details="Development decks" data-count="24 cards" data-primary-art="DOT_001" data-secondary-art="DOT_006">Engine fixture (24 cards)</option>
            <?php foreach ($decks as $deck):
              $choiceID = (string)$deck['assetIdentifier'];
              $choiceName = trim((string)($deck['assetName'] ?? '')) ?: 'Hellbreak Deck #' . $choiceID;
              $monsterID = trim((string)($deck['keyIndicator1'] ?? ''));
              $locationID = trim((string)($deck['keyIndicator2'] ?? ''));
              $isFavorite = intval($deck['assetFolder'] ?? 0) === 1;
            ?>
              <option value="hellbreakdeck:<?php echo htmlspecialchars($choiceID, ENT_QUOTES); ?>" data-kind="saved" data-deck-id="<?php echo htmlspecialchars($choiceID, ENT_QUOTES); ?>" data-name="<?php echo htmlspecialchars($choiceName, ENT_QUOTES); ?>" data-details="Saved Hellbreak deck" data-count="Custom list" data-favorite="<?php echo $isFavorite ? '1' : '0'; ?>" data-primary-art="<?php echo htmlspecialchars($monsterID, ENT_QUOTES); ?>" data-secondary-art="<?php echo htmlspecialchars($locationID, ENT_QUOTES); ?>"><?php echo htmlspecialchars($choiceName, ENT_QUOTES); ?></option>
            <?php endforeach; ?>
          </select>
          <span class="hellbreak-selected-check" aria-label="Selected">&#10003;</span>
          <div id="hellbreak-selected-actions" class="hellbreak-selected-actions" aria-label="Selected deck actions" hidden></div>
        </div>
      </section>

      <div class="hellbreak-ready-label"><span aria-hidden="true">&#9651;</span> Enter North Beach</div>

      <div class="hellbreak-game-actions">
        <button id="join-queue-btn" class="hellbreak-game-action primary coming-soon" type="button" disabled>
          <span class="hellbreak-action-icon" aria-hidden="true"><img src="/TCGEngine/SharedUI/Sites/HellbreakSim/assets/north-beach-red-buoy.png" alt=""></span><span><strong>Find a Match</strong><small>Coming Soon</small></span>
        </button>
        <button id="start-tutorial-btn" class="hellbreak-game-action" type="button" onclick="startTutorial()">
          <span class="hellbreak-action-icon" aria-hidden="true">?</span><span><strong>First Visit</strong><small>Shoreline orientation</small></span>
        </button>
        <button id="start-fixture-match-btn" class="hellbreak-game-action coming-soon" type="button" disabled>
          <span class="hellbreak-action-icon" aria-hidden="true"><img src="/TCGEngine/SharedUI/Sites/HellbreakSim/assets/north-beach-red-buoy.png" alt=""></span><span><strong>Solo Patrol</strong><small>Coming Soon</small></span>
        </button>
        <button id="create-private-game-btn" class="hellbreak-game-action coming-soon" type="button" disabled>
          <span class="hellbreak-action-icon" aria-hidden="true"><img src="/TCGEngine/SharedUI/Sites/HellbreakSim/assets/north-beach-red-buoy.png" alt=""></span><span><strong>Closed Session</strong><small>Coming Soon</small></span>
        </button>
        <button id="join-private-invite-btn" class="hellbreak-game-action invite" type="button" onclick="joinPrivateInvite()">
          <span class="hellbreak-action-icon" aria-hidden="true">&#10148;</span><span><strong>Join Private Invite</strong><small>Enter your friend's lobby</small></span>
        </button>
      </div>
      <div id="private-invite-notice" class="hellbreak-inline-notice" style="display:none"></div>
      <div id="queue-inline-error" class="hellbreak-inline-error" role="alert" hidden></div>
    </section>

    <section class="hellbreak-panel hellbreak-info-panel">
      <div class="hellbreak-info-tabs"><span class="is-active">Visitor Advisory</span></div>
      <div class="hellbreak-welcome">
        <span class="hellbreak-station-id">NB&ndash;09 / NORTH SHORE</span>
        <h2>Welcome to<br>North Beach.</h2>
        <p>A fan-made online simulator and deck builder for the Hellbreak TCG.</p>
      </div>
      <div class="hellbreak-tip">
        <span class="hellbreak-eyebrow"><i aria-hidden="true"></i> Current conditions</span>
        <p><strong>Visibility: poor.</strong> The GAMA Demo pits Dracula against Jaws and is ready to play immediately.</p>
      </div>
      <div class="hellbreak-quick-reference">
        <h3>Boardwalk Notices</h3>
        <div><kbd>?</kbd><span>Open shoreline orientation</span></div>
        <div><kbd>Esc</kbd><span>Stop scanning for a match</span></div>
      </div>
    </section>
  </div>
</main>

<div id="hellbreak-deck-picker" class="hellbreak-deck-picker" aria-hidden="true">
  <button type="button" class="hellbreak-deck-picker-backdrop" onclick="closeHellbreakDeckPicker()" aria-label="Close deck picker"></button>
  <section class="hellbreak-deck-picker-dialog" role="dialog" aria-modal="true" aria-labelledby="hellbreak-deck-picker-title">
    <button type="button" class="hellbreak-deck-picker-close" onclick="closeHellbreakDeckPicker()" aria-label="Close deck picker">&times;</button>
    <span class="hellbreak-eyebrow">Deck Registry</span>
    <h2 id="hellbreak-deck-picker-title">Choose a Loadout</h2>
    <p>Select what you are taking down to the shoreline.</p>

    <div class="hellbreak-picker-tabs" role="tablist" aria-label="Deck sources">
      <button type="button" role="tab" data-picker-tab="saved" onclick="switchHellbreakDeckPickerGroup('saved')">My Decks</button>
      <button type="button" role="tab" data-picker-tab="starter" onclick="switchHellbreakDeckPickerGroup('starter')">Starter Decks</button>
    </div>

    <section class="hellbreak-picker-group" data-picker-group="saved">
      <h3>My Decks</h3>
      <div class="hellbreak-picker-grid">
        <?php if ($decks): foreach ($decks as $deck):
          $pickerID = (string)$deck['assetIdentifier'];
          $pickerName = trim((string)($deck['assetName'] ?? '')) ?: 'Hellbreak Deck #' . $pickerID;
          $pickerMonster = trim((string)($deck['keyIndicator1'] ?? '')) ?: 'DOT_001';
          $pickerLocation = trim((string)($deck['keyIndicator2'] ?? '')) ?: 'DOT_006';
        ?>
          <button type="button" class="hellbreak-picker-option" data-deck-value="hellbreakdeck:<?php echo htmlspecialchars($pickerID, ENT_QUOTES); ?>" onclick="chooseHellbreakDeck(this.dataset.deckValue)">
            <span class="hellbreak-picker-art" aria-hidden="true">
              <img src="/TCGEngine/HellbreakSim/crops/<?php echo rawurlencode($pickerMonster); ?>_cropped.png" alt="">
              <img src="/TCGEngine/HellbreakSim/crops/<?php echo rawurlencode($pickerLocation); ?>_cropped.png" alt="">
            </span>
            <span><strong><?php echo htmlspecialchars($pickerName, ENT_QUOTES); ?></strong><small>Saved Hellbreak deck</small></span>
            <span class="hellbreak-picker-check" aria-hidden="true">&#10003;</span>
          </button>
        <?php endforeach; else: ?>
          <div class="hellbreak-picker-empty">No saved decks yet. Create one to add it here.</div>
        <?php endif; ?>
      </div>
    </section>

    <section class="hellbreak-picker-group" data-picker-group="starter">
      <h3>Starter Decks</h3>
      <div class="hellbreak-picker-grid">
        <button type="button" class="hellbreak-picker-option" data-deck-value="preset:HellbreakGamaDemo" onclick="chooseHellbreakDeck(this.dataset.deckValue)">
          <span class="hellbreak-picker-art" aria-hidden="true"><img src="/TCGEngine/HellbreakSim/crops/DOT_001_cropped.png" alt=""><img src="/TCGEngine/HellbreakSim/crops/DOT_006_cropped.png" alt=""></span>
          <span><strong>GAMA Demo</strong><small>Dracula vs. Jaws &middot; 40 cards</small></span><span class="hellbreak-picker-check" aria-hidden="true">&#10003;</span>
        </button>
        <button type="button" class="hellbreak-picker-option" data-deck-value="preset:HellbreakFixture" onclick="chooseHellbreakDeck(this.dataset.deckValue)">
          <span class="hellbreak-picker-art" aria-hidden="true"><img src="/TCGEngine/HellbreakSim/crops/DOT_001_cropped.png" alt=""><img src="/TCGEngine/HellbreakSim/crops/DOT_006_cropped.png" alt=""></span>
          <span><strong>Engine Fixture</strong><small>Development decks &middot; 24 cards</small></span><span class="hellbreak-picker-check" aria-hidden="true">&#10003;</span>
        </button>
      </div>
    </section>
  </section>
</div>

<div id="hellbreak-lobby-modal" class="hellbreak-modal" hidden>
  <div class="hellbreak-modal-backdrop" onclick="cancelWaitingLobby()"></div>
  <section class="hellbreak-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="hellbreak-modal-title">
    <button class="hellbreak-modal-close" type="button" onclick="cancelWaitingLobby()" aria-label="Cancel">&times;</button>
    <span class="hellbreak-modal-mark" aria-hidden="true">NB</span>
    <h2 id="hellbreak-modal-title">Scanning the shoreline</h2>
    <p id="hellbreak-modal-message">Your signal is live.</p>
    <div id="hellbreak-invite-wrap" class="hellbreak-invite-wrap" hidden>
      <input id="hellbreak-invite-link" readonly aria-label="Private invite link">
      <button type="button" onclick="copyInviteLink()">Copy</button>
    </div>
    <button class="hellbreak-button secondary" type="button" onclick="cancelWaitingLobby()">Cancel</button>
  </section>
</div>

<?php include __DIR__ . '/NorthBeachVignette.php'; ?>

<script src="<?php echo _VersionAsset('/TCGEngine/SharedUI/js/private-invite.js'); ?>"></script>
<script src="/TCGEngine/HellbreakDeck/HomeActions.js?v=20260817"></script>
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
  var selectedDeckKey = 'tcgengine:selectedDeck:' + rootName;
  var deckPickerPreviousFocus = null;

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

    var primaryArt = document.getElementById('hellbreak-selected-art-primary');
    var secondaryArt = document.getElementById('hellbreak-selected-art-secondary');
    var fallbackPrimary = 'DOT_001';
    var fallbackSecondary = 'DOT_006';
    primaryArt.src = '/TCGEngine/HellbreakSim/crops/' + encodeURIComponent(option.dataset.primaryArt || fallbackPrimary) + '_cropped.png';
    secondaryArt.src = '/TCGEngine/HellbreakSim/crops/' + encodeURIComponent(option.dataset.secondaryArt || fallbackSecondary) + '_cropped.png';
    primaryArt.onerror = function() { this.onerror = null; this.src = '/TCGEngine/HellbreakSim/crops/' + fallbackPrimary + '_cropped.png'; };
    secondaryArt.onerror = function() { this.onerror = null; this.src = '/TCGEngine/HellbreakSim/crops/' + fallbackSecondary + '_cropped.png'; };

    var actionBar = document.getElementById('hellbreak-selected-actions');
    actionBar.replaceChildren();
    actionBar.hidden = option.dataset.kind !== 'saved';
    if (!actionBar.hidden) renderSelectedDeckActions(actionBar, option);

    document.querySelectorAll('.hellbreak-picker-option').forEach(function(button) {
      button.classList.toggle('is-selected', button.dataset.deckValue === option.value);
    });
    document.querySelectorAll('.hellbreak-library-tabs button').forEach(function(button, index) {
      button.classList.toggle('is-active', option.dataset.kind === (index === 0 ? 'saved' : 'starter'));
    });
    try { localStorage.setItem(selectedDeckKey, option.value); } catch (error) {}
  }

  function actionIcon(control, icon, label) {
    control.title = label;
    control.setAttribute('aria-label', label);
    control.innerHTML = '<img src="/TCGEngine/Assets/Images/Zendo/UIIconsRaster/' + icon + '.webp?v=4" alt="" aria-hidden="true">';
    return control;
  }

  function runDeckAction(url, successMessage) {
    fetch(url, { credentials: 'same-origin' })
      .then(function(response) { if (!response.ok) throw new Error('Request failed'); return response.json(); })
      .then(function(payload) {
        if (payload && payload.error) throw new Error(payload.error);
        if (successMessage && window.Toast) Toast(successMessage, { type: 'success' });
        window.location.reload();
      })
      .catch(function(error) { if (window.Toast) Toast(error.message || 'That deck action failed.', { type: 'danger' }); });
  }

  function copyDeckLink(deckID) {
    var link = location.origin + '/TCGEngine/NextTurn.php?gameName=' + encodeURIComponent(deckID) + '&playerID=1&folderPath=HellbreakDeck';
    var copy = navigator.clipboard && navigator.clipboard.writeText
      ? navigator.clipboard.writeText(link)
      : new Promise(function(resolve) {
          var input = document.createElement('input');
          input.value = link;
          document.body.appendChild(input);
          input.select();
          document.execCommand('copy');
          input.remove();
          resolve();
        });
    copy.then(function() { if (window.Toast) Toast('Deck link copied!', { type: 'success' }); })
      .catch(function() { if (window.Toast) Toast('Could not copy the deck link.', { type: 'danger' }); });
  }

  function renderSelectedDeckActions(actionBar, option) {
    var deckID = option.dataset.deckId;
    var favorite = option.dataset.favorite === '1';
    var edit = actionIcon(document.createElement('a'), 'edit', 'Edit deck');
    edit.href = '/TCGEngine/NextTurn.php?gameName=' + encodeURIComponent(deckID) + '&playerID=1&folderPath=HellbreakDeck';
    actionBar.appendChild(edit);

    var favoriteButton = actionIcon(document.createElement('button'), 'star', favorite ? 'Remove from favorites' : 'Add to favorites');
    favoriteButton.type = 'button';
    favoriteButton.onclick = function() {
      runDeckAction('/TCGEngine/AccountFiles/MoveAsset.php?assetID=' + encodeURIComponent(deckID) + '&assetType=1&folderID=' + (favorite ? '0' : '1'), favorite ? 'Removed from favorites.' : 'Added to favorites.');
    };
    actionBar.appendChild(favoriteButton);

    var linkButton = actionIcon(document.createElement('button'), 'link', 'Copy deck link');
    linkButton.type = 'button';
    linkButton.onclick = function() { copyDeckLink(deckID); };
    actionBar.appendChild(linkButton);

    var imageButton = actionIcon(document.createElement('button'), 'image', 'Generate deck image');
    imageButton.type = 'button';
    imageButton.onclick = function() { HellbreakDeckHome.generateImage(deckID); };
    actionBar.appendChild(imageButton);

    var deleteButton = actionIcon(document.createElement('button'), 'trash', 'Delete deck');
    deleteButton.type = 'button';
    deleteButton.className = 'danger';
    deleteButton.onclick = function() {
      StyledConfirm('Are you sure you want to delete this deck?', { title: 'Delete deck', danger: true, confirmLabel: 'Delete' }).then(function(ok) {
        if (!ok) return;
        runDeckAction('/TCGEngine/AccountFiles/DeleteAsset.php?assetID=' + encodeURIComponent(deckID) + '&assetType=1');
      });
    };
    actionBar.appendChild(deleteButton);
  }

  window.switchHellbreakDeckPickerGroup = function(group) {
    var modal = document.getElementById('hellbreak-deck-picker');
    if (!modal) return;
    modal.querySelectorAll('.hellbreak-picker-group').forEach(function(section) {
      section.hidden = section.dataset.pickerGroup !== group;
    });
    modal.querySelectorAll('[data-picker-tab]').forEach(function(tab) {
      var active = tab.dataset.pickerTab === group;
      tab.classList.toggle('is-active', active);
      tab.setAttribute('aria-selected', active ? 'true' : 'false');
      tab.tabIndex = active ? 0 : -1;
    });
  };

  window.openHellbreakDeckPicker = function(group) {
    var modal = document.getElementById('hellbreak-deck-picker');
    var select = document.getElementById('hellbreak-match-deck');
    if (!modal) return;
    deckPickerPreviousFocus = document.activeElement;
    if (!group && select && select.selectedIndex >= 0) group = select.options[select.selectedIndex].dataset.kind;
    switchHellbreakDeckPickerGroup(group === 'starter' ? 'starter' : 'saved');
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('hellbreak-picker-open');
    modal.querySelector('.hellbreak-deck-picker-close').focus();
  };

  window.closeHellbreakDeckPicker = function() {
    var modal = document.getElementById('hellbreak-deck-picker');
    if (!modal || !modal.classList.contains('is-open')) return;
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('hellbreak-picker-open');
    if (deckPickerPreviousFocus && typeof deckPickerPreviousFocus.focus === 'function') deckPickerPreviousFocus.focus();
  };

  window.chooseHellbreakDeck = function(value) {
    var select = document.getElementById('hellbreak-match-deck');
    if (!select || !Array.from(select.options).some(function(option) { return option.value === value; })) return;
    select.value = value;
    updateSelectedDeck();
    closeHellbreakDeckPicker();
  };

  function showWaiting(message, inviteLink) {
    document.getElementById('hellbreak-modal-message').textContent = message || 'Scanning for another signal...';
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
      showWaiting(options.private ? 'Send this private frequency to your opponent.' : 'Scanning the shoreline for another player...', inviteLink);
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
          list.innerHTML = '<div class="hellbreak-empty hellbreak-active-empty"><span class="hellbreak-radar-mark" aria-hidden="true"></span><strong>The shoreline is quiet.</strong><small>No active signals. Check again after the tide turns.</small></div>';
          return;
        }
        list.innerHTML = games.map(function(game) {
          var name = String(game.gameName || '');
          return '<article class="hellbreak-active-game"><span><strong>Signal ' + escapeHtml(name) + '</strong><small>Public Hellbreak match</small></span>' +
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
    var deckSelect = document.getElementById('hellbreak-match-deck');
    var rememberedDeck = '';
    try { rememberedDeck = localStorage.getItem(selectedDeckKey) || ''; } catch (error) {}
    var rememberedOption = Array.from(deckSelect.options).find(function(option) { return option.value === rememberedDeck; });
    var firstSavedOption = Array.from(deckSelect.options).find(function(option) { return option.dataset.kind === 'saved'; });
    if (rememberedOption) deckSelect.value = rememberedOption.value;
    else if (firstSavedOption) deckSelect.value = firstSavedOption.value;
    updateSelectedDeck();
    deckSelect.addEventListener('change', updateSelectedDeck);
    document.addEventListener('keydown', function(event) { if (event.key === 'Escape') closeHellbreakDeckPicker(); });
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
