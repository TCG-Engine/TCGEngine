<?php
// Use __DIR__-relative includes (matching the SWUDeck pilot): this page is reached via the
// SharedUI/MainMenu.php pointer (which include()s it), so the cwd is SharedUI/, not this dir.
// Bare './'/'../../../' paths resolved against the wrong cwd → missing-file warnings AND silently
// pulled the ROOT SharedUI/MenuBar.php + Header.php (wrong chrome) instead of the SWUSim ones.
include_once __DIR__ . '/MenuBar.php';
include_once __DIR__ . '/../../../AccountFiles/AccountSessionAPI.php';
include_once __DIR__ . '/../../../Database/ConnectionManager.php';
include_once __DIR__ . '/../../../SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
include_once __DIR__ . '/../../../AppCore/SWU/Formats.php';
require_once __DIR__ . '/../../Render/DeckLibrary.php';

include_once __DIR__ . '/Header.php';

$swuFormats = function_exists('SWUListFormats') ? SWUListFormats() : ['premier' => 'Premier'];
$swuQueueTypes = function_exists('SWUQueueTypeDefinitions') ? SWUQueueTypeDefinitions() : ['bo1' => ['displayName' => 'Best of 1']];
$swuSiteDef = require __DIR__ . '/SiteDef.php';
$swuDeckLibraryConfig = DeckLibraryConfigFromSiteDef($swuSiteDef);
?>
<div class="row-wrapper swu-menu-grid">
  <!-- Active Games (left) -->
  <div class="card ga-glass-card swu-active-card" style="padding: 20px; color: var(--text); border-radius: 12px; position: relative;">
    <button style="position: absolute; top: 10px; right: 10px; background: none; border: none; cursor: pointer;" onclick="refreshOpenGames()">
      <img src='/TCGEngine/Assets/Icons/refresh.svg' width='16' height='16' alt='Refresh' style='filter: invert(100%);' />
    </button>
    <h2>Active Games (<span id="active-game-count">0</span>)</h2>
    <div id="active-games-list" class="swu-active-games-list"></div>
    <p class="swu-active-empty" style="color: var(--text-muted); font-size: 13px; margin: 6px 0 0;">Games in the public queue appear in the count above.</p>
  </div>

  <!-- Create a New Game (middle) -->
  <div class="card ga-glass-card swu-queue-card" style="padding: 20px; color: var(--text); border-radius: 12px; position: relative;">
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
      " onmouseover="this.style.borderColor='rgba(var(--accent-rgb), 0.8)'; this.style.backgroundColor='rgba(50, 50, 50, 0.95)';" onmouseout="this.style.borderColor='rgba(100, 100, 100, 0.5)'; this.style.backgroundColor='rgba(40, 40, 40, 0.95)';" onfocus="this.style.borderColor='var(--accent)'; this.style.boxShadow='0 0 8px rgba(var(--accent-rgb), 0.4)';" onblur="this.style.borderColor='rgba(100, 100, 100, 0.5)'; this.style.boxShadow='none';">
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
      <?php if (getenv('DEVENV') === 'true'): ?>
      <!-- Dev-only convenience: prefills a known-good deck link. Gated to DEVENV so it never
           renders in production. -->
      <button onclick="loadTestDeck()" style="margin-bottom: 10px; padding: 6px 14px; background: rgba(60,120,60,0.25); color: #90e090; border: 1px solid rgba(90,160,90,0.45); border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 600;">⚗ Test Deck</button>
      <?php endif; ?>

      <div style="display: flex; gap: 0; margin-bottom: 10px; border-bottom: 2px solid rgba(100,100,100,0.4);">
        <button id="tab-link" onclick="switchDeckTab('link')" style="flex: 1; padding: 8px; background: rgba(var(--accent-rgb),0.18); color: var(--text); border: none; border-bottom: 2px solid var(--accent); cursor: pointer; font-size: 13px; font-weight: 600;">Deck Link</button>
        <button id="tab-text" onclick="switchDeckTab('text')" style="flex: 1; padding: 8px; background: rgba(var(--accent-rgb),0.06); color: var(--text-muted); border: none; border-bottom: 2px solid transparent; cursor: pointer; font-size: 13px;">Free Text</button>
      </div>
      <div id="deck-input-link">
        <label for="deck-link" style="display: block; margin-bottom: 8px; font-weight: 500;">Paste a deck link:</label>
        <input type="text" id="deck-link" name="deck_link" placeholder="https://swustats.net/TCGEngine/NextTurn.php?gameName=..." style="width: 100%; padding: 10px 15px; background-color: var(--surface-sunken); color: var(--text); border: 2px solid var(--border); border-radius: 8px; font-size: 14px; outline: none; box-sizing: border-box;">
        <div style="margin-top: 8px; color: var(--text-muted); font-size: 12px; line-height: 1.35;">
          Supported deck links: SWUStats, SWUDB
        </div>
      </div>
      <div id="deck-input-text" style="display: none;">
        <label for="deck-text" style="display: block; margin-bottom: 8px; font-weight: 500;">Paste deck list (e.g. from SWUDB or SWUDeck):</label>
        <textarea id="deck-text" name="deck_text" rows="12" placeholder="# Leader&#10;1 Luke Skywalker, Faithful Friend&#10;&#10;# Base&#10;1 Echo Base&#10;&#10;# Main Deck&#10;3 Alliance X-Wing&#10;..." style="width: 100%; padding: 10px 15px; background-color: var(--surface-sunken); color: var(--text); border: 2px solid var(--border); border-radius: 8px; font-size: 13px; font-family: monospace; outline: none; box-sizing: border-box; resize: vertical;"></textarea>
      </div>
      <!-- Hotseat: a second deck link for Player 2 (revealed only when the Hotseat format is selected). -->
      <div id="swu-deck2-group" style="display: none; margin-top: 10px;">
        <label for="swu-deck2-input" style="display: block; margin-bottom: 8px; font-weight: 500;">Player 2 deck link (Hotseat):</label>
        <input type="text" id="swu-deck2-input" placeholder="Second deck link" style="width: 100%; padding: 10px 15px; background-color: var(--surface-sunken); color: var(--text); border: 2px solid var(--border); border-radius: 8px; font-size: 14px; outline: none; box-sizing: border-box;">
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
      <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 12px;">
        <div style="flex: 1; min-width: 140px;">
          <label for="swu-format-select" style="display: block; margin-bottom: 6px; font-weight: 500; font-size: 13px;">Format:</label>
          <select id="swu-format-select" class="swu-queue-select">
            <?php
              // Only logged-in users may queue non-Open formats, so only offer 'Open' when logged out.
              // (The JoinQueue endpoint enforces this too, for anyone who bypasses the UI.)
              $swuLoggedIn = isset($_SESSION['userid']);
              $swuDefaultFormat = $swuLoggedIn ? 'premier' : 'open';
            ?>
            <?php foreach ($swuFormats as $fid => $fname): ?>
            <?php if (!$swuLoggedIn && $fid !== 'open' && $fid !== 'goldfish' && $fid !== 'hotseat') continue; ?>
            <option value="<?php echo htmlspecialchars($fid, ENT_QUOTES); ?>"<?php echo $fid === $swuDefaultFormat ? ' selected' : ''; ?>><?php echo htmlspecialchars($fname, ENT_QUOTES); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div style="flex: 1; min-width: 140px;">
          <label for="swu-queuetype-select" style="display: block; margin-bottom: 6px; font-weight: 500; font-size: 13px;">Match Type:</label>
          <select id="swu-queuetype-select" class="swu-queue-select">
            <?php foreach ($swuQueueTypes as $qid => $qdef): ?>
            <option value="<?php echo htmlspecialchars($qid, ENT_QUOTES); ?>"<?php echo $qid === 'bo1' ? ' selected' : ''; ?>><?php echo htmlspecialchars($qdef['displayName'] ?? $qid, ENT_QUOTES); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <button onclick="joinQueue()" disabled title="Public matchmaking isn't open yet — use Create Private Game." style="opacity: 0.5; cursor: not-allowed;">Join Queue</button>
        <button onclick="saveCurrentDeck()" style="background-color: #6b4f9f;" title="Save this deck link to your library">Save Deck</button>
        <button onclick="createPrivateGame()" style="background-color: #2f6f9f;">Create Private Game</button>
        <button id="join-private-invite-btn" onclick="joinPrivateInvite()" style="display: none; background-color: #2d8a57;">Join Private Invite</button>
      </div>
      <div id="queue-inline-error" style="display: none; margin-top: 10px; color: #ff6b6b; font-size: 13px; line-height: 1.35;"></div>
      <div id="private-invite-notice" style="display: none; margin-top: 10px; color: var(--text-muted); font-size: 13px;"></div>
      <?php
        if (isset($_SESSION['userid'])) {
            echo "<div class='saved-decks-panel' style='margin-top:16px;'><h3 style='margin:0 0 8px 0;'>Saved Decks</h3>";
            // Default (no action buttons): the dropdown only loads a deck into the queue box.
            // Managing saved decks (favorite/rename/delete) lives on the Profile page.
            echo RenderDeckLibrary((int)$_SESSION['userid'], $swuDeckLibraryConfig);
            echo "</div>";
        }
      ?>
    </div>
  </div>

  <!-- Welcome + Replays (right, tabbed) -->
  <div class="card ga-glass-card swu-info-card" style="padding: 20px; color: var(--text); border-radius: 12px; display: flex; flex-direction: column; gap: 16px;">
    <div class="ga-info-tabs" role="tablist" aria-label="Petranaki information">
      <button type="button" id="ga-info-tab-welcome" class="ga-info-tab is-active" onclick="switchInfoTab('welcome')" role="tab" aria-selected="true" aria-controls="ga-info-panel-welcome">Welcome</button>
      <button type="button" id="ga-info-tab-replays" class="ga-info-tab" onclick="switchInfoTab('replays')" role="tab" aria-selected="false" aria-controls="ga-info-panel-replays">Replays</button>
    </div>
    <div id="ga-info-panel-welcome" class="ga-info-panel is-active" role="tabpanel" aria-labelledby="ga-info-tab-welcome">
    <h2 style="margin: 0 0 4px 0;">Welcome to Petranaki Arena!</h2>
    <p class="login-message" style="margin: 0; color: var(--text-muted); font-size: 14px;">Petranaki Arena is a fan-made online simulator for Star Wars: Unlimited.</p>

    <hr style="border: none; border-top: 1px solid rgba(var(--accent-rgb),0.20); margin: 0;">

    <!-- Did you know? -->
    <div id="did-you-know-box" style="
      background: linear-gradient(135deg, rgba(var(--accent-rgb),0.14) 0%, rgba(var(--accent-rgb),0.20) 100%);
      border: 1px solid rgba(var(--accent-rgb),0.28);
      border-radius: 8px;
      padding: 14px 16px;
      position: relative;
    ">
      <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
        <span style="font-size: 18px;">⚡</span>
        <span style="font-size: 12px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--accent);">Did you know?</span>
      </div>
      <p id="did-you-know-text" style="margin: 0; font-size: 14px; color: var(--text); line-height: 1.55;"></p>
      <button onclick="cycleDidYouKnow()" title="Next tip" style="
        position: absolute; top: 10px; right: 10px;
        background: none; border: none; cursor: pointer;
        color: var(--accent); font-size: 16px; padding: 2px 6px; border-radius: 4px;
        transition: background 0.2s;
      " onmouseover="this.style.background='rgba(var(--accent-rgb),0.14)'" onmouseout="this.style.background='none'">→</button>
    </div>

    <!-- Quick-reference hotkeys -->
    <div>
      <div style="font-size: 12px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--text-muted); margin-bottom: 8px;">Quick Reference</div>
      <div style="display: flex; flex-direction: column; gap: 6px;" id="hotkey-list"></div>
    </div>
    </div>
    <div id="ga-info-panel-replays" class="ga-info-panel" role="tabpanel" aria-labelledby="ga-info-tab-replays">
    <h2 style="margin: 0 0 4px 0;">Your Replays</h2>
    <p style="margin: 0; color: var(--text-muted); font-size: 13px; line-height: 1.4;">Saved in this browser. Use the <strong>Save Replay</strong> button on the end-of-game screen to add one here.</p>
    <div id="match-replay-menu-list" class="swu-replay-list" style="margin-top: 6px;"></div>
    </div><!-- end replays panel -->
  </div><!-- end info card -->
