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
require_once __DIR__ . '/../../../SWUSim/Custom/SetupPanels.php';   // the setup modals' REAL data
require_once __DIR__ . '/../../../SWUSim/Custom/MenuLobbyStats.php'; // the mode cards' REAL counts

include_once __DIR__ . '/Header.php';

$swuLoggedIn = isset($_SESSION['userid']);

// The four setup modals were built from mockup fixtures: three invented saved decks, and one of
// the four Twin Suns pre-cons that were already in TwinSunsPreCons.json. These are the real ones.
$swuSetupSaved   = SWUSetupSavedDecks($swuLoggedIn ? (int)$_SESSION['userid'] : 0);
$swuSetupTSPre   = SWUSetupTwinSunsPreCons();
$swuSetupBotPre  = SWUSetupBotPreCons();
// Saved decks are per-account, so the empty state is the honest one for a guest — and the copy
// must not claim the decks live in this browser, because for this sim they do not.
// Guests may save decks too (owner, 2026-09-25) — into this browser, since they have no account
// row. Only LINKS are savable either way, so the copy says so once here.
// The deck this player last STARTED A GAME with, rendered into the page rather than fetched —
// the account copy is authoritative when signed in, and the client falls back to localStorage
// for guests. Verified before it is used to prefill anything (see LAST_DECK below).
$swuLastDeck = $swuLoggedIn ? LoadLastDeck((int)$_SESSION['userid']) : null;

// The PvP and Twin Suns cards' stat lines. Rendered server-side as well as refreshed on the 20s
// poll, so the first paint is the truth rather than a fixture that the poll corrects a beat later.
$swuLobbyStats = SWUMenuLobbyStats(SWUMenuLobbyRows());

$swuSavedNote = $swuLoggedIn
    ? 'Saved decks are on your account, so they follow you to any device. Deck links only.'
    : 'Saved decks are kept in this browser. Log in to keep them on your account and reach them anywhere. Deck links only.';
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
<div class="row-wrapper swu2-arena-wrap">
  <main class="wrap arena">

    <!-- ---------- the three modes ---------- -->
    <section class="modes" aria-labelledby="swu-modes-h">
      <h2 class="u-vh" id="swu-modes-h">Game modes</h2>

<?php if (!$swuLoggedIn): /* it came across from the mockup ungated and told signed-in players they were guests */ ?>
      <p class="guest ch gl">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <circle cx="12" cy="12" r="9"/><path d="M12 11v5.4M12 7.5v.2"/>
        </svg>
        <span>Playing as a guest — log in to use in-game chat.</span>
      </p>
<?php endif; ?>

      <ul class="modes__grid">

        <li class="slot lift-3">
          <a class="mode ch gl" href="#setup-pvp" style="--tint: var(--tint-pvp);">
            <span class="mode__art" aria-hidden="true">
              <img class="mode__plate mode__plate--hi" src="/TCGEngine/SharedUI/Sites/SWUSim/assets/hmw-pvp.webp" alt="" width="1200" height="1600" decoding="async">              
            </span>
            <span class="mode__body">
              <span class="mode__kind">Live play</span>
              <h3 class="mode__title">PvP</h3>
              <span class="mode__desc">Queue against a live opponent, or open a private room.</span>
              <span class="mode__foot">
                <span class="mode__stat" data-stat="pvp"><?php echo htmlspecialchars(SWUMenuStatLabel('pvp', $swuLobbyStats), ENT_QUOTES, "UTF-8"); ?></span>
                <span class="mode__go">Enter
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M4 12h15M13 6l6 6-6 6"/>
                  </svg>
                </span>
              </span>
            </span>
          </a>
        </li>

        <li class="slot lift-3">
          <a class="mode ch gl" href="#setup-twin-suns" style="--tint: var(--tint-duo);">
            <span class="mode__art" aria-hidden="true">
              <img class="mode__plate mode__plate--hi" src="/TCGEngine/SharedUI/Sites/SWUSim/assets/hmw-twinsuns.webp" alt="" width="1200" height="1600" decoding="async">              
            </span>
            <span class="mode__body">
              <span class="mode__kind">Multiplayer</span>
              <h3 class="mode__title">Twin Suns</h3>
              <span class="mode__desc">Two leaders each. Three or four players, free-for-all or in teams.</span>
              <span class="mode__foot">
                <span class="mode__stat" data-stat="multi"><?php echo htmlspecialchars(SWUMenuStatLabel('multi', $swuLobbyStats), ENT_QUOTES, "UTF-8"); ?></span>
                <span class="mode__go">Enter
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M4 12h15M13 6l6 6-6 6"/>
                  </svg>
                </span>
              </span>
            </span>
          </a>
        </li>

        <li class="slot lift-3">
          <a class="mode ch gl" href="#setup-arenabot" style="--tint: var(--tint-bot);">
            <span class="mode__art" aria-hidden="true">
              <img class="mode__plate mode__plate--hi" src="/TCGEngine/SharedUI/Sites/SWUSim/assets/hmw-arenabot.webp" alt="" width="1200" height="1600" decoding="async">              
            </span>
            <span class="mode__body">
              <span class="mode__kind">Solo practice</span>
              <h3 class="mode__title">Arenabot</h3>
              <span class="mode__desc">Practice against five bot archetypes, from Hyper Aggro to Hard Control.</span>
              <span class="mode__foot">
                <span class="mode__stat">Beta · always available</span>
                <span class="mode__go">Enter
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M4 12h15M13 6l6 6-6 6"/>
                  </svg>
                </span>
              </span>
            </span>
          </a>
        </li>

        <li class="slot lift-3">
          <a class="mode ch gl" href="#setup-solo" style="--tint: var(--tint-solo);">
            <span class="mode__art" aria-hidden="true">
              <img class="mode__plate mode__plate--hi" src="/TCGEngine/SharedUI/Sites/SWUSim/assets/hmw-1p.webp" alt="" width="1200" height="1600" decoding="async">              
            </span>
            <span class="mode__body">
              <span class="mode__kind">Local play</span>
              <h3 class="mode__title">1P Mode</h3>
              <span class="mode__desc">Goldfish a deck solo, or play both seats yourself in Hotseat.</span>
              <span class="mode__foot">
                <span class="mode__stat">Instant · no queue</span>
                <span class="mode__go">Enter
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M4 12h15M13 6l6 6-6 6"/>
                  </svg>
                </span>
              </span>
            </span>
          </a>
        </li>

      </ul>

    </section>

    <!-- ---------- right rail ---------- -->
    <div class="rail">

      <!-- Welcome / Replays -->
      <div class="lift">
        <section class="swu2-panel ch gl" aria-labelledby="swu-wr-h">
          <h2 class="u-vh" id="swu-wr-h">Welcome and replays</h2>

          <div class="tabs" role="tablist" aria-label="Welcome and replays">
            <button class="swu2-tab ch" type="button" role="tab" id="ga-info-tab-welcome"
                    aria-selected="true" aria-controls="ga-info-panel-welcome">Welcome</button>
            <button class="swu2-tab ch" type="button" role="tab" id="ga-info-tab-replays"
                    aria-selected="false" aria-controls="ga-info-panel-replays" tabindex="-1">Replays</button>
          </div>

          <div class="tabpanel" id="ga-info-panel-welcome" role="tabpanel" aria-labelledby="ga-info-tab-welcome" tabindex="0">
            <h3 class="welcome__title">Welcome to Petranaki Arena</h3>
            <p class="welcome__note">Everything plays in the browser. No install, no account needed.</p>
            <ul class="keys" id="hotkey-list">
              <li class="keys__row"><kbd class="ch">u</kbd> Undo most recent action</li>
              <li class="keys__row"><kbd class="ch">Space</kbd> Pass optional decision</li>
              <li class="keys__row"><kbd class="ch">Esc</kbd> Cancel matchmaking</li>
            </ul>
          </div>

          <div class="tabpanel" id="ga-info-panel-replays" role="tabpanel" aria-labelledby="ga-info-tab-replays" tabindex="0" hidden>
            <div class="empty">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M3.5 12a8.5 8.5 0 1 0 2.6-6.1"/><path d="M3.5 4.5V10h5.5"/><path d="M12 8.5V12l2.8 1.8"/>
              </svg>
              <p class="empty__t">No replays yet</p>
              <p class="empty__s">Finished games show up here for 30 days.</p>
            </div>
          </div>
        </section>
      </div>

      <!-- Games in Progress -->
      <div class="lift">
        <section class="swu2-panel ch gl" aria-labelledby="swu-games-h">
          <div class="panel__head">
            <h2 class="panel__title" id="swu-games-h">Games in Progress</h2>
            <span class="count" id="active-game-count" aria-label="Public games in progress">0</span>
            <div class="filter">
              <label class="filter__label" for="swu-games-filter">Format</label>
              <span class="selwrap ch">
                <select class="select" id="swu-games-filter" name="fmt">
                  <option selected>All formats</option>
                  <option>Premier</option>
                  <option>Eternal</option>
                  <option>Twin Suns</option>
                  <option>Padawan</option>
                </select>
              </span>
            </div>
          </div>

          <div class="games-frame">
            <span class="games-rail" aria-hidden="true"><span class="games-rail__thumb"></span></span>
            <div class="games-scroll" tabindex="0" role="region" aria-label="Games in progress">
              <ul class="games" id="active-games-list" aria-live="off"></ul>
              <div class="empty" id="swu-active-empty" hidden>
                <p class="empty__t" id="swu-active-empty-title">No games in progress</p>
                <p class="empty__s" id="swu-active-empty-text">Be the first to challenge an opponent in the arena.</p>
              </div>
            </div>
          </div>
        </section>
      </div>

    </div>

    <?php
    // A .deckprev row is display:none until a rule for ITS key reveals it. The mockup could
    // hard-code the ten rules its ten fixtures needed; real keys are per-account hashes, so the
    // rules ship with the page. Without this the picker rendered as a BLANK BAR for anyone with
    // saved decks — the deck was in the DOM, nothing was allowed to paint it.
    echo SWUSetupPreviewStyles([array_column($swuSetupSaved, 'key')]);
    // A guest's decks live in localStorage, so the client renders their pickers.
    echo '<script>window.SWU_IS_GUEST = ' . ($swuLoggedIn ? 'false' : 'true') . ';'
       . 'window.SWU_LAST_DECK = ' . json_encode($swuLastDeck ?: null) . ";</script>\n";
    ?>
    <!-- ── per-mode setup modals ──────────────────────────────────────────
         Native <dialog> + showModal(): the platform supplies the focus trap,
         Escape, ::backdrop and the inert background. The splash stays rendered
         behind. A :target fallback keeps them reachable with JS off. -->
      <dialog class="setup lift" id="setup-pvp" aria-labelledby="setup-pvp-t" style="--tint: var(--tint-pvp);">
        <div class="setup__pane ch gl">
          <a class="setup__x ch" href="#" aria-label="Close" data-close><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></a>
          <div class="setup__scroll" tabindex="-1">
      <div class="setup__head">
        <span class="setup__tile ch" aria-hidden="true">
          <img src="/TCGEngine/SharedUI/Sites/SWUSim/assets/hmw-pvp.webp" alt="" width="1200" height="1600" decoding="async">
        </span>
        <div class="setup__id">
          <p class="setup__kind">Live play</p>
          <h3 class="setup__title" id="setup-pvp-t">PvP</h3>
          <p class="setup__desc">Queue against a live opponent, or open a private room.</p>
        </div>
        <div class="setup__tools">
          <span class="poolpick">
            <label class="u-vh" id="pvp-pool-lbl" for="pvp-pool">Card pool</label>
            <span class="selwrap ch">
              <select class="select" id="pvp-pool" name="pvp-pool">
