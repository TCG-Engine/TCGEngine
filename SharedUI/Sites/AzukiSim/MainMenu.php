<?php
require_once __DIR__ . '/../../Render/AssetVersion.php';   // _VersionAsset() — ?v=<filemtime> cache busting
// Use __DIR__-relative includes (matching the SWUSim/SWUDeck pilot): this page is reached via the
// SharedUI/MainMenu.php pointer (which include()s it), so the cwd is SharedUI/, not this dir.
// Bare './'/'../../../' paths resolved against the wrong cwd → missing-file warnings AND silently
// pulled the ROOT SharedUI/MenuBar.php + Header.php (wrong chrome) instead of the AzukiSim ones.
include_once __DIR__ . '/MenuBar.php';
include_once __DIR__ . '/../../../AccountFiles/AccountSessionAPI.php';
include_once __DIR__ . '/../../../Database/ConnectionManager.php';
include_once __DIR__ . '/../../../AzukiSim/GeneratedCode/GeneratedCardDictionaries.php';
include_once __DIR__ . '/../../../AzukiDeck/DeckService.php';
require_once __DIR__ . '/../../Render/DeckLibrary.php';

include_once __DIR__ . '/Header.php';

$azukiSiteDef = require __DIR__ . '/SiteDef.php';
$azukiDeckLibraryConfig = DeckLibraryConfigFromSiteDef($azukiSiteDef, ['actionButtons' => true]);
$azukiBuilderDecks = IsUserLoggedIn() ? AzukiDeckLoadOwnedDecks(LoggedInUser()) : [];
$hasAzukiBuilderDecks = !empty($azukiBuilderDecks);
$azukiDeckError = trim((string)($_GET['deckError'] ?? ''));
$azukiDeckCodes = [];
foreach ($azukiBuilderDecks as $azukiBuilderDeck) {
  $friendlyCode = trim((string)($azukiBuilderDeck['friendlyCode'] ?? ''));
  if ($friendlyCode !== '') {
    $azukiDeckCodes[(string)intval($azukiBuilderDeck['assetIdentifier'] ?? 0)] = $friendlyCode;
  }
}

