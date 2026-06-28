<?php
// Use __DIR__-relative includes (matching the SWUSim/SWUDeck pilot): this page is reached via the
// SharedUI/MainMenu.php pointer (which include()s it), so the cwd is SharedUI/, not this dir.
// Bare './'/'../../../' paths resolved against the wrong cwd → missing-file warnings AND silently
// pulled the ROOT SharedUI/MenuBar.php + Header.php (wrong chrome) instead of the GrandArchiveSim ones.
include_once __DIR__ . '/MenuBar.php';
include_once __DIR__ . '/../../../AccountFiles/AccountSessionAPI.php';
include_once __DIR__ . '/../../../Database/ConnectionManager.php';
include_once __DIR__ . '/../../../GrandArchiveSim/GeneratedCode/GeneratedCardDictionaries.php';

include_once __DIR__ . '/Header.php';

?>
<div class="row-wrapper" style="display: flex; flex-direction: row; flex-grow: 1;">
  <!-- Create New Game Section -->
  <div class="card ga-glass-card" style="flex-grow: 1; margin: 10px; padding: 20px; color: white; border-radius: 12px; position: relative;">
    <button style="position: absolute; top: 10px; right: 10px; background: none; border: none; cursor: pointer;" onclick="refreshOpenGames()">
      <img src='../../../Assets/Icons/refresh.svg' width='16' height='16' alt='Refresh' style='filter: invert(100%);' />
    </button>
    <h2>Active Games (<span id="active-game-count">0</span>)</h2>
    <div id="active-games-list" class="active-games-list" style="margin-bottom: 18px;"></div>
    <h2>Create a New Game</h2>
    <div>
      <!--
      <label for="preconstructed-deck" style="display: block; margin-bottom: 8px; font-weight: 500;">Choose Your Deck:</label>
      <select id="preconstructed-deck" name="preconstructed_deck" required style="
        width: 100%;
        padding: 10px 15px;
        background-color: rgba(40, 40, 40, 0.95);
        color: white;
        border: 2px solid rgba(100, 100, 100, 0.5);
        border-radius: 8px;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s ease;
        outline: none;
      " onmouseover="this.style.borderColor='rgba(52, 152, 219, 0.8)'; this.style.backgroundColor='rgba(50, 50, 50, 0.95)';" onmouseout="this.style.borderColor='rgba(100, 100, 100, 0.5)'; this.style.backgroundColor='rgba(40, 40, 40, 0.95)';" onfocus="this.style.borderColor='#3498db'; this.style.boxShadow='0 0 8px rgba(52, 152, 219, 0.4)';" onblur="this.style.borderColor='rgba(100, 100, 100, 0.5)'; this.style.boxShadow='none';">
        <option value="" disabled selected style="color: #999;">Select a preconstructed deck...</option>
        <option value="Refractory">Refractory</option>
        <option value="Gloaming">Gloaming</option>
        <option value="Shardsworn">Shardsworn</option>
        <option value="Delguon">Delguon</option>
      </select>
      <div style="display: flex; align-items: center; margin: 12px 0; color: #888;">
        <hr style="flex-grow: 1; border-color: #555; border-top-width: 1px;"><span style="margin: 0 10px; font-size: 12px;">OR</span><hr style="flex-grow: 1; border-color: #555; border-top-width: 1px;">
      </div>