<?php echo SWUSetupPoolOptions($swuMenuTreeFull, 'constructed', 'pvp'); ?>
              </select>
            </span>
          </span>
        </div>
        <!-- the detected-pool note. A SIBLING of .setup__tools, not a
             child: as a flex item of the cluster its text grew the
             head's max-content `auto` track and slid the chip and the
             title 205px left the instant a deck resolved (measured in
             all three engines). Here it spans the head's flexible
             track, so per the grid sizing rules it contributes to no
             track's intrinsic size and nothing beside it can move. -->
        <span class="poolnote" hidden></span>
      </div>
          <div class="setup__body">
        <!-- the reason a switch landed the player here. Hidden until
             one does; the script points this dialog's
             aria-describedby at .whyline__t before it opens. -->
        <p class="whyline ch" hidden>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M4 12h14"/><path d="M13 6l6 6-6 6"/>
          </svg>
          <span class="whyline__t" id="setup-pvp-why"></span>
        </p>
        <div class="dblock">
        <div class="drow">
          <div>
            <label class="flabel" for="pvp-link">Deck Link</label>
            <span class="inwrap ch">
              <input class="input" id="pvp-link" name="pvp-link" type="url" inputmode="url" data-detect
                     autocomplete="off" spellcheck="false" placeholder="Paste your deck link here">
            </span>
          </div>
          <button data-act="save" class="swu2-btn ch" type="button"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4.5 4.5h11l4 4v11h-15Z"/><path d="M8 4.5v5h7"/><path d="M8 19.5v-6h8v6"/></svg>Save Deck</button>
        </div>
        <!-- MOCKUP SCAFFOLDING, not product. There is no backend
             behind this page, so these fill the field with one of
             the four fixture links and run detection on it. -->
        </div>
        <div>
          <label class="flabel" for="pvp-saved">Saved Decks</label>
          <?php echo SWUSetupDeckPicker('pvp-saved', $swuSetupSaved); ?>
          <p class="note note--under"><?php echo htmlspecialchars($swuSavedNote, ENT_QUOTES, "UTF-8"); ?></p>
        </div>
            <div class="prow">
          <div>
            <label class="flabel" for="pvp-match">Match Type</label>
            <span class="selwrap ch">
              <select class="select" id="pvp-match" name="pvp-match">
                  <option selected>Best of 1</option>
                  <option>Best of 3</option>
              </select>
            </span>
          </div>
            </div>
            <div class="arow">
              <button data-act="join" class="swu2-btn swu2-btn--primary ch" type="button">Join Queue</button>
              <button data-act="private" class="swu2-btn ch" type="button">Create Private Room</button>
              <button data-act="cancel" class="swu2-btn swu2-btn--quiet ch" type="button" data-close>Cancel</button>
            </div>
          </div>
          </div>
        </div>
      </dialog>

      <!-- ===== SETUP · Twin Suns ===== -->
      <dialog class="setup lift" id="setup-twin-suns" aria-labelledby="setup-twin-suns-t" style="--tint: var(--tint-duo);">
        <div class="setup__pane ch gl">
          <a class="setup__x ch" href="#" aria-label="Close" data-close><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></a>
          <div class="setup__scroll" tabindex="-1">
      <div class="setup__head">
        <span class="setup__tile ch" aria-hidden="true">
          <img src="/TCGEngine/SharedUI/Sites/SWUSim/assets/hmw-twinsuns.webp" alt="" width="1200" height="1600" decoding="async">
        </span>
        <div class="setup__id">
          <p class="setup__kind">Multiplayer</p>
          <h3 class="setup__title" id="setup-twin-suns-t">Twin Suns</h3>
          <p class="setup__desc">Two leaders each. Three or four players, free-for-all or in teams.</p>
        </div>
        <div class="setup__tools">
          <span class="poolpick">
            <label class="u-vh" id="ts-pool-lbl" for="ts-pool">Card pool</label>
            <span class="selwrap ch">
              <select class="select" id="ts-pool" name="ts-pool">
<?php echo SWUSetupPoolOptions($swuMenuTreeFull, 'twinsuns', 'ffa'); ?>
              </select>
            </span>
          </span>
        </div>
        <!-- the detected-pool note. A SIBLING of .setup__tools, not a
             child: as a flex item of the cluster its text grew the
             head's max-content `auto` track and slid the chip and the
             title 205px left the instant a deck resolved (measured in
             all three engines). Here it spans the head's flexible
             track, so per the grid sizing rules it contributes to no
             track's intrinsic size and nothing beside it can move. -->
        <span class="poolnote" hidden></span>
      </div>
          <div class="setup__body">
        <p class="whyline ch" hidden>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M4 12h14"/><path d="M13 6l6 6-6 6"/>
          </svg>
          <span class="whyline__t" id="setup-twin-suns-why"></span>
        </p>
        <div class="dblock">
        <div class="drow">
          <div>
            <label class="flabel" for="ts-link">Deck Link</label>
            <span class="inwrap ch">
              <input class="input" id="ts-link" name="ts-link" type="url" inputmode="url" data-detect
                     autocomplete="off" spellcheck="false" placeholder="https://swudb.com/deck/kWzBQPfCopFMV">
            </span>
          </div>
          <button data-act="save" class="swu2-btn ch" type="button"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4.5 4.5h11l4 4v11h-15Z"/><path d="M8 4.5v5h7"/><path d="M8 19.5v-6h8v6"/></svg>Save Deck</button>
        </div>
        <!-- MOCKUP SCAFFOLDING, not product. See the PvP copy. -->
        </div>
        <div>
          <label class="flabel" for="ts-saved">Saved Decks</label>
          <?php echo SWUSetupDeckPicker('ts-saved', $swuSetupSaved); ?>
          <p class="note note--under"><?php echo htmlspecialchars($swuSavedNote, ENT_QUOTES, "UTF-8"); ?></p>
        </div>
            <fieldset class="fs">
              <legend class="lg">Pre-Cons</legend>
              <p class="note note--over"><?php echo count($swuSetupTSPre); ?> pre-cons, each a complete Twin Suns deck.</p>
              <div class="pool ch">
                <div class="pool__scroll">
                  <ul class="pcs">
<?php foreach ($swuSetupTSPre as $_i => $_p)
      echo SWUSetupPreConRow('ts-precon', 'ts', $_p, true, false), "\n"; ?>
                  </ul>
                </div>
              </div>
            </fieldset>

            <!-- The zero-pre-cons state, kept live in the stylesheet and parked
                 here so it is one unwrap away when a format ships with none.
                 A <template> renders nothing and validates. -->
            <template id="ts-precons-empty">
              <div class="pool ch">
                <ul class="ghosts" aria-hidden="true">
                  <li class="ghost ch"><span class="ghost__bar ghost__bar--name"></span><span class="ghost__bar ghost__bar--meta"></span><span class="ghost__tag"></span></li>
                  <li class="ghost ch"><span class="ghost__bar ghost__bar--name"></span><span class="ghost__bar ghost__bar--meta"></span><span class="ghost__tag"></span></li>
                  <li class="ghost ch"><span class="ghost__bar ghost__bar--name"></span><span class="ghost__bar ghost__bar--meta"></span><span class="ghost__tag"></span></li>
                </ul>
                <div class="empty">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3.5 7.5 12 3l8.5 4.5v9L12 21l-8.5-4.5Z"/><path d="M3.5 7.5 12 12l8.5-4.5M12 12v9"/>
                  </svg>
                  <p class="empty__t">No pre-constructed decks yet</p>
                  <p class="empty__s">Paste a deck link or pick a saved deck above to play Twin Suns now.</p>
                </div>
              </div>
            </template>

            <div class="prow">
        <fieldset class="fs">
          <legend class="lg">Arrangement</legend>
          <div class="seg">
              <input class="seg__in u-vh" type="radio" name="ts-arr" id="ts-arr-1" data-group="twinsuns" data-opt="ffa" checked>
              <label class="seg__opt ch" for="ts-arr-1">Free-For-All</label>
              <input class="seg__in u-vh" type="radio" name="ts-arr" id="ts-arr-2" data-group="twinsuns" data-opt="teams">
              <label class="seg__opt ch" for="ts-arr-2">Team Suns</label>
          </div>
        </fieldset>
            </div>
            <div class="arow">
              <button data-act="join" class="swu2-btn swu2-btn--primary ch" type="button">Join Queue</button>
              <button data-act="private" class="swu2-btn ch" type="button">Create Private Room</button>
              <button data-act="cancel" class="swu2-btn swu2-btn--quiet ch" type="button" data-close>Cancel</button>
            </div>
          </div>
          </div>
        </div>
      </dialog>

      <!-- ===== SETUP · Arenabot ===== -->
      <dialog class="setup lift" id="setup-arenabot" aria-labelledby="setup-arenabot-t" style="--tint: var(--tint-bot);">
        <div class="setup__pane ch gl">
          <a class="setup__x ch" href="#" aria-label="Close" data-close><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></a>
          <div class="setup__scroll" tabindex="-1">
      <div class="setup__head">
        <span class="setup__tile ch" aria-hidden="true">
          <img src="/TCGEngine/SharedUI/Sites/SWUSim/assets/hmw-arenabot.webp" alt="" width="1200" height="1600" decoding="async">
        </span>
        <div class="setup__id">
          <p class="setup__kind">Solo practice</p>
          <h3 class="setup__title" id="setup-arenabot-t">Arenabot</h3>
          <p class="setup__desc">Practice against five bot archetypes, from Hyper Aggro to Hard Control.</p>
        </div>
      </div>
          <div class="setup__body">
        <div class="drow">
          <div>
            <label class="flabel" for="ab-link">Deck Link</label>
            <span class="inwrap ch">
              <input class="input" id="ab-link" name="ab-link" type="url" inputmode="url" data-detect
                     autocomplete="off" spellcheck="false" placeholder="https://swudb.com/deck/PCQRTCWTgMLr">
            </span>
          </div>
          <button data-act="save" class="swu2-btn ch" type="button"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4.5 4.5h11l4 4v11h-15Z"/><path d="M8 4.5v5h7"/><path d="M8 19.5v-6h8v6"/></svg>Save Deck</button>
        </div>
        <div>
          <label class="flabel" for="ab-saved">Saved Decks<span class="u-vh"> for your deck</span></label>
          <?php echo SWUSetupDeckPicker('ab-saved', $swuSetupSaved); ?>
          <p class="note note--under"><?php echo htmlspecialchars($swuSavedNote, ENT_QUOTES, "UTF-8"); ?></p>
        </div>
        <div class="drow">
          <div>
            <label class="flabel" for="ab-bot-link">Bot Deck Link</label>
            <span class="inwrap ch">
              <input class="input" id="ab-bot-link" name="ab-bot-link" type="url" inputmode="url"
                     autocomplete="off" spellcheck="false" placeholder="https://swudb.com/deck/rYBmXPaxDUaSY">
            </span>
          </div>
          <button data-act="save" class="swu2-btn ch" type="button"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4.5 4.5h11l4 4v11h-15Z"/><path d="M8 4.5v5h7"/><path d="M8 19.5v-6h8v6"/></svg>Save Deck</button>
        </div>
        <div>
          <label class="flabel" for="ab-bot-saved">Saved Decks<span class="u-vh"> for the bot's deck</span></label>
          <?php echo SWUSetupDeckPicker('ab-bot-saved', $swuSetupSaved,
                    'No saved decks yet — use a pre-con below',
                    '— Use one of your saved decks or use a pre-con below —'); ?>
          <p class="note note--under">One list, the same one as above &mdash; a saved deck here, or a pre-con below, never both.</p>
        </div>
            <fieldset class="fs">
              <legend class="lg">Bot Pre-Cons</legend>
              <p class="note note--over"><?php echo count($swuSetupBotPre); ?> tuned fixtures. Scroll for the rest.</p>
              <div class="pool ch">
                <div class="pool__scroll">
                  <ul class="pcs">