?>
<div class="row-wrapper azuki-menu-grid">
  <!-- Active Games Section -->
  <div class="card azuki-glass-card azuki-active-card is-empty">
    <button class="azuki-active-refresh" type="button" onclick="refreshOpenGames(this)" aria-label="Refresh active games">
      <img class="azuki-active-refresh-icon" src="/TCGEngine/Assets/Icons/refresh.svg" width="16" height="16" alt="">
      <span class="azuki-active-refresh-spinner" aria-hidden="true"></span>
      <span class="azuki-active-refresh-check" aria-hidden="true">&#10003;</span>
    </button>
    <h2>Active Games <span id="active-game-count" class="azuki-active-count" aria-live="polite">0</span></h2>
    <div id="rejoin-last-game-banner" class="azuki-rejoin-banner" style="display: none;">
      <button id="rejoin-last-game-btn" class="azuki-rejoin-banner-button" type="button" onclick="rejoinLastGame()" aria-describedby="rejoin-last-game-note">
        <span class="azuki-rejoin-banner-icon" aria-hidden="true">↩</span>
        <span class="azuki-rejoin-banner-copy">
          <strong>Rejoin recent game</strong>
          <span id="rejoin-last-game-note"></span>
        </span>
        <span class="azuki-rejoin-banner-action" aria-hidden="true">Rejoin</span>
      </button>
    </div>
    <div id="active-games-list" class="active-games-list"></div>
  </div>

  <!-- Create New Game Section -->
  <div class="card azuki-glass-card azuki-queue-card">
    <div class="azuki-prepare-heading">
      <h2>Prepare a Deck</h2>
      <p>Build or choose a deck to begin your match</p>
    </div>
    <div class="azuki-game-setup">
      <input class="azuki-source-radio" type="radio" name="azuki-deck-source-mode" id="azuki-source-owned" value="builder"<?php echo $hasAzukiBuilderDecks ? ' checked' : ''; ?>>
      <input class="azuki-source-radio" type="radio" name="azuki-deck-source-mode" id="azuki-source-starter" value="starter"<?php echo $hasAzukiBuilderDecks ? '' : ' checked'; ?>>
      <input class="azuki-source-radio" type="radio" name="azuki-deck-source-mode" id="azuki-source-link" value="link">

      <section class="azuki-owned-decks" aria-labelledby="azuki-owned-decks-title">
        <div class="azuki-deck-section-heading">
          <div>
            <h3 id="azuki-owned-decks-title"><img class="zendo-raster-icon" src="/TCGEngine/Assets/Images/Zendo/UIIconsRaster/layers.webp?v=4" alt="" aria-hidden="true">Deck Library</h3>
            <p>Choose a saved deck or bring in a new list.</p>
          </div>
          <?php if (IsUserLoggedIn()): ?>
            <div class="azuki-deck-section-actions">
              <a class="azuki-deck-management-button primary" href="/TCGEngine/AzukiDeck/CreateDeck.php">Create Deck</a>
              <button
                type="button"
                class="azuki-import-toggle<?php echo $azukiDeckError !== '' ? ' is-active' : ''; ?>"
                aria-label="Import a deck"
                aria-controls="azuki-import-popover"
                aria-expanded="<?php echo $azukiDeckError !== '' ? 'true' : 'false'; ?>"
                title="Import a deck"
                onclick="toggleAzukiDeckImportPopover()"
              ><img class="zendo-raster-icon" src="/TCGEngine/Assets/Images/Zendo/UIIconsRaster/download.webp?v=4" alt="" aria-hidden="true"></button>
            </div>
          <?php endif; ?>
        </div>

        <?php if (IsUserLoggedIn()): ?>
          <div id="azuki-import-popover" class="azuki-import-popover<?php echo $azukiDeckError !== '' ? ' is-open' : ''; ?>" aria-hidden="<?php echo $azukiDeckError !== '' ? 'false' : 'true'; ?>">
            <form class="azuki-deck-import" action="/TCGEngine/AzukiDeck/CreateDeck.php" method="get" onsubmit="return beginAzukiDeckImport(this)">
              <div class="azuki-import-popover-heading">
                <label for="azuki-import-deck-link">
                  <span class="azuki-import-icon" aria-hidden="true"><img class="zendo-raster-icon" src="/TCGEngine/Assets/Images/Zendo/UIIconsRaster/download.webp?v=4" alt=""></span>
                  <span class="azuki-import-copy"><strong>Import a deck</strong><small id="azuki-import-help">Paste a thegateikz.com URL or deck slug.</small></span>
                </label>
                <button type="button" class="azuki-import-popover-close" aria-label="Close deck import" onclick="setAzukiDeckImportPopover(false)">&times;</button>
              </div>
              <div class="azuki-deck-import-row">
                <input id="azuki-import-deck-link" name="deckLink" placeholder="Paste a thegateikz.com deck link" aria-describedby="azuki-import-help azuki-import-status" required>
                <button class="azuki-deck-management-button" type="submit">Import Deck</button>
              </div>
              <span id="azuki-import-status" class="azuki-import-status<?php echo $azukiDeckError !== '' ? ' is-error' : ''; ?>"<?php echo $azukiDeckError !== '' ? ' role="alert"' : ' aria-live="polite"'; ?>><?php echo htmlspecialchars($azukiDeckError, ENT_QUOTES); ?></span>
            </form>
          </div>
        <?php endif; ?>

        <div class="azuki-library-tabs" role="tablist" aria-label="Deck library">
          <button type="button" id="azuki-library-tab-decks" class="<?php echo $hasAzukiBuilderDecks ? 'is-active' : ''; ?>" role="tab" aria-selected="<?php echo $hasAzukiBuilderDecks ? 'true' : 'false'; ?>" onclick="switchLibraryView('decks')">My Decks</button>
          <button type="button" id="azuki-library-tab-starters" class="<?php echo $hasAzukiBuilderDecks ? '' : 'is-active'; ?>" role="tab" aria-selected="<?php echo $hasAzukiBuilderDecks ? 'false' : 'true'; ?>" onclick="switchLibraryView('starters')">Starter Decks</button>
          <button type="button" id="azuki-library-tab-link" role="tab" aria-selected="false" onclick="switchLibraryView('link')">Deck Link</button>
        </div>

        <div id="azuki-library-panel-decks" class="azuki-library-panel<?php echo $hasAzukiBuilderDecks ? ' is-active' : ''; ?>" role="tabpanel" aria-labelledby="azuki-library-tab-decks">
        <?php if ($hasAzukiBuilderDecks): ?>
          <div id="azuki-builder-deck-select" class="azuki-builder-deck-grid" role="radiogroup" aria-label="Your saved decks">
            <?php foreach ($azukiBuilderDecks as $deckIndex => $deck):
              $deckID = trim((string)($deck['assetIdentifier'] ?? ''));
              $deckName = trim((string)($deck['assetName'] ?? ''));
              $leaderID = trim((string)($deck['keyIndicator1'] ?? ''));
              $gateID = trim((string)($deck['keyIndicator2'] ?? ''));
              $leaderName = $leaderID !== '' ? trim((string)CardName($leaderID)) : '';
              $gateName = $gateID !== '' ? trim((string)CardName($gateID)) : '';
              $leaderImageFallback = $leaderID !== '' ? trim((string)CardImage($leaderID)) : '';
              $gateImageFallback = $gateID !== '' ? trim((string)CardImage($gateID)) : '';
              $isFavorite = intval($deck['assetFolder'] ?? 0) === 1;
              $deckState = AzukiDeckReadDeckState($deckID);
              $mainDeckCount = count($deckState['mainDeck'] ?? []);
              if ($deckName === '') $deckName = $leaderName !== '' ? $leaderName . ' Deck' : 'Azuki Deck';
              $deckMetaParts = [];
              if ($mainDeckCount > 0) $deckMetaParts[] = $mainDeckCount . ($mainDeckCount === 1 ? ' card' : ' cards');
              if ($leaderName !== '') $deckMetaParts[] = $leaderName;
              if ($gateName !== '') $deckMetaParts[] = $gateName;
              $deckMeta = $deckMetaParts ? implode(' • ', $deckMetaParts) : 'Empty deck • Add a leader and cards';
              $deckTraits = implode(' • ', array_values(array_filter([
                $leaderName !== '' ? str_replace(',', ' •', $leaderName) : '',
                $gateName
              ], function($value) { return $value !== ''; })));
              if ($deckTraits === '') $deckTraits = 'Add a leader and gate';
            ?>
              <div class="azuki-builder-deck-option"
                   data-deck-id="<?php echo htmlspecialchars($deckID, ENT_QUOTES); ?>"
                   data-deck-name="<?php echo htmlspecialchars($deckName, ENT_QUOTES); ?>"
                   data-card-count="<?php echo intval($mainDeckCount); ?>"
                   data-is-favorite="<?php echo $isFavorite ? '1' : '0'; ?>"
                   data-deck-traits="<?php echo htmlspecialchars($deckTraits, ENT_QUOTES); ?>">
                <label>
                  <input type="radio" name="azuki-builder-deck" value="azukideck:<?php echo htmlspecialchars($deckID, ENT_QUOTES); ?>"<?php echo $deckIndex === 0 ? ' checked' : ''; ?> onchange="chooseAzukiDeck(this)">
                  <span class="azuki-builder-deck-tile">
                    <?php if ($leaderID !== '' || $gateID !== ''): ?>
                      <span class="azuki-builder-deck-art<?php echo $leaderID !== '' && $gateID !== '' ? ' has-two' : ' has-one'; ?>" aria-hidden="true">
                        <?php if ($leaderID !== ''): ?><img src="/TCGEngine/AzukiSim/WebpImages/<?php echo rawurlencode($leaderID); ?>.webp" data-fallback="<?php echo htmlspecialchars($leaderImageFallback, ENT_QUOTES); ?>" alt="" onerror="if(this.dataset.fallback && this.src !== this.dataset.fallback){this.src=this.dataset.fallback}else{this.remove()}"><?php endif; ?>
                        <?php if ($gateID !== ''): ?><img src="/TCGEngine/AzukiSim/WebpImages/<?php echo rawurlencode($gateID); ?>.webp" data-fallback="<?php echo htmlspecialchars($gateImageFallback, ENT_QUOTES); ?>" alt="" onerror="if(this.dataset.fallback && this.src !== this.dataset.fallback){this.src=this.dataset.fallback}else{this.remove()}"><?php endif; ?>
                      </span>
                    <?php endif; ?>
                    <span class="azuki-builder-deck-copy">
                      <strong><?php echo htmlspecialchars($deckName, ENT_QUOTES); ?></strong>
                      <span><?php echo htmlspecialchars($deckMeta, ENT_QUOTES); ?></span>
                    </span>
                    <span class="azuki-builder-deck-selected" aria-label="Selected"><svg class="zendo-icon" aria-hidden="true"><use href="/TCGEngine/Assets/Images/Zendo/zendo-ui-icons.svg?v=3#check"></use></svg></span>
                  </span>
                </label>
                <div class="azuki-builder-deck-actions" aria-label="<?php echo htmlspecialchars($deckName, ENT_QUOTES); ?> actions">
                  <a class="azuki-deck-edit-action" href="/TCGEngine/NextTurn.php?gameName=<?php echo rawurlencode($deckID); ?>&amp;playerID=1&amp;folderPath=AzukiDeck">Edit Deck</a>
                  <details class="azuki-deck-more" ontoggle="if(this.open)setTimeout(() => this.scrollIntoView({block:'nearest'}), 0)">
                    <summary>More <span aria-hidden="true">▾</span></summary>
                    <div class="azuki-deck-more-menu">
                      <button type="button" onclick="AzukiDeckHome.move(<?php echo intval($deckID); ?>, <?php echo $isFavorite ? 0 : 1; ?>)"><?php echo $isFavorite ? 'Remove from favorites' : 'Add to favorites'; ?></button>
                      <button type="button" onclick="AzukiDeckHome.copyLink(<?php echo intval($deckID); ?>)">Copy deck link</button>
                      <button type="button" onclick="AzukiDeckHome.generateImage(<?php echo intval($deckID); ?>)">Generate deck image</button>
                      <button type="button" class="danger" onclick="AzukiDeckHome.remove(<?php echo intval($deckID); ?>)">Delete deck</button>
                    </div>
                  </details>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php elseif (IsUserLoggedIn()): ?>
          <div class="azuki-deck-source-empty">You do not have a saved deck yet. Create one from scratch or import an existing list.</div>
        <?php else: ?>
          <div class="azuki-deck-source-empty">Log in to create, import, and manage saved decks.</div>
          <div class="azuki-deck-auth-actions">
            <a class="azuki-deck-management-button primary" href="/TCGEngine/SharedUI/Sites/AzukiSim/Signup.php?redirect=%2FTCGEngine%2FSharedUI%2FSites%2FAzukiSim%2FMainMenu.php">Create account</a>
            <a class="azuki-deck-management-button" href="/TCGEngine/SharedUI/Sites/AzukiSim/LoginPage.php?redirect=%2FTCGEngine%2FSharedUI%2FSites%2FAzukiSim%2FMainMenu.php">Log in</a>
          </div>
        <?php endif; ?>
        </div>

        <div id="azuki-library-panel-starters" class="azuki-library-panel<?php echo $hasAzukiBuilderDecks ? '' : ' is-active'; ?>" role="tabpanel" aria-labelledby="azuki-library-tab-starters">
          <section class="azuki-starter-source" id="azuki-starter-source" onclick="selectDeckSource('starter')" aria-labelledby="azuki-starter-title">
            <label for="starter-deck-select">
              <span>
                <strong id="azuki-starter-title">Choose a starter deck</strong>
                <small>Jump in without building a deck first.</small>
              </span>
            </label>
            <select id="starter-deck-select" onchange="selectDeckSource('starter')">
              <option value="Raizan">Raizan Starter Deck</option>
              <option value="Shao">Shao Starter Deck</option>
              <option value="Bobu">Bobu Starter Deck</option>
              <option value="Zero">Zero Starter Deck</option>
            </select>
          </section>
        </div>

        <div id="azuki-library-panel-link" class="azuki-library-panel" role="tabpanel" aria-labelledby="azuki-library-tab-link">
          <section class="azuki-link-source" id="azuki-link-source" aria-labelledby="azuki-deck-link-label">
            <div class="azuki-link-source-body">
              <label id="azuki-deck-link-label" for="azuki-deck-link">Play from a deck link</label>
              <input type="text" id="azuki-deck-link" placeholder="https://thegateikz.com/... or deck slug" oninput="selectDeckSource('link')">
              <p>This uses the linked list for this game without adding it to your saved decks.</p>
              <div class="saved-decks-panel">
                <div class="azuki-inline-section-title">Links saved in this browser</div>
                <?php echo RenderDeckLibrary(0, $azukiDeckLibraryConfig); ?>
              </div>
            </div>
          </section>
        </div>
      </section>

      <?php if ($hasAzukiBuilderDecks): ?>
        <section class="azuki-selected-deck-section" aria-labelledby="azuki-selected-deck-title">
          <div class="azuki-selected-deck-heading">
            <div id="azuki-selected-deck-title" class="azuki-selected-deck-label"><img class="zendo-raster-icon" src="/TCGEngine/Assets/Images/Zendo/UIIconsRaster/selected.webp?v=4" alt="" aria-hidden="true">Selected Deck</div>
            <button type="button" class="azuki-change-deck-button" onclick="openAzukiDeckPicker()">Change Deck</button>
          </div>
          <div id="azuki-selected-deck-preview" class="azuki-selected-deck-preview" aria-live="polite"></div>
        </section>
      <?php endif; ?>

      <div class="azuki-ready-label"><img class="zendo-raster-icon" src="/TCGEngine/Assets/Images/Zendo/UIIconsRaster/ready.webp?v=4" alt="" aria-hidden="true">Ready to Play?</div>
      <div class="azuki-game-actions">
        <button class="azuki-game-action-primary" onclick="joinQueue()"><img class="zendo-raster-icon" src="/TCGEngine/Assets/Images/Zendo/UIIconsRaster/users.webp?v=4" alt="" aria-hidden="true"><span><strong>Join Queue</strong><small>Find an opponent</small></span></button>
        <button class="azuki-game-action-learn" onclick="createTutorialGame()"><img class="zendo-raster-icon" src="/TCGEngine/Assets/Images/Zendo/UIIconsRaster/book.webp?v=4" alt="" aria-hidden="true"><span><strong>Learn to Play</strong><small>Interactive tutorial</small></span></button>
        <button class="azuki-game-action-bot" onclick="createRlBotGame()" aria-haspopup="dialog"><img class="zendo-raster-icon" src="/TCGEngine/Assets/Images/Zendo/UIIconsRaster/bot.webp?v=4" alt="" aria-hidden="true"><span><strong>Play vs. bot</strong><small>Practice mode</small></span></button>
        <button class="azuki-game-action-private" onclick="createPrivateGame()"><img class="zendo-raster-icon" src="/TCGEngine/Assets/Images/Zendo/UIIconsRaster/lock.webp?v=4" alt="" aria-hidden="true"><span><strong>Private Game</strong><small>Invite your friends</small></span></button>
        <button id="join-private-invite-btn" onclick="joinPrivateInvite()" style="display: none;">Join Private Invite</button>
      </div>
      <div id="queue-inline-error" style="display: none; margin-top: 10px; color: #ff6b6b; font-size: 13px; line-height: 1.35;"></div>
      <div id="private-invite-notice" style="display: none; margin-top: 10px; color: #9ed9b4; font-size: 13px;"></div>
    </div>
  </div>
  
  <!-- Tips & Info Section -->
  <div class="card azuki-glass-card azuki-info-card">
    <div class="azuki-info-tabs" role="tablist" aria-label="Azuki information">
      <button type="button" id="azuki-info-tab-welcome" class="azuki-info-tab is-active" onclick="switchInfoTab('welcome')" role="tab" aria-selected="true" aria-controls="azuki-info-panel-welcome">Welcome</button>
      <button type="button" id="azuki-info-tab-replays" class="azuki-info-tab" onclick="switchInfoTab('replays')" role="tab" aria-selected="false" aria-controls="azuki-info-panel-replays">Replays</button>
      <button type="button" id="azuki-info-tab-game-logs" class="azuki-info-tab" onclick="switchInfoTab('game-logs')" role="tab" aria-selected="false" aria-controls="azuki-info-panel-game-logs">Game Logs</button>
    </div>
    <div id="azuki-info-panel-welcome" class="azuki-info-panel is-active" role="tabpanel" aria-labelledby="azuki-info-tab-welcome">
    <h2 style="margin: 0 0 4px 0;">Welcome to Zendō</h2>
    <p class="login-message" style="margin: 0; color: #ccc; font-size: 14px;">A fan-made online simulator for the Azuki TCG.</p>

    <hr style="border: none; border-top: 1px solid rgba(255,255,255,0.1); margin: 0;">

    <!-- Did you know? -->
    <div id="did-you-know-box">
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
      <div class="azuki-quick-reference-title">Quick Reference</div>
      <div style="display: flex; flex-direction: column; gap: 6px;" id="hotkey-list"></div>
    </div>
    </div>
    <div id="azuki-info-panel-replays" class="azuki-info-panel" role="tabpanel" aria-labelledby="azuki-info-tab-replays">
      <h2 style="margin: 0;">Your Replays</h2>
      <p style="margin: 0; color: #ccc; font-size: 13px; line-height: 1.4;">Saved in this browser.</p>
      <div id="match-replay-menu-list" class="ga-replay-list azuki-saved-list"></div>
    </div>
    <div id="azuki-info-panel-game-logs" class="azuki-info-panel" role="tabpanel" aria-labelledby="azuki-info-tab-game-logs">
      <h2 style="margin: 0;">Your Game Logs</h2>
      <p style="margin: 0; color: #ccc; font-size: 13px; line-height: 1.4;">Captured automatically and saved in this browser.</p>
      <div id="azuki-game-log-menu-list" class="ga-replay-list azuki-saved-list"></div>
    </div>
  </div>