-->
      <div style="display: flex; gap: 0; margin-bottom: 10px; border-bottom: 2px solid rgba(100,100,100,0.4);">
        <button id="tab-link" onclick="switchDeckTab('link')" style="flex: 1; padding: 8px; background: rgba(52,152,219,0.25); color: white; border: none; border-bottom: 2px solid #3498db; cursor: pointer; font-size: 13px; font-weight: 600;">Deck Link</button>
        <button id="tab-text" onclick="switchDeckTab('text')" style="flex: 1; padding: 8px; background: rgba(40,40,40,0.7); color: #aaa; border: none; border-bottom: 2px solid transparent; cursor: pointer; font-size: 13px;">Free Text</button>
      </div>
      <div id="deck-input-link">
        <label for="deck-link" style="display: block; margin-bottom: 8px; font-weight: 500;">Paste a deck link:</label>
        <input type="text" id="deck-link" name="deck_link" placeholder="https://shoutatyourdecks.com/decks/..." style="width: 100%; padding: 10px 15px; background-color: rgba(40, 40, 40, 0.95); color: white; border: 2px solid rgba(100, 100, 100, 0.5); border-radius: 8px; font-size: 14px; outline: none; box-sizing: border-box;">
        <div style="margin-top: 8px; color: #b9b9b9; font-size: 12px; line-height: 1.35;">
          Supported deck links: DungeonGUI, Shout At Your Decks, sleeved.gg, TCGArchitect
        </div>
      </div>
      <div id="deck-input-text" style="display: none;">
        <label for="deck-text" style="display: block; margin-bottom: 8px; font-weight: 500;">Paste deck list (e.g. from fractalofin.site):</label>
        <textarea id="deck-text" name="deck_text" rows="12" placeholder="# Material Deck&#10;1 Lorraine, Wandering Warrior&#10;&#10;# Main Deck&#10;4 Fireball&#10;..." style="width: 100%; padding: 10px 15px; background-color: rgba(40, 40, 40, 0.95); color: white; border: 2px solid rgba(100, 100, 100, 0.5); border-radius: 8px; font-size: 13px; font-family: monospace; outline: none; box-sizing: border-box; resize: vertical;"></textarea>
      </div>
      <!--
      <label for="game-name">Game Name:</label>
      <input type="text" id="game-name" name="game_name" required>
      <br>
      <label for="game-type">Game Type:</label>
      <select id="game-type" name="game_type">
      <option value="casual">Casual</option>
      <option value="ranked">Ranked</option>
      </select>
    -->
      <br>
      <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <button onclick="joinQueue()">Join Queue</button>
        <button onclick="createPrivateGame()" style="background-color: #2f6f9f;">Create Private Game</button>
        <button onclick="startGoldfishGame()" style="background-color: #9f7a2f;">Start Goldfish</button>
        <button id="rejoin-last-game-btn" onclick="rejoinLastGame()" style="display: none; background-color: #5b4aa3;">Rejoin Last Game</button>
        <button id="join-private-invite-btn" onclick="joinPrivateInvite()" style="display: none; background-color: #2d8a57;">Join Private Invite</button>
      </div>
      <div id="queue-inline-error" style="display: none; margin-top: 10px; color: #ff6b6b; font-size: 13px; line-height: 1.35;"></div>
      <div id="private-invite-notice" style="display: none; margin-top: 10px; color: #9ed9b4; font-size: 13px;"></div>
      <div id="rejoin-last-game-note" style="display: none; margin-top: 10px; color: #b9b9b9; font-size: 13px;"></div>
    </div>
  </div>
  
  <!-- Saved Replays Section -->
  <div class="card ga-glass-card ga-replay-card" style="flex-grow: 1; margin: 10px; padding: 20px; color: white; border-radius: 12px; display: flex; flex-direction: column; gap: 12px;">
    <h2 style="margin: 0;">Your Replays</h2>
    <p style="margin: 0; color: #ccc; font-size: 13px; line-height: 1.4;">Saved in this browser.</p>
    <div id="match-replay-menu-list" class="ga-replay-list"></div>
  </div>

  <!-- Tips & Info Section -->
  <div class="card ga-glass-card" style="flex-grow: 1; margin: 10px; padding: 20px; color: white; border-radius: 12px; display: flex; flex-direction: column; gap: 16px;">
    <h2 style="margin: 0 0 4px 0;">Welcome to Clarent!</h2>
    <p class="login-message" style="margin: 0; color: #ccc; font-size: 14px;">Clarent is a fan-made online simulator for the Grand Archive TCG.</p>

    <hr style="border: none; border-top: 1px solid rgba(255,255,255,0.1); margin: 0;">

    <!-- Did you know? -->
    <div id="did-you-know-box" style="
      background: linear-gradient(135deg, rgba(52,152,219,0.15) 0%, rgba(30,30,50,0.4) 100%);
      border: 1px solid rgba(52,152,219,0.35);
      border-radius: 8px;
      padding: 14px 16px;
      position: relative;
    ">
      <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
        <span style="font-size: 18px;">💡</span>
        <span style="font-size: 12px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #3498db;">Did you know?</span>
      </div>
      <p id="did-you-know-text" style="margin: 0; font-size: 14px; color: #e8e8e8; line-height: 1.55;"></p>
      <button onclick="cycleDidYouKnow()" title="Next tip" style="
        position: absolute; top: 10px; right: 10px;
        background: none; border: none; cursor: pointer;
        color: #3498db; font-size: 16px; padding: 2px 6px; border-radius: 4px;
        transition: background 0.2s;
      " onmouseover="this.style.background='rgba(52,152,219,0.15)'" onmouseout="this.style.background='none'">→</button>
    </div>

    <!-- Quick-reference hotkeys -->
    <div>
      <div style="font-size: 12px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #888; margin-bottom: 8px;">Quick Reference</div>
      <div style="display: flex; flex-direction: column; gap: 6px;" id="hotkey-list"></div>
    </div>
  </div>
</div>

<div id="ga-settings-modal" class="ga-settings-modal" aria-hidden="true">
  <div class="ga-settings-modal__overlay" data-close-settings-modal="true"></div>
  <div class="ga-settings-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="ga-settings-modal-title">
    <div class="ga-settings-modal__header">
      <h3 id="ga-settings-modal-title">Menu Settings</h3>
      <button type="button" class="ga-settings-modal__close" id="ga-close-settings-btn" aria-label="Close settings modal">x</button>
    </div>
    <div class="ga-settings-modal__body">
      <label for="ga-enable-menu-sounds" class="ga-settings-row">
        <input type="checkbox" id="ga-enable-menu-sounds">
        <span>Enable menu sounds</span>
      </label>
      <label for="ga-disable-keyword-indicators" class="ga-settings-row">
        <input type="checkbox" id="ga-disable-keyword-indicators">
        <span>Disable keyword indicators (stealth, spellshroud, true sight, vigor)</span>
      </label>
      <label for="ga-board-background-theme" class="ga-settings-row ga-settings-row--split">
        <span>Board background</span>
        <select id="ga-board-background-theme">
          <option value="dawn">Dawn of Ashes</option>
          <option value="classic">Classic Blue</option>
        </select>
      </label>
    </div>
  </div>
</div>

<audio id="ga-player-joined-sound" src="../../../Assets/playerJoinedSound.mp3" preload="auto"></audio>
<script src="/TCGEngine/Core/MatchReplayClient.js"></script>