<?php foreach ($swuSetupBotPre as $_i => $_p)
      echo SWUSetupPreConRow('ab-precon', 'ab', $_p, false, $_i === 0), "\n"; ?>
                  </ul>
                </div>
              </div>
            </fieldset>
            <div class="prow">
          <div>
            <label class="flabel" for="ab-style">Bot Style</label>
            <span class="selwrap ch">
              <select class="select" id="ab-style" name="ab-style">
                  <option>Hyper Aggro</option>
                  <option>Soft Aggro</option>
                  <option selected>Midrange</option>
                  <option>Soft Control</option>
                  <option>Hard Control</option>
              </select>
            </span>
          </div>
              <button data-act="solo" class="swu2-btn swu2-btn--primary ch" type="button">Start Arenabot</button>
              <button data-act="cancel" class="swu2-btn swu2-btn--quiet ch" type="button" data-close>Cancel</button>
            </div>
            <?php /* OUTSIDE the .prow, deliberately. Inside it (even spanning 1/-1) a full-width
                     item contributes its min-content to the row's `auto` button tracks, and
                     Start Arenabot came out 195px against the mockup's 150px. */
                  echo SWUSetupPickMsg('botstyle'); ?>
          </div>
          </div>
        </div>
      </dialog>

      <!-- ===== SETUP · 1P Mode ===== -->
      <dialog class="setup lift" id="setup-solo" aria-labelledby="setup-solo-t" style="--tint: var(--tint-solo);">
        <div class="setup__pane ch gl">
          <a class="setup__x ch" href="#" aria-label="Close" data-close><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></a>
          <div class="setup__scroll" tabindex="-1">
      <div class="setup__head">
        <span class="setup__tile ch" aria-hidden="true">
          <img src="/TCGEngine/SharedUI/Sites/SWUSim/assets/hmw-1p.webp" alt="" width="1200" height="1600" decoding="async">
        </span>
        <div class="setup__id">
          <p class="setup__kind">Local play</p>
          <h3 class="setup__title" id="setup-solo-t">1P Mode</h3>
          <p class="setup__desc">Goldfish a deck solo, or play both seats yourself in Hotseat.</p>
        </div>
      </div>
          <div class="setup__body">
        <fieldset class="fs">
          <legend class="lg">Mode</legend>
          <div class="seg">
              <input class="seg__in u-vh" type="radio" name="sp-mode" id="sp-mode-1" data-format="goldfish" checked>
              <label class="seg__opt ch" for="sp-mode-1">Goldfish (Solo)</label>
              <input class="seg__in u-vh hotpick" type="radio" name="sp-mode" id="sp-mode-2" data-format="hotseat">
              <label class="seg__opt ch" for="sp-mode-2">Hotseat (2P local)</label>
          </div>
        </fieldset>
        <div class="drow">
          <div>
            <label class="flabel" for="sp-link">Deck Link</label>
            <span class="inwrap ch">
              <input class="input" id="sp-link" name="sp-link" type="url" inputmode="url" data-detect
                     autocomplete="off" spellcheck="false" placeholder="https://swudb.com/deck/HeEAAQjVtrhee">
            </span>
          </div>
          <button data-act="save" class="swu2-btn ch" type="button"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4.5 4.5h11l4 4v11h-15Z"/><path d="M8 4.5v5h7"/><path d="M8 19.5v-6h8v6"/></svg>Save Deck</button>
        </div>
        <div>
          <label class="flabel" for="sp-saved">Saved Decks</label>
          <?php echo SWUSetupDeckPicker('sp-saved', $swuSetupSaved); ?>
          <p class="note note--under"><?php echo htmlspecialchars($swuSavedNote, ENT_QUOTES, "UTF-8"); ?></p>
        </div>
            <div class="hot">
              <p class="hot__lede">Hotseat runs both seats from this browser, so the second player needs a deck too.</p>
        <div class="drow">
          <div>
            <label class="flabel" for="sp-link-2">Second Deck Link</label>
            <span class="inwrap ch">
              <input class="input" id="sp-link-2" name="sp-link-2" type="url" inputmode="url" data-detect
                     autocomplete="off" spellcheck="false" placeholder="https://swudb.com/deck/LImIrpIS">
            </span>
          </div>
          <button data-act="save" class="swu2-btn ch" type="button"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4.5 4.5h11l4 4v11h-15Z"/><path d="M8 4.5v5h7"/><path d="M8 19.5v-6h8v6"/></svg>Save Deck</button>
        </div>
        <div>
          <label class="flabel" for="sp-saved-2">Saved Decks<span class="u-vh"> for the second seat</span></label>
          <?php echo SWUSetupDeckPicker('sp-saved-2', $swuSetupSaved, 'No saved decks yet', '', 'bot'); ?>
          <p class="note note--under">Both seats are stored on this device only.</p>
        </div>
            </div>
            <div class="arow">
              <button data-act="solo" class="swu2-btn swu2-btn--primary ch" type="button">Start 1P Game</button>
              <button data-act="cancel" class="swu2-btn swu2-btn--quiet ch" type="button" data-close>Cancel</button>
            </div>
          </div>
          </div>
        </div>
      </dialog>

    <!-- Legacy submission fields. getDeckSubmission() reads THESE ids; the modals write into
         them via SYNC_ACTIVE_SETUP() just before submitting, so the proven queue/game-start path
         is untouched. ⚠ #swu-format-select must keep its id and stay hidden: private-invite.js
         assigns to every [id$="-format-select"]. -->
    <input type="hidden" id="deck-link">
    <input type="hidden" id="swu-deck2-input">
    <input type="hidden" id="swu-queuetype-select" value="bo1">
    <input type="hidden" id="swu-botstyle-select" value="midrange">
    <!-- ⚠ hidden INPUT, not an empty <select>: assigning an arbitrary value to a select
         with no matching <option> silently does nothing, so the format never reached
         getDeckSubmission(). The id must stay *-format-select — private-invite.js
         assigns to every [id$="-format-select"], and that selector matches an input too. -->
    <input type="hidden" id="swu-format-select" value="premier">
  <input type="hidden" id="swu-cardpool-input" value="premier">
</main>
</div>


<script src="<?php echo _VersionAsset('/TCGEngine/Core/MatchReplayClient.js'); ?>"></script>
<script src="<?php echo _VersionAsset('/TCGEngine/SharedUI/js/private-invite.js'); ?>"></script>