</div>

<script src="/TCGEngine/Core/MatchReplayClient.js"></script>

<div id="ga-settings-modal" class="ga-settings-modal" aria-hidden="true">
  <div class="ga-settings-modal__overlay" data-close-settings-modal="true"></div>
  <div class="ga-settings-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="ga-settings-modal-title">
    <div class="ga-settings-modal__header">
      <h3 id="ga-settings-modal-title">Menu Settings</h3>
      <button type="button" class="ga-settings-modal__close" id="ga-close-settings-btn" aria-label="Close settings modal">x</button>
    </div>
    <div class="ga-settings-modal__body">
      <label for="ga-board-background-theme" class="ga-settings-row ga-settings-row--split">
        <span>Board background</span>
        <select id="ga-board-background-theme">
          <option value="space">Dark Space</option>
          <option value="desert">Geonosis Desert</option>
        </select>
      </label>
    </div>
  </div>
</div>

<style>
  .swu-menu-grid {
    display: grid;
    grid-template-columns: minmax(240px, 0.85fr) minmax(360px, 1.2fr) minmax(300px, 1fr);
    gap: 14px;
    align-items: start;
    flex-grow: 1;
    margin: 0 10px 10px;
  }
  .swu-menu-grid > .card { min-width: 0; margin: 0; }
  @media (max-width: 920px) {
    .swu-menu-grid { grid-template-columns: 1fr; }
  }
  /* Right-column Welcome/Replays tabs (scoped past .ga-glass-card * color rule) */
  .ga-info-tabs { display: flex; gap: 0; border-bottom: 1px solid rgba(var(--accent-rgb), 0.28); }
  .ga-glass-card .ga-info-tab {
    flex: 1; padding: 8px; border: 0; border-bottom: 2px solid transparent;
    background: rgba(var(--accent-rgb), 0.06); color: var(--text-muted);
    cursor: pointer; font-size: 13px; text-transform: uppercase; letter-spacing: 0.04em;
    transition: background 0.2s, color 0.2s, border-color 0.2s;
  }
  .ga-glass-card .ga-info-tab:hover { color: var(--text); }
  .ga-glass-card .ga-info-tab.is-active {
    background: rgba(var(--accent-rgb), 0.16); color: var(--text); border-bottom-color: var(--accent);
  }
  .ga-info-panel { display: none; flex-direction: column; gap: 16px; }
  .ga-info-panel.is-active { display: flex; }
  .ga-glass-card {
    background: var(--surface-raised);
    border: 1px solid rgba(var(--accent-rgb), 0.30);
    box-shadow: 0 14px 36px rgba(10, 4, 0, 0.50), inset 0 1px 0 rgba(255, 255, 255, 0.06);
    backdrop-filter: blur(10px) saturate(110%);
    -webkit-backdrop-filter: blur(10px) saturate(110%);
    color: var(--text) !important;
  }
  .ga-glass-card * { color: var(--text); }
  .swu-queue-select {
    width: 100%;
    padding: 8px 12px;
    background-color: var(--surface-sunken);
    color: var(--text) !important;
    border: 2px solid rgba(var(--accent-rgb), 0.40);
    border-radius: 8px;
    font-size: 14px;
    cursor: pointer;
    outline: none;
    box-sizing: border-box;
    transition: border-color 0.2s, box-shadow 0.2s;
  }
  .swu-queue-select:focus {
    border-color: var(--accent);
    box-shadow: 0 0 8px rgba(var(--accent-rgb), 0.4);
  }
  .swu-queue-select option { background-color: var(--surface-raised); color: var(--text); }
  .hotkey-row { display: flex; align-items: center; gap: 10px; font-size: 13px; color: var(--text-muted); }
  .hotkey-badge {
    display: inline-block; min-width: 28px; text-align: center;
    padding: 2px 7px; border-radius: 5px;
    background: rgba(var(--accent-rgb), 0.12); border: 1px solid rgba(var(--accent-rgb), 0.30);
    font-family: monospace; font-size: 13px; font-weight: 700; color: var(--text);
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
    background: var(--surface-raised);
    border: 1px solid rgba(var(--accent-rgb), 0.35);
    border-radius: 10px;
    box-shadow: 0 20px 50px rgba(10, 4, 0, 0.55);
    color: var(--text);
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
    color: var(--text);
  }
  .ga-settings-modal__close {
    border: 0;
    background: rgba(var(--accent-rgb), 0.14);
    color: var(--text);
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
  .ga-settings-row {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--text-muted);
    font-size: 13px;
  }
  .ga-settings-row--split {
    justify-content: space-between;
  }
  #ga-board-background-theme {
    background: var(--surface-sunken);
    color: var(--text);
    border: 1px solid rgba(var(--accent-rgb), 0.35);
    border-radius: 6px;
    padding: 4px 8px;
    font-size: 12px;
  }