</div>

<div id="azuki-deck-picker-modal" class="azuki-deck-picker-modal" aria-hidden="true">
  <button type="button" class="azuki-deck-picker-backdrop" onclick="closeAzukiDeckPicker()" aria-label="Close deck picker"></button>
  <div class="azuki-deck-picker-dialog" role="dialog" aria-modal="true" aria-labelledby="azuki-deck-picker-title" aria-describedby="azuki-deck-picker-description">
    <button type="button" class="azuki-deck-picker-close" onclick="closeAzukiDeckPicker()" aria-label="Close deck picker">&times;</button>
    <div class="azuki-deck-picker-kicker">Deck Library</div>
    <h2 id="azuki-deck-picker-title">Choose a Deck</h2>
    <p id="azuki-deck-picker-description">Select the deck you want to use for your next game.</p>
    <div id="azuki-deck-picker-content"></div>
  </div>
</div>

<div id="rl-bot-opponent-modal" class="rl-bot-opponent-modal" aria-hidden="true">
  <div class="rl-bot-opponent-modal__backdrop" onclick="closeRlBotOpponentModal()" aria-hidden="true"></div>
  <div class="rl-bot-opponent-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="rl-bot-opponent-title" aria-describedby="rl-bot-opponent-description">
    <button type="button" class="rl-bot-opponent-modal__close" onclick="closeRlBotOpponentModal()" aria-label="Close opponent selection">&times;</button>
    <h2 id="rl-bot-opponent-title">Choose Your Opponent</h2>
    <p id="rl-bot-opponent-description">Choose a trained bot or a passive opponent that automatically passes.</p>
    <div class="rl-bot-opponent-grid">
      <button type="button" class="rl-bot-opponent-choice" onclick="startRlBotGame('raizan')">
        <img src="/TCGEngine/AzukiSim/WebpImages/S1-STT01-001_Raizan_L_L_die.webp" alt="" aria-hidden="true">
        <span>
          <strong>Raizan</strong>
          <small>Deck 373</small>
        </span>
      </button>
      <button type="button" class="rl-bot-opponent-choice" onclick="startRlBotGame('zero')">
        <img src="/TCGEngine/AzukiSim/WebpImages/S1-STT04-001_Zero_L_L_die.webp" alt="" aria-hidden="true">
        <span>
          <strong>Zero</strong>
          <small>Deck 51</small>
        </span>
      </button>
      <button type="button" class="rl-bot-opponent-choice" onclick="startRlBotGame('bobu')">
        <img src="/TCGEngine/AzukiSim/WebpImages/S1-STT03-001_Bobu_L_L_die.webp" alt="" aria-hidden="true">
        <span>
          <strong>Bobu</strong>
          <small>Deck 241 · Midrange/control</small>
        </span>
      </button>
      <button type="button" class="rl-bot-opponent-choice" onclick="startRlBotGame('goldfish')">
        <img src="/TCGEngine/Assets/Images/Zendo/UIIconsRaster/bot.webp?v=4" alt="" aria-hidden="true">
        <span>
          <strong>Goldfish</strong>
          <small>Automatically passes</small>
        </span>
      </button>
    </div>
  </div>