<script>

  var _hotkeyList = [
    { key: 'u',   label: 'Undo most recent action' },
    { key: 'Space', label: 'Pass optional decision (when available)' },
    { key: 'Esc', label: 'Cancel matchmaking' },
  ];

  function renderHotkeyList() {
    var container = document.getElementById('hotkey-list');
    if (!container) return;
    var html = '';
    _hotkeyList.forEach(function(h) {
      html += '<div class="hotkey-row"><span class="hotkey-badge">' + h.key + '</span><span>' + h.label + '</span></div>';
    });
    container.innerHTML = html;
  }

  document.addEventListener('DOMContentLoaded', function() {
    renderHotkeyList();
    // Rotate tips every 8 seconds
    // Did-you-know rotator removed with the menu redesign (also a WCAG 2.2.2 failure:
    // auto-rotating content with no pause control).
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
      // Save the deck in ONE box — whichever Save Deck button was pressed. $link is the input
      // beside it, so the Arenabot bot row saves the bot's deck and not the player's.
      //
      // Owner, 2026-09-25, two rules:
      //   * only a LINK may be saved — a pasted JSON blob or free-text list has no source to
      //     return to. The verdict comes from ValidateDeck.php's `savable`, which is
      //     SWUDeckInputIsLink(); the client does not re-implement it. SavedDecks.php enforces
      //     it again server-side, because the UI is not a boundary.
      //   * a GUEST may save too — into this browser, since they have no account row.
      function saveCurrentDeck(link, slot, dlg) {
        link = String(link || '').trim();
        if (!link) { StyledAlert('Enter a deck link first, then Save Deck.'); return; }
        var tell = function (msg) {
          if (dlg && typeof PICK_SAY === 'function') PICK_SAY(dlg, slot || 'own', msg);
          else showQueueInlineError(msg);
        };

        var body = 'deckLink=' + encodeURIComponent(link) + '&format=premier';
        fetch(swusimAppBase() + 'SWUSim/ValidateDeck.php',
              { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body })
          .then(function (r) { return r.json(); })
          .then(function (d) {
            if (!d || !d.success) { tell('Could not read that deck: ' + ((d && d.message) || 'unknown')); return; }
            if (!d.savable) {
              tell('Only deck links can be saved — a pasted list has no source to return to. ' +
                   'Paste a link from SWUDB, SWUStats, melee.gg or another deck site.');
              return;
            }
            var name = d.deckName || [d.leaderName, d.baseName].filter(Boolean).join(' - ') || 'Untitled deck';

            if (!IS_GUEST) {
              var x = new XMLHttpRequest();
              x.open('POST', SAVEDECKS_URL, true);
              x.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
              x.onload = function () {
                var r = {}; try { r = JSON.parse(x.responseText); } catch (e) {}
                if (r.success) location.reload();
                else tell('Could not save deck: ' + (r.error === 'not_a_link'
                          ? 'only deck links can be saved' : (r.error || 'unknown')));
              };
              x.send('action=save&deckInput=' + encodeURIComponent(link));
              return;
            }

            var leaders = [].concat(d.leaderID || []).filter(Boolean);
            var subtitle = [d.leaderName, d.baseName].filter(Boolean).join(' · ');
            var stored = GUEST_DECKS.add({
              key: GUEST_KEY_FOR(link), name: name, leaders: leaders, base: d.baseID || '',
              subtitle: subtitle, count: d.deckCount || 0, input: link, format: d.detectedFormat || ''
            });
            if (!stored) {
              tell('This browser would not let the deck be saved (private window or storage full).');
              return;
            }
            tell('Saved "' + name + '" to this browser. Log in to keep your decks on your account.');
            GUEST_RENDER_ALL();         // in place, so the line above survives to be read
          })
          .catch(function () { tell('Could not reach the server to check that deck link.'); });
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

      // The Arenabot bot-style auto-picker lives with the modals it drives — see
      // BOT_STYLE_AUTOPICK below, next to SETUP_BIND_PICKERS.

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
          // This deck is valid and a game is about to start with it, so it becomes the deck the
          // menu offers next time. Recorded HERE and not on resolve: a deck you pasted to look
          // at and then rejected must not come back on your next visit.
          if (typeof window.REMEMBER_DECK === 'function') window.REMEMBER_DECK(submission.deckLink, vres);
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
      // ONE MATCH CHIP, in the approved shape (docs/superpowers/mockups/2026-09-24-swusim-main-menu.html).
      // The stylesheet's MATCH CHIPS section was ported with the redesign and has been sitting unused,
      // because this builder still emitted the older .swu-game-chip / .swu-idstack markup. That is why the
      // panel looked nothing like the mockup: the CSS was right and nothing was wearing it.
      //
      // The shape, per seat: real leader + base art, the leader's title with its SET CODE, the base name
      // underneath. Between the two seats a "VS" rule. On the side, the format, the round and how long the
      // game has been running, then Spectate.
      //
      // Card art is DECORATIVE here -- every card is named in text beside it -- so the images carry alt=""
      // and each seat gets one visually-hidden sentence naming its cards for a screen reader.
      function swuCardThumb(c, kind) {
        if (!c) return '';
        return '<span class="tc tc--' + kind + '">'
             + '<img src="' + swuEsc(c.url) + '" alt="" loading="lazy" decoding="async" width="628" height="450">'
             + '</span>';
      }
      // "Ahsoka Tano, Snips" -> title "Ahsoka Tano" + the set code beside it. The subtitle is dropped from
      // the chip: at 148px of rail the full string ellipsises before the name is even readable.
      function swuLeaderName(c) {
        var full = String((c && c.name) || '');
        var title = full.split(',')[0].trim() || full;
        var set = (c && c.set) ? ' <span class="seat__set">(' + swuEsc(c.set) + ')</span>' : '';
        return '<span class="seat__leader">' + swuEsc(title) + set + '</span>';
      }
      function swuSeat(seat) {
        var leaders = (seat && seat.leaders) || [];
        var thumbs = leaders.slice(0, 2).map(function (l) { return swuCardThumb(l, 'leader'); }).join('')
                   + swuCardThumb(seat.base, 'base');
        // the dot only separates PAIRED leaders, and .match--multi hides it and stacks them instead
        var names = leaders.slice(0, 2).map(swuLeaderName).join('<span class="seat__dot">&middot;</span>');
        var spoken = leaders.map(function (l) { return l.name; });
        if (seat.base) spoken.push('Base ' + seat.base.name);
        return '<span class="seat">'
             + '<span class="cards" aria-hidden="true">' + thumbs + '</span>'
             + '<span class="seat__text">'
             +   '<span class="seat__name">' + names + '</span>'
             +   '<span class="seat__sub">' + swuEsc((seat.base && seat.base.name) || '') + '</span>'
             + '</span>'
             + '<span class="u-vh">' + swuEsc(spoken.join('. ')) + '.</span>'
             + '</span>';
      }
      // "12m", "1h 04m". Anything under a minute reads as 0m rather than as seconds, because the poll is
      // 20s and a ticking seconds counter would be wrong more often than right.
      function swuElapsed(startedAt) {
        if (!startedAt) return '';
        var mins = Math.max(0, Math.floor((Date.now() / 1000 - startedAt) / 60));
        if (mins < 60) return mins + 'm';
        var h = Math.floor(mins / 60), m = mins % 60;
        return h + 'h ' + (m < 10 ? '0' : '') + m + 'm';
      }
      function swuGameChip(g) {
        var seats = g.seats || [];
        var multi = seats.length > 2;
        var body;
        if (multi) {
          // 3 and 4 seats: seat-grouped, never three "vs" rules (the stylesheet's own note).
          body = seats.map(swuSeat).join('');
        } else {
          body = swuSeat(seats[0]) + '<span class="match__vs" aria-hidden="true">VS</span>' + swuSeat(seats[1]);
        }
        // left of the meta row names the format (and player count when that is the interesting part);
        // right of it is where the game has got to.
        var fmt = g.formatName + (multi && !g.isTeam ? ' · ' + seats.length + ' players' : '');
        var when = [];
        if (g.round > 0) when.push('Round ' + g.round);
        var el = swuElapsed(g.startedAt);
        if (el) when.push(el);
        var vs = seats.map(function (s) {
          return ((s.leaders || []).map(function (l) { return String(l.name || '').split(',')[0]; }).join(' and ')) || 'a player';
        }).join(' versus ');

        return '<li class="match ch' + (multi ? ' match--multi' : '') + '"'
          + ' data-seats="' + seats.length + '" data-format="' + swuEsc(g.format) + '">'
          + '<div class="match__seats">' + body + '</div>'
          + '<div class="match__side">'
          +   '<p class="match__meta">'
          +     '<span class="match__fmt">' + swuEsc(fmt) + '</span>'
          +     (when.length ? '<span>' + swuEsc(when.join(' · ')) + '</span>' : '')
          +   '</p>'
          +   '<button type="button" class="swu2-btn swu2-btn--block ch swu-spectate-btn" data-href="' + swuEsc(g.spectateUrl) + '">'
          +     '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.8-6 10-6 10 6 10 6-3.8 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="2.6"/></svg>'
          +     'Spectate<span class="u-vh"> ' + swuEsc(vs) + '</span>'
          +   '</button>'
          + '</div>'
          + '</li>';
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
      // The PvP and Twin Suns cards' stat lines. The SERVER already rendered them into the page
      // (SWUMenuStatLabel in SWUSim/Custom/MenuLobbyStats.php), so this only keeps them current —
      // and the labels come from that same function, so the first paint and every refresh cannot
      // word the same state two different ways.
      // A failed poll passes null and the line is LEFT ALONE: the last known count is closer to
      // the truth than blanking it, and a card whose foot empties out reads as broken.
      function swuRenderModeStats(labels) {
        if (!labels) return;
        ['pvp', 'multi'].forEach(function (k) {
          var el = document.querySelector('.mode__stat[data-stat="' + k + '"]');
          if (el && typeof labels[k] === 'string' && labels[k] !== '') el.textContent = labels[k];
        });
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
          swuRenderModeStats(data ? data.lobbyLabels : null);
        };
        xhr.onerror = function () {
          _swuPublicGames = [];
          if (countEl) countEl.textContent = '0';
          swuRenderPublicGames();
          swuRenderModeStats(null);      // a failed poll leaves the server-rendered line alone
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
    
// ── setup modals, rich listboxes and format detection ──────────────────────
/* Progressive enhancement only: the Welcome tab is correct with JS off. */
(function () {
  var tablist = document.querySelector('[role="tablist"]');
  if (!tablist) return;
  var tabs = Array.prototype.slice.call(tablist.querySelectorAll('[role="tab"]'));

  function select(tab) {
    tabs.forEach(function (t) {
      var on = t === tab;
      t.setAttribute('aria-selected', String(on));
      t.tabIndex = on ? 0 : -1;
      document.getElementById(t.getAttribute('aria-controls')).hidden = !on;
    });
  }

  tabs.forEach(function (tab, i) {
    tab.addEventListener('click', function () { select(tab); });
    tab.addEventListener('keydown', function (e) {
      var d = e.key === 'ArrowRight' ? 1 : e.key === 'ArrowLeft' ? -1 : 0;
      if (!d) return;
      e.preventDefault();
      var next = tabs[(i + d + tabs.length) % tabs.length];
      select(next);
      next.focus();
    });
  });
})();

/* Progressive enhancement only: the list scrolls, by wheel, touch and
   keyboard, with or without this. All this adds is the two honest
   affordances — a bottom fade that exists only while there really is
   more below, and an always-visible steel scrollbar to replace the
   overlay one the platform will not paint until it is too late. */
(function () {
  var frame = document.querySelector('.games-frame');
  var scroller = frame && frame.querySelector('.games-scroll');
  var thumb = frame && frame.querySelector('.games-rail__thumb');
  if (!scroller || !thumb) return;

  function sync() {
    var max = scroller.scrollHeight - scroller.clientHeight;
    frame.setAttribute('data-scrollable', String(max > 4));
    frame.setAttribute('data-more', String(max - scroller.scrollTop > 4));
    if (max <= 4) return;
    var ratio = scroller.clientHeight / scroller.scrollHeight;
    var h = Math.max(ratio * 100, 12);
    thumb.style.setProperty('--thumb-h', h.toFixed(2) + '%');
    thumb.style.setProperty('--thumb-y', ((scroller.scrollTop / max) * (100 - h)).toFixed(2) + '%');
  }

  thumb.addEventListener('pointerdown', function (e) {
    e.preventDefault();
    var rail = thumb.parentNode.getBoundingClientRect();
    var start = e.clientY, from = scroller.scrollTop;
    var max = scroller.scrollHeight - scroller.clientHeight;
    var travel = rail.height - thumb.getBoundingClientRect().height;
    thumb.setPointerCapture(e.pointerId);
    function move(ev) {
      if (travel <= 0) return;
      scroller.scrollTop = from + ((ev.clientY - start) / travel) * max;
    }
    function up() {
      thumb.removeEventListener('pointermove', move);
      thumb.removeEventListener('pointerup', up);
    }
    thumb.addEventListener('pointermove', move);
    thumb.addEventListener('pointerup', up);
  });

  scroller.addEventListener('scroll', sync, { passive: true });
  if (window.ResizeObserver) new ResizeObserver(sync).observe(scroller);
  window.addEventListener('resize', sync);
  sync();
})();

/* ==================================================================
   THE MODE SETUPS AS MODALS

   Progressive enhancement. If <dialog>.showModal is missing this
   whole block returns before setting `data-dlg`, and the CSS falls
   straight back to the approved :target page states.

   Everything a hand-rolled modal gets wrong is the platform's job
   here: the focus trap, Escape, the ::backdrop, and making the
   splash behind inert. What is left is the three things the
   platform does NOT do — lock the page scroll, return focus to the
   card that opened it, and close on a backdrop click.
   ================================================================== */
(function () {
  var html = document.documentElement;
  var dialogs = [].slice.call(document.querySelectorAll('dialog.setup'));
  if (!dialogs.length || typeof dialogs[0].showModal !== 'function') return;

  html.setAttribute('data-dlg', '');

  function anyOpen() { return !!document.querySelector('dialog.setup[open]'); }

  function openSetup(dlg, from) {
    dlg.__opener = from || null;
    dlg.showModal();
    html.setAttribute('data-modal', '');
    /* Focus the content region rather than whatever happens to be
       first. It is the scroll container, and a scroll container with
       no tabindex is unreachable by keyboard in Chromium and WebKit
       — so this earns the tabindex twice over. */
    var region = dlg.querySelector('.setup__scroll');
    if (region) region.focus();
  }

  dialogs.forEach(function (dlg) {
    dlg.addEventListener('close', function () {
      if (!anyOpen()) html.removeAttribute('data-modal');
      /* every listbox inside this dialog goes with it */
      dlg.__closeListboxes && dlg.__closeListboxes();
      var o = dlg.__opener;
      dlg.__opener = null;
      if (o && document.contains(o)) o.focus();
    });

    /* A click whose target is the dialog itself is a click on the
       ::backdrop — the dialog's own box is filled edge to edge by
       the pane, so nothing else can report it. */
    dlg.addEventListener('click', function (e) {
      if (e.target === dlg) dlg.close();
    });

    dlg.querySelectorAll('[data-close]').forEach(function (b) {
      b.addEventListener('click', function (e) { e.preventDefault(); dlg.close(); });
    });
  });

  document.querySelectorAll('a.mode[href^="#setup-"]').forEach(function (a) {
    var dlg = document.getElementById(a.getAttribute('href').slice(1));
    if (!dlg || dlg.tagName !== 'DIALOG') return;
    a.addEventListener('click', function (e) {
      e.preventDefault();
      openSetup(dlg, a);
    });
  });

  /* a deep link still opens the right setup — as a modal, and
     without leaving a fragment behind that the CSS would act on. */
  var hash = location.hash;
  if (/^#setup-[\w-]+$/.test(hash)) {
    var deep = document.getElementById(hash.slice(1));
    if (deep && deep.tagName === 'DIALOG') {
      history.replaceState(null, '', location.pathname + location.search);
      openSetup(deep, null);
    }
  }

  /* the one seam format detection needs: it has to be able to open a
     DIFFERENT setup than the one the player is in, and it must reuse
     this opener rather than call showModal() itself — the scroll lock,
     the focus target and the opener bookkeeping all live here. Also
     the feature test: if this block bailed out above, SETUP_OPEN is
     undefined and detection stands down with it, because with no
     <dialog> there is nothing to switch. */
  window.SETUP_OPEN = openSetup;
})();

/* ==================================================================
   THE SAVED-DECK / CARD-POOL LISTBOX

   One component, two skins. See the CSS block for why the popup is
   parked in .lb-layer rather than inside .deckpick or on <body>.

   The <select> remains the value. Nothing here reads state from the
   DOM it built; it reads selectedIndex and writes selectedIndex.
   ================================================================== */
(function () {
  var seq = 0;

  function svgCaret() {
    var s = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    s.setAttribute('class', 'lb__caret');
    s.setAttribute('viewBox', '0 0 24 24');
    s.setAttribute('fill', 'none');
    s.setAttribute('stroke', 'currentColor');
    s.setAttribute('stroke-width', '2.6');
    s.setAttribute('stroke-linecap', 'round');
    s.setAttribute('stroke-linejoin', 'round');
    s.setAttribute('aria-hidden', 'true');
    var p = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    p.setAttribute('d', 'M5 9l7 7 7-7');
    s.appendChild(p);
    return s;
  }

  function svgMark() {
    var w = document.createElement('span');
    w.className = 'mark';
    w.setAttribute('aria-hidden', 'true');
    w.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" ' +
                  'stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 12.5 9.5 17.5 19.5 6.5"/></svg>';
    return w;
  }

  function build(root, kind) {
    var sel = root.querySelector('select');
    if (!sel) return;
    var pane = root.closest('.setup__pane');
    if (!pane) return;

    var layer = pane.querySelector(':scope > .lb-layer');
    if (!layer) {
      layer = document.createElement('div');
      layer.className = 'lb-layer';
      pane.appendChild(layer);
    }

    var id = sel.id || ('lb' + (++seq));
    var opts = [].slice.call(sel.options);
    var label = sel.id ? document.querySelector('label[for="' + sel.id + '"]') : null;
    if (label && !label.id) label.id = id + '-lbl';

    /* the original rich rows, captured BEFORE they are moved, so the
       clones can be matched to their option by slug */
    var byValue = {};
    root.querySelectorAll('.deckprev').forEach(function (p) {
      var m = /(?:^|\s)deckprev--([\w-]+)/.exec(p.className);
      if (m) byValue[m[1]] = p;
    });

    root.classList.add('lb', kind === 'chip' ? 'lb--chip' : 'lb--deck');

    /* ---------------- the trigger ---------------- */
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.id = id + '-btn';
    btn.className = 'lb__btn' + (kind === 'chip' ? ' ch' : '');
    btn.setAttribute('aria-haspopup', 'listbox');
    btn.setAttribute('aria-expanded', 'false');

    var valText = document.createElement('span');
    valText.id = id + '-val';
    if (kind === 'chip') {
      valText.className = 'lb__text';
      btn.appendChild(valText);
    } else {
      valText.className = 'u-vh';
      var visual = document.createElement('span');
      visual.className = 'lb__val';
      visual.setAttribute('aria-hidden', 'true');
      opts.forEach(function (o) {
        var p = byValue[o.value];
        if (p) visual.appendChild(p);
      });
      btn.appendChild(visual);
      btn.appendChild(valText);
    }
    btn.appendChild(svgCaret());
    /* the accessible name is the FIELD then the value — a trigger
       that announces only "Premier" names the value and not the
       field it belongs to. */
    btn.setAttribute('aria-labelledby', (label ? label.id + ' ' : '') + valText.id);
    if (!label) btn.setAttribute('aria-label', 'Options');
    root.appendChild(btn);

    /* ---------------- the popup ---------------- */
    var wrap = document.createElement('div');
    wrap.className = 'lb__wrap lift';
    var pop = document.createElement('div');
    pop.className = 'lb__pop ch';
    var list = document.createElement('div');
    list.id = id + '-list';
    list.className = 'lb__opts';
    list.setAttribute('role', 'listbox');
    list.tabIndex = -1;
    if (label) list.setAttribute('aria-labelledby', label.id);
    else list.setAttribute('aria-label', 'Options');

    var rows = opts.map(function (o, i) {
      var row = document.createElement('div');
      row.id = id + '-opt-' + i;
      row.className = 'lb__opt ch' + (kind === 'chip' ? ' lb__opt--text' : '');
      row.setAttribute('role', 'option');
      row.setAttribute('aria-selected', String(i === sel.selectedIndex));

      if (kind === 'chip') {
        var t = document.createElement('span');
        t.className = 'lb__txt';
        t.textContent = o.text;
        row.appendChild(t);
      } else {
        var src = byValue[o.value];
        if (src) {
          var c = src.cloneNode(true);
          c.classList.remove('ch');
          /* the row's visible text IS its name; the sighted-hidden
             restatement would only double it */
          c.querySelectorAll('.u-vh').forEach(function (n) { n.remove(); });
          row.appendChild(c);
        } else {
          var f = document.createElement('span');
          f.className = 'lb__txt';
          f.textContent = o.text;
          row.appendChild(f);
        }
      }
      row.appendChild(svgMark());
      row.addEventListener('click', function () { commit(i); });
      list.appendChild(row);
      return row;
    });

    pop.appendChild(list);
    wrap.appendChild(pop);
    layer.appendChild(wrap);

    var active = sel.selectedIndex < 0 ? 0 : sel.selectedIndex;

    /* ---------------- value + sync ---------------- */
    function valueLabel(i) {
      var o = opts[i];
      if (!o) return '';
      if (kind === 'chip') return o.text;
      var p = byValue[o.value];
      if (!p) return o.text;
      var name = p.querySelector('.deck__name');
      var sub = p.querySelector('.deck__sub');
      var n = p.querySelector('.deck__n');
      if (!name) return (p.textContent || o.text).trim();
      return [name.textContent, sub && sub.textContent, n && n.textContent]
        .filter(Boolean).map(function (s) { return s.replace(/\s+/g, ' ').trim(); }).join(', ');
    }

    function sync() {
      rows.forEach(function (r, j) { r.setAttribute('aria-selected', String(j === sel.selectedIndex)); });
      valText.textContent = valueLabel(sel.selectedIndex);
    }

    function commit(i) {
      if (sel.selectedIndex !== i) {
        sel.selectedIndex = i;
        sel.dispatchEvent(new Event('change', { bubbles: true }));
      }
      sync();
      close(true);
    }

    /* ---------------- open / close / place ---------------- */
    function isOpen() { return wrap.hasAttribute('data-open'); }

    function place() {
      var r = btn.getBoundingClientRect();
      var vw = document.documentElement.clientWidth;
      var vh = document.documentElement.clientHeight;
      var w = kind === 'chip' ? Math.max(r.width, 248) : r.width;
      w = Math.min(w, vw - 16);
      /* The deck popup is exactly as wide as its trigger, so it hangs
         off the leading edge. The chip's is wider than the chip, and
         the chip lives at the modal's top-RIGHT — aligned leading it
         hung out past the modal's own edge, which read as a popup
         belonging to the page rather than to the modal. Trailing-
         aligned it stays inside. */
      var left = kind === 'chip' ? r.right - w : r.left;
      left = Math.min(Math.max(8, left), Math.max(8, vw - w - 8));
      var below = vh - r.bottom - 14;
      var above = r.top - 14;
      var up = below < 190 && above > below;
      var room = Math.max(110, Math.min(352, up ? above : below));
      wrap.style.inlineSize = w + 'px';
      wrap.style.left = left + 'px';
      /* 10px of that room is the well's own padding and rim */
      list.style.setProperty('--lb-max', (room - 12) + 'px');
      if (up) {
        wrap.style.top = 'auto';
        wrap.style.bottom = (vh - r.top + 6) + 'px';
      } else {
        wrap.style.bottom = 'auto';
        wrap.style.top = (r.bottom + 6) + 'px';
      }
    }

    function setActive(i) {
      active = (i + rows.length) % rows.length;
      rows.forEach(function (r, j) {
        if (j === active) r.setAttribute('data-active', '');
        else r.removeAttribute('data-active');
      });
      list.setAttribute('aria-activedescendant', rows[active].id);
      /* scrolled by hand: scrollIntoView() would scroll the dialog
         and the page behind it as well */
      var r = rows[active];
      var top = r.offsetTop, bot = top + r.offsetHeight;
      if (top < list.scrollTop) list.scrollTop = top;
      else if (bot > list.scrollTop + list.clientHeight) list.scrollTop = bot - list.clientHeight;
    }

    function onOutside(e) {
      if (wrap.contains(e.target) || btn.contains(e.target)) return;
      close(false);
    }

    function open() {
      if (isOpen()) return;
      wrap.setAttribute('data-open', '');
      btn.setAttribute('aria-expanded', 'true');
      place();
      setActive(sel.selectedIndex < 0 ? 0 : sel.selectedIndex);
      list.focus();
      document.addEventListener('pointerdown', onOutside, true);
      window.addEventListener('resize', place);
      window.addEventListener('scroll', place, true);
    }

    function close(focusTrigger) {
      if (!isOpen()) return;
      wrap.removeAttribute('data-open');
      btn.setAttribute('aria-expanded', 'false');
      document.removeEventListener('pointerdown', onOutside, true);
      window.removeEventListener('resize', place);
      window.removeEventListener('scroll', place, true);
      if (focusTrigger !== false) btn.focus();
    }

    /* the dialog closing takes its listboxes with it */
    var dlg = root.closest('dialog');
    if (dlg) {
      var prev = dlg.__closeListboxes;
      dlg.__closeListboxes = function () { prev && prev(); close(false); };
    }

    /* ---------------- keyboard ---------------- */
    btn.addEventListener('click', function () { isOpen() ? close(true) : open(); });

    btn.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === 'Down' || e.key === 'Up') {
        e.preventDefault();
        open();
      }
    });

    var buf = '', bufAt = 0;
    function typeahead(ch) {
      var now = Date.now();
      buf = (now - bufAt < 800) ? buf + ch : ch;
      bufAt = now;
      var q = buf.toLowerCase();
      /* one repeated letter cycles by first letter, which is what a
         native <select> does */
      var n = rows.length;

      function hunt(needle, from) {
        for (var k = 0; k < n; k++) {
          var i = (from + k + n) % n;
          if ((opts[i].text || '').trim().toLowerCase().indexOf(needle) === 0) { setActive(i); return true; }
        }
        return false;
      }

      /* one letter repeated cycles through the options starting with
         it, which is what a native <select> does */
      var all = /^(.)\1*$/.test(buf);
      if (hunt(all ? q.charAt(0) : q, all ? active + 1 : active)) return;

      /* the buffer matched nothing — "a" then "m" is not a hunt for
         "am", it is a fresh hunt for "m". Without this the second
         letter silently does nothing, which reads as a dead key. */
      if (buf.length > 1) {
        buf = ch;
        hunt(ch.toLowerCase(), active + 1);
      }
    }

    list.addEventListener('keydown', function (e) {
      var k = e.key;
      if (k === 'ArrowDown' || k === 'Down') { e.preventDefault(); setActive(active + 1); }
      else if (k === 'ArrowUp' || k === 'Up') { e.preventDefault(); setActive(active - 1); }
      else if (k === 'Home') { e.preventDefault(); setActive(0); }
      else if (k === 'End') { e.preventDefault(); setActive(rows.length - 1); }
      else if (k === 'Enter' || k === ' ' || k === 'Spacebar') { e.preventDefault(); commit(active); }
      else if (k === 'Escape' || k === 'Esc') {
        /* stop the dialog from taking the Escape as its own — the
           listbox is the innermost layer, so it closes first, and
           WITHOUT changing the selection */
        e.preventDefault();
        e.stopPropagation();
        close(true);
      }
      else if (k === 'Tab') { e.preventDefault(); close(true); }
      else if (k.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey && /\S/.test(k)) {
        e.preventDefault();
        typeahead(k);
      }
    });

    /* clicking the visible label should reach the trigger, not the
       <select> it still points at */
    if (label) {
      label.addEventListener('click', function (e) { e.preventDefault(); btn.focus(); });
    }

    sel.addEventListener('change', sync);

    /* Format detection writes selectedIndex directly and then calls
       this. It deliberately does NOT dispatch `change`: `change` is
       the PLAYER's signal on this page — detection listens for it to
       know the chip has been taken over by hand — so firing it here
       would make the machine look like the person. */
    root.__lbSync = sync;

    sync();
    root.setAttribute('data-lb', kind);
  }

  // [data-empty] pickers have no <select> to enhance — dressing one up produced the blank
  // bar a guest saw where the saved-deck dropdown should be.
  document.querySelectorAll('.deckpick:not([data-empty])').forEach(function (r) { build(r, 'deck'); });
  document.querySelectorAll('.poolpick').forEach(function (r) { build(r, 'chip'); });
  // A guest's pickers are built from localStorage AFTER this ran, so they need the same
  // enhancement applied to them on the way in.
  window.BUILD_LISTBOX = build;
})();

/* ==================================================================
   AUTOMATIC FORMAT DETECTION

   When a deck link resolves, the page works out which format the
   deck is and sets the Card Pool chip itself. Four decisions worth
   writing down, because three of them are the difference between
   this reading as help and reading as a bug.

   1. THE BACKEND DECIDES. SWUDetectFormat() (AppCore/SWU/DeckValidation.php)
      walks the ladder most-restrictive-first and returns the first
      format the deck is legal in; Open is its floor, so a DETECTION
      cannot fail and there is no "no legal format" state to design
      for. The client deliberately keeps NO ladder of its own — a
      second copy would drift from the format registry the first
      time a set is added. (A link that will not PARSE is a
      different thing: see resolve().)

   2. IT SAYS SO. A silent change is the defect this product already
      has: swuAutoPickBotStyle() overwrites a hand-picked bot style
      on every deck blur, with no notice and no undo, and it reads
      as a bug. So every detection writes the .poolnote beside the
      chip. Quiet, not an alert — it is an explanation.

   3. A MANUAL PICK DOES NOT SURVIVE A NEW PASTE (owner ruling).
      Pasting re-detects and overwrites whatever the player chose.
      That is exactly what makes (2) load-bearing: the player WILL
      watch their own choice change, and the note is the only thing
      that tells them why. A manual pick does clear the note while
      it lasts, because "detected from your deck" would then be a
      lie about a chip the player set themselves.

   4. THE SWITCH FIRES ON INCOMPATIBILITY, NEVER ON BEST FIT, and
      compatibility is ONE test: can this modal seat the deck's
      leaders. A 50-card list is legal in Premier, Eternal AND Open
      at once, so "best fit" would bounce the player between modals
      for nothing. Two leaders genuinely cannot be played as 1v1
      PvP — that one moves them, and says why.
   ================================================================== */
(function () {
  /* With no <dialog> support the modal block bailed out and left
     SETUP_OPEN undefined; the page is on its approved :target
     fallback, where there is no modal to switch. Detection stands
     down with it rather than half-working. */
  var OPEN = window.SETUP_OPEN;
  if (!OPEN) return;



  /* Each modal: how many leaders it seats, what it is called when we
     announce opening it, and which pool-chip option each format maps
     to. `pool: null` means the modal has no Card Pool chip in the
     approved design — Arenabot and 1P do not — so there is nothing
     for detection to set there. They are still wired, because an
     unseatable deck must still move the player out of them. */
  var MODALS = {
    'setup-pvp': {
      name: 'PvP', leaders: 1,
      pool: { padawan: 'Padawan', premier: 'Premier', eternal: 'Eternal', open: 'Open' }
    },
    'setup-twin-suns': {
      name: 'Twin Suns', leaders: 2,
      /* Team Suns is the Twin Suns pool played in teams; the chip
         has no separate option and should not grow one. */
      pool: { twinsuns: 'Twin Suns', teamsuns: 'Twin Suns', open: 'Open' }
    },
    'setup-arenabot': { name: 'Arenabot', leaders: 1, pool: null },
    'setup-solo':     { name: '1P Mode',  leaders: 1, pool: null }
  };

  var NOTE = 'Detected from your deck — change if you need to';
  /* the floor needs one more clause, or "detected" on the wildcard
     pool looks like detection gave up rather than answered */
  var NOTE_FLOOR = 'Detected from your deck — no tighter pool fits it. Change if you need to';


  /* where a deck this modal cannot seat belongs. Among the modals
     that CAN seat it, prefer one that also has a chip for the
     detected pool, so a Twin Suns list lands in Twin Suns rather
     than in whichever two-leader modal is first. */
  function homeFor(deck, fmt) {
    var want = deck.leaders.length, fallback = null, id, m;
    for (id in MODALS) {
      m = MODALS[id];
      if (m.leaders !== want) continue;
      if (m.pool && m.pool[fmt]) return id;
      if (!fallback) fallback = id;
    }
    return fallback;
  }

  /* WRITTEN FROM THE DECK, NOT FROM THE FORMAT REGISTRY. "your deck
     has two leaders" tells the player something true about the thing
     in their hand. "this deck is Twin Suns legal" restates a label
     they never asked about. */
  function reason(deck) {
    var n = deck.leaders.length;
    if (n === 2) return 'your deck has two leaders';
    if (n === 1) return 'your deck has one leader and ' + deck.cards + ' cards';
    return 'your deck has ' + n + ' leaders';
  }


  /* ---------------- the chip and its note ---------------- */
  function setPool(dlg, label) {
    var pick = dlg.querySelector('.poolpick');
    var sel = pick && pick.querySelector('select');
    if (!sel) return false;
    for (var i = 0; i < sel.options.length; i++) {
      if (sel.options[i].text !== label) continue;
      sel.selectedIndex = i;
      /* the listbox is a VIEW of the <select>; ask it to re-read.
         No `change` — see the note on __lbSync. */
      if (pick.__lbSync) pick.__lbSync();
      return true;
    }
    return false;
  }

  function setNote(dlg, text) {
    var n = dlg.querySelector('.poolnote');
    if (!n) return;
    n.textContent = text || '';
    n.hidden = !text;
  }

  function apply(dlg, deck, fmt) {
    var m = MODALS[dlg.id];
    var label = m && m.pool && m.pool[fmt];
    if (!label) return;                 /* no chip in this modal */
    if (setPool(dlg, label)) setNote(dlg, fmt === 'open' ? NOTE_FLOOR : NOTE);
  }

  /* ---------------- the announced reason ----------------
     The modal switches out from under the player, so the reason has
     to reach assistive tech and not only sighted eyes. The channel is
     the dialog's accessible DESCRIPTION, which is announced together
     with its name the moment it opens — hence set BEFORE showModal().
     Deliberately not a live region: a live region would race the
     dialog's own announcement and read the reason twice. */
  function say(dlg, text) {
    var box = dlg.querySelector('.whyline');
    var t = box && box.querySelector('.whyline__t');
    if (!t) return;
    t.textContent = text;
    box.hidden = false;
    dlg.setAttribute('aria-describedby', t.id);
  }

  function unsay(dlg) {
    var box = dlg.querySelector('.whyline');
    var t = box && box.querySelector('.whyline__t');
    if (box) box.hidden = true;
    if (t) t.textContent = '';
    dlg.removeAttribute('aria-describedby');
  }

  function switchTo(from, to, deck, fmt, link) {
    var input = to.querySelector('input[data-detect]');
    if (input) input.value = link;      /* carry the deck link across */
    /* ...and carry the CHOICE across too. Without this the destination's saved-deck picker
       still showed whichever deck it defaults to, so the link box said one deck and the picker
       said another. The link is what gets played, so the picker must not contradict it. */
    SYNC_PICKER_TO_LINK(to, link);
    apply(to, deck, fmt);
    say(to, 'Opened ' + MODALS[to.id].name + ' — ' + reason(deck));
    /* keep the card that started all this as the return address, so
       Escape out of the modal the player did not ask for still lands
       back on the splash where they left off. Read before close(),
       which clears it. */
    var opener = from.__opener || null;
    from.close();
    OPEN(to, opener);                   /* focuses the new .setup__scroll */
  }

  /* Ask the BACKEND. SWUDetectFormat() (AppCore/SWU/DeckValidation.php) walks the ladder
     most-restrictive-first and returns the first format the deck is legal in, with Open as the
     floor — so detection never fails. The client does not re-implement that ladder; it would
     drift from the format registry the moment a set is added.
     ⚠ This replaces the mockup's four-URL fixture table, which only ever worked for its samples. */
  var _detectSeq = 0;

  function resolve(input) {
    var dlg = input.closest('dialog.setup');
    if (!dlg) return;
    var link = String(input.value || '').trim();
    if (!link) return;

    /* a sequence guard, so a slow response for an old link cannot land after a newer one and
       drag the player into a modal for a deck they have already replaced */
    var seq = ++_detectSeq;

    var body = 'deckLink=' + encodeURIComponent(link) + '&format=premier';
    fetch('/TCGEngine/SWUSim/ValidateDeck.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (seq !== _detectSeq) return;                 /* superseded */
      /* A link that will not resolve is NOT this feature's business — the deck-link error path
         owns it. Detection changes nothing: no note, no chip move, no switch. */
      if (!data || !data.success || !data.detectedFormat) return;

      var leaders = String(data.leaderName || '').split(' / ').filter(function (x) { return x.trim(); });
      var deck = {
        leaders: leaders.length ? leaders : [''],
        base: data.baseName || '',
        cards: data.deckCount || 0
      };
      var fmt = data.detectedFormat;
      var here = MODALS[dlg.id];

      /* playable where they already are → stay put, just set the chip */
      if (here && here.leaders === deck.leaders.length) { apply(dlg, deck, fmt); return; }

      var toId = homeFor(deck, fmt);
      var to = toId && document.getElementById(toId);
      if (!to || to === dlg) { apply(dlg, deck, fmt); return; }
      switchTo(dlg, to, deck, fmt, link);
    })
    .catch(function () { /* network failure is the error path's business, not detection's */ });
  }

  /* ---------------- wiring ---------------- */
  [].slice.call(document.querySelectorAll('dialog.setup input[data-detect]')).forEach(function (input) {
    /* `change` is the resolve — blur or Enter, never a keystroke, so
       nothing fires half way through a typed URL. `paste` is added on
       top because a pasted link is the entire story of this feature,
       and making the player blur the field before anything happens
       feels broken. The timeout is what lets the value land first. */
    input.addEventListener('change', function () { resolve(input); });
    input.addEventListener('paste', function () {
      setTimeout(function () { resolve(input); }, 0);
    });
  });

  [].slice.call(document.querySelectorAll('dialog.setup')).forEach(function (dlg) {
    /* a stale reason must not greet the player the next time they
       open this modal by hand */
    dlg.addEventListener('close', function () { unsay(dlg); });

    var sel = dlg.querySelector('.poolpick select');
    if (!sel) return;
    /* the player taking the chip over. Only ever the player: setPool
       writes selectedIndex without dispatching `change`. */
    sel.addEventListener('change', function () { setNote(dlg, ''); });
  });

  /* mockup scaffolding: fill the field and resolve, so a reviewer can
     reach all four fixtures without typing. */

  /* Choosing a saved deck writes its link into the box, and the pool chip has to follow the
     deck it just loaded. resolve() is scoped to this block, so the picker reaches it here. */
  window.SETUP_RESOLVE = resolve;
  /* "last deck used" needs the same two facts this block owns: how many leaders a modal seats,
     and how to set its pool chip. */
  window.SETUP_MODALS  = MODALS;
  window.SETUP_SETPOOL = setPool;
})();

// ── Guest saved decks live in THIS BROWSER ────────────────────────────────────
// Owner, 2026-09-25: guests may save decks too. They have no account row to write to, so the
// list is localStorage — same key the shared DeckLibrary uses for its 'local' storage mode, so
// the two never fork. The server renders an empty picker for a guest (it cannot know what is in
// their browser) and this fills it in on load.
//
// ⚠ Only LINKS are stored, here and on the account (SWUDeckInputIsLink, enforced server-side).
// The savable/name/leader/base facts all come from ValidateDeck.php — the client never
// re-implements that rule, or it drifts from the resolver the first time a deck site is added.
var GUEST_KEY = 'tcgengine:savedDecks:SWUSim';
var IS_GUEST = !!window.SWU_IS_GUEST;

var GUEST_DECKS = {
  load: function () {
    try {
      var raw = localStorage.getItem(GUEST_KEY);
      var list = raw ? JSON.parse(raw) : [];
      return Array.isArray(list) ? list.filter(function (d) { return d && d.input; }) : [];
    } catch (e) { return []; }        /* private window, blocked storage, corrupt JSON */
  },
  save: function (list) {
    try { localStorage.setItem(GUEST_KEY, JSON.stringify(list)); return true; }
    catch (e) { return false; }       /* quota or blocked — the caller reports it */
  },
  add: function (deck) {
    var list = GUEST_DECKS.load();
    // identity is the LINK, so re-saving the same deck updates rather than duplicates
    list = list.filter(function (d) { return d.input !== deck.input; });
    list.unshift(deck);
    return GUEST_DECKS.save(list) ? list : null;
  }
};

/* a stable key per deck, for the .deckprev--KEY rule that reveals its row */
function GUEST_KEY_FOR(link) {
  var h = 0, i;
  for (i = 0; i < link.length; i++) { h = ((h << 5) - h + link.charCodeAt(i)) | 0; }
  return 'g' + (h >>> 0).toString(36);
}

function CARD_IMG(id) {
  return '/TCGEngine/AppCore/SWU/Images/WebpImages/' + encodeURIComponent(id) + '.webp';
}

/* ⚠ SECOND BUILDER. SWUSetupDeckPicker() in SWUSim/Custom/SetupPanels.php builds this same
   markup server-side for an account. The two must agree on every class and data attribute the
   stylesheet and SETUP_DECK_FOR() depend on — swusim-menu2-pickers.mjs asserts a guest-rendered
   picker and an account-rendered one have the SAME shape, which is the only thing keeping them
   honest. Change one, run that gate. */
function GUEST_BUILD_PICKER(host, decks) {
  var slot = host.getAttribute('data-slot') || 'own';
  var selId = host.getAttribute('data-select-id') || ('guest-' + slot);
  var noneLabel = host.getAttribute('data-none-label') || '';
  var esc = function (s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[c];
    });
  };

  var opts = '', rows = '', css = '';
  if (noneLabel) {
    opts += '<option value="none" data-deck-input="" selected>' + esc(noneLabel) + '</option>';
    rows += '<span class="deckprev deckprev--none ch"><span>' + esc(noneLabel) + '</span></span>';
  }
  decks.forEach(function (d, i) {
    var key = d.key || GUEST_KEY_FOR(d.input);
    var subs = (d.leaders || []).concat(d.base ? [d.base] : []);
    var sub = (d.subtitle || '') || subs.join(' · ');
    var cards = '<span class="cards" aria-hidden="true">' +
      (d.leaders || []).map(function (id) {
        return '<span class="tc tc--leader"><img src="' + CARD_IMG(id) + '" alt="" decoding="async" width="628" height="450"></span>';
      }).join('') +
      (d.base ? '<span class="tc tc--base"><img src="' + CARD_IMG(d.base) + '" alt="" decoding="async" width="628" height="450"></span>' : '') +
      '</span>';
    opts += '<option value="' + esc(key) + '" data-deck-input="' + esc(d.input) + '"' +
            ((i === 0 && !noneLabel) ? ' selected' : '') + '>' + esc(d.name) + '</option>';
    rows += '<span class="deckprev deckprev--' + esc(key) + ' ch">' + cards +
            '<span class="deck__text"><span class="deck__name">' + esc(d.name) + '</span>' +
            '<span class="deck__sub">' + esc(sub) + '</span></span>' +
            '<span class="deck__end">' +
            (d.count ? '<span class="deck__n">' + (d.count | 0) + '&nbsp;cards</span>' : '') +
            '</span><span class="u-vh">' + esc(sub) + (d.count ? '. ' + (d.count | 0) + ' cards.' : '') + '</span>' +
            '</span>';
    // the row is display:none until a rule for ITS key reveals it (same contract as the
    // server-rendered keys — see SWUSetupPreviewStyles)
    css += '.deckpick:has(option[value="' + key + '"]:checked) .deckprev--' + key + '{display:grid}';
  });

  if (css) {
    var st = document.getElementById('guest-deck-styles') ||
             document.head.appendChild(Object.assign(document.createElement('style'), { id: 'guest-deck-styles' }));
    st.textContent += css;
  }

  var div = document.createElement('div');
  div.className = 'deckpick ch';
  div.setAttribute('data-slot', slot);
  /* keep what it was built FROM, so a later save can rebuild it in place */
  div.setAttribute('data-guest', '');
  div.setAttribute('data-select-id', selId);
  div.setAttribute('data-none-label', noneLabel);
  div.innerHTML = '<span class="selwrap ch"><select class="select" id="' + esc(selId) +
                  '" name="' + esc(selId) + '" data-slot="' + esc(slot) + '">' + opts +
                  '</select></span>' + rows;
  host.replaceWith(div);
  return div;
}