<style>
  .row-wrapper > .card {
    flex: 1 1 0 !important;
    min-width: 0;
  }
  .ga-glass-card {
    background: linear-gradient(165deg, rgba(9, 23, 44, 0.82) 0%, rgba(6, 17, 34, 0.74) 100%);
    border: 1px solid rgba(214, 184, 109, 0.24);
    box-shadow: 0 14px 36px rgba(2, 8, 20, 0.45), inset 0 1px 0 rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(10px) saturate(115%);
    -webkit-backdrop-filter: blur(10px) saturate(115%);
  }
  .ga-replay-card {
    flex: 0 1 420px !important;
  }
  .ga-replay-list {
    min-height: 72px;
    max-height: 360px;
    overflow-y: auto;
    padding-right: 4px;
  }
  .hotkey-row { display: flex; align-items: center; gap: 10px; font-size: 13px; color: #ccc; }
  .hotkey-badge {
    display: inline-block; min-width: 28px; text-align: center;
    padding: 2px 7px; border-radius: 5px;
    background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.2);
    font-family: monospace; font-size: 13px; font-weight: 700; color: #fff;
    flex-shrink: 0;
  }
  #did-you-know-box {
    transition: opacity 0.25s;
    min-height: 140px;
    width: 100%;
    max-width: 100%;
    min-width: 0;
    box-sizing: border-box;
  }
  #did-you-know-text {
    display: block;
    width: 100%;
    max-width: 100%;
    min-height: 66px;
    max-height: 66px;
    overflow-y: auto;
    overflow-x: hidden;
    padding-right: 4px;
    white-space: normal !important;
    overflow-wrap: anywhere !important;
    word-break: break-word !important;
  }
  .ga-settings-modal {
    position: fixed;
    inset: 0;
    z-index: 3000;
    display: none;
    align-items: center;
    justify-content: center;
  }
  .ga-settings-modal.is-open {
    display: flex;
  }
  .ga-settings-modal__overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.66);
  }
  .ga-settings-modal__dialog {
    position: relative;
    width: min(560px, 92vw);
    background: rgba(14, 24, 39, 0.96);
    border: 1px solid rgba(214, 184, 109, 0.35);
    border-radius: 10px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.45);
    color: #fff;
    padding: 18px;
  }
  .ga-settings-modal__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
  }
  .ga-settings-modal__header h3 {
    margin: 0;
    font-size: 18px;
    color: #f4e2a4;
  }
  .ga-settings-modal__close {
    border: 0;
    background: rgba(255, 255, 255, 0.08);
    color: #fff;
    border-radius: 6px;
    width: 28px;
    height: 28px;
    cursor: pointer;
    font-size: 14px;
    line-height: 1;
  }
  .ga-settings-modal__body {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }
  .active-games-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    max-height: 240px;
    overflow-y: auto;
    padding-right: 4px;
  }
  .active-game-card {
    border: 1px solid rgba(214, 184, 109, 0.22);
    border-radius: 10px;
    background: rgba(9, 20, 36, 0.75);
    padding: 10px 12px;
  }
  .active-game-meta {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: center;
    margin-bottom: 8px;
    font-size: 13px;
    color: #d9d9d9;
  }
  .active-game-badge {
    display: inline-flex;
    align-items: center;
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
  }
  .active-game-badge.private {
    background: rgba(201, 168, 76, 0.18);
    color: #f4e2a4;
  }
  .active-game-badge.public {
    background: rgba(68, 170, 130, 0.18);
    color: #9ed9b4;
  }
  .active-game-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
  }
  .active-game-empty {
    color: #b9b9b9;
    font-size: 13px;
    line-height: 1.4;
    padding: 8px 0 2px;
  }
  .ga-settings-row {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #ddd;
    font-size: 13px;
  }
  .ga-settings-row--split {
    justify-content: space-between;
  }
  #ga-board-background-theme {
    background: rgba(20, 20, 28, 0.9);
    color: #fff;
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 6px;
    padding: 4px 8px;
    font-size: 12px;
  }
  @media (max-width: 1180px) {
    .row-wrapper {
      flex-direction: column !important;
    }
    .ga-replay-card {
      flex-basis: auto !important;
    }
  }
</style>