</div>
<script src="<?php echo _VersionAsset('/TCGEngine/Core/MatchReplayClient.js'); ?>"></script>
<script src="<?php echo _VersionAsset('/TCGEngine/SharedUI/js/private-invite.js'); ?>"></script>
<script src="/TCGEngine/AzukiSim/Custom/GameLogClient.js?v=<?php echo @filemtime(__DIR__ . '/../../../AzukiSim/Custom/GameLogClient.js'); ?>"></script>
<script>
  window.AZUKI_DECK_CODES = <?php echo json_encode($azukiDeckCodes, JSON_UNESCAPED_SLASHES); ?>;
</script>
<script src="/TCGEngine/AzukiDeck/HomeActions.js?v=20260727"></script>


<script>
  var _didYouKnowTips = [
    { key: 'u', label: 'Undo your most recent action' },
    { text: 'Hover a card on the field to see its full text' },
    { text: 'You can queue with the Raizan, Shao, Bobu, or Zero starter deck.' },
    { text: 'You can also paste a thegateikz.com deck link and AzukiSim will load that deck instead of a starter deck.' },
    { text: 'Private games generate a shareable invite link — send it to your opponent and they can join instantly.' },
    { text: 'The queue matches you with the first available opponent. No need to refresh — it polls automatically.' },
    { key: 'Esc', label: 'Cancel matchmaking while waiting for an opponent' },
  ];
  var _dykIndex = 0;

  var _hotkeyList = [
    { key: 'u',   label: 'Undo most recent action' },
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

  function switchInfoTab(tab) {
    ['welcome', 'replays', 'game-logs'].forEach(function (name) {
      var isActive = tab === name;
      var tabElement = document.getElementById('azuki-info-tab-' + name);
      var panelElement = document.getElementById('azuki-info-panel-' + name);
      if (tabElement) {
        tabElement.classList.toggle('is-active', isActive);
        tabElement.setAttribute('aria-selected', isActive ? 'true' : 'false');
      }
      if (panelElement) panelElement.classList.toggle('is-active', isActive);
    });
  }

  function selectDeckSource(mode) {
    var sourceID = mode === 'builder' ? 'azuki-source-owned' : 'azuki-source-' + mode;
    var source = document.getElementById(sourceID);
    if (!source) return;
    source.checked = true;
    var starterSource = document.getElementById('azuki-starter-source');
    var linkSource = document.getElementById('azuki-link-source');
    if (starterSource) starterSource.classList.toggle('is-source-active', mode === 'starter');
    if (linkSource) linkSource.classList.toggle('is-source-active', mode === 'link');
    clearQueueInlineError();
  }

  function setAzukiDeckImportPopover(open) {
    var popover = document.getElementById('azuki-import-popover');
    var toggle = document.querySelector('.azuki-import-toggle');
    if (!popover || !toggle) return;
    popover.classList.toggle('is-open', open);
    popover.setAttribute('aria-hidden', open ? 'false' : 'true');
    toggle.classList.toggle('is-active', open);
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (open) {
      var input = document.getElementById('azuki-import-deck-link');
      if (input) setTimeout(function() { input.focus(); }, 0);
    }
  }

  function toggleAzukiDeckImportPopover() {
    var popover = document.getElementById('azuki-import-popover');
    if (!popover) return;
    setAzukiDeckImportPopover(!popover.classList.contains('is-open'));
  }

  document.addEventListener('click', function(event) {
    var popover = document.getElementById('azuki-import-popover');
    var toggle = document.querySelector('.azuki-import-toggle');
    if (!popover || !toggle || !popover.classList.contains('is-open')) return;
    if (popover.contains(event.target) || toggle.contains(event.target)) return;
    setAzukiDeckImportPopover(false);
  });

  function switchLibraryView(view) {
    var showDecks = view === 'decks';
    var showStarters = view === 'starters';
    var showLink = view === 'link';
    var decksTab = document.getElementById('azuki-library-tab-decks');
    var startersTab = document.getElementById('azuki-library-tab-starters');
    var linkTab = document.getElementById('azuki-library-tab-link');
    var decksPanel = document.getElementById('azuki-library-panel-decks');
    var startersPanel = document.getElementById('azuki-library-panel-starters');
    var linkPanel = document.getElementById('azuki-library-panel-link');
    if (!decksTab || !startersTab || !linkTab || !decksPanel || !startersPanel || !linkPanel) return;
    decksTab.classList.toggle('is-active', showDecks);
    startersTab.classList.toggle('is-active', showStarters);
    linkTab.classList.toggle('is-active', showLink);
    decksTab.setAttribute('aria-selected', showDecks ? 'true' : 'false');
    startersTab.setAttribute('aria-selected', showStarters ? 'true' : 'false');
    linkTab.setAttribute('aria-selected', showLink ? 'true' : 'false');
    decksPanel.classList.toggle('is-active', showDecks);
    startersPanel.classList.toggle('is-active', showStarters);
    linkPanel.classList.toggle('is-active', showLink);
    if (showStarters) {
      selectDeckSource('starter');
    } else if (showLink) {
      selectDeckSource('link');
    } else if (document.querySelector('#azuki-builder-deck-select input[name="azuki-builder-deck"]:checked')) {
      selectDeckSource('builder');
    }
  }

  var _azukiDeckPickerPreviousFocus = null;
  var _selectedAzukiDeckStorageKey = 'tcgengine:selectedDeck:AzukiSim';

  function getRememberedAzukiDeck() {
    try {
      return (localStorage.getItem(_selectedAzukiDeckStorageKey) || '').trim();
    } catch (error) {
      return '';
    }
  }

  function rememberAzukiDeck(input) {
    if (!input || !input.value) return;
    try {
      localStorage.setItem(_selectedAzukiDeckStorageKey, input.value);
    } catch (error) {
      // Deck selection still works when storage is disabled or unavailable.
    }
  }

  function updateAzukiSelectedDeckPreview(input) {
    var preview = document.getElementById('azuki-selected-deck-preview');
    var option = input ? input.closest('.azuki-builder-deck-option') : null;
    if (!preview || !option) return;

    var label = option.querySelector(':scope > label');
    if (!label) return;

    var card = document.createElement('div');
    card.className = 'azuki-selected-deck-card';
    var labelClone = label.cloneNode(true);
    var clonedInput = labelClone.querySelector('input');
    if (clonedInput) clonedInput.remove();
    var copy = labelClone.querySelector('.azuki-builder-deck-copy');
    if (copy) {
      var detail = copy.querySelector(':scope > span');
      if (detail) detail.textContent = option.dataset.deckTraits || 'Add a leader and gate';

      var stats = document.createElement('span');
      stats.className = 'azuki-selected-deck-stats';
      var count = parseInt(option.dataset.cardCount || '0', 10);
      if (!Number.isFinite(count)) count = 0;

      var countStat = document.createElement('span');
      countStat.innerHTML = '<img class="zendo-raster-icon" src="/TCGEngine/Assets/Images/Zendo/UIIconsRaster/deck.webp?v=4" alt="" aria-hidden="true">';
      countStat.appendChild(document.createTextNode(count + (count === 1 ? ' card' : ' cards')));

      var formatStat = document.createElement('span');
      formatStat.innerHTML = '<img class="zendo-raster-icon" src="/TCGEngine/Assets/Images/Zendo/UIIconsRaster/standard.webp?v=4" alt="" aria-hidden="true">';
      formatStat.appendChild(document.createTextNode('Standard'));

      stats.appendChild(countStat);
      stats.appendChild(formatStat);
      copy.appendChild(stats);
    }
    card.appendChild(labelClone);

    var deckID = parseInt(option.dataset.deckId || '0', 10);
    var isFavorite = option.dataset.isFavorite === '1';
    var actionBar = document.createElement('div');
    actionBar.className = 'azuki-selected-deck-actions';
    actionBar.setAttribute('aria-label', (option.dataset.deckName || 'Selected deck') + ' actions');

    function setActionIcon(control, icon, label) {
      control.title = label;
      control.setAttribute('aria-label', label);
      control.innerHTML = '<img class="zendo-raster-icon" src="/TCGEngine/Assets/Images/Zendo/UIIconsRaster/' + icon + '.webp?v=4" alt="" aria-hidden="true">';
      return control;
    }

    var edit = setActionIcon(document.createElement('a'), 'edit', 'Edit deck');
    edit.href = '/TCGEngine/NextTurn.php?gameName=' + encodeURIComponent(deckID) + '&playerID=1&folderPath=AzukiDeck';
    actionBar.appendChild(edit);

    var favorite = setActionIcon(document.createElement('button'), 'star', isFavorite ? 'Remove from favorites' : 'Add to favorites');
    favorite.type = 'button';
    favorite.onclick = function () { AzukiDeckHome.move(deckID, isFavorite ? 0 : 1); };
    actionBar.appendChild(favorite);

    var link = setActionIcon(document.createElement('button'), 'link', 'Copy deck link');
    link.type = 'button';
    link.onclick = function () { AzukiDeckHome.copyLink(deckID); };
    actionBar.appendChild(link);

    var image = setActionIcon(document.createElement('button'), 'image', 'Generate deck image');
    image.type = 'button';
    image.onclick = function () { AzukiDeckHome.generateImage(deckID); };
    actionBar.appendChild(image);

    var remove = setActionIcon(document.createElement('button'), 'trash', 'Delete deck');
    remove.type = 'button';
    remove.className = 'danger';
    remove.onclick = function () { AzukiDeckHome.remove(deckID); };
    actionBar.appendChild(remove);

    card.appendChild(actionBar);
    preview.replaceChildren(card);
  }

  function initializeAzukiDeckPicker() {
    var grid = document.getElementById('azuki-builder-deck-select');
    var content = document.getElementById('azuki-deck-picker-content');
    if (!grid || !content) return;
    content.appendChild(grid);

    var inputs = Array.from(grid.querySelectorAll('input[name="azuki-builder-deck"]'));
    var rememberedValue = getRememberedAzukiDeck();
    var selected = rememberedValue
      ? inputs.find(function(input) { return input.value === rememberedValue; })
      : null;
    if (!selected) selected = grid.querySelector('input[name="azuki-builder-deck"]:checked') || inputs[0] || null;
    if (selected) {
      selected.checked = true;
      rememberAzukiDeck(selected);
      updateAzukiSelectedDeckPreview(selected);
    }
  }

  function openAzukiDeckPicker() {
    var modal = document.getElementById('azuki-deck-picker-modal');
    if (!modal) return;
    _azukiDeckPickerPreviousFocus = document.activeElement;
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('azuki-modal-open');
    var closeButton = modal.querySelector('.azuki-deck-picker-close');
    if (closeButton) closeButton.focus();
  }

  function closeAzukiDeckPicker() {
    var modal = document.getElementById('azuki-deck-picker-modal');
    if (!modal || !modal.classList.contains('is-open')) return;
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('azuki-modal-open');
    if (_azukiDeckPickerPreviousFocus && typeof _azukiDeckPickerPreviousFocus.focus === 'function') {
      _azukiDeckPickerPreviousFocus.focus();
    }
  }

  function chooseAzukiDeck(input) {
    if (!input) return;
    input.checked = true;
    rememberAzukiDeck(input);
    selectDeckSource('builder');
    updateAzukiSelectedDeckPreview(input);
    closeAzukiDeckPicker();
  }

  function beginAzukiDeckImport(form) {
    var button = form ? form.querySelector('button[type="submit"]') : null;
    var status = document.getElementById('azuki-import-status');
    if (button) {
      button.disabled = true;
      button.textContent = 'Importing…';
    }
    if (status) {
      status.classList.remove('is-error');
      status.setAttribute('aria-live', 'polite');
      status.textContent = 'Importing deck…';
    }
    return true;
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

  document.addEventListener('DOMContentLoaded', function() {
    renderDidYouKnow();
    renderHotkeyList();
    // Rotate tips every 8 seconds
    setInterval(cycleDidYouKnow, 8000);
  });
</script>

<script>

  var rootName = "AzukiSim";
  // Derive the app base from the URL so API/asset paths work no matter what depth this menu is served
  // at — the canonical entry is the root pointer (/TCGEngine/SharedUI/MainMenu.php, which follows the DB)
  // AND the direct /TCGEngine/SharedUI/Sites/AzukiSim/MainMenu.php. Relative '../../../' paths broke from
  // the shallower root URL (they resolved above /TCGEngine/ → 404). Matches swusimAppBase()/gaAppBase().
  function azukiAppBase(){ var p=location.pathname, i=p.indexOf('/TCGEngine/'); return i>=0 ? p.slice(0, i+11) : '/TCGEngine/'; }
  var _lobby_id = "";
  var _privateInviteCode = "";
  var _privateInviteCasterMode = false;
  var _waitingEscHandler = null;
  var _lastSimGameStorageKey = 'tcgengine:lastSimGame:' + rootName;
  var _rejoinFreshnessMs = 5 * 60 * 1000;
  var _rejoinExpiryTimer = null;
  var _activeGamesServerCount = 0;
  var _activeGamesSnapshot = [];

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
          typeof record.authKey === 'string' && record.authKey !== '' &&
          Number.isFinite(Number(record.updatedAt));
      }

      function lastSimGameFreshnessRemaining(record) {
        if (!isValidLastSimGameRecord(record)) return 0;
        var age = Date.now() - Number(record.updatedAt);
        if (age < 0 || age >= _rejoinFreshnessMs) return 0;
        return _rejoinFreshnessMs - age;
      }

      function syncRejoinActiveGamesState() {
        var gameList = document.getElementById('active-games-list');
        var gameCount = document.getElementById('active-game-count');
        var record = getLastSimGame();
        var hasFreshRejoin = lastSimGameFreshnessRemaining(record) > 0;
        var rejoinAlreadyListed = hasFreshRejoin && _activeGamesSnapshot.some(function(game) {
          return String(game && game.gameName || '') === String(record.gameName);
        });
        if (gameCount) {
          gameCount.textContent = String(_activeGamesServerCount + (hasFreshRejoin && !rejoinAlreadyListed ? 1 : 0));
        }
        if (gameList && !gameList.querySelector('.active-game-card')) {
          renderActiveGames([]);
        }
      }

      function updateRejoinLastGameUI() {
        var banner = document.getElementById('rejoin-last-game-banner');
        var button = document.getElementById('rejoin-last-game-btn');
        var note = document.getElementById('rejoin-last-game-note');
        if (!banner || !button || !note) return;
        if (_rejoinExpiryTimer !== null) {
          clearTimeout(_rejoinExpiryTimer);
          _rejoinExpiryTimer = null;
        }
        var record = getLastSimGame();
        var freshnessRemaining = lastSimGameFreshnessRemaining(record);
        if (freshnessRemaining <= 0) {
          banner.style.display = 'none';
          note.textContent = '';
          syncRejoinActiveGamesState();
          return;
        }
        banner.style.display = '';
        note.textContent = 'Game ' + record.gameName + ' · Player ' + record.playerID;
        syncRejoinActiveGamesState();
        _rejoinExpiryTimer = setTimeout(updateRejoinLastGameUI, freshnessRemaining + 50);
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
        var url = new URL(azukiAppBase() + 'NextTurn.php', window.location.href);
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
        if (lastSimGameFreshnessRemaining(record) <= 0) {
          updateRejoinLastGameUI();
          return;
        }
        window.location.href = buildGameUrl(record.playerID, record.gameName, record.authKey, false);
      }

      // Shared private-invite lobby UI (SharedUI/js/private-invite.js). Azuki reveals the join button
      // with a CSS class rather than inline display, and has its own caster-mode copy, so both are
      // passed as options; everything else (hiding Create Private Game / Join Queue, re-applying after
      // the page's own handlers run) is the shared behavior.
      function initializePrivateInviteFromUrl() {
        try {
          _privateInviteCode = window.PrivateInviteUI ? window.PrivateInviteUI.init({
            rootName: 'AzukiSim',
            joinBtnVisibleClass: 'is-visible',
            noticeText: 'Private invite detected. Choose your deck, then click Join Private Invite.',
            noticeTextCaster: 'Caster-mode invite detected. Spectators can see both players\' hands. '
                            + 'Joining this game opts you in.'
          }) : '';
          _privateInviteCasterMode = window.PrivateInviteUI ? !!window.PrivateInviteUI.casterMode : false;
          if (_privateInviteCode) {
            var joinBtn = document.getElementById('join-private-invite-btn');
            if (joinBtn) joinBtn.textContent = 'Join Private Invite';
          }
        } catch (e) {
          console.error('Failed to parse private invite URL:', e);
        }
      }

      function initializeDeckLinkFromUrl() {
        try {
          var params = new URLSearchParams(window.location.search || '');
          var deckLinkParam = (params.get('deckLink') || params.get('deck') || '').trim();
          if (!deckLinkParam) return;

          var deckLinkInput = document.getElementById('azuki-deck-link');
          if (deckLinkInput && !deckLinkInput.value.trim()) {
            deckLinkInput.value = deckLinkParam;
          }
          switchLibraryView('link');
        } catch (e) {
          console.error('Failed to parse deck link URL:', e);
        }
      }

      function getDeckSubmission() {
        var deckLink = '';
        var sourceChoice = document.querySelector('input[name="azuki-deck-source-mode"]:checked');
        var deckSource = sourceChoice ? sourceChoice.value : 'starter';
        if (deckSource === 'builder') {
          var builderChoice = document.querySelector('#azuki-builder-deck-select input[name="azuki-builder-deck"]:checked');
          deckLink = builderChoice && builderChoice.value ? builderChoice.value.trim() : '';
          if (!deckLink) {
            showQueueInlineError('Choose one of Your Decks, or use a starter deck.');
            return null;
          }
        } else if (deckSource === 'link') {
          var deckLinkInput = document.getElementById('azuki-deck-link');
          if (deckLinkInput && deckLinkInput.value) {
            deckLink = deckLinkInput.value.trim();
          }
          if (!deckLink) {
            showQueueInlineError('Paste a deck link or slug, or choose another deck source.');
            return null;
          }
        }

        var starterDeck = 'Raizan';
        var starterSelect = document.getElementById('starter-deck-select');
        if (starterSelect && starterSelect.value) {
          starterDeck = starterSelect.value;
        }

        var gameType = 'casual';
        return {
          preconstructedDeck: starterDeck,
          deckLink: deckLink,
          deckSource: deckSource,
          gameType: gameType
        };
      }

      function buildPrivateInviteLink(inviteCode, casterMode) {
        var url = new URL(window.location.href);
        url.searchParams.set('privateInvite', inviteCode);
        if (casterMode) url.searchParams.set('casterMode', '1');
        else url.searchParams.delete('casterMode');
        return url.toString();
      }

      function joinQueue() {
        submitQueueJoin({
          waitingMessage: 'Waiting for opponent... (Esc to cancel)'
        });
      }

      function autoSaveCurrentDeckLink(submission) {
        if (!submission || submission.deckSource !== 'link' || !submission.deckLink || !window.TCGDeckLibrarySaveCurrent) return;
        window.TCGDeckLibrarySaveCurrent(submission.deckLink, {
          localStorageKey: 'tcgengine:savedDecks:AzukiSim',
          promptName: false,
          name: submission.deckLink
        });
      }

      function loadSavedDeckInput(input) {
        var linkEl = document.getElementById('azuki-deck-link');
        if (linkEl) linkEl.value = input || '';
        if (input) selectDeckSource('link');
      }

      document.addEventListener('change', function(e) {
        var sel = e.target.closest('.saved-decks-panel .dl-select');
        if (!sel) return;
        var opt = sel.options[sel.selectedIndex];
        loadSavedDeckInput(opt ? opt.getAttribute('data-queue-input') : '');
      });

      function createPrivateGame() {
        submitQueueJoin({
          createPrivate: true,
          waitingMessage: 'Waiting for invited opponent... (Esc to cancel)'
        });
      }

      function createRlBotGame() {
        var modal = document.getElementById('rl-bot-opponent-modal');
        if (!modal) return;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        var firstChoice = modal.querySelector('.rl-bot-opponent-choice');
        if (firstChoice) firstChoice.focus();
      }

      function closeRlBotOpponentModal() {
        var modal = document.getElementById('rl-bot-opponent-modal');
        if (!modal) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
      }

      function startRlBotGame(opponent) {
        opponent = ['raizan', 'zero', 'bobu', 'goldfish'].indexOf(opponent) !== -1 ? opponent : 'raizan';
        closeRlBotOpponentModal();
        submitQueueJoin({
          createRlBot: true,
          rlBotOpponent: opponent,
          waitingMessage: opponent === 'goldfish' ? 'Starting goldfish game...' : 'Starting RL bot game...'
        });
      }

      document.addEventListener('keydown', function(event) {
        if (event.key !== 'Escape') return;
        var importPopover = document.getElementById('azuki-import-popover');
        if (importPopover && importPopover.classList.contains('is-open')) {
          event.preventDefault();
          setAzukiDeckImportPopover(false);
          var importToggle = document.querySelector('.azuki-import-toggle');
          if (importToggle) importToggle.focus();
          return;
        }
        var deckPicker = document.getElementById('azuki-deck-picker-modal');
        if (deckPicker && deckPicker.classList.contains('is-open')) {
          event.preventDefault();
          closeAzukiDeckPicker();
          return;
        }
        var modal = document.getElementById('rl-bot-opponent-modal');
        if (modal && modal.classList.contains('is-open')) {
          event.preventDefault();
          closeRlBotOpponentModal();
        }
      });

      function createTutorialGame() {
        submitQueueJoin({
          createTutorial: true,
          waitingMessage: 'Preparing tutorial...'
        });
      }

      function joinPrivateInvite() {
        if (!_privateInviteCode) {
          showQueueInlineError('No private invite code found in this link.');
          return;
        }
        submitQueueJoin({
          privateInviteCode: _privateInviteCode,
          casterMode: _privateInviteCasterMode,
          waitingMessage: 'Waiting for host to start... (Esc to cancel)'
        });
      }

      function submitQueueJoin(options) {
        options = options || {};
        clearQueueInlineError();
        var submission = getDeckSubmission();
        if (!submission) return;

        var xhr = new XMLHttpRequest();
        xhr.open('POST', azukiAppBase() + 'APIs/Lobbies/JoinQueue.php', true);
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
            autoSaveCurrentDeckLink(submission);
            clearQueueInlineError();
            if(response.ready) {
              DisplayMatchFoundPopup(response.playerID, response.gameName, response.authKey);
            } else {
              _lobby_id = response.lobbyID;
              var inviteLink = '';
              if (response.inviteCode) {
                inviteLink = buildPrivateInviteLink(response.inviteCode, !!response.casterMode);
              }
              DisplayWaitingPopup(options.waitingMessage || 'Waiting for opponent... (Esc to cancel)', response.playerID, response.authKey, inviteLink, !!response.casterMode);
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

        var deckLink = submission.deckLink;
        var preconstructedDeck = submission.preconstructedDeck;
        var params = 'deckLink=' + encodeURIComponent(deckLink) + '&game_type=' + encodeURIComponent(submission.gameType);
        params += '&preconstructedDeck=' + encodeURIComponent(preconstructedDeck);
        params += "&rootName=" + encodeURIComponent(rootName);
        if (options.casterMode) params += '&casterMode=1';
        if (options.createPrivate) {
          params += '&createPrivate=1';
        }
        if (options.createRlBot) {
          params += '&createRlBot=1&format=rlbot';
          params += '&rlBotOpponent=' + encodeURIComponent(options.rlBotOpponent || 'raizan');
        }
        if (options.createTutorial) {
          params += '&createTutorial=1&format=tutorial';
        }
        if (options.privateInviteCode) {
          params += '&privateInviteCode=' + encodeURIComponent(options.privateInviteCode);
        }
        xhr.send(params);
      }

      function showQueueInlineError(message) {
        var el = document.getElementById('queue-inline-error');
        if (!el) {
          StyledAlert(message);
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

      function DisplayWaitingPopup(message, playerID, authKey, inviteLink, casterMode) {
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

        var currentInviteLink = inviteLink || '';
        var linkPreview = null;
        if (inviteLink && String(playerID) === '1') {
          var casterOption = document.createElement('label');
          casterOption.className = 'azuki-waiting-caster-option';

          var casterCheckbox = document.createElement('input');
          casterCheckbox.type = 'checkbox';
          casterCheckbox.checked = !!casterMode;
          casterCheckbox.setAttribute('aria-describedby', 'azuki-waiting-caster-status');

          var casterCopy = document.createElement('span');
          var casterTitle = document.createElement('strong');
          casterTitle.textContent = 'Caster mode';
          var casterDescription = document.createElement('small');
          casterDescription.textContent = 'Allow spectators to see both players\' hands.';
          casterCopy.appendChild(casterTitle);
          casterCopy.appendChild(casterDescription);
          casterOption.appendChild(casterCheckbox);
          casterOption.appendChild(casterCopy);
          waitingPopup.appendChild(casterOption);

          var casterStatus = document.createElement('p');
          casterStatus.id = 'azuki-waiting-caster-status';
          casterStatus.className = 'azuki-waiting-caster-status';
          casterStatus.textContent = casterCheckbox.checked ? 'Caster mode is enabled for this invite.' : '';
          waitingPopup.appendChild(casterStatus);

          casterCheckbox.addEventListener('change', function() {
            var requestedMode = casterCheckbox.checked;
            casterCheckbox.disabled = true;
            casterStatus.textContent = 'Saving caster mode...';

            var xhr = new XMLHttpRequest();
            xhr.open('POST', azukiAppBase() + 'APIs/Lobbies/UpdateLobbyCasterMode.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
              var response = null;
              try { response = JSON.parse(xhr.responseText || '{}'); } catch (e) {}
              if (xhr.status >= 200 && xhr.status < 300 && response && response.success) {
                var inviteUrl = new URL(currentInviteLink, window.location.href);
                if (requestedMode) inviteUrl.searchParams.set('casterMode', '1');
                else inviteUrl.searchParams.delete('casterMode');
                currentInviteLink = inviteUrl.toString();
                if (linkPreview) linkPreview.textContent = currentInviteLink;
                casterStatus.textContent = requestedMode
                  ? 'Caster mode is enabled. Share the updated invite link below.'
                  : 'Caster mode is off.';
              } else {
                casterCheckbox.checked = !requestedMode;
                casterStatus.textContent = (response && response.message) || 'Could not update caster mode.';
              }
              casterCheckbox.disabled = false;
            };
            xhr.onerror = function() {
              casterCheckbox.checked = !requestedMode;
              casterCheckbox.disabled = false;
              casterStatus.textContent = 'Could not update caster mode.';
            };
            var params = 'rootName=' + encodeURIComponent(rootName)
              + '&lobbyID=' + encodeURIComponent(_lobby_id)
              + '&playerID=' + encodeURIComponent(playerID)
              + '&authKey=' + encodeURIComponent(authKey)
              + '&casterMode=' + (requestedMode ? '1' : '0');
            xhr.send(params);
          });
        }

        if (inviteLink) {
          var inviteHint = document.createElement('p');
          inviteHint.textContent = 'Share this invite link with your opponent:';
          inviteHint.style.color = '#d8d8d8';
          inviteHint.style.marginTop = '14px';
          inviteHint.style.marginBottom = '8px';
          inviteHint.style.fontSize = '14px';
          waitingPopup.appendChild(inviteHint);

          linkPreview = document.createElement('div');
          linkPreview.textContent = currentInviteLink;
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
            copyTextToClipboard(currentInviteLink)
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
            xhr.open('POST', azukiAppBase() + 'APIs/Lobbies/LeaveQueue.php', true);
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
          font-family: Barlow, Arial, sans-serif;
          animation: pulseGlow 1.5s ease-in-out infinite;
        `;

        var subtitleElement = document.createElement('p');
        subtitleElement.textContent = 'Joining in...';
        subtitleElement.style.cssText = `
          color: #ccc;
          font-size: 20px;
          margin-bottom: 20px;
          font-family: Barlow, Arial, sans-serif;
        `;

        var countdownElement = document.createElement('div');
        countdownElement.id = 'countdown-number';
        countdownElement.style.cssText = `
          color: white;
          font-size: 120px;
          font-weight: bold;
          font-family: Barlow, Arial, sans-serif;
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

      function setActiveGamesRefreshState(button, state) {
        if (!button) return;
        window.clearTimeout(button._azukiRefreshResetTimer);
        button.classList.toggle('is-loading', state === 'loading');
        button.classList.toggle('is-complete', state === 'complete');
        button.disabled = state === 'loading';
        button.setAttribute('aria-busy', state === 'loading' ? 'true' : 'false');

        if (state === 'complete') {
          button._azukiRefreshResetTimer = window.setTimeout(function() {
            setActiveGamesRefreshState(button, 'idle');
          }, 900);
        }
      }

      function refreshOpenGames(refreshButton) {
        console.log('Refreshing open games');
        var feedbackStartedAt = Date.now();
        if (refreshButton) setActiveGamesRefreshState(refreshButton, 'loading');

        function finishRefreshFeedback(succeeded) {
          if (!refreshButton) return;
          var minimumLoadingTime = 300;
          var remainingLoadingTime = Math.max(0, minimumLoadingTime - (Date.now() - feedbackStartedAt));
          window.setTimeout(function() {
            setActiveGamesRefreshState(refreshButton, succeeded ? 'complete' : 'idle');
          }, remainingLoadingTime);
        }

        var xhr = new XMLHttpRequest();
        xhr.open('GET', azukiAppBase() + 'APIs/Lobbies/GetActiveGames.php?rootName=' + encodeURIComponent(rootName), true);
        xhr.responseType = 'json';

        xhr.onload = function() {
          if (xhr.status >= 200 && xhr.status < 300) {
          var data = xhr.response;
          
          if (data.data && Array.isArray(data.data)) {
            var totalCount = (typeof data.totalCount === 'number') ? data.totalCount : data.data.length;
            _activeGamesServerCount = totalCount;
            _activeGamesSnapshot = data.data;
            syncRejoinActiveGamesState();
            renderActiveGames(data.data);
          } else {
            _activeGamesServerCount = 0;
            _activeGamesSnapshot = [];
            syncRejoinActiveGamesState();
            renderActiveGames([]);
          }
          finishRefreshFeedback(true);
          } else {
          console.error('Error fetching open games:', xhr.statusText);
          _activeGamesServerCount = 0;
          _activeGamesSnapshot = [];
          syncRejoinActiveGamesState();
          renderActiveGames([]);
          finishRefreshFeedback(false);
          }
        };

        xhr.onerror = function() {
          console.error('Error fetching open games:', xhr.statusText);
          _activeGamesServerCount = 0;
          _activeGamesSnapshot = [];
          syncRejoinActiveGamesState();
          renderActiveGames([]);
          finishRefreshFeedback(false);
        };

        xhr.send();
      }

      function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function(ch) {
          return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
          }[ch];
        });
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
        var url = new URL(azukiAppBase() + 'NextTurn.php', window.location.href);
        url.searchParams.set('playerID', 'S');
        url.searchParams.set('viewerPerspective', perspective === 2 ? '2' : '1');
        url.searchParams.set('gameName', gameName);
        url.searchParams.set('folderPath', rootName);
        window.location.href = url.toString();
      }

      function renderActiveGames(games) {
        var gameListElement = document.getElementById('active-games-list');
        if (!gameListElement) return;
        var activeCard = gameListElement.closest('.azuki-active-card');
        if (!games || !games.length) {
          var rejoinBanner = document.getElementById('rejoin-last-game-banner');
          var hasRejoinGame = !!rejoinBanner && rejoinBanner.style.display !== 'none';
          if (activeCard) activeCard.classList.toggle('is-empty', !hasRejoinGame);
          gameListElement.innerHTML = hasRejoinGame ? '' :
            '<div class="active-game-empty">' +
              '<img src="/TCGEngine/Assets/Images/Zendo/active-games-empty-mark.svg?v=20260726e" alt="">' +
              '<p>No active games right now.<span>Start one or refresh again<br>in a moment.</span></p>' +
            '</div>';
          return;
        }
        if (activeCard) activeCard.classList.remove('is-empty');

        var html = '';
        games.forEach(function(game) {
          var visibilityClass = game.isPrivate ? 'private' : 'public';
          var visibilityLabel = game.isPrivate ? 'Private' : (game.casterMode ? 'Caster' : 'Public');
          var gameName = String(game.gameName || '');
          html += '<div class="active-game-card">';
          html +=   '<div class="active-game-meta">';
          html +=     '<div>Game <strong>' + escapeHtml(gameName) + '</strong><br><span style="font-size:12px; color:#b9b9b9;">Updated ' + escapeHtml(formatActiveGameTime(game.lastUpdatedAt)) + '</span></div>';
          html +=     '<span class="active-game-badge ' + visibilityClass + '">' + visibilityLabel + '</span>';
          html +=   '</div>';
          html +=   '<div class="active-game-actions">';
          html +=     '<button class="spectate-button" onclick="openSpectatorView(' + JSON.stringify(gameName).replace(/"/g, '&quot;') + ', 1)">Spectate P1 Side</button>';
          html +=     '<button class="spectate-button" onclick="openSpectatorView(' + JSON.stringify(gameName).replace(/"/g, '&quot;') + ', 2)">Spectate P2 Side</button>';
          html +=   '</div>';
          html += '</div>';
        });
        gameListElement.innerHTML = html;
      }
      function pollLobbyUpdates(playerID, authKey) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', azukiAppBase() + 'APIs/Lobbies/PollLobbyUpdates.php', true);
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
        if (window.GameLogClient && typeof window.GameLogClient.renderGameLibrary === 'function') {
          window.GameLogClient.renderGameLibrary('azuki-game-log-menu-list');
        }
        initializeDeckLinkFromUrl();
        initializeAzukiDeckPicker();
        var selectedSource = document.querySelector('input[name="azuki-deck-source-mode"]:checked');
        selectDeckSource(selectedSource ? selectedSource.value : 'starter');
        initializePrivateInviteFromUrl();
        updateRejoinLastGameUI();
        refreshOpenGames();
      });
    </script>

<?php
include_once __DIR__ . '/Disclaimer.php';
?>