function GUEST_RENDER_ALL() {
  if (!IS_GUEST) return;
  var decks = GUEST_DECKS.load();
  if (!decks.length) return;                 /* leave the empty state exactly as rendered */
  /* [data-guest] pickers are ones this function built earlier — after a save they are rebuilt in
     place. Reloading the page would be simpler and would also throw away the "Saved X" line the
     player just earned, which is the feedback this whole flow exists to give. */
  document.querySelectorAll('.deckpick[data-empty], .deckpick[data-guest]').forEach(function (host) {
    var wrap = host.closest('.lb') || host;   /* the enhancement wraps it; replace the whole thing */
    if (wrap !== host) {
      var fresh = host.cloneNode(false);
      fresh.className = 'deckpick';
      wrap.replaceWith(fresh);
      host = fresh;
    }
    var built = GUEST_BUILD_PICKER(host, decks);
    if (typeof window.BUILD_LISTBOX === 'function') window.BUILD_LISTBOX(built, 'deck');
  });
}
GUEST_RENDER_ALL();

// ── "Last deck used" (owner, 2026-09-25) ──────────────────────────────────────
// Auto-fill the Deck Link box with the deck you last STARTED A GAME with; if that deck is no
// longer available, leave it blank.
//
// Three decisions worth stating, because each rules out an obvious-looking alternative:
//
//  1. USED means a game actually started — recorded where the submission succeeds, not where a
//     link resolves. A deck you pasted to look at and rejected must not come back next visit.
//  2. It prefills only the modals that CAN SEAT IT, matched on leader count. Prefilling a
//     two-leader deck into PvP would either leave an illegal deck in the box or let detection
//     yank the player into Twin Suns for a modal they opened deliberately.
//  3. It is remembered in BOTH places: localStorage always, the account too when signed in. The
//     ACCOUNT COPY WINS on read, so the two cannot meaningfully disagree after you sign in on a
//     machine you had used as a guest.
var LAST_KEY = 'tcgengine:lastDeck:SWUSim';
/* this block is a separate <script> from the legacy menu code, so it computes its own base
   rather than borrowing swusimAppBase()/SAVEDECKS_URL out of that scope */