</style>

<script>
  var _didYouKnowTips = [
    { key: 'u', label: 'Undo your most recent action' },
    { key: 'Space', label: 'Pass an optional decision when available' },
    { text: 'Hover a card on the field to see its full text.' },
    { text: 'You can paste a deck link directly from SWUStats or SWUDB.' },
    { text: 'Private games generate a shareable invite link — send it to your opponent and they can join instantly.' },
    { text: 'The queue matches you with the first available opponent. No need to refresh — it polls automatically.' },
    { text: 'Units enter the arena exhausted when played from hand.' },
    { text: 'Taking the initiative lets you pass the rest of the action phase to your opponent — use it wisely.' },
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
      window.TCGSettings.registerSchema('SWUSim', {
        BoardBackgroundTheme: {
          type: 'string',
          defaultValue: 'space'
        }
      });
    }

    renderDidYouKnow();
    renderHotkeyList();
    var boardThemeSelect = document.getElementById('ga-board-background-theme');
    if (boardThemeSelect && window.TCGSettings) {
      var savedTheme = window.TCGSettings.get('BoardBackgroundTheme', { rootName: 'SWUSim', type: 'string', defaultValue: 'space' });
      boardThemeSelect.value = (savedTheme === 'desert') ? 'desert' : 'space';
      boardThemeSelect.addEventListener('change', function() {
        var value = boardThemeSelect.value === 'desert' ? 'desert' : 'space';
        window.TCGSettings.set('BoardBackgroundTheme', value, { rootName: 'SWUSim', type: 'string' });
      });
    }

    window.openSWUSimSettingsModal = openGASettingsModal;
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

  var rootName = "SWUSim";
  var _lobby_id = "";
  var _privateInviteCode = "";
  var _waitingEscHandler = null;

      // TODO: remove this function before deploy
      function loadTestDeck() {
        switchDeckTab('link');
        var el = document.getElementById('deck-link');
        if (el) el.value = 'https://swudb.com/deck/prozLLKSsRS';
      }

      // Right-column info card: switch between the Welcome and Replays tabs.
      function switchInfoTab(tab) {
        var isReplays = tab === 'replays';
        var welcomeTab = document.getElementById('ga-info-tab-welcome');
        var replaysTab = document.getElementById('ga-info-tab-replays');
        var welcomePanel = document.getElementById('ga-info-panel-welcome');
        var replaysPanel = document.getElementById('ga-info-panel-replays');
        if (!welcomeTab || !replaysTab || !welcomePanel || !replaysPanel) return;
        welcomeTab.classList.toggle('is-active', !isReplays);
        replaysTab.classList.toggle('is-active', isReplays);
        welcomeTab.setAttribute('aria-selected', isReplays ? 'false' : 'true');
        replaysTab.setAttribute('aria-selected', isReplays ? 'true' : 'false');
        welcomePanel.classList.toggle('is-active', !isReplays);
        replaysPanel.classList.toggle('is-active', isReplays);
      }
      function switchDeckTab(tab) {
        var isLink = tab === 'link';
        document.getElementById('deck-input-link').style.display = isLink ? '' : 'none';
        document.getElementById('deck-input-text').style.display = isLink ? 'none' : '';
        document.getElementById('tab-link').style.background = isLink ? 'rgba(var(--accent-rgb),0.18)' : 'rgba(var(--accent-rgb),0.06)';
        document.getElementById('tab-link').style.color = isLink ? 'var(--text)' : 'var(--text-muted)';
        document.getElementById('tab-link').style.borderBottom = isLink ? '2px solid var(--accent)' : '2px solid transparent';
        document.getElementById('tab-text').style.background = isLink ? 'rgba(var(--accent-rgb),0.06)' : 'rgba(var(--accent-rgb),0.18)';
        document.getElementById('tab-text').style.color = isLink ? 'var(--text-muted)' : 'var(--text)';
        document.getElementById('tab-text').style.borderBottom = isLink ? '2px solid transparent' : '2px solid var(--accent)';
        try { localStorage.setItem('swu_deck_tab', tab); } catch(e) {}
      }

      (function() {
        var saved = '';
        try { saved = localStorage.getItem('swu_deck_tab') || ''; } catch(e) {}
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
          StyledAlert('Please enter a deck link or paste a deck list.');
          return null;
        }
        var gameType = 'casual'; // Default game type since select is commented out

        var formatEl = document.getElementById('swu-format-select');
        var queueTypeEl = document.getElementById('swu-queuetype-select');
        var format = formatEl ? formatEl.value : 'premier';
        var queueType = queueTypeEl ? queueTypeEl.value : 'bo1';

        var deck2El = document.getElementById('swu-deck2-input');
        var deckLink2 = deck2El ? deck2El.value.trim() : '';

        return {
          preconstructedDeck: preconstructedDeck,
          deckLink: deckLink,
          deckLink2: deckLink2,
          gameType: gameType,
          format: format,
          queueType: queueType
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

      // ── Saved deck links ──────────────────────────────────────────────────
      // This page is served at two URL depths (the ActiveSite root /TCGEngine/SharedUI/MainMenu.php
      // AND /TCGEngine/SharedUI/Sites/SWUSim/MainMenu.php), so a fixed '../../../' prefix overshoots
      // from the root entry. Anchor to /TCGEngine/ from the live URL instead — depth-independent.
      function swusimAppBase(){ var p=location.pathname, i=p.indexOf('/TCGEngine/'); return i>=0 ? p.slice(0, i+11) : '/TCGEngine/'; }
      var SAVEDECKS_URL = swusimAppBase() + 'SWUSim/SavedDecks.php';
      function saveCurrentDeck() {
        var sub = getDeckSubmission();
        if (!sub || !sub.deckLink) return;   // getDeckSubmission alerts when empty
        var x = new XMLHttpRequest();
        x.open('POST', SAVEDECKS_URL, true);
        x.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        x.onload = function(){ var r={}; try{ r=JSON.parse(x.responseText); }catch(e){}
          if (r.success) location.reload();
          else showQueueInlineError('Could not save deck: ' + (r.error || 'unknown')); };
        x.send('action=save&deckInput=' + encodeURIComponent(sub.deckLink));
      }

      // Load a saved deck's input into the correct deck box (URL → Link tab, raw JSON → Free Text tab).
      function loadSavedDeckInput(input) {
        if (!input) return;
        if (input.charAt(0) === '{') {
          if (typeof switchDeckTab === 'function') switchDeckTab('text');
          var t = document.getElementById('deck-text'); if (t) t.value = input;
        } else {
          if (typeof switchDeckTab === 'function') switchDeckTab('link');
          var el = document.getElementById('deck-link'); if (el) el.value = input;
        }
      }
      // Selecting a deck from the dropdown loads it into the queue box (Join Queue takes it from there).
      document.addEventListener('change', function(e){
        var sel = e.target.closest('.saved-decks-panel .dl-select'); if(!sel) return;
        var opt = sel.options[sel.selectedIndex];
        loadSavedDeckInput(opt ? opt.getAttribute('data-queue-input') : '');
      });

      // Format-dependent UI: Hotseat reveals a 2nd deck input; both solo/local modes are Bo1-only
      // for now (lock Match Type to Bo1 — remove the isMode branch below to re-enable Bo3 later).
      (function(){
        var fmt = document.getElementById('swu-format-select');
        if (!fmt) return;
        function applyFormatUI(){
          var isMode = (fmt.value === 'goldfish' || fmt.value === 'hotseat');
          var isTwinSuns = (fmt.value === 'twinsuns');
          var g = document.getElementById('swu-deck2-group');
          if (g) g.style.display = (fmt.value === 'hotseat') ? '' : 'none';
          var qt = document.getElementById('swu-queuetype-select');
          if (qt) {
            if (isMode || isTwinSuns) { qt.value = 'bo1'; qt.disabled = true; }
            else { qt.disabled = false; }
          }
          var joinBtn = document.querySelector('button[onclick="joinQueue()"]');
          var createBtn = document.querySelector('button[onclick="createPrivateGame()"]');
          if (joinBtn) joinBtn.style.display = isTwinSuns ? 'none' : '';
          if (createBtn) createBtn.textContent = isTwinSuns ? 'Create Twin Suns Room' : 'Create Private Game';
        }
        fmt.addEventListener('change', applyFormatUI);
        applyFormatUI();
      })();

      function createPrivateGame() {
        submitQueueJoin({
          createPrivate: true,
          waitingMessage: 'Waiting for invited opponent... (Esc to cancel)'
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
        if (submission.format === 'hotseat' && !submission.deckLink2) {
          showQueueInlineError('Hotseat needs a second deck link (Player 2).');
          return;
        }

        // ── Step 1: validate deck before touching the queue ──────────────────
        showQueueInlineInfo('Validating deck…');
        var vxhr = new XMLHttpRequest();
        vxhr.open('POST', swusimAppBase() + 'SWUSim/ValidateDeck.php', true);
        vxhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        vxhr.onload = function() {
          clearQueueInlineError();
          var vres;
          try { vres = JSON.parse(vxhr.responseText); } catch(e) {
            showQueueInlineError('Unexpected response from deck validator.');
            return;
          }
          if (!vres.success) {
            showQueueInlineError('Deck error: ' + (vres.message || 'Could not load deck.'));
            return;
          }
          // Hard block on format violations
          if (vres.formatErrors && vres.formatErrors.length) {
            var formatLabel = (submission.format || 'premier');
            formatLabel = formatLabel.charAt(0).toUpperCase() + formatLabel.slice(1);
            showQueueInlineError(formatLabel + ' format error:\n• ' + vres.formatErrors.join('\n• '));
            return;
          }
          // Build deck summary line
          var summary = '✓ ' + (vres.leaderName || vres.leaderID || '?') +
                        ' / ' + (vres.baseName || vres.baseID || '?') +
                        ' — ' + vres.deckCount + ' cards';
          if (vres.sideboardCount) summary += ', sideboard ' + vres.sideboardCount;
          if (vres.warnings && vres.warnings.length) {
            summary += ' ⚠ ' + vres.warnings.join('; ');
          }
          showQueueInlineInfo(summary);
          // ── Step 2: join the queue now that we know the deck is valid ──────
          doJoinQueue(options, submission);
        };
        vxhr.onerror = function() {
          showQueueInlineError('Could not reach deck validator. Check your connection.');
        };
        vxhr.send('deckLink=' + encodeURIComponent(submission.deckLink) +
                  '&format=' + encodeURIComponent(submission.format || 'premier'));
      }

      function doJoinQueue(options, submission) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', swusimAppBase() + 'APIs/Lobbies/JoinQueue.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        xhr.onload = function() {
          if (xhr.status >= 200 && xhr.status < 300) {
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
            if (response.isRoom) {
              _lobby_id = response.lobbyID;
              DisplayRoomScreen(response.playerID, response.authKey, response.lobbyID, response.inviteCode || '');
              pollRoom(response.playerID, response.authKey, response.lobbyID);
            } else if(response.ready) {
              DisplayMatchFoundPopup(response.playerID, response.gameName, response.authKey);
            } else {
              _lobby_id = response.lobbyID;
              var inviteLink = '';
              if (response.inviteCode) {
                inviteLink = buildPrivateInviteLink(response.inviteCode);
              }
              DisplayWaitingPopup(options.waitingMessage || 'Waiting for opponent… (Esc to cancel)', response.playerID, response.authKey, inviteLink);
              pollLobbyUpdates(response.playerID, response.authKey);
            }
          } else {
            showQueueInlineError('Failed to join queue. Please try again.');
          }
        };

        xhr.onerror = function() {
          showQueueInlineError('Failed to join queue. Please try again.');
        };

        var params = 'deckLink=' + encodeURIComponent(submission.deckLink) + '&game_type=' + encodeURIComponent(submission.gameType);
        params += '&preconstructedDeck=' + encodeURIComponent(submission.preconstructedDeck);
        params += '&rootName=' + encodeURIComponent(rootName);
        params += '&format=' + encodeURIComponent(submission.format || 'premier');
        params += '&queueType=' + encodeURIComponent(submission.queueType || 'bo1');
        if (submission.deckLink2)       params += '&deckLink2=' + encodeURIComponent(submission.deckLink2);
        if (options.createPrivate)      params += '&createPrivate=1';
        if (options.privateInviteCode)  params += '&privateInviteCode=' + encodeURIComponent(options.privateInviteCode);
        xhr.send(params);
      }

      function showQueueInlineError(message) {
        var el = document.getElementById('queue-inline-error');
        if (!el) { StyledAlert(message); return; }
        el.style.color = '#ff6b6b';
        el.style.display = '';
        var lines = (message || 'Unable to join queue.').split('\n');
        el.innerHTML = lines.map(function(l) {
          return '<div>' + l.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</div>';
        }).join('');
      }

      function showQueueInlineInfo(message) {
        var el = document.getElementById('queue-inline-error');
        if (!el) return;
        el.textContent = message;
        el.style.color = '#a8c8a0';
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
        animation.style.borderTop = '16px solid var(--accent)';
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
                StyledAlert('Unable to copy automatically. Please copy the invite link manually.');
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
            xhr.open('POST', swusimAppBase() + 'APIs/Lobbies/LeaveQueue.php', true);
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

      // ── Twin Suns private room ─────────────────────────────────────────────
      var _roomPollTimer = null;
      var _roomEscHandler = null;

      function DisplayRoomScreen(playerID, authKey, lobbyID, inviteCode) {
        var existing = document.getElementById('room-screen');
        if (existing) existing.remove();
        if (_roomEscHandler) { document.removeEventListener('keydown', _roomEscHandler); _roomEscHandler = null; }

        var overlay = document.createElement('div');
        overlay.id = 'room-screen';
        overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.85);display:flex;flex-direction:column;justify-content:center;align-items:center;z-index:1000;padding:24px;box-sizing:border-box;';

        var card = document.createElement('div');
        card.id = 'room-screen-card';
        card.style.cssText = 'width:min(560px,100%);background:rgba(15,25,40,0.95);border:1px solid rgba(201,168,76,0.3);border-radius:14px;padding:24px;color:#f2ead7;';
        card.innerHTML =
          '<h2 style="margin:0 0 12px 0;">Twin Suns Room</h2>' +
          (inviteCode ? ('<div style="margin-bottom:16px;">Share code: <strong id="room-invite-code">' + inviteCode + '</strong> ' +
            '<button id="room-copy-btn" style="margin-left:8px;">Copy Invite Link</button></div>') : '') +
          '<div id="room-roster" style="margin-bottom:16px;"></div>' +
          '<div id="room-deck-swap" style="margin-bottom:16px;">' +
            '<input id="room-deck-input" type="text" placeholder="Paste a Twin Suns deck link" style="width:70%;">' +
            '<button id="room-deck-submit">Change Deck</button>' +
            '<div id="room-deck-msg" style="font-size:12px;margin-top:6px;"></div>' +
          '</div>' +
          '<div>' +
            '<button id="room-start-btn" style="background-color:#2d8a57;" disabled>Start</button>' +
            ' <button id="room-leave-btn">Leave</button>' +
          '</div>' +
          '<div id="room-hint" style="margin-top:10px;font-size:13px;color:#aab6c4;"></div>';

        overlay.appendChild(card);
        document.body.appendChild(overlay);

        if (inviteCode) {
          document.getElementById('room-copy-btn').onclick = function () {
            copyTextToClipboard(buildPrivateInviteLink(inviteCode)).then(function () {
              var btn = document.getElementById('room-copy-btn');
              if (btn) { btn.textContent = 'Copied!'; setTimeout(function () { if (btn) btn.textContent = 'Copy Invite Link'; }, 1200); }
            }).catch(function () { StyledAlert('Unable to copy automatically. Please copy the invite link manually.'); });
          };
        }

        document.getElementById('room-deck-submit').onclick = function () {
          var link = document.getElementById('room-deck-input').value.trim();
          if (!link) return;
          var x = new XMLHttpRequest();
          x.open('POST', swusimAppBase() + 'APIs/Lobbies/UpdateLobbyDeck.php', true);
          x.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
          x.onload = function () {
            var r = {}; try { r = JSON.parse(x.responseText); } catch (e) {}
            var msg = document.getElementById('room-deck-msg');
            if (!msg) return;
            if (r.deckOk) { msg.style.color = '#9ed9b4'; msg.textContent = 'Deck accepted.'; }
            else { msg.style.color = '#ff6b6b'; msg.textContent = r.message || 'Deck rejected.'; }
          };
          x.send('lobbyID=' + encodeURIComponent(lobbyID) + '&playerID=' + encodeURIComponent(playerID) +
                 '&authKey=' + encodeURIComponent(authKey) + '&deckLink=' + encodeURIComponent(link));
        };

        document.getElementById('room-start-btn').onclick = function () {
          var x = new XMLHttpRequest();
          x.open('POST', swusimAppBase() + 'APIs/Lobbies/StartRoom.php', true);
          x.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
          x.onload = function () {
            var r = {}; try { r = JSON.parse(x.responseText); } catch (e) {}
            if (!r.success) { StyledAlert(r.message || 'Could not start the room.'); return; }
            // pollRoom's next tick will see `started` and redirect.
          };
          x.send('lobbyID=' + encodeURIComponent(lobbyID) + '&playerID=' + encodeURIComponent(playerID) + '&authKey=' + encodeURIComponent(authKey));
        };

        document.getElementById('room-leave-btn').onclick = function () {
          if (_roomPollTimer) { clearTimeout(_roomPollTimer); _roomPollTimer = null; }
          overlay.remove();
          var x = new XMLHttpRequest();
          x.open('POST', swusimAppBase() + 'APIs/Lobbies/LeaveQueue.php', true);
          x.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
          x.send('rootName=' + encodeURIComponent(rootName) + '&playerID=' + encodeURIComponent(playerID) +
                 '&lobbyID=' + encodeURIComponent(lobbyID) + '&authKey=' + encodeURIComponent(authKey));
        };

        _roomEscHandler = function (event) {
          if (event.key === 'Escape') document.getElementById('room-leave-btn').click();
        };
        document.addEventListener('keydown', _roomEscHandler);

        renderRoomRoster(playerID, { roster: [], numPlayers: 0, maxPlayers: 4 });
      }

      function renderRoomRoster(myPlayerID, data) {
        var el = document.getElementById('room-roster');
        if (!el) return;
        var rows = [];
        var roster = data.roster || [];
        for (var i = 1; i <= (data.maxPlayers || 4); i++) {
          var seatEntry = null;
          for (var j = 0; j < roster.length; j++) { if (roster[j].seat === i) { seatEntry = roster[j]; break; } }
          if (seatEntry) {
            rows.push('<div>P' + i + (seatEntry.isHost ? ' (host)' : '') + (seatEntry.seat === myPlayerID ? ' (you)' : '') +
              ' — ' + (seatEntry.deckOk ? 'deck ✓' : 'deck missing/invalid') + '</div>');
          } else {
            rows.push('<div style="color:#aab6c4;">P' + i + ' — waiting…</div>');
          }
        }
        el.innerHTML = rows.join('');

        var isHost = false;
        for (var k = 0; k < roster.length; k++) { if (roster[k].seat === myPlayerID && roster[k].isHost) isHost = true; }
        var startBtn = document.getElementById('room-start-btn');
        var hint = document.getElementById('room-hint');
        if (startBtn) {
          startBtn.style.display = isHost ? '' : 'none';
          var allOk = roster.length >= 3 && roster.every(function (r) { return r.deckOk; });
          startBtn.disabled = !allOk;
        }
        if (hint) hint.textContent = isHost
          ? (roster.length < 3 ? 'Need 3+ players, all with a valid deck.' : (roster.every(function (r) { return r.deckOk; }) ? '' : 'All present players need a valid deck.'))
          : 'Waiting for host to start.';
      }

      function pollRoom(playerID, authKey, lobbyID) {
        var x = new XMLHttpRequest();
        x.open('POST', swusimAppBase() + 'APIs/Lobbies/PollLobbyUpdates.php', true);
        x.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        x.onload = function () {
          var r = {}; try { r = JSON.parse(x.responseText); } catch (e) {}
          if (r.started && r.gameName) {
            var overlay = document.getElementById('room-screen');
            if (overlay) overlay.remove();
            if (_roomEscHandler) { document.removeEventListener('keydown', _roomEscHandler); _roomEscHandler = null; }
            DisplayMatchFoundPopup(playerID, r.gameName, authKey);
            return;
          }
          if (r.success && r.isRoom) renderRoomRoster(playerID, r);
          _roomPollTimer = setTimeout(function () { pollRoom(playerID, authKey, lobbyID); }, 1500);
        };
        x.onerror = function () {
          _roomPollTimer = setTimeout(function () { pollRoom(playerID, authKey, lobbyID); }, 1500);
        };
        x.send('lobbyID=' + encodeURIComponent(lobbyID) + '&rootName=' + encodeURIComponent(rootName) +
               '&playerID=' + encodeURIComponent(playerID) + '&authKey=' + encodeURIComponent(authKey));
      }

      function DisplayMatchFoundPopup(playerID, gameName, authKey) {
        // Persist the seat authKey so NextTurn.php / ProcessInput.php can authenticate
        // this browser as the player. NextTurn.php emits HTML before session_start(),
        // so the PHP session can't carry the key — the URL param + lastAuthKey cookie do.
        if (authKey && ['1','2','3','4'].indexOf(String(playerID)) >= 0) {
          try {
            document.cookie = 'lastAuthKey=' + encodeURIComponent(authKey) + '; max-age=' + (30 * 24 * 60 * 60) + '; path=/; SameSite=Lax';
          } catch (e) {}
        }
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
            0%, 100% { text-shadow: 0 0 20px rgba(var(--accent-rgb), 0.8), 0 0 40px rgba(var(--accent-rgb), 0.4); }
            50% { text-shadow: 0 0 30px rgba(var(--accent-rgb), 1), 0 0 60px rgba(var(--accent-rgb), 0.6); }
          }
          @keyframes pulseGlowIcon {
            0%, 100% { filter: drop-shadow(0 0 12px rgba(255, 255, 255, 0.35)) drop-shadow(0 0 24px rgba(255, 255, 255, 0.2)); }
            50% { filter: drop-shadow(0 0 18px rgba(255, 255, 255, 0.55)) drop-shadow(0 0 36px rgba(255, 255, 255, 0.3)); }
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

        var iconElement = document.createElement('img');
        iconElement.src = '/TCGEngine/Assets/Icons/swusim-all-aspects.webp';
        iconElement.alt = 'Match Found';
        iconElement.style.cssText = `
          width: 213px;
          height: 160px;
          margin-bottom: 20px;
          filter: drop-shadow(0 0 12px rgba(255, 255, 255, 0.35)) drop-shadow(0 0 24px rgba(255, 255, 255, 0.2));
          animation: pulseGlowIcon 1.5s ease-in-out infinite;
        `;

        var titleElement = document.createElement('h1');
        titleElement.textContent = 'Match Found!';
        titleElement.style.cssText = `
          color: var(--accent);
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

        matchPopup.appendChild(iconElement);
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
                    var _redirUrl = swusimAppBase() + `NextTurn.php?playerID=${playerID}&gameName=${gameName}&folderPath=${encodeURIComponent(rootName)}&fromMatch=1`;
                    if (authKey) _redirUrl += '&authKey=' + encodeURIComponent(authKey);
                    window.location.href = _redirUrl;
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
        var xhr = new XMLHttpRequest();
        xhr.open('GET', swusimAppBase() + 'APIs/Lobbies/GetActiveGames.php?rootName=' + encodeURIComponent(rootName), true);
        xhr.responseType = 'json';

        xhr.onload = function() {
          if (xhr.status >= 200 && xhr.status < 300) {
          var data = xhr.response;

          if (data.data && Array.isArray(data.data)) {
            var totalCount = (typeof data.totalCount === 'number') ? data.totalCount : data.data.length;
            gameCountElement.textContent = totalCount;
          } else {
            gameCountElement.textContent = '0';
          }
          } else {
          console.error('Error fetching open games:', xhr.statusText);
          gameCountElement.textContent = '0';
          }
        };

        xhr.onerror = function() {
          console.error('Error fetching open games:', xhr.statusText);
          gameCountElement.textContent = '0';
        };

        xhr.send();
      }
      function pollLobbyUpdates(playerID, authKey) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', swusimAppBase() + 'APIs/Lobbies/PollLobbyUpdates.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        xhr.onload = function() {
          if (xhr.status >= 200 && xhr.status < 300) {
            var response = JSON.parse(xhr.responseText);
            if (response.ready) {
              // Close waiting popup and show match found popup
              var waitingPopup = document.getElementById('waiting-popup');
              if (waitingPopup) waitingPopup.remove();
              if (_waitingEscHandler) {
                document.removeEventListener('keydown', _waitingEscHandler);
                _waitingEscHandler = null;
              }
              // PollLobbyUpdates.php validates but does not echo the authKey back,
              // so reuse the seat authKey this client already holds.
              DisplayMatchFoundPopup(response.playerID, response.gameName, authKey);
            } else {
              // Continue polling if the lobby is not ready
              pollLobbyUpdates(playerID, authKey);
            }
          } else {
            // Non-2xx (e.g. 500 under load): xhr.onerror does NOT fire for HTTP error statuses, so
            // reschedule here too, else a single failed poll strands the player in the queue forever.
            console.error('Error polling lobby updates:', xhr.statusText);
            setTimeout(function() { pollLobbyUpdates(playerID, authKey); }, 5000);
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
        refreshOpenGames();
      });
    </script>

<?php
include_once __DIR__ . '/Disclaimer.php';
?>
