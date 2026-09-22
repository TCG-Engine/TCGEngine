<?php
require_once __DIR__ . '/../../Render/AssetVersion.php';   // _VersionAsset() — ?v=<filemtime> cache busting
// Use __DIR__-relative includes (matching the SWUDeck pilot): this page is reached via the
// SharedUI/MainMenu.php pointer (which include()s it), so the cwd is SharedUI/, not this dir.
// Bare './'/'../../../' paths resolved against the wrong cwd → missing-file warnings AND silently
// pulled the ROOT SharedUI/MenuBar.php + Header.php (wrong chrome) instead of the SWUSim ones.
include_once __DIR__ . '/MenuBar.php';
include_once __DIR__ . '/../../../AccountFiles/AccountSessionAPI.php';
include_once __DIR__ . '/../../../Database/ConnectionManager.php';
include_once __DIR__ . '/../../../SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
include_once __DIR__ . '/../../../AppCore/SWU/Formats.php';
include_once __DIR__ . '/../../../SWUSim/Mod/DevGate.php';   // SWUBotPracticeAllowed() — the Arenabot access gate
require_once __DIR__ . '/../../Render/DeckLibrary.php';

include_once __DIR__ . '/Header.php';

$swuLoggedIn = isset($_SESSION['userid']);
// The game-setup menu is a VIEW over the format registry (docs/superpowers/specs/2026-09-16-swusim-format-menu-design.md):
// game type → opponent / players / mode → card pool. SWUMenuTreeFor() applies this viewer's access: Arenabot only where
// SWUBotPracticeAllowed() (APIs/Lobbies/JoinQueue.php enforces the same gate). Logging in no longer changes the tree
// (owner, 2026-09-21) — guests play every format and lose only chat. The FULL tree rides along for invites, whose host
// may have picked a path this viewer could not.
$swuMenuTree = SWUMenuTreeFor(SWUBotPracticeAllowed());
$swuMenuTreeFull = SWUMenuTree();
$swuQueueTypes = function_exists('SWUQueueTypeDefinitions') ? SWUQueueTypeDefinitions() : ['bo1' => ['displayName' => 'Best of 1']];
$swuSiteDef = require __DIR__ . '/SiteDef.php';
$swuDeckLibraryConfig = DeckLibraryConfigFromSiteDef($swuSiteDef);
?>
<?php
// Inline icons for the menu (decorative: every one sits next to a text label, so aria-hidden). currentColor, so the
// CSS colours them per button. A function rather than a sprite so the page stays one request.
function SWUMenuIcon(string $name): string {
    $paths = [
        'users'   => '<circle cx="9" cy="8" r="3.2"/><path d="M2.5 19c.6-3.6 3.2-5.6 6.5-5.6s5.9 2 6.5 5.6z"/><circle cx="17" cy="9" r="2.6"/><path d="M16.2 13.6c2.9-.3 5 1.5 5.4 5.4h-4.5c-.2-2-.5-3.7-.9-5.4z"/>',
        'save'    => '<path d="M4 3h13l3 3v15H4z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M7 3h9v6H7zM7 14h10v7H7z"/>',
        'refresh' => '<path d="M19.5 12a7.5 7.5 0 1 1-2.2-5.3" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/><path d="M20.5 3.5v6h-6z"/>',
        'bolt'    => '<path d="M13.5 2 4.5 13.5h6l-1.5 8.5 9.5-12h-6.2z"/>',
        'next'    => '<path d="M14 4h6v6M20 4l-9 9M18 14v6H4V6h6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
        'play'    => '<path d="M7 4.5v15l12-7.5z"/>',
        'join'    => '<path d="M10 4h9v16h-9" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M3 12h11M10.5 8l4 4-4 4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
    ];
    return '<svg class="swu-ico" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">' . ($paths[$name] ?? '') . '</svg>';
}
$swuLogo = strval($swuSiteDef['branding']['logo'] ?? '');
?>
<link rel="stylesheet" href="<?php echo _VersionAsset('/TCGEngine/SharedUI/Sites/SWUSim/css/swusim-menu.css'); ?>">
<div class="row-wrapper swu-menu-grid">
  <!-- Games in Progress (left): public SWUSim matches anyone can spectate, one chip per game — leader vs leader (a
       leader/leader/base stack per seat in Twin Suns / Team Suns) and a Spectate button. Data: SWUSim/PublicGames.php;
       rendered by swuRenderPublicGames(). Layout modelled on the owner's reference (2026-09-21). -->
  <div class="card ga-glass-card swu-panel swu-active-card">
    <div class="swu-panel-head">
      <h2 class="swu-panel-title">Games in Progress</h2>
      <span class="swu-games-count" id="active-game-count" aria-label="Public games in progress">0</span>
      <button type="button" class="swu-icon-btn" onclick="refreshOpenGames()" title="Refresh" aria-label="Refresh games in progress"><?php echo SWUMenuIcon('refresh'); ?></button>
    </div>
    <select id="swu-games-filter" class="swu-queue-select" aria-label="Filter by format">
      <option value="">Filter by Format</option>
    </select>
    <hr class="swu-hr swu-games-rule">
    <div id="active-games-list" class="swu-games-list" aria-live="polite"></div>
    <!-- Empty state, shown only when no public game (or none in the chosen format) is running. -->
    <div class="swu-active-empty" id="swu-active-empty">
      <span class="swu-active-empty__icon"><?php echo SWUMenuIcon('users'); ?></span>
      <div class="swu-active-empty__title" id="swu-active-empty-title">No games in progress</div>
      <div class="swu-active-empty__text" id="swu-active-empty-text">Be the first to challenge an opponent in the arena!</div>
      <button type="button" class="swu-refresh-btn" onclick="refreshOpenGames()"><?php echo SWUMenuIcon('refresh'); ?><span>Refresh</span></button>
    </div>
  </div>

  <!-- Create a New Game (middle) -->
  <div class="card ga-glass-card swu-panel swu-queue-card">
    <div class="swu-panel-head">
      <h2 class="swu-panel-title">Create a New Game</h2>
      <span class="swu-panel-rule" aria-hidden="true"></span>
      <span class="swu-panel-kicker" aria-hidden="true"></span>
    </div>
    <div>
      <div class="swu-tabs">
        <button type="button" id="tab-link" class="swu-tab is-active" onclick="switchDeckTab('link')">Deck Link</button>
        <button type="button" id="tab-text" class="swu-tab" onclick="switchDeckTab('text')">Free Text</button>
      </div>
      <div id="deck-input-link">
        <!-- The supported-sites list lives in an info tooltip (owner, 2026-09-21). The bubble is positioned against the
             whole ROW, not the icon, so it always fits the card on a phone. Opens on hover, keyboard focus, or a tap
             (WebKit does not focus a button on click, so the tap toggles .is-open in JS); Escape or a click elsewhere
             closes it. -->
        <div class="swu-label-row">
          <label for="deck-link" class="swu-label">Paste a deck link:</label>
          <button type="button" class="swu-info-tip" id="deck-link-sites-btn" aria-label="Supported deck links"
                  aria-describedby="deck-link-sites" aria-expanded="false">i</button>
          <span class="swu-info-tip__bubble" id="deck-link-sites" role="tooltip">
            <strong>Supported deck links:</strong> SWUStats, SWUDB, melee.gg, SWUBase, Protect the Pod, SWU Card Hub, SWUForge, SWU Meta Stats, SW-Unlimited-DB
          </span>
        </div>
        <input type="text" id="deck-link" name="deck_link" class="swu-input" placeholder="https://swustats.net/deck/...">
      </div>
      <div id="deck-input-text" style="display: none;">
        <label for="deck-text" class="swu-label">Paste deck list (e.g. from SWUDB or SWUDeck):</label>
        <textarea id="deck-text" name="deck_text" class="swu-input swu-input--mono" rows="12" placeholder="# Leader&#10;1 Luke Skywalker, Faithful Friend&#10;&#10;# Base&#10;1 Echo Base&#10;&#10;# Main Deck&#10;3 Alliance X-Wing&#10;..."></textarea>
      </div>
      <!-- Hotseat / Arenabot: a second deck link for Player 2 (revealed only for those formats; the
           label and placeholder switch in applyFormatUI). Arenabot may leave it empty: the bot then
           plays the host's own list (APIs/Lobbies/JoinQueue.php). -->
      <div id="swu-deck2-group" class="swu-field-block" style="display: none;">
        <label id="swu-deck2-label" for="swu-deck2-input" class="swu-label">Player 2 deck link (Hotseat):</label>
        <input type="text" id="swu-deck2-input" class="swu-input" placeholder="Second deck link">
      </div>
      <!-- Arenabot: the bot's Play Style (revealed only for Arenabot). Sent as botStyle; the game
           stores SWUBotProfile = heuristic-<style> (SWUSim/CreateGame.php). -->
      <div id="swu-botstyle-group" class="swu-field-block" style="display: none;">
        <label for="swu-botstyle-select" class="swu-field-label">Bot play style:</label>
        <select id="swu-botstyle-select" class="swu-queue-select">
          <?php
            // The five bot archetypes (AppCore-side registry: SWUSim/Custom/BotArchetypes.php). Five rather than
            // three because a picker should not be forced to choose an extreme (owner, 2026-09-17).
            foreach (['hyperaggro' => 'Hyper Aggro', 'softaggro' => 'Soft Aggro', 'midrange' => 'Midrange',
                      'softcontrol' => 'Soft Control', 'hardcontrol' => 'Hard Control'] as $sid => $slabel):
          ?>
          <option value="<?php echo htmlspecialchars($sid, ENT_QUOTES); ?>"<?php echo $sid === 'midrange' ? ' selected' : ''; ?>><?php echo htmlspecialchars($slabel, ENT_QUOTES); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="swu-field-row">
        <div class="swu-field">
          <label for="swu-gametype-select" class="swu-field-label">Game type:</label>
          <select id="swu-gametype-select" class="swu-queue-select"></select>
        </div>
        <div class="swu-field">
          <label id="swu-second-label" for="swu-second-select" class="swu-field-label">Opponent:</label>
          <select id="swu-second-select" class="swu-queue-select"></select>
        </div>
        <div id="swu-pool-group" class="swu-field">
          <label for="swu-pool-select" class="swu-field-label">Card pool:</label>
          <select id="swu-pool-select" class="swu-queue-select"></select>
        </div>
      </div>
      <!-- ⚠ The STORED format and card pool. The three dropdowns above write these; everything else reads them — the request,
           the Bo1 lock, and SharedUI/js/private-invite.js, which sets every [id$="-format-select"] to the host's format id.
           So this select must keep its id and stay hidden, and the visible dropdowns' ids must NOT end in -format-select. -->
      <select id="swu-format-select" style="display: none;" aria-hidden="true" tabindex="-1"></select>
      <input type="hidden" id="swu-cardpool-input" value="">
      <div class="swu-field-row">
        <div class="swu-field">
          <label for="swu-queuetype-select" class="swu-field-label">Match Type:</label>
          <select id="swu-queuetype-select" class="swu-queue-select">
            <?php foreach ($swuQueueTypes as $qid => $qdef): ?>
            <option value="<?php echo htmlspecialchars($qid, ENT_QUOTES); ?>"<?php echo $qid === 'bo1' ? ' selected' : ''; ?>><?php echo htmlspecialchars($qdef['displayName'] ?? $qid, ENT_QUOTES); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <!-- Colour-coding these actions sets the BUTTON TOKENS (--btn-rim / --btn-fill, in swusim-menu.css), never a
           flat `background-color`. Under a chamfer theme the element box is deliberately transparent and the shape is
           drawn by the ::before rim + ::after fill pseudos, which are clip-path'd to the cut corners; an element
           background is NOT clipped, so it paints a full rectangle that shows through as a solid triangle in each chamfer.
           ⚠ Labels live in .swu-btn-label: applyFormatUI rewrites the text through swuSetBtnLabel(), which keeps the icon
           (setting the button's textContent would wipe it). -->
      <div class="swu-actions">
        <!-- Public matchmaking (open since 2026-09-16): applyFormatUI shows this for a PvP card pool whose tree entry says
             publicQueue — never Arenabot or 1P Mode. The server enforces the same rule (JoinQueue.php). -->
        <button type="button" id="join-queue-btn" class="swu-action swu-action--primary" onclick="joinQueue()"><?php echo SWUMenuIcon('users'); ?><span class="swu-btn-label">Join Queue</span></button>
        <!-- Solo / local modes (Goldfish, Hotseat, Arenabot) are NOT matchmade — JoinQueue.php creates the
             game immediately; this button is their own entry point. Hidden unless a mode format is selected
             (see applyFormatUI). -->
        <button type="button" id="start-solo-btn" class="swu-action swu-action--primary" onclick="startSoloGame()" style="display: none;"><?php echo SWUMenuIcon('play'); ?><span class="swu-btn-label">Start 1P Game</span></button>
        <button type="button" class="swu-action" onclick="saveCurrentDeck()" title="Save this deck link to your library"><?php echo SWUMenuIcon('save'); ?><span class="swu-btn-label">Save Deck</span></button>
        <!-- Hidden when the URL carries a privateInvite code: that visitor is JOINING someone else's
             invite, so offering "Create Private Room" right next to "Join Private Invite" is ambiguous
             (and creating one would silently abandon the invite they followed). See
             initializePrivateInviteFromUrl. -->
        <button type="button" id="create-private-game-btn" class="swu-action" onclick="createPrivateGame()"><?php echo SWUMenuIcon('users'); ?><span class="swu-btn-label">Create Private Room</span></button>
        <button type="button" id="join-private-invite-btn" class="swu-action swu-action--primary" onclick="joinPrivateInvite()" style="display: none;"><?php echo SWUMenuIcon('join'); ?><span class="swu-btn-label">Join Private Invite</span></button>
      </div>
      <div id="queue-inline-error" class="swu-note" style="display: none;"></div>
      <div id="private-invite-notice" class="swu-note" style="display: none;"></div>
      <?php if (!$swuLoggedIn): ?>
      <!-- Guest note. Guests play every format (owner, 2026-09-21); the one thing an account adds is in-game chat,
           which SubmitChat.php refuses for a logged-out SWUSim sender. Server-rendered: the logged-out state is known
           at render time, so it never flashes on a logged-in page. -->
      <div id="guest-format-notice" class="swu-note">
        Playing as a guest —
        <a href="/TCGEngine/SharedUI/LoginPage.php">log in</a>
        to use in-game chat.
      </div>
      <?php endif; ?>
      <?php
        if (isset($_SESSION['userid'])) {
            echo "<div class='saved-decks-panel swu-saved-decks'><h3 class='swu-section-label'>Saved Decks</h3>";
            // Default (no action buttons): the dropdown only loads a deck into the queue box.
            // Managing saved decks (favorite/rename/delete) lives on the Profile page.
            echo RenderDeckLibrary((int)$_SESSION['userid'], $swuDeckLibraryConfig);
            echo "</div>";
        }
      ?>
    </div>
  </div>

  <!-- Welcome + Replays (right, tabbed) -->
  <div class="card ga-glass-card swu-panel swu-info-card">
    <div class="ga-info-tabs" role="tablist" aria-label="Petranaki information">
      <button type="button" id="ga-info-tab-welcome" class="ga-info-tab swu-tab is-active" onclick="switchInfoTab('welcome')" role="tab" aria-selected="true" aria-controls="ga-info-panel-welcome">Welcome</button>
      <button type="button" id="ga-info-tab-replays" class="ga-info-tab swu-tab" onclick="switchInfoTab('replays')" role="tab" aria-selected="false" aria-controls="ga-info-panel-replays">Replays</button>
    </div>
    <div id="ga-info-panel-welcome" class="ga-info-panel is-active" role="tabpanel" aria-labelledby="ga-info-tab-welcome">
      <div class="swu-welcome">
        <div class="swu-welcome__body">
          <h2 class="swu-welcome__title">Welcome to Petranaki Arena!</h2>
          <p class="login-message swu-welcome__text">Petranaki Arena is a fan-made online simulator for Star Wars: Unlimited.</p>
        </div>        
      </div>
      <hr class="swu-hr">

      <!-- Did you know? -->
      <div id="did-you-know-box" class="swu-dyk">
        <div class="swu-dyk__head"><?php echo SWUMenuIcon('bolt'); ?><span>Did you know?</span></div>
        <p id="did-you-know-text"></p>
        <button type="button" class="swu-icon-btn swu-dyk__next" onclick="cycleDidYouKnow()" title="Next tip" aria-label="Next tip"><?php echo SWUMenuIcon('next'); ?></button>
      </div>

      <!-- Quick-reference hotkeys -->
      <div>
        <div class="swu-section-label">Quick Reference</div>
        <div id="hotkey-list" class="swu-hotkeys"></div>
      </div>
      <div class="swu-flourish" aria-hidden="true"><span class="swu-flourish__gem">◇</span></div>
    </div>
    <div id="ga-info-panel-replays" class="ga-info-panel" role="tabpanel" aria-labelledby="ga-info-tab-replays">
      <h2 class="swu-welcome__title">Your Replays</h2>
      <p class="swu-welcome__text">Saved in this browser. Use the <strong>Save Replay</strong> button on the end-of-game screen to add one here.</p>
      <div id="match-replay-menu-list" class="swu-replay-list"></div>
    </div><!-- end replays panel -->
  </div><!-- end info card -->
</div>

<script src="<?php echo _VersionAsset('/TCGEngine/Core/MatchReplayClient.js'); ?>"></script>
<script src="<?php echo _VersionAsset('/TCGEngine/SharedUI/js/private-invite.js'); ?>"></script>

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


<script>
  var _didYouKnowTips = [
    { key: 'u', label: 'Undo your most recent action' },
    { key: 'Space', label: 'Pass an optional decision when available' },
    { text: 'Hover a card on the field to see its full text.' },
    { text: 'You can paste a deck link directly from SWUStats, SWUDB, melee.gg and most other SWU deck builders.' },
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
  // The game-setup menu tree (AppCore/SWU/Formats.php SWUMenuTreeFor / SWUMenuTree); see the menu section further down.
  var SWU_MENU = {
    tree: <?php echo json_encode($swuMenuTree, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
    full: <?php echo json_encode($swuMenuTreeFull, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
    // Arenabot → Premier for everyone (owner, 2026-09-21). If the Arenabot gate is ever closed, swuSelectFormat
    // finds no such leaf and the menu falls back to its first path (PvP → Premier).
    defaultFormat: 'botpractice',
    defaultPool: 'premier'
  };
  var _waitingEscHandler = null;

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
      // Info tooltips (.swu-info-tip): hover and keyboard focus are pure CSS; a tap toggles .is-open, and Escape or a
      // click anywhere else closes every open one.
      (function () {
        function closeAll(except) {
          document.querySelectorAll('.swu-info-tip.is-open').forEach(function (b) {
            if (b === except) return;
            b.classList.remove('is-open'); b.setAttribute('aria-expanded', 'false');
          });
        }
        document.addEventListener('click', function (e) {
          var btn = e.target.closest ? e.target.closest('.swu-info-tip') : null;
          closeAll(btn);
          if (!btn) return;
          var open = !btn.classList.contains('is-open');
          btn.classList.toggle('is-open', open);
          btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeAll(null); });
      })();
      // Menu action buttons carry an icon + <span class="swu-btn-label">; rewriting the button's own textContent would
      // wipe the icon, so every label change goes through here.
      function swuSetBtnLabel(btn, text) {
        if (!btn) return;
        var label = btn.querySelector('.swu-btn-label');
        if (label) label.textContent = text; else btn.textContent = text;
      }
      function switchDeckTab(tab) {
        var isLink = tab === 'link';
        document.getElementById('deck-input-link').style.display = isLink ? '' : 'none';
        document.getElementById('deck-input-text').style.display = isLink ? 'none' : '';
        document.getElementById('tab-link').classList.toggle('is-active', isLink);
        document.getElementById('tab-text').classList.toggle('is-active', !isLink);
        try { localStorage.setItem('swu_deck_tab', tab); } catch(e) {}
      }

      (function() {
        var saved = '';
        try { saved = localStorage.getItem('swu_deck_tab') || ''; } catch(e) {}
        if (saved === 'text') switchDeckTab('text');
      })();

      // Private-invite lobby UI lives in the shared module (SharedUI/js/private-invite.js) so every
      // sim behaves identically: reveal Join Private Invite, hide the competing Create Private Room /
      // Join Queue actions, disable the format + match-type selects (the server adopts the HOST
      // lobby's settings for an invite join), and RE-APPLY after applyFormatUI re-runs.
      function initializePrivateInviteFromUrl() {
        try {
          if (window.PrivateInviteUI && !window.PrivateInviteUI._swuMenuWrapped) {
            // The shared module applies an invite by setting #swu-format-select to the host's format — without firing a
            // change event, and a second time when its async lookup returns. Wrap its public enforce() BEFORE init(), so
            // both applies also re-sync the three visible dropdowns. (init() registers api.enforce, so it registers this
            // wrapper; the module itself is unchanged.)
            var baseEnforce = window.PrivateInviteUI.enforce;
            window.PrivateInviteUI.enforce = function () {
              baseEnforce.apply(this, arguments);
              swuSyncMenuFromStoredFormat();
            };
            window.PrivateInviteUI._swuMenuWrapped = true;
          }
          _privateInviteCode = window.PrivateInviteUI ? window.PrivateInviteUI.init({ rootName: 'SWUSim' }) : '';
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
        var botStyleEl = document.getElementById('swu-botstyle-select');
        var botStyle = (format === 'botpractice' && botStyleEl) ? botStyleEl.value : '';
        var cardPoolEl = document.getElementById('swu-cardpool-input');
        var cardPool = (format === 'botpractice' && cardPoolEl) ? cardPoolEl.value : '';

        return {
          preconstructedDeck: preconstructedDeck,
          deckLink: deckLink,
          deckLink2: deckLink2,
          botStyle: botStyle,
          cardPool: cardPool,
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

      // Goldfish / Hotseat: same submission path, but the server creates the game immediately
      // (no matchmaking), so this never actually waits — the response comes back ready.
      function startSoloGame() {
        submitQueueJoin({
          waitingMessage: 'Starting game... (Esc to cancel)'
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

      // ── Game-setup menu: game type → opponent / players / mode → card pool ────────────────────────────────────────────
      // docs/superpowers/specs/2026-09-16-swusim-format-menu-design.md. The dropdowns are a VIEW: they write the stored
      // format (#swu-format-select) and card pool (#swu-cardpool-input), and applyFormatUI below reads only those.
      var swuMenuTree = SWU_MENU.tree;
      function swuMenuEl(id) { return document.getElementById(id); }
      function swuFindById(list, id) { for (var i = 0; i < list.length; i++) { if (list[i].id === id) return list[i]; } return null; }
      // Replace a select's options, keeping its current value when the new list still offers it.
      function swuSetOptions(sel, items) {
        if (!sel) return;
        var keep = sel.value;
        sel.innerHTML = '';
        items.forEach(function (it) {
          var o = document.createElement('option'); o.value = it.value; o.textContent = it.label; sel.appendChild(o);
        });
        if (items.some(function (it) { return it.value === keep; })) sel.value = keep;
      }
      function swuCurrentGameType() {
        var gt = swuMenuEl('swu-gametype-select');
        return swuFindById(swuMenuTree, gt ? gt.value : '') || swuMenuTree[0];
      }
      function swuCurrentOption() {
        var t = swuCurrentGameType(); var s = swuMenuEl('swu-second-select');
        return t ? (swuFindById(t.options, s ? s.value : '') || t.options[0]) : null;
      }
      // Refill the lower dropdowns for the current upper choices. A card pool the new branch also offers is kept.
      function swuFillMenu() {
        swuSetOptions(swuMenuEl('swu-gametype-select'), swuMenuTree.map(function (t) { return { value: t.id, label: t.label }; }));
        var t = swuCurrentGameType();
        if (!t) return;
        swuSetOptions(swuMenuEl('swu-second-select'), t.options.map(function (o) { return { value: o.id, label: o.label }; }));
        var lbl = swuMenuEl('swu-second-label'); if (lbl) lbl.textContent = t.secondLabel + ':';
        var opt = swuCurrentOption();
        var pools = (opt && opt.pools) || [];
        swuSetOptions(swuMenuEl('swu-pool-select'), pools.map(function (p) { return { value: p.format, label: p.label }; }));
        var pg = swuMenuEl('swu-pool-group'); if (pg) pg.style.display = pools.length ? '' : 'none';
      }
      // Write the stored format and card pool from the dropdowns — the same rule as SWUMenuLeaves() in AppCore/SWU/Formats.php:
      // an option with its own format stores it and plays the chosen pool; otherwise the chosen pool is the format.
      function swuWriteStored() {
        var opt = swuCurrentOption();
        var pools = (opt && opt.pools) || [];
        var poolSel = swuMenuEl('swu-pool-select');
        var pool = (pools.length && poolSel) ? poolSel.value : '';
        var format = opt ? (opt.format || pool) : '';
        var fmt = swuMenuEl('swu-format-select');
        if (fmt && format) {
          if (!Array.prototype.some.call(fmt.options, function (o) { return o.value === format; })) {
            var o = document.createElement('option'); o.value = format; o.textContent = format; fmt.appendChild(o);
          }
          fmt.value = format;
        }
        var cp = swuMenuEl('swu-cardpool-input'); if (cp) cp.value = pools.length ? pool : 'open';
      }
      function swuOnMenuChange() { swuFillMenu(); swuWriteStored(); applyFormatUI(); }
      // Point the dropdowns at a stored format (and, for Arenabot, a card pool). Used by invites and the browser harnesses.
      // useFull searches the FULL tree and makes it the menu's source: an invite's host may have picked a path this viewer's
      // own menu does not offer. Returns false, changing nothing, when no path matches.
      function swuSelectFormat(formatId, cardPool, useFull) {
        var source = useFull ? SWU_MENU.full : SWU_MENU.tree;
        for (var i = 0; i < source.length; i++) {
          var t = source[i];
          for (var j = 0; j < t.options.length; j++) {
            var o = t.options[j];
            var pools = o.pools || [];
            var pool = null;
            if (o.format) {
              if (o.format !== formatId) continue;
              if (pools.length) {
                pool = pools.filter(function (p) { return p.format === (cardPool || 'open'); })[0] || null;
                if (!pool) continue;
              }
            } else {
              pool = pools.filter(function (p) { return p.format === formatId; })[0] || null;
              if (!pool) continue;
            }
            swuMenuTree = source;
            swuSetOptions(swuMenuEl('swu-gametype-select'), source.map(function (x) { return { value: x.id, label: x.label }; }));
            swuMenuEl('swu-gametype-select').value = t.id;
            swuFillMenu();
            swuMenuEl('swu-second-select').value = o.id;
            swuFillMenu();
            if (pool) swuMenuEl('swu-pool-select').value = pool.format;
            swuWriteStored();
            applyFormatUI();
            return true;
          }
        }
        return false;
      }
      // Invites: mirror the stored format into the dropdowns and lock them (the shared module locks only the stored select).
      function swuSyncMenuFromStoredFormat() {
        var joining = !!(window.PrivateInviteUI && window.PrivateInviteUI.code);
        var fmt = swuMenuEl('swu-format-select'); var cp = swuMenuEl('swu-cardpool-input');
        if (fmt && fmt.value) swuSelectFormat(fmt.value, cp ? cp.value : '', joining);
        ['swu-gametype-select', 'swu-second-select', 'swu-pool-select'].forEach(function (id) {
          var el = swuMenuEl(id); if (el) el.disabled = joining;
        });
      }
      // Format-dependent UI, read from the STORED values: Hotseat and Arenabot reveal a 2nd deck input, Arenabot its Play
      // Style select. The local modes and the whole Twin Suns family are Bo1 with no public queue (owner, 2026-09-16:
      // "Twin Suns Bo3 was not needed") — the family is decided by the menu branch, not by listing ids.
      // Join Queue is offered per card pool: SWUMenuTree() marks each pool with publicQueue (SWUFormatAllowsPublicQueue on
      // the server), which is true only for PvP pools. docs/superpowers/specs/2026-09-16-swusim-public-queues-design.md §3.
      function swuCurrentPoolQueues() {
        var opt = swuCurrentOption();
        var pools = (opt && opt.pools) || [];
        var poolSel = swuMenuEl('swu-pool-select');
        var cur = poolSel ? poolSel.value : '';
        for (var i = 0; i < pools.length; i++) { if (pools[i].format === cur) return pools[i].publicQueue === true; }
        return false;
      }
      function applyFormatUI() {
        var fmt = swuMenuEl('swu-format-select');
        if (!fmt) return;
        var gameTypeEl = swuMenuEl('swu-gametype-select');
        var isArenabot = (fmt.value === 'botpractice');
        var isMode = (fmt.value === 'goldfish' || fmt.value === 'hotseat' || isArenabot);
        var isTwinSunsFamily = !!gameTypeEl && gameTypeEl.value === 'twinsuns';
        var g = swuMenuEl('swu-deck2-group');
        if (g) g.style.display = (fmt.value === 'hotseat' || isArenabot) ? '' : 'none';
        var d2Label = swuMenuEl('swu-deck2-label');
        if (d2Label) d2Label.textContent = isArenabot ? 'Bot deck link:' : 'Player 2 deck link (Hotseat):';
        var d2Input = swuMenuEl('swu-deck2-input');
        if (d2Input) d2Input.placeholder = isArenabot ? 'Leave empty for the bot to play your deck' : 'Second deck link';
        var bs = swuMenuEl('swu-botstyle-group');
        if (bs) bs.style.display = isArenabot ? '' : 'none';
        // Followed someone else's invite link? Then the ONLY sensible action is Join Private Invite. This gate lives here as
        // well as in the shared module, because this function owns these controls and re-runs on every menu change.
        var joiningInvite = !!_privateInviteCode || !!(window.PrivateInviteUI && window.PrivateInviteUI.code);
        var qt = swuMenuEl('swu-queuetype-select');
        if (qt) {
          if (joiningInvite) { qt.disabled = true; }
          else if (isMode || isTwinSunsFamily) { qt.value = 'bo1'; qt.disabled = true; }
          else { qt.disabled = false; }
        }
        var joinBtn = swuMenuEl('join-queue-btn');
        var createBtn = swuMenuEl('create-private-game-btn');
        var soloBtn = swuMenuEl('start-solo-btn');
        if (joinBtn) joinBtn.style.display = (joiningInvite || !swuCurrentPoolQueues()) ? 'none' : '';
        if (createBtn) {
          // One label for every format: EVERY private lobby is a room (WaitingRoom.php).
          createBtn.style.display = (joiningInvite || isMode) ? 'none' : '';
          swuSetBtnLabel(createBtn, 'Create Private Room');
        }
        if (soloBtn) {
          soloBtn.style.display = isMode ? '' : 'none';
          swuSetBtnLabel(soloBtn, (fmt.value === 'hotseat') ? 'Start Hotseat Game' : (isArenabot ? 'Start Arenabot' : 'Start 1P Game'));
        }
      }
      (function () {
        if (!swuMenuEl('swu-gametype-select')) return;
        ['swu-gametype-select', 'swu-second-select', 'swu-pool-select'].forEach(function (id) {
          swuMenuEl(id).addEventListener('change', swuOnMenuChange);
        });
        if (!swuSelectFormat(SWU_MENU.defaultFormat, SWU_MENU.defaultPool, false)) { swuFillMenu(); swuWriteStored(); applyFormatUI(); }
      })();

      // ── Arenabot: preselect "Bot play style" from the deck the BOT will play ────────────────────────────────────
      // Its own deck link, or the host's list when that field is empty (JoinQueue.php falls back to the host's deck).
      // APIs/SWUBotDeckStyle.php reads the list and answers with an archetype; the classifier is
      // SWUSim/Custom/BotDeckStyle.php (spec docs/superpowers/specs/2026-09-22-swusim-deck-style-classifier-design.md).
      // Owner, 2026-09-22: EVERY deck load re-picks, even over a manual change. A failed lookup changes nothing.
      var _swuStyleSeq = 0;
      function swuAutoPickBotStyle() {
        var fmt = swuMenuEl('swu-format-select');
        if (!fmt || fmt.value !== 'botpractice') return;
        var sel = swuMenuEl('swu-botstyle-select');
        if (!sel) return;
        var d2 = swuMenuEl('swu-deck2-input');
        var deck = (d2 && d2.value.trim()) ? d2.value.trim() : '';
        if (!deck) {
          var link = swuMenuEl('deck-link'), text = swuMenuEl('deck-text');
          deck = (link && link.value.trim()) ? link.value.trim() : ((text && text.value.trim()) ? text.value.trim() : '');
        }
        if (!deck) return;
        var seq = ++_swuStyleSeq;
        var xhr = new XMLHttpRequest();
        xhr.open('POST', swusimAppBase() + 'APIs/SWUBotDeckStyle.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function () {
          if (seq !== _swuStyleSeq) return;            // a newer deck was entered while this was in flight
          var j;
          try { j = JSON.parse(xhr.responseText); } catch (e) { return; }
          if (!j || !j.ok || !j.style) return;         // unreadable deck: leave the player's choice alone
          sel.value = j.style;
        };
        xhr.onerror = function () {};
        xhr.send('rootName=SWUSim&deckLink=' + encodeURIComponent(deck));
      }
      ['swu-deck2-input', 'deck-link', 'deck-text'].forEach(function (id) {
        var el = swuMenuEl(id);
        if (!el) return;
        el.addEventListener('change', swuAutoPickBotStyle);
        el.addEventListener('blur', swuAutoPickBotStyle);
      });

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
        // Arenabot is checked against the card pool it plays, not its unrestricted internal format. The server checks both
        // decks again (APIs/Lobbies/JoinQueue.php); this is only the early, friendlier message.
        var checkFormat = (submission.format === 'botpractice') ? (submission.cardPool || 'open') : (submission.format || 'premier');
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
            var formatLabel = checkFormat;
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
                  '&format=' + encodeURIComponent(checkFormat));
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
            // EVERY private lobby now lives on the shared WaitingRoom page. The two public branches
            // below (instant pair / queue wait) are deliberately untouched.
            // The seat authKey is handed over through localStorage under the same key the page reads —
            // it must NEVER ride in the URL, where it would land in history, referrers and screenshots.
            // Without this the page would show "not seated" immediately after you created the lobby.
            if (response.isRoom || options.createPrivate || options.privateInviteCode) {
              try {
                localStorage.setItem('tcg:lobbyAuth:' + response.lobbyID,
                                     JSON.stringify({ authKey: response.authKey, ts: Date.now() }));
              } catch (e) {}
              window.location.href = swusimAppBase() + 'SharedUI/WaitingRoom.php?lobby=' +
                                     encodeURIComponent(response.lobbyID);
              return;
            }
            if (response.ready && !response.gameName) {
              // Never navigate to gameName=undefined (docs/superpowers/specs/2026-09-16-swusim-public-queues-design.md §2.4).
              showQueueInlineError('The match could not be started — please queue again.');
              return;
            }
            if (response.ready) {
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
        if (submission.botStyle)        params += '&botStyle=' + encodeURIComponent(submission.botStyle);
        if (submission.cardPool)        params += '&cardPool=' + encodeURIComponent(submission.cardPool);
        if (options.createPrivate)      params += '&createPrivate=1';
        if (options.privateInviteCode)  params += '&privateInviteCode=' + encodeURIComponent(options.privateInviteCode);
        xhr.send(params);
      }

      function showQueueInlineError(message) {
        var el = document.getElementById('queue-inline-error');
        if (!el) { StyledAlert(message); return; }
        // The colour is a CLASS: the menu stylesheet sets .swu-note colours with !important, so an inline
        // colour loses (the revamp's fixed swu-note--error class turned every success message red).
        el.classList.remove('swu-note--ok'); el.classList.add('swu-note--error');
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
        el.classList.remove('swu-note--error'); el.classList.add('swu-note--ok');
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
          copyButton.style.setProperty('--btn-rim', '#77b392');     // see the colour note on the queue buttons
          copyButton.style.setProperty('--btn-border', '#77b392');
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


      // ── Team-room seat model (mirrors the server: red = seats 1,3 / blue = seats 2,4) ──











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

      // ── Games in Progress (left panel) ─────────────────────────────────────────────────────────────────────
      // SWUSim/PublicGames.php lists public matches; each becomes a chip: an identity stack per seat (base behind,
      // leader(s) in front — one leader in Premier-style formats, two in Twin Suns / Team Suns) and a Spectate button.
      var _swuPublicGames = [];
      function swuEsc(v) {
        return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) {
          return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
      }
      // One seat's identity: the base peeks out behind, the leader(s) sit in front. Card art is decorative next to the
      // text title, so alt="" and the names go in the stack's title/aria-label.
      function swuIdentityStack(seat) {
        var leaders = seat.leaders || [];
        var twin = leaders.length > 1;
        var names = leaders.map(function (l) { return l.name; });
        if (seat.base) names.push(seat.base.name);
        var html = '<div class="swu-idstack' + (twin ? ' is-twin' : '') + '" role="img" aria-label="' + swuEsc(names.join(' · ')) + '" title="' + swuEsc(names.join('\n')) + '">';
        if (seat.base) html += '<img class="swu-idstack__base" src="' + swuEsc(seat.base.url) + '" alt="" loading="lazy">';
        leaders.slice(0, 2).forEach(function (l, i) {
          html += '<img class="swu-idstack__leader' + (twin ? (i === 0 ? ' is-a' : ' is-b') : '') + '" src="' + swuEsc(l.url) + '" alt="" loading="lazy">';
        });
        return html + '</div>';
      }
      function swuGameChip(g) {
        var seats = g.seats || [];
        var body, layout;
        if (g.isTeam) {
          // Team Suns: partners side by side, the two teams stacked with "vs" between them.
          var team = function (t) {
            return '<div class="swu-game-team">' + seats.filter(function (s) { return s.team === t; }).map(swuIdentityStack).join('') + '</div>';
          };
          body = team(1) + '<span class="swu-game-vs">vs</span>' + team(2);
          layout = ' is-team';
        } else if (seats.length > 2) {
          // Twin Suns free-for-all: every seat side by side (2×2 for four); the footer names the player count.
          body = seats.map(swuIdentityStack).join('');
          layout = ' is-ffa is-ffa-' + seats.length;
        } else {
          body = seats.map(swuIdentityStack).join('<span class="swu-game-vs">vs</span>');
          layout = '';
        }
        var label = g.formatName + (seats.length > 2 && !g.isTeam ? ' · ' + seats.length + ' players' : '');
        return '<div class="swu-game-chip' + layout + '" data-format="' + swuEsc(g.format) + '">'
          + '<div class="swu-game-chip__seats">' + body + '</div>'
          + '<div class="swu-game-chip__foot">'
          +   '<span class="swu-game-chip__format">' + swuEsc(label) + '</span>'
          +   '<button type="button" class="swu-spectate-btn" data-href="' + swuEsc(g.spectateUrl) + '">Spectate</button>'
          + '</div>'
          + '</div>';
      }
      function swuRenderPublicGames() {
        var list = document.getElementById('active-games-list');
        var filter = document.getElementById('swu-games-filter');
        if (!list) return;
        var want = filter ? filter.value : '';
        var shown = _swuPublicGames.filter(function (g) { return !want || g.format === want; });
        list.innerHTML = shown.map(swuGameChip).join('');
        list.style.display = shown.length ? '' : 'none';
        swuRenderActiveEmpty(shown.length, want !== '');
      }
      function swuFillGamesFilter(formats) {
        var filter = document.getElementById('swu-games-filter');
        if (!filter) return;
        var keep = filter.value;
        filter.innerHTML = '<option value="">Filter by Format</option>' + (formats || []).map(function (f) {
          return '<option value="' + swuEsc(f.id) + '">' + swuEsc(f.name) + '</option>';
        }).join('');
        // Keep the viewer's choice across refreshes while that format still has games; otherwise fall back to all.
        filter.value = (formats || []).some(function (f) { return f.id === keep; }) ? keep : '';
      }
      // The empty-state message. filtered = a format is chosen but has no games right now.
      function swuRenderActiveEmpty(count, filtered) {
        var box = document.getElementById('swu-active-empty');
        var title = document.getElementById('swu-active-empty-title');
        var text = document.getElementById('swu-active-empty-text');
        if (!box || !title || !text) return;
        count = parseInt(count, 10) || 0;
        box.style.display = count > 0 ? 'none' : '';
        title.textContent = filtered ? 'No games in this format' : 'No games in progress';
        text.textContent = filtered ? 'Try another format, or start one yourself!' : 'Be the first to challenge an opponent in the arena!';
      }
      function refreshOpenGames() {
        var countEl = document.getElementById('active-game-count');
        var xhr = new XMLHttpRequest();
        xhr.open('GET', swusimAppBase() + 'SWUSim/PublicGames.php', true);
        xhr.responseType = 'json';
        xhr.onload = function () {
          var data = (xhr.status >= 200 && xhr.status < 300 && xhr.response && xhr.response.success) ? xhr.response : null;
          _swuPublicGames = data && Array.isArray(data.games) ? data.games : [];
          if (countEl) countEl.textContent = _swuPublicGames.length;
          swuFillGamesFilter(data ? data.formats : []);
          swuRenderPublicGames();
        };
        xhr.onerror = function () {
          _swuPublicGames = [];
          if (countEl) countEl.textContent = '0';
          swuRenderPublicGames();
        };
        xhr.send();
      }
      (function () {
        var filter = document.getElementById('swu-games-filter');
        if (filter) filter.addEventListener('change', swuRenderPublicGames);
        var list = document.getElementById('active-games-list');
        if (list) list.addEventListener('click', function (e) {
          var btn = e.target.closest ? e.target.closest('.swu-spectate-btn') : null;
          if (btn && btn.getAttribute('data-href')) window.location.href = btn.getAttribute('data-href');
        });
        // Keep the list live without hammering the server: every 20s, only while this tab is visible.
        setInterval(function () { if (!document.hidden) refreshOpenGames(); }, 20000);
        document.addEventListener('visibilitychange', function () { if (!document.hidden) refreshOpenGames(); });
      })();
      function pollLobbyUpdates(playerID, authKey) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', swusimAppBase() + 'APIs/Lobbies/PollLobbyUpdates.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        xhr.onload = function() {
          if (xhr.status >= 200 && xhr.status < 300) {
            var response = JSON.parse(xhr.responseText);
            if (response.gone || (response.ready && !response.gameName)) {
              // Released from the queue, or the lobby is gone: say why and STOP polling. Re-polling a gone lobby used to
              // spin (docs/superpowers/specs/2026-09-16-swusim-public-queues-design.md §2.4).
              var goneWaitingPopup = document.getElementById('waiting-popup');
              if (goneWaitingPopup) goneWaitingPopup.remove();
              if (_waitingEscHandler) {
                document.removeEventListener('keydown', _waitingEscHandler);
                _waitingEscHandler = null;
              }
              showQueueInlineError(response.message || 'The match could not be started — please queue again.');
              return;
            }
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