function LAST_BASE() { var p = location.pathname, i = p.indexOf('/TCGEngine/'); return i >= 0 ? p.slice(0, i + 11) : '/TCGEngine/'; }
function LAST_SAVEDECKS_URL() { return LAST_BASE() + 'SWUSim/SavedDecks.php'; }

var LAST_DECK = {
  read: function () {
    if (window.SWU_LAST_DECK && window.SWU_LAST_DECK.deckInput) {
      var a = window.SWU_LAST_DECK;
      return { input: a.deckInput, format: a.format || '', leaders: +a.leaders || 1, name: a.deckName || '' };
    }
    try {
      var raw = localStorage.getItem(LAST_KEY);
      var d = raw ? JSON.parse(raw) : null;
      return (d && d.input) ? d : null;
    } catch (e) { return null; }
  },
  write: function (deck) {
    try { localStorage.setItem(LAST_KEY, JSON.stringify(deck)); } catch (e) {}
    if (IS_GUEST) return;
    var body = 'action=lastdeck&deckInput=' + encodeURIComponent(deck.input) +
               '&format=' + encodeURIComponent(deck.format || '') +
               '&leaders=' + encodeURIComponent(deck.leaders || 1) +
               '&deckName=' + encodeURIComponent(deck.name || '');
    fetch(LAST_SAVEDECKS_URL(), { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body })
      .catch(function () { /* the browser copy already landed; the account copy is a bonus */ });
  },
  forget: function () {
    try { localStorage.removeItem(LAST_KEY); } catch (e) {}
    window.SWU_LAST_DECK = null;
    if (IS_GUEST) return;
    fetch(LAST_SAVEDECKS_URL(), { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                           body: 'action=forgetlastdeck' }).catch(function () {});
  }
};