<script>
  var _didYouKnowTips = [
    { key: 'u', label: 'Undo your most recent action' },
    { key: 'Space', label: 'Pass an optional decision when available' },
    { text: 'Hover a card on the field to see its full text' },
    { text: 'You can paste a deck link directly from DungeonGUI, Shout At Your Decks, sleeved.gg, or TCGArchitect.' },
    { text: 'Private games generate a shareable invite link — send it to your opponent and they can join instantly.' },
    { text: 'The queue matches you with the first available opponent. No need to refresh — it polls automatically.' },
    { key: 'Esc', label: 'Cancel matchmaking while waiting for an opponent' },
  ];
  var _dykIndex = 0;

  var _hotkeyList = [
    { key: 'u',   label: 'Undo most recent action' },
    { key: 'Space', label: 'Pass optional decision (when available)' },
    { key: 'Esc', label: 'Cancel matchmaking' },
  ];

  function renderDidYouKnow() {
    var tip = _didYouKnowTips[_dykIndex];
    var el = document.getElementById('did-you-know-text');
    if (!el) return;
    var box = document.getElementById('did-you-know-box');
    box.style.opacity = '0';
    setTimeout(function() {
      if (tip.key) {
        el.innerHTML = 'Press <span class="hotkey-badge">' + tip.key + '</span> to <strong>' + tip.label + '</strong>.';
      } else {
        el.textContent = tip.text;
      }
      box.style.opacity = '1';
    }, 200);
  }

  function cycleDidYouKnow() {
    _dykIndex = (_dykIndex + 1) % _didYouKnowTips.length;
    renderDidYouKnow();
  }

  function renderHotkeyList() {
    var container = document.getElementById('hotkey-list');
    if (!container) return;
    var html = '';
    _hotkeyList.forEach(function(h) {
      html += '<div class="hotkey-row"><span class="hotkey-badge">' + h.key + '</span><span>' + h.label + '</span></div>';
    });
    container.innerHTML = html;
  }

  function openGASettingsModal() {
    var modal = document.getElementById('ga-settings-modal');
    if (!modal) return;
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
  }

  function closeGASettingsModal() {
    var modal = document.getElementById('ga-settings-modal');
    if (!modal) return;
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
  }

  document.addEventListener('DOMContentLoaded', function() {
    if (window.TCGSettings) {
      window.TCGSettings.registerSchema('GrandArchiveSim', {
        EnableMenuSounds: {
          type: 'boolean',
          defaultValue: true
        },
        DisableKeywordIndicators: {
          type: 'boolean',
          defaultValue: false
        },
        BoardBackgroundTheme: {
          type: 'string',
          defaultValue: 'dawn'
        }
      });
    }

    renderDidYouKnow();
    renderHotkeyList();
    var menuSoundsToggle = document.getElementById('ga-enable-menu-sounds');
    if (menuSoundsToggle && window.TCGSettings) {
      menuSoundsToggle.checked = !!window.TCGSettings.get('EnableMenuSounds', { rootName: 'GrandArchiveSim', type: 'boolean', defaultValue: true });
      menuSoundsToggle.addEventListener('change', function() {
        window.TCGSettings.set('EnableMenuSounds', !!menuSoundsToggle.checked, { rootName: 'GrandArchiveSim', type: 'boolean' });
      });
    }
    var keywordToggle = document.getElementById('ga-disable-keyword-indicators');
    if (keywordToggle && window.TCGSettings) {
      keywordToggle.checked = !!window.TCGSettings.get('DisableKeywordIndicators', { rootName: 'GrandArchiveSim', type: 'boolean', defaultValue: false });
      keywordToggle.addEventListener('change', function() {
        window.TCGSettings.set('DisableKeywordIndicators', !!keywordToggle.checked, { rootName: 'GrandArchiveSim', type: 'boolean' });
      });
    }
    var boardThemeSelect = document.getElementById('ga-board-background-theme');
    if (boardThemeSelect && window.TCGSettings) {
      var savedTheme = window.TCGSettings.get('BoardBackgroundTheme', { rootName: 'GrandArchiveSim', type: 'string', defaultValue: 'dawn' });
      boardThemeSelect.value = (savedTheme === 'classic') ? 'classic' : 'dawn';
      boardThemeSelect.addEventListener('change', function() {
        var value = boardThemeSelect.value === 'classic' ? 'classic' : 'dawn';
        window.TCGSettings.set('BoardBackgroundTheme', value, { rootName: 'GrandArchiveSim', type: 'string' });
      });
    }

    window.openGrandArchiveSettingsModal = openGASettingsModal;
    var openSettingsBtn = document.getElementById('ga-open-settings-btn');
    if (openSettingsBtn) {
      openSettingsBtn.addEventListener('click', openGASettingsModal);
    }

    var closeSettingsBtn = document.getElementById('ga-close-settings-btn');
    if (closeSettingsBtn) {
      closeSettingsBtn.addEventListener('click', closeGASettingsModal);
    }

    var settingsModal = document.getElementById('ga-settings-modal');
    if (settingsModal) {
      settingsModal.addEventListener('click', function(event) {
        var target = event.target;
        if (target && target.getAttribute('data-close-settings-modal') === 'true') {
          closeGASettingsModal();
        }
      });
    }

    document.addEventListener('keydown', function(event) {
      if (event.key !== 'Escape') return;
      var modal = document.getElementById('ga-settings-modal');
      if (!modal || !modal.classList.contains('is-open')) return;
      closeGASettingsModal();
    });
    // Rotate tips every 8 seconds
    setInterval(cycleDidYouKnow, 8000);
  });
</script>