/* Record it where the game actually starts, from the VALIDATED response — the server has just
   resolved this exact deck, so its leader count and format are known good rather than guessed
   from whichever modal happens to be open. Only links are remembered: a pasted list has no
   source to return to and nothing to show in a link box. */
function REMEMBER_DECK(input, vres) {
  input = String(input || '').trim();
  if (!input || !IS_LINK(input)) return;
  vres = vres || {};
  var leaders = [].concat(vres.leaderID || []).filter(Boolean).length || 1;
  LAST_DECK.write({
    input: input,
    format: vres.detectedFormat || '',
    leaders: leaders,
    name: vres.deckName || ''
  });
}
window.REMEMBER_DECK = REMEMBER_DECK;

/* Verify ONCE, on load, and only prefill after it resolves. Filling first and clearing on
   failure would flash a dead link into the box; starting blank and filling ~300ms later is
   invisible, because the modals are closed at that point anyway. */
var _lastDeckReady = null;
function LAST_DECK_VERIFY() {
  if (_lastDeckReady) return _lastDeckReady;
  var d = LAST_DECK.read();
  if (!d) { _lastDeckReady = Promise.resolve(null); return _lastDeckReady; }
  _lastDeckReady = fetch(LAST_BASE() + 'SWUSim/ValidateDeck.php', {
      method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'deckLink=' + encodeURIComponent(d.input) + '&format=premier'
    })
    .then(function (r) { return r.json(); })
    .then(function (j) {
      if (!j || !j.success) { LAST_DECK.forget(); return null; }   // gone upstream → leave it blank
      var leaders = [].concat(j.leaderID || []).filter(Boolean).length || d.leaders || 1;
      return { input: d.input, format: j.detectedFormat || d.format || '', leaders: leaders,
               name: j.deckName || d.name || '' };
    })
    .catch(function () { return null; });   // offline is NOT "no longer available" — forget nothing
  return _lastDeckReady;
}

/* Fill a modal as it opens, if it can seat the deck and the player has not already put
   something there. */
function LAST_DECK_PREFILL(dlg) {
  if (!dlg) return;
  LAST_DECK_VERIFY().then(function (d) {
    if (!d || !dlg.open) return;
    var here = (window.SETUP_MODALS || {})[dlg.id];
    if (!here || here.leaders !== d.leaders) return;      // cannot seat it — leave the modal blank
    var link = dlg.querySelector('input[data-detect]');
    if (!link || String(link.value || '').trim()) return; // never overwrite what the player typed
    link.value = d.input;
    SYNC_PICKER_TO_LINK(dlg, d.input);                    // keep the picker from contradicting it
    if (here.pool && here.pool[d.format] && window.SETUP_SETPOOL) window.SETUP_SETPOOL(dlg, here.pool[d.format]);
    /* deck names often carry their own closing punctuation ("… Lightmaker Explosion!"), and a
       second full stop after one reads as a typo */
    var tail = d.name ? ' — ' + d.name : '';
    PICK_SAY(dlg, 'own', 'Filled in the deck you played last' + tail + (/[.!?]$/.test(tail) ? '' : '.'));
  });
}
LAST_DECK_VERIFY();   // start the round trip now, so an opening modal rarely waits on it

/* Watch the `open` attribute rather than wrapping one opener: a modal is opened by a mode card,
   by a #hash on load, by a private invite and by format detection switching modals, and only the
   attribute is common to all of them. */
(function WATCH_SETUP_OPEN() {
  var dialogs = document.querySelectorAll('dialog.setup');
  if (!dialogs.length || typeof MutationObserver !== 'function') return;
  var mo = new MutationObserver(function (recs) {
    recs.forEach(function (r) { if (r.target.open) LAST_DECK_PREFILL(r.target); });
  });
  dialogs.forEach(function (d) { mo.observe(d, { attributes: true, attributeFilter: ['open'] }); });
  dialogs.forEach(function (d) { if (d.open) LAST_DECK_PREFILL(d); });   // already open on load
})();

// ── What deck a slot ("own" / "bot") is actually set to ───────────────────────
// Three controls can name a deck: the link field, the Saved Decks dropdown, and the pre-con
// well. Whichever the player touched LAST is the one they meant, so each control clears the
// others in its slot (see SETUP_BIND_PICKERS) and this simply reads whatever survived. Without
// this the pickers were decoration — they changed the preview and not the deck that was played.
function SETUP_DECK_FOR(dlg, slot, linkEl) {
  var typed = linkEl ? String(linkEl.value || '').trim() : '';
  if (typed) return typed;
  var sel = dlg.querySelector('select[data-slot="' + slot + '"]');
  if (sel && sel.selectedIndex >= 0) {
    var o = sel.options[sel.selectedIndex];
    var v = o && o.getAttribute('data-deck-input');
    if (v) return v;
  }
  var pc = dlg.querySelector('input[data-slot="' + slot + '"][data-deck-input]:checked');
  return pc ? String(pc.getAttribute('data-deck-input') || '') : '';
}

// Point a dialog's own-deck picker at the deck a link belongs to, so the picker and the Deck
// Link box never disagree about what is about to be played. When the link is not one of the
// saved decks, the picker says so rather than leaving its default sitting there looking chosen.
function SYNC_PICKER_TO_LINK(dlg, link) {
  var sel = dlg.querySelector('select[data-slot="own"]');
  if (!sel) return;
  var match = null, i;
  for (i = 0; i < sel.options.length; i++) {
    if (sel.options[i].getAttribute('data-deck-input') === link) { match = sel.options[i]; break; }
  }
  // a pre-con left checked in this slot would still look chosen next to the deck we just loaded
  dlg.querySelectorAll('input[data-slot="own"][data-deck-input]:checked')
     .forEach(function (r) { r.checked = false; });
  if (match) {
    sel.value = match.value;
    PICK_SAY(dlg, 'own', 'Loaded ' + (match.textContent || '').trim() + ' — its deck link is in the box above.');
  } else {
    var none = sel.querySelector('option[value="none"]');
    if (none) sel.value = 'none';
    PICK_SAY(dlg, 'own', 'Playing the deck link above' + (none ? '.' : ', not a saved deck.'));
  }
  LB_SYNC(sel);
}

/* The enhanced listbox mirrors its <select>; when WE move the value under it, it has to be
   told. build() parks that as root.__lbSync — there is no event for it. */
function LB_SYNC(sel) {
  var root = sel && sel.closest ? sel.closest('.lb') : null;
  if (root && typeof root.__lbSync === 'function') root.__lbSync();
}

// Say what a pick just did. The picker changes three things at once (the link box, the format
// chip, the preview), so without a sentence the player is left to infer which of them moved.
function PICK_SAY(dlg, slot, text) {
  var box = dlg.querySelector('[data-pickmsg="' + slot + '"]');
  var t = box && box.querySelector('.pickmsg__t');
  if (!t) return;
  t.textContent = text || '';
  box.hidden = !text;
}

// One chosen deck per slot. Picking a saved deck clears a stale pre-con and a stale typed link,
// and vice versa — otherwise two controls both look chosen and only one of them counts.
(function SETUP_BIND_PICKERS() {
  document.addEventListener('change', function (ev) {
    var el = ev.target;
    if (!el || !el.closest) return;
    var dlg = el.closest('dialog.setup, .setup');
    if (!dlg) return;
    var slot = el.getAttribute && el.getAttribute('data-slot');
    if (!slot) return;
    var isSelect = el.tagName === 'SELECT';
    var opt = isSelect ? el.options[el.selectedIndex] : null;
    var input = isSelect
      ? (opt && opt.getAttribute('data-deck-input')) || ''
      : el.getAttribute('data-deck-input') || '';
    if (!input) {                             // the "use a pre-con below" row chooses nothing
      if (isSelect) PICK_SAY(dlg, slot, '');
      return;
    }

    var links = dlg.querySelectorAll('input[data-detect], input[type="url"], input[type="text"]');
    var linkEl = links[slot === 'bot' ? 1 : 0];
    var label = isSelect ? (opt.textContent || '').trim() : pcName(el);

    if (isSelect) {
      // clear a stale pre-con in this slot
      dlg.querySelectorAll('input[data-slot="' + slot + '"][data-deck-input]:checked')
         .forEach(function (r) { r.checked = false; });
      // Owner, 2026-09-25: show the SOURCE LINK of the list you picked. Only links are savable,
      // so a saved deck always has one — a legacy raw row (saved before that rule) has not, and
      // the field is cleared rather than filled with a JSON blob.
      if (linkEl) {
        if (IS_LINK(input)) {
          linkEl.value = input;
          /* re-detect, so the pool chip follows the deck it just loaded */
          if (typeof window.SETUP_RESOLVE === 'function') window.SETUP_RESOLVE(linkEl);
          PICK_SAY(dlg, slot, 'Loaded ' + label + ' — its deck link is in the box above.');
        } else {
          linkEl.value = '';
          PICK_SAY(dlg, slot, 'Loaded ' + label + ' — saved before deck links were required, so there is no link to show.');
        }
      }
    } else {
      var sel = dlg.querySelector('select[data-slot="' + slot + '"]');
      if (sel) {
        var none = sel.querySelector('option[value="none"]');
        if (none) sel.value = 'none';
        else sel.selectedIndex = -1;
        LB_SYNC(sel);
      }
      // a pre-con is a full deck list, not a link — there is nothing to put in the box
      if (linkEl) linkEl.value = '';
      PICK_SAY(dlg, slot, 'Playing the ' + label + ' pre-con' +
        (slot === 'bot' ? ' as the bot’s deck.' : '.'));
    }
  });

  // typing a link drops whatever was picked in THAT slot, and says so.
  // The selector has to be the same list the rest of this file uses to find a slot's link box:
  // only the OWN box carries [data-detect] (format detection reads the player's deck, not the
  // bot's), so matching on that alone left the bot's pre-con checked and looking chosen while
  // SETUP_DECK_FOR was already playing the typed link instead.
  var LINKBOX = 'input[data-detect], input[type="url"], input[type="text"]';
  document.addEventListener('input', function (ev) {
    var el = ev.target;
    if (!el || !el.matches || !el.matches(LINKBOX)) return;
    var dlg = el.closest('dialog.setup, .setup');
    if (!dlg) return;
    var links = [].slice.call(dlg.querySelectorAll(LINKBOX));
    var slot = links.indexOf(el) === 1 ? 'bot' : 'own';
    dlg.querySelectorAll('input[data-slot="' + slot + '"][data-deck-input]:checked')
       .forEach(function (r) { r.checked = false; });
    PICK_SAY(dlg, slot, '');
  });

  function pcName(radio) {
    var lab = radio.parentElement && radio.parentElement.querySelector('.pc__name');
    return lab ? (lab.childNodes[0].textContent || '').trim() : 'selected';
  }
})();

/* ── Arenabot: the bot's play style follows the bot's DECK ────────────────────
   Owner, 2026-09-22: EVERY deck load re-picks, even over a hand-picked style; a failed lookup
   changes nothing. What is new here is that it SAYS SO — the old version rewrote the control on
   every deck blur with no notice and no undo, which read as the control fighting you.

   Registered AFTER SETUP_BIND_PICKERS so that, on a shared `change`, the binder has already done
   its one-deck-per-slot clearing and this reads the state that survived.

   Two sources, deliberately:
     · a bot PRE-CON answers offline — BotDeckLabels.json's own `style` is what the classifier
       would say, so asking the server would be a round trip to be told what we just rendered;
     · a link or saved deck goes to APIs/SWUBotDeckStyle.php (classifier: Custom/BotDeckStyle.php).
   With the bot slot empty, the player's own deck is the subject: JoinQueue.php hands the bot the
   host's list, so the style must describe the deck the bot will actually play. */
(function BOT_STYLE_AUTOPICK() {
  var DLG = 'setup-arenabot';
  var seq = 0;

  function styleSlug(s) { return String(s || '').toLowerCase().replace(/[^a-z0-9]+/g, ''); }

  // The select carries DISPLAY LABELS ("Hard Control"), the classifier answers in slugs
  // ("hardcontrol") — SWUSetupStyleLabel() in SetupPanels.php is the other half of this mapping.
  function apply(dlg, style, why) {
    var sel = document.getElementById('ab-style');
    if (!sel || !style) return;                       // no answer: leave the player's choice alone
    var opt = null;
    for (var i = 0; i < sel.options.length; i++) {
      if (styleSlug(sel.options[i].text) === styleSlug(style)) { opt = sel.options[i]; break; }
    }
    if (!opt) return;                                 // an archetype this menu does not offer
    sel.value = opt.value;
    LB_SYNC(sel);
    PICK_SAY(dlg, 'botstyle', 'Bot style set to ' + (opt.text || '').trim() + ' — ' + why +
                              '. Change it above for a different matchup.');
  }

  function pick() {
    var dlg = document.getElementById(DLG);
    if (!dlg) return;
    var links = dlg.querySelectorAll('input[data-detect], input[type="url"], input[type="text"]');
    var botLink = links[1], ownLink = links[0];
    var typed = String((botLink && botLink.value) || '').trim();

    var pc = typed ? null : dlg.querySelector('input[data-slot="bot"][data-style]:checked');
    if (pc) {
      seq++;                                          // cancel any lookup still in flight
      var lab = pc.parentElement && pc.parentElement.querySelector('.pc__name');
      var nm = lab ? (lab.childNodes[0].textContent || '').trim() : 'that pre-con';
      apply(dlg, pc.getAttribute('data-style'), 'matching the ' + nm + ' pre-con');
      return;
    }

    var deck = SETUP_DECK_FOR(dlg, 'bot', botLink);
    var mine = false;
    if (!deck) { deck = SETUP_DECK_FOR(dlg, 'own', ownLink); mine = true; }
    if (!deck) return;

    var mySeq = ++seq;
    var xhr = new XMLHttpRequest();
    xhr.open('POST', LAST_BASE() + 'APIs/SWUBotDeckStyle.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function () {
      if (mySeq !== seq) return;                      // a newer deck was chosen while this was out
      var j;
      try { j = JSON.parse(xhr.responseText); } catch (e) { return; }
      if (!j || !j.ok || !j.style) return;            // unreadable deck: changes nothing
      apply(dlg, j.style, mine ? 'the bot will be playing your deck'
                               : 'it matches the deck you gave the bot');
    };
    xhr.onerror = function () {};
    xhr.send('rootName=SWUSim&deckLink=' + encodeURIComponent(deck));
  }

  document.addEventListener('change', function (ev) {
    var el = ev.target;
    if (!el || !el.closest) return;
    if (el.id === 'ab-style') return;                 // the player moving it by hand is not a deck load
    if (!el.closest('#' + DLG)) return;
    pick();
  });

  // Opening the modal is itself a deck load: a pre-con is checked by default, and without this
  // the control would sit on "Midrange" describing a deck that is not midrange.
  var dlg = document.getElementById(DLG);
  if (dlg && typeof MutationObserver === 'function') {
    new MutationObserver(function (recs) {
      recs.forEach(function (r) { if (r.target.open) pick(); });
    }).observe(dlg, { attributes: true, attributeFilter: ['open'] });
  }
})();

/* ── Twin Suns: the arrangement owns its own pools ───────────────────────────
   The registry gives each arrangement its OWN format ids for the same two labels:
     ffa   -> twinsuns : "Standard",  twinsuns-preview : "Preview (IC27)"
     teams -> teamsuns : "Standard",  teamsuns-preview : "Preview (IC27)"
   So the pool <select> cannot be a single static list -- its option VALUES change with the
   segment. This repopulates it from SWU_MENU (the same tree Formats.php built) whenever the
   arrangement changes, keeping the player's Standard/Preview choice by LABEL across the swap. */
(function TS_ARRANGEMENT_POOLS() {
  var dlg = document.getElementById('setup-twin-suns');
  if (!dlg) return;

  function poolsFor(groupId, optId) {
    var tree = (window.SWU_MENU && (SWU_MENU.full || SWU_MENU.tree)) || [];
    for (var i = 0; i < tree.length; i++) {
      if (tree[i].id !== groupId) continue;
      var opts = tree[i].options || [];
      for (var j = 0; j < opts.length; j++) if (opts[j].id === optId) return opts[j].pools || [];
    }
    return [];
  }

  function apply(radio) {
    var sel = dlg.querySelector('select[id$="-pool"]');
    if (!sel || !radio || !radio.dataset.opt) return;
    var pools = poolsFor(radio.dataset.group || 'twinsuns', radio.dataset.opt);
    if (!pools.length) return;
    var keepLabel = (sel.options[sel.selectedIndex] || {}).text || '';
    sel.innerHTML = '';
    pools.forEach(function (p) {
      var o = document.createElement('option');
      o.value = p.format; o.textContent = p.label || p.format;
      sel.appendChild(o);
    });
    // the same CHOICE, in the other arrangement's vocabulary -- match on the label, not the id
    for (var i = 0; i < sel.options.length; i++) {
      if (sel.options[i].text === keepLabel) { sel.selectedIndex = i; break; }
    }
    LB_SYNC(sel);        // the enhanced listbox is a VIEW of the select; ask it to re-read
  }

  dlg.addEventListener('change', function (ev) {
    if (ev.target && ev.target.name === 'ts-arr') apply(ev.target);
  });
  var checked = dlg.querySelector('input[name="ts-arr"]:checked');
  if (checked) apply(checked);
})();

/* A link, as opposed to a pasted list. The AUTHORITY is SWUDeckInputIsLink() in
   SWUSim/Custom/DeckImport.php, which ValidateDeck.php reports as `savable` — this is only the
   cheap local shape check used to decide what to put in a text box. Anything that decides
   whether a deck may be SAVED asks the server. */
function IS_LINK(s) {
  s = String(s || '').trim();
  return s !== '' && s.charAt(0) !== '{' && s.indexOf('\n') === -1 && s.indexOf('\r') === -1;
}

// ── Task 17: the modals drive the EXISTING submission path ────────────────────
// getDeckSubmission() reads a fixed set of legacy ids. Rather than rewrite that proven function,
// copy the OPEN modal's values into those fields immediately before submitting.
window.SYNC_ACTIVE_SETUP = function () {
  var dlg = document.querySelector('dialog.setup[open]') ||
            document.querySelector('.setup:target');
  if (!dlg) return false;
  var set = function (id, val) { var el = document.getElementById(id); if (el) el.value = val; };
  var val = function (sel) { var el = dlg.querySelector(sel); return el ? String(el.value || '').trim() : ''; };

  // the own-deck field is the first [data-detect] in the dialog; a second one is the bot's or
  // the hotseat second seat
  var links = dlg.querySelectorAll('input[data-detect], input[type="text"], input[type="url"]');
  set('deck-link', SETUP_DECK_FOR(dlg, 'own', links[0]));
  set('swu-deck2-input', SETUP_DECK_FOR(dlg, 'bot', links[1]));

  // The modal selects follow an id-suffix convention (pvp-pool, ts-match, ab-botstyle...) and
  // carry DISPLAY LABELS as their values ("Premier", "Best of 1"), not registry slugs. Normalise.
  var slug = function (label) {
    return String(label || '').toLowerCase().replace(/[^a-z0-9]+/g, '');
  };
  var modeFormat = ({ 'setup-pvp': 'premier', 'setup-twin-suns': 'twinsuns',
                      'setup-arenabot': 'botpractice', 'setup-solo': 'goldfish' })[dlg.id] || 'premier';

  // THE FORMAT IS READ, NEVER RECONSTRUCTED.
  // This used to slug the pool's DISPLAY LABEL back into an id, which cannot round-trip:
  // "Premier Preview (IC27)" -> premierpreviewic27, when the registry id is `preview`. Every
  // preview format in every modal submitted something that does not exist. And a SEGMENTED
  // control was never read at all, so Hotseat started Goldfish and Team Suns started Twin Suns
  // (owner report, 2026-09-26) -- the stylesheet keyed off the radio, so the UI responded while
  // the submission did not.
  //
  // Now the markup carries the registry's own id: pool <option value> is the format, and a
  // segmented radio either names its format outright (1P: goldfish / hotseat) or names the
  // registry OPTION it selects (Twin Suns: ffa / teams), whose pools the block below installs.
  // format ids are a PRIMARY KEY on the stats tables -- they have to be exact.
  var seg = dlg.querySelector('input[type="radio"][data-format]:checked');
  var poolSel = dlg.querySelector('select[id$="-pool"]');
  var poolFmt = poolSel ? String(poolSel.value || '').trim() : '';
  var fmt = (seg && seg.dataset.format) || poolFmt || modeFormat;
  set('swu-format-select', fmt);
  // The card pool IS the format for the constructed modals; the local modes have none, and
  // 'premier' is the legacy default the queue expects there.
  set('swu-cardpool-input', poolFmt || (dlg.id === 'setup-solo' || dlg.id === 'setup-arenabot' ? 'premier' : fmt));

  // "Best of 1" -> bo1. Anything else falls back to bo1 rather than sending an unknown value.
  var mt = slug(val('select[id$="-match"]'));
  set('swu-queuetype-select', mt === 'bestof3' ? 'bo3' : 'bo1');

  var bs = slug(val('select[id$="-botstyle"]')) || slug(val('select[id$="-style"]'));
  if (bs) set('swu-botstyle-select', bs);
  return true;
};

document.addEventListener('click', function (ev) {
  var btn = ev.target.closest ? ev.target.closest('button[data-act]') : null;
  if (!btn) return;
  var act = btn.dataset.act;
  if (act === 'cancel') {
    var d = btn.closest('dialog');
    if (d && d.close) d.close(); else location.hash = '#';
    return;
  }
  // Save acts on the box BESIDE the button, so Arenabot's bot row saves the bot's deck. It is
  // not a submission, so it must not run SYNC first.
  if (act === 'save') {
    var dlg = btn.closest('dialog.setup, .setup');
    var links = dlg ? [].slice.call(dlg.querySelectorAll('input[data-detect]')) : [];
    var row = btn.closest('.drow') || btn.parentElement;
    var own = (row && row.querySelector('input[data-detect]')) || links[0];
    if (typeof saveCurrentDeck === 'function') {
      saveCurrentDeck(own ? own.value : '', links.indexOf(own) === 1 ? 'bot' : 'own', dlg);
    }
    return;
  }
  // ⚠ ORDER: sync, THEN close, THEN act. SYNC_ACTIVE_SETUP() finds the open dialog with
  // `dialog.setup[open]` and returns false if there isn't one, so closing first would silently
  // submit whatever the legacy hidden fields happened to hold from a previous run.
  window.SYNC_ACTIVE_SETUP();

  // Owner, 2026-09-25: a submit dismisses the setup sheet. Every one of these three either opens
  // the waiting popup over the page or navigates away, so leaving the modal stacked underneath
  // was never right.
  // It also hands Escape back: joinQueue()'s popup says "Esc to cancel", but a native <dialog>
  // opened with showModal() eats the Escape key for itself, so that cancel could not fire while
  // the modal stayed open. Failures do not need the modal either — SWUSim has no
  // #queue-inline-error element, so showQueueInlineError() falls through to StyledAlert().
  var open = btn.closest('dialog.setup');
  if (open && open.close) open.close();

  if (act === 'join'    && typeof joinQueue === 'function')         joinQueue();
  if (act === 'private' && typeof createPrivateGame === 'function') createPrivateGame();
  if (act === 'solo'    && typeof startSoloGame === 'function')     startSoloGame();
});
</script>

<footer class="foot">
<?php include_once __DIR__ . '/Disclaimer.php'; ?>
</footer>