<script>

  var rootName = "GrandArchiveSim";
  var _lobby_id = "";
  var _privateInviteCode = "";
  var _waitingEscHandler = null;
  var _autoLaunchGoldfish = false;
  var _lastSimGameStorageKey = 'tcgengine:lastSimGame:' + rootName;

      function getLastSimGame() {
        try {
          var raw = localStorage.getItem(_lastSimGameStorageKey);
          if (!raw) return null;
          return JSON.parse(raw);
        } catch (e) {
          return null;
        }
      }

      function isValidLastSimGameRecord(record) {
        return !!record &&
          record.rootName === rootName &&
          (record.playerID === '1' || record.playerID === '2') &&
          typeof record.gameName === 'string' && record.gameName !== '' &&
          typeof record.authKey === 'string' && record.authKey !== '';
      }

      function updateRejoinLastGameUI() {
        var button = document.getElementById('rejoin-last-game-btn');
        var note = document.getElementById('rejoin-last-game-note');
        if (!button || !note) return;
        var record = getLastSimGame();
        if (!isValidLastSimGameRecord(record)) {
          button.style.display = 'none';
          note.style.display = 'none';
          note.textContent = '';
          return;
        }
        button.style.display = '';
        note.style.display = '';
        note.textContent = 'Resume game ' + record.gameName + ' as P' + record.playerID + '.';
      }

      function persistLastSimGame(gameName, playerID, authKey) {
        if (!gameName || !authKey) return;
        var normalizedPlayerID = String(playerID);
        if (normalizedPlayerID !== '1' && normalizedPlayerID !== '2') return;

        try {
          localStorage.setItem(_lastSimGameStorageKey, JSON.stringify({
            rootName: rootName,
            gameName: String(gameName),
            playerID: normalizedPlayerID,
            authKey: String(authKey),
            updatedAt: Date.now()
          }));
        } catch (e) {}

        document.cookie = 'lastAuthKey=' + encodeURIComponent(authKey) + '; max-age=' + (30 * 24 * 60 * 60) + '; path=/; SameSite=Lax';
        updateRejoinLastGameUI();
      }

      function buildGameUrl(playerID, gameName, authKey, fromMatch) {
        var url = new URL('../../../NextTurn.php', window.location.href);
        url.searchParams.set('playerID', String(playerID));
        url.searchParams.set('gameName', String(gameName));
        url.searchParams.set('folderPath', rootName);
        if (authKey) url.searchParams.set('authKey', String(authKey));
        if (fromMatch) url.searchParams.set('fromMatch', '1');
        else url.searchParams.delete('fromMatch');
        return url.toString();
      }

      function navigateToGame(playerID, gameName, authKey, fromMatch) {
        persistLastSimGame(gameName, playerID, authKey);
        window.location.href = buildGameUrl(playerID, gameName, authKey, fromMatch);
      }

      function rejoinLastGame() {
        var record = getLastSimGame();
        if (!isValidLastSimGameRecord(record)) {
          updateRejoinLastGameUI();
          return;
        }
        window.location.href = buildGameUrl(record.playerID, record.gameName, record.authKey, false);
      }

      function shouldPlayMenuSounds() {
        if (!window.TCGSettings || typeof window.TCGSettings.get !== 'function') return true;
        return !!window.TCGSettings.get('EnableMenuSounds', {
          rootName: 'GrandArchiveSim',
          type: 'boolean',
          defaultValue: true
        });
      }

      function playPlayerJoinedSound() {
        if (!shouldPlayMenuSounds()) return;
        var audioEl = document.getElementById('ga-player-joined-sound');
        if (!audioEl) return;
        try {
          audioEl.currentTime = 0;
        } catch (e) {}
        var playPromise = audioEl.play();
        if (playPromise && typeof playPromise.catch === 'function') {
          playPromise.catch(function(err) {
            console.warn('Unable to play player joined sound:', err);
          });
        }
      }

      function switchDeckTab(tab) {
        var isLink = tab === 'link';
        document.getElementById('deck-input-link').style.display = isLink ? '' : 'none';
        document.getElementById('deck-input-text').style.display = isLink ? 'none' : '';
        document.getElementById('tab-link').style.background = isLink ? 'rgba(52,152,219,0.25)' : 'rgba(40,40,40,0.7)';
        document.getElementById('tab-link').style.color = isLink ? 'white' : '#aaa';
        document.getElementById('tab-link').style.borderBottom = isLink ? '2px solid #3498db' : '2px solid transparent';
        document.getElementById('tab-text').style.background = isLink ? 'rgba(40,40,40,0.7)' : 'rgba(52,152,219,0.25)';
        document.getElementById('tab-text').style.color = isLink ? '#aaa' : 'white';
        document.getElementById('tab-text').style.borderBottom = isLink ? '2px solid transparent' : '2px solid #3498db';
        try { localStorage.setItem('ga_deck_tab', tab); } catch(e) {}
      }

      (function() {
        var saved = '';
        try { saved = localStorage.getItem('ga_deck_tab') || ''; } catch(e) {}
        if (saved === 'text') switchDeckTab('text');
      })();

      function initializePrivateInviteFromUrl() {
        try {
          var params = new URLSearchParams(window.location.search || '');
          _privateInviteCode = (params.get('privateInvite') || params.get('invite') || '').trim();
          if (!_privateInviteCode) return;

          var joinBtn = document.getElementById('join-private-invite-btn');
          var notice = document.getElementById('private-invite-notice');
          if (joinBtn) joinBtn.style.display = '';
          if (notice) {
            notice.style.display = '';
            notice.textContent = 'Private invite detected. Choose your deck, then click Join Private Invite.';
          }
        } catch (e) {
          console.error('Failed to parse private invite URL:', e);
        }
      }

      function initializeGoldfishLinkFromUrl() {
        try {
          var params = new URLSearchParams(window.location.search || '');
          var deckLinkParam = (params.get('deckLink') || params.get('deck') || '').trim();
          var deckTextParam = (params.get('deckText') || params.get('list') || '').trim();
          var goldfishParam = (params.get('goldfish') || '').trim().toLowerCase();
          var shouldAutostart = goldfishParam === '1' || goldfishParam === 'true' || goldfishParam === 'yes';

          if (deckLinkParam) {
            var deckLinkEl = document.getElementById('deck-link');
            if (deckLinkEl && !deckLinkEl.value.trim()) {
              deckLinkEl.value = deckLinkParam;
            }
            switchDeckTab('link');
          } else if (deckTextParam) {
            var deckTextEl = document.getElementById('deck-text');
            if (deckTextEl && !deckTextEl.value.trim()) {
              deckTextEl.value = deckTextParam;
            }
            switchDeckTab('text');
          }

          if (shouldAutostart && (deckLinkParam || deckTextParam)) {
            _autoLaunchGoldfish = true;
          }
        } catch (e) {
          console.error('Failed to parse goldfish launch URL:', e);
        }
      }

      function getDeckSubmission() {
        var preconstructedDeckDropdown = document.getElementById('preconstructed-deck');
        var preconstructedDeck = preconstructedDeckDropdown ? preconstructedDeckDropdown.value : '';
        var deckLinkEl = document.getElementById('deck-link');
        var deckTextEl = document.getElementById('deck-text');
        var deckLink = '';
        if (deckTextEl && deckTextEl.closest('#deck-input-text') && document.getElementById('deck-input-text').style.display !== 'none') {
          deckLink = deckTextEl.value.trim();
        } else if (deckLinkEl) {
          deckLink = deckLinkEl.value.trim();
        }
        if (!deckLink && !preconstructedDeck) {
          alert('Please enter a deck link or paste a deck list.');
          return null;
        }
        var gameType = 'casual'; // Default game type since select is commented out

        return {
          preconstructedDeck: preconstructedDeck,
          deckLink: deckLink,
          gameType: gameType
        };
      }

      function buildPrivateInviteLink(inviteCode) {
        var url = new URL(window.location.href);
        url.searchParams.set('privateInvite', inviteCode);
        return url.toString();
      }

      function joinQueue() {
        submitQueueJoin({
          waitingMessage: 'Waiting for opponent... (Esc to cancel)'
        });
      }

      function createPrivateGame() {
        submitQueueJoin({
          createPrivate: true,
          waitingMessage: 'Waiting for invited opponent... (Esc to cancel)'
        });
      }

      function startGoldfishGame() {
        submitQueueJoin({
          createGoldfish: true
        });
      }

      function joinPrivateInvite() {
        if (!_privateInviteCode) {
          showQueueInlineError('No private invite code found in this link.');
          return;
        }
        submitQueueJoin({
          privateInviteCode: _privateInviteCode,
          waitingMessage: 'Waiting for host to start... (Esc to cancel)'
        });
      }

      function submitQueueJoin(options) {
        options = options || {};
        clearQueueInlineError();
        var submission = getDeckSubmission();
        if (!submission) return;

        var xhr = new XMLHttpRequest();
        xhr.open('POST', '../../../APIs/Lobbies/JoinQueue.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        xhr.onload = function() {
          if (xhr.status >= 200 && xhr.status < 300) {
            console.log('Successfully joined queue:', xhr.responseText);
            var response;
            try {
              response = JSON.parse(xhr.responseText);
            } catch (e) {
              var raw = (xhr.responseText || '').trim();
              var preview = raw.length > 240 ? raw.slice(0, 240) + '...' : raw;
              showQueueInlineError('Unexpected server response while joining queue. ' + preview);
              return;
            }
            if (!response.success) {
              showQueueInlineError(response.message || 'Unable to join queue.');
              return;
            }
            clearQueueInlineError();
            if(response.ready) {
              DisplayMatchFoundPopup(response.playerID, response.gameName, response.authKey);
            } else {
              _lobby_id = response.lobbyID;
              var inviteLink = '';
              if (response.inviteCode) {
                inviteLink = buildPrivateInviteLink(response.inviteCode);
              }
              DisplayWaitingPopup(options.waitingMessage || 'Waiting for opponent... (Esc to cancel)', response.playerID, response.authKey, inviteLink);
              // Start polling for lobby updates
              pollLobbyUpdates(response.playerID, response.authKey);
            }
          } else {
            console.error('Error joining queue:', xhr.statusText);
            showQueueInlineError('Failed to join queue. Please try again.');
          }
        };

        xhr.onerror = function() {
          console.error('Error joining queue:', xhr.statusText);
          showQueueInlineError('Failed to join queue. Please try again.');
        };

        var params = 'deckLink=' + encodeURIComponent(submission.deckLink) + '&game_type=' + encodeURIComponent(submission.gameType);
        params += '&preconstructedDeck=' + encodeURIComponent(submission.preconstructedDeck);
        params += "&rootName=" + encodeURIComponent(rootName);
        if (options.createPrivate) {
          params += '&createPrivate=1';
        }
        if (options.createGoldfish) {
          params += '&createGoldfish=1';
        }
        if (options.privateInviteCode) {
          params += '&privateInviteCode=' + encodeURIComponent(options.privateInviteCode);
        }
        xhr.send(params);
      }

      function showQueueInlineError(message) {
        var el = document.getElementById('queue-inline-error');
        if (!el) {
          alert(message);
          return;
        }
        el.textContent = message || 'Unable to join queue.';
        el.style.display = '';
      }

      function clearQueueInlineError() {
        var el = document.getElementById('queue-inline-error');
        if (!el) return;
        el.textContent = '';
        el.style.display = 'none';
      }

      function copyTextToClipboard(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
          return navigator.clipboard.writeText(text);
        }
        return new Promise(function(resolve, reject) {
          try {
            var tempInput = document.createElement('textarea');
            tempInput.value = text;
            tempInput.style.position = 'fixed';
            tempInput.style.opacity = '0';
            document.body.appendChild(tempInput);
            tempInput.focus();
            tempInput.select();
            var ok = document.execCommand('copy');
            document.body.removeChild(tempInput);
            if (ok) resolve();
            else reject(new Error('copy_failed'));
          } catch (err) {
            reject(err);
          }
        });
      }

      function DisplayWaitingPopup(message, playerID, authKey, inviteLink) {
        var existingWaitingPopup = document.getElementById('waiting-popup');
        if (existingWaitingPopup) existingWaitingPopup.remove();
        if (_waitingEscHandler) {
          document.removeEventListener('keydown', _waitingEscHandler);
          _waitingEscHandler = null;
        }

        var waitingPopup = document.createElement('div');
        waitingPopup.id = 'waiting-popup';
        waitingPopup.style.position = 'fixed';
        waitingPopup.style.top = '0';
        waitingPopup.style.left = '0';
        waitingPopup.style.width = '100%';
        waitingPopup.style.height = '100%';
        waitingPopup.style.backgroundColor = 'rgba(0, 0, 0, 0.8)';
        waitingPopup.style.display = 'flex';
        waitingPopup.style.flexDirection = 'column';
        waitingPopup.style.justifyContent = 'center';
        waitingPopup.style.alignItems = 'center';
        waitingPopup.style.zIndex = '1000';

        var animation = document.createElement('div');
        animation.style.border = '16px solid #f3f3f3';
        animation.style.borderTop = '16px solid #3498db';
        animation.style.borderRadius = '50%';
        animation.style.width = '120px';
        animation.style.height = '120px';
        animation.style.animation = 'spin 2s linear infinite';

        var style = document.createElement('style');
        style.textContent = `
          @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
          }
        `;
        document.head.appendChild(style);

        var messageElement = document.createElement('p');
        messageElement.textContent = message;
        messageElement.style.color = 'white';
        messageElement.style.marginTop = '20px';
        messageElement.style.fontSize = '18px';
        messageElement.style.textAlign = 'center';
        messageElement.style.fontStyle = 'italic';

        waitingPopup.appendChild(animation);
        waitingPopup.appendChild(messageElement);

        if (inviteLink) {
          var inviteHint = document.createElement('p');
          inviteHint.textContent = 'Share this invite link with your opponent:';
          inviteHint.style.color = '#d8d8d8';
          inviteHint.style.marginTop = '14px';
          inviteHint.style.marginBottom = '8px';
          inviteHint.style.fontSize = '14px';
          waitingPopup.appendChild(inviteHint);

          var linkPreview = document.createElement('div');
          linkPreview.textContent = inviteLink;
          linkPreview.style.maxWidth = '680px';
          linkPreview.style.wordBreak = 'break-all';
          linkPreview.style.color = '#9ed9b4';
          linkPreview.style.fontSize = '12px';
          linkPreview.style.marginBottom = '10px';
          linkPreview.style.padding = '8px 10px';
          linkPreview.style.border = '1px solid rgba(255,255,255,0.15)';
          linkPreview.style.borderRadius = '6px';
          linkPreview.style.backgroundColor = 'rgba(0,0,0,0.28)';
          waitingPopup.appendChild(linkPreview);

          var copyButton = document.createElement('button');
          copyButton.textContent = 'Copy Invite Link';
          copyButton.style.backgroundColor = '#2d8a57';
          copyButton.onclick = function() {
            copyTextToClipboard(inviteLink)
              .then(function() {
                copyButton.textContent = 'Copied!';
                setTimeout(function() {
                  copyButton.textContent = 'Copy Invite Link';
                }, 1200);
              })
              .catch(function() {
                alert('Unable to copy automatically. Please copy the invite link manually.');
              });
          };
          waitingPopup.appendChild(copyButton);
        }

        document.body.appendChild(waitingPopup);

        // Add event listener for Escape key
        _waitingEscHandler = function handleEscapeKey(event) {
          if (event.key === 'Escape') {
            document.body.removeChild(waitingPopup);
            document.removeEventListener('keydown', _waitingEscHandler);
            _waitingEscHandler = null;

            // Send a message to the server to cancel the queue
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '../../../APIs/Lobbies/LeaveQueue.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onload = function() {
              if (xhr.status >= 200 && xhr.status < 300) {
              console.log('Queue canceled successfully:', xhr.responseText);
              } else {
              console.error('Error canceling queue:', xhr.statusText);
              }
            };

            xhr.onerror = function() {
              console.error('Error canceling queue:', xhr.statusText);
            };

            var params = 'rootName=' + encodeURIComponent(rootName) + '&playerID=' + encodeURIComponent(playerID) + '&lobbyID=' + encodeURIComponent(_lobby_id) + '&authKey=' + encodeURIComponent(authKey);
            xhr.send(params);
            }
        };
        document.addEventListener('keydown', _waitingEscHandler);
      }

      function DisplayMatchFoundPopup(playerID, gameName, authKey) {
        var matchPopup = document.createElement('div');
        matchPopup.id = 'match-found-popup';
        matchPopup.style.cssText = `
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background-color: rgba(0, 0, 0, 0.9);
          display: flex;
          flex-direction: column;
          justify-content: center;
          align-items: center;
          z-index: 1000;
          animation: fadeInPopup 0.3s ease-out;
        `;

        var style = document.createElement('style');
        style.textContent = `
          @keyframes fadeInPopup {
            from { opacity: 0; }
            to { opacity: 1; }
          }
          @keyframes pulseGlow {
            0%, 100% { text-shadow: 0 0 20px rgba(52, 152, 219, 0.8), 0 0 40px rgba(52, 152, 219, 0.4); }
            50% { text-shadow: 0 0 30px rgba(52, 152, 219, 1), 0 0 60px rgba(52, 152, 219, 0.6); }
          }
          @keyframes countdownPop {
            0% { transform: scale(1.5); opacity: 0; }
            50% { transform: scale(1.1); opacity: 1; }
            100% { transform: scale(1); opacity: 1; }
          }
          @keyframes countdownFade {
            0% { transform: scale(1); opacity: 1; }
            100% { transform: scale(0.8); opacity: 0; }
          }
        `;
        document.head.appendChild(style);

        var titleElement = document.createElement('h1');
        titleElement.textContent = '⚔️ Match Found!';
        titleElement.style.cssText = `
          color: #3498db;
          font-size: 48px;
          margin-bottom: 30px;
          font-family: 'Roboto', sans-serif;
          animation: pulseGlow 1.5s ease-in-out infinite;
        `;

        var subtitleElement = document.createElement('p');
        subtitleElement.textContent = 'Joining in...';
        subtitleElement.style.cssText = `
          color: #ccc;
          font-size: 20px;
          margin-bottom: 20px;
          font-family: 'Roboto', sans-serif;
        `;

        var countdownElement = document.createElement('div');
        countdownElement.id = 'countdown-number';
        countdownElement.style.cssText = `
          color: white;
          font-size: 120px;
          font-weight: bold;
          font-family: 'Roboto', sans-serif;
          min-height: 150px;
          display: flex;
          align-items: center;
          justify-content: center;
        `;

        matchPopup.appendChild(titleElement);
        matchPopup.appendChild(subtitleElement);
        matchPopup.appendChild(countdownElement);
        document.body.appendChild(matchPopup);

        // Animated countdown
        var count = 3;
        function updateCountdown() {
          countdownElement.textContent = count;
          countdownElement.style.animation = 'none';
          countdownElement.offsetHeight; // Trigger reflow
          countdownElement.style.animation = 'countdownPop 0.5s ease-out forwards';
          
          if (count > 0) {
            setTimeout(function() {
              countdownElement.style.animation = 'countdownFade 0.4s ease-in forwards';
              setTimeout(function() {
                count--;
                if (count > 0) {
                  updateCountdown();
                } else {
                  countdownElement.textContent = 'GO!';
                  countdownElement.style.color = '#2ecc71';
                  countdownElement.style.animation = 'countdownPop 0.3s ease-out forwards';
                  setTimeout(function() {
                    // Remove the popup before redirecting
                    if (matchPopup && matchPopup.parentNode) {
                      matchPopup.parentNode.removeChild(matchPopup);
                    }
                    // Redirect with fade parameter
                    navigateToGame(playerID, gameName, authKey, true);
                  }, 400);
                }
              }, 400);
            }, 500);
          }
        }
        updateCountdown();
        
        // Also clean up any existing match found popups on page load to handle browser back button
        window.addEventListener('pageshow', function(event) {
          if (event.persisted) {
            var existingPopup = document.getElementById('match-found-popup');
            if (existingPopup) {
              existingPopup.remove();
            }
          }
        });
      }

      function refreshOpenGames() {
        console.log('Refreshing open games');
        var gameCountElement = document.getElementById('active-game-count');
        var gameListElement = document.getElementById('active-games-list');
        var xhr = new XMLHttpRequest();
        xhr.open('GET', '../../../APIs/Lobbies/GetActiveGames.php?rootName=' + encodeURIComponent(rootName), true);
        xhr.responseType = 'json';

        xhr.onload = function() {
          if (xhr.status >= 200 && xhr.status < 300) {
          var data = xhr.response;
          
          if (data.data && Array.isArray(data.data)) {
            var totalCount = (typeof data.totalCount === 'number') ? data.totalCount : data.data.length;
            gameCountElement.textContent = totalCount;
            renderActiveGames(data.data);
          } else {
            gameCountElement.textContent = '0';
            renderActiveGames([]);
          }
          } else {
          console.error('Error fetching open games:', xhr.statusText);
          gameCountElement.textContent = '0';
          renderActiveGames([]);
          }
        };

        xhr.onerror = function() {
          console.error('Error fetching open games:', xhr.statusText);
          gameCountElement.textContent = '0';
          renderActiveGames([]);
        };

        xhr.send();
      }

      function formatActiveGameTime(timestamp) {
        if (!timestamp) return 'Unknown';
        try {
          return new Date(timestamp * 1000).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
        } catch (e) {
          return 'Unknown';
        }
      }

      function openSpectatorView(gameName, perspective) {
        var url = new URL('../../../NextTurn.php', window.location.href);
        url.searchParams.set('playerID', 'S');
        url.searchParams.set('viewerPerspective', perspective === 2 ? '2' : '1');
        url.searchParams.set('gameName', gameName);
        url.searchParams.set('folderPath', rootName);
        window.location.href = url.toString();
      }

      function renderActiveGames(games) {
        var gameListElement = document.getElementById('active-games-list');
        if (!gameListElement) return;
        if (!games || !games.length) {
          gameListElement.innerHTML = '<div class="active-game-empty">No active games right now. Start one or refresh again in a moment.</div>';
          return;
        }

        var html = '';
        games.forEach(function(game) {
          var visibilityClass = game.isPrivate ? 'private' : 'public';
          var visibilityLabel = game.isPrivate ? 'Private' : 'Public';
          html += '<div class="active-game-card">';
          html +=   '<div class="active-game-meta">';
          html +=     '<div>Game <strong>' + game.gameName + '</strong><br><span style="font-size:12px; color:#b9b9b9;">Updated ' + formatActiveGameTime(game.lastUpdatedAt) + '</span></div>';
          html +=     '<span class="active-game-badge ' + visibilityClass + '">' + visibilityLabel + '</span>';
          html +=   '</div>';
          html +=   '<div class="active-game-actions">';
          html +=     '<button class="spectate-button" onclick="openSpectatorView(\'' + game.gameName + '\', 1)">Spectate P1 Side</button>';
          html +=     '<button class="spectate-button" onclick="openSpectatorView(\'' + game.gameName + '\', 2)">Spectate P2 Side</button>';
          html +=   '</div>';
          html += '</div>';
        });
        gameListElement.innerHTML = html;
      }

      function pollLobbyUpdates(playerID, authKey) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '../../../APIs/Lobbies/PollLobbyUpdates.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        xhr.onload = function() {
          if (xhr.status >= 200 && xhr.status < 300) {
            var response = JSON.parse(xhr.responseText);
            if (response.ready) {
              playPlayerJoinedSound();
              // Close waiting popup and show match found popup
              var waitingPopup = document.getElementById('waiting-popup');
              if (waitingPopup) waitingPopup.remove();
              if (_waitingEscHandler) {
                document.removeEventListener('keydown', _waitingEscHandler);
                _waitingEscHandler = null;
              }
              DisplayMatchFoundPopup(response.playerID, response.gameName, authKey);
            } else {
              // Continue polling if the lobby is not ready
              pollLobbyUpdates(playerID, authKey);
            }
          } else {
            console.error('Error polling lobby updates:', xhr.statusText);
          }
        };

        xhr.onerror = function() {
          console.error('Error polling lobby updates:', xhr.statusText);
          // Retry polling after a delay in case of an error
          setTimeout(function() {
            pollLobbyUpdates(playerID, authKey);
          }, 5000);
        };

        var params = 'rootName=' + encodeURIComponent(rootName) + 
                     '&playerID=' + encodeURIComponent(playerID) + 
                     '&lobbyID=' + encodeURIComponent(_lobby_id) + 
                     '&authKey=' + encodeURIComponent(authKey);
        xhr.send(params);
      }

      document.addEventListener('DOMContentLoaded', function() {
        if (window.MatchReplayClient) {
          window.MatchReplayClient.init({
            enabled: true,
            rootName: rootName,
            apiBaseUrl: '/TCGEngine/APIs/MatchReplay.php',
            nextTurnBaseUrl: '/TCGEngine/NextTurn.php'
          });
          window.MatchReplayClient.renderReplayLibrary('match-replay-menu-list', {
            rootName: rootName
          });
        }
        initializePrivateInviteFromUrl();
        initializeGoldfishLinkFromUrl();
        updateRejoinLastGameUI();
        refreshOpenGames();
        if (_autoLaunchGoldfish) {
          window.setTimeout(function() {
            _autoLaunchGoldfish = false;
            startGoldfishGame();
          }, 0);
        }
      });
    </script>

<?php
include_once __DIR__ . '/Disclaimer.php';
?>
