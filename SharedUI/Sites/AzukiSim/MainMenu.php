<?php
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
<div class="row-wrapper azuki-menu-grid">
  <!-- Active Games Section -->
  <div class="card azuki-glass-card azuki-active-card is-empty">
    <button class="azuki-active-refresh" type="button" onclick="refreshOpenGames(this)" aria-label="Refresh active games">
      <img class="azuki-active-refresh-icon" src="/TCGEngine/Assets/Icons/refresh.svg" width="16" height="16" alt="">
      <span class="azuki-active-refresh-spinner" aria-hidden="true"></span>
      <span class="azuki-active-refresh-check" aria-hidden="true">&#10003;</span>
    </button>
    <h2>Active Games <span id="active-game-count" class="azuki-active-count" aria-live="polite">0</span></h2>
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
      <div id="match-replay-menu-list" class="ga-replay-list"></div>
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
    <p id="rl-bot-opponent-description">Your selected deck will face one of these trained RL bots.</p>
    <div class="rl-bot-opponent-grid">
      <button type="button" class="rl-bot-opponent-choice" onclick="startRlBotGame('raizan')">
        <img src="/TCGEngine/AzukiSim/WebpImages/S1-STT01-001_Raizan_L_L_die.webp" alt="" aria-hidden="true">
        <span>
          <strong>Raizan</strong>
          <small>Starter Deck</small>
        </span>
      </button>
      <button type="button" class="rl-bot-opponent-choice" onclick="startRlBotGame('zero')">
        <img src="/TCGEngine/AzukiSim/WebpImages/S1-STT04-001_Zero_L_L_die.webp" alt="" aria-hidden="true">
        <span>
          <strong>Zero</strong>
          <small>Deck 51</small>
        </span>
      </button>
    </div>
  </div>
</div>
<script src="/TCGEngine/Core/MatchReplayClient.js"></script>
<script>
  window.AZUKI_DECK_CODES = <?php echo json_encode($azukiDeckCodes, JSON_UNESCAPED_SLASHES); ?>;
</script>
<script src="/TCGEngine/AzukiDeck/HomeActions.js?v=20260726"></script>

<style>
  .azuki-rejoin-banner {
    position: absolute;
    top: 20px;
    left: 50%;
    z-index: 80;
    width: min(600px, calc(100vw - 850px));
    min-width: 360px;
    transform: translateX(-50%);
  }
  .azuki-rejoin-banner > button.azuki-rejoin-banner-button {
    display: flex;
    width: 100%;
    min-height: 58px;
    align-items: center;
    gap: 12px;
    box-sizing: border-box;
    padding: 9px 12px;
    color: #f8f2df;
    background: linear-gradient(135deg, rgba(28, 73, 112, 0.96), rgba(39, 43, 91, 0.96));
    border: 1px solid rgba(240, 201, 108, 0.62);
    border-radius: 12px;
    box-shadow: 0 10px 28px rgba(2, 8, 20, 0.38), inset 0 1px 0 rgba(255,255,255,0.1);
    text-align: left;
  }
  .azuki-rejoin-banner > button.azuki-rejoin-banner-button:hover,
  .azuki-rejoin-banner > button.azuki-rejoin-banner-button:focus-visible {
    border-color: #f0c96c;
    background: linear-gradient(135deg, rgba(36, 91, 137, 0.98), rgba(52, 55, 111, 0.98));
    box-shadow: 0 12px 32px rgba(2, 8, 20, 0.48), 0 0 0 2px rgba(240, 201, 108, 0.16);
    transform: none !important;
  }
  .azuki-rejoin-banner-icon {
    display: grid;
    width: 34px;
    height: 34px;
    flex: 0 0 auto;
    place-items: center;
    color: #10243b;
    background: #f0c96c;
    border-radius: 50%;
    font-size: 22px;
    font-weight: 900;
  }
  .azuki-rejoin-banner-copy {
    display: flex;
    min-width: 0;
    flex: 1;
    flex-direction: column;
    gap: 2px;
  }
  .azuki-rejoin-banner-copy strong {
    font-size: 15px;
    line-height: 1.15;
  }
  .azuki-rejoin-banner-copy > span {
    overflow: hidden;
    color: #c7d4e3;
    font-size: 12px;
    line-height: 1.2;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .azuki-rejoin-banner-action {
    flex: 0 0 auto;
    padding: 6px 10px;
    color: #f0c96c;
    border: 1px solid rgba(240, 201, 108, 0.38);
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
  }
  .row-wrapper > .card {
    flex: 1 1 0 !important;
    min-width: 0;
  }
  .hotkey-row { display: flex; align-items: center; gap: 10px; font-size: 13px; color: #ccc; }
  .azuki-inline-section-title {
    color: #ccc;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.08em;
    margin: 0 0 8px;
    text-transform: uppercase;
  }
  .azuki-info-tabs {
    display: flex;
    gap: 4px;
    padding: 4px;
    background: rgba(5, 16, 31, 0.72);
    border: 1px solid rgba(118, 196, 255, 0.22);
    border-radius: 10px;
    box-shadow: inset 0 1px 4px rgba(0, 0, 0, 0.34);
  }
  .azuki-info-tab {
    display: flex !important;
    flex: 1;
    min-width: 0;
    min-height: 34px;
    align-items: center;
    justify-content: center;
    padding: 7px 10px !important;
    color: #aebed0;
    background: transparent !important;
    border: 1px solid transparent !important;
    border-radius: 7px !important;
    box-shadow: none !important;
    clip-path: none !important;
    cursor: pointer;
    font-size: 13px;
    line-height: 1.15;
    transform: none !important;
    filter: none !important;
  }
  .azuki-info-tab::before,
  .azuki-info-tab::after {
    content: none !important;
  }
  .azuki-info-tabs > button.azuki-info-tab:hover,
  .azuki-info-tabs > button.azuki-info-tab:focus-visible {
    color: #eef5ff;
    background: rgba(74, 133, 184, 0.16) !important;
    border-color: rgba(118, 196, 255, 0.3) !important;
    transform: none !important;
    filter: none !important;
  }
  .azuki-info-tabs > button.azuki-info-tab.is-active {
    color: #fff4d6;
    background: linear-gradient(180deg, rgba(53, 91, 132, 0.92), rgba(31, 59, 94, 0.96)) !important;
    border-color: rgba(240, 201, 108, 0.62) !important;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.08), 0 2px 7px rgba(0,0,0,0.2) !important;
    font-weight: 700;
  }
  .rl-bot-opponent-modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 5000;
    align-items: center;
    justify-content: center;
    padding: 20px;
    box-sizing: border-box;
  }
  .rl-bot-opponent-modal.is-open {
    display: flex;
  }
  .rl-bot-opponent-modal__backdrop {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    padding: 0;
    border: 0;
    border-radius: 0;
    background: rgba(5, 7, 10, 0.84);
    cursor: default;
  }
  .rl-bot-opponent-modal__dialog {
    position: relative;
    width: min(560px, 100%);
    padding: 24px;
    border: 1px solid rgba(118, 196, 255, 0.32);
    border-radius: 14px;
    background: linear-gradient(145deg, #20232a, #121419);
    box-shadow: 0 24px 80px rgba(0, 0, 0, 0.65);
    color: #fff;
  }
  .rl-bot-opponent-modal__dialog h2 {
    margin: 0 36px 6px 0;
    font-size: 24px;
  }
  .rl-bot-opponent-modal__dialog > p {
    margin: 0 0 20px;
    color: #bfc5ce;
    font-size: 14px;
  }
  .rl-bot-opponent-modal__close {
    position: absolute !important;
    top: 10px;
    right: 12px;
    min-width: 36px;
    padding: 4px 8px;
    border: 0;
    background: transparent;
    color: #bfc5ce;
    font-size: 28px;
    line-height: 1;
    transform: none !important;
    clip-path: none !important;
  }
  .rl-bot-opponent-modal__close:hover {
    transform: none !important;
  }
  .rl-bot-opponent-modal__close::before,
  .rl-bot-opponent-modal__close::after,
  .rl-bot-opponent-choice::before,
  .rl-bot-opponent-choice::after {
    content: none !important;
  }
  .rl-bot-opponent-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
  }
  .rl-bot-opponent-choice {
    display: flex;
    align-items: center;
    gap: 14px;
    min-width: 0;
    padding: 14px;
    border: 1px solid rgba(255, 255, 255, 0.14);
    border-radius: 10px;
    background: rgba(53, 58, 69, 0.9);
    color: #fff;
    text-align: left;
  }
  .rl-bot-opponent-choice:hover,
  .rl-bot-opponent-choice:focus-visible {
    border-color: #76c4ff;
    background: rgba(79, 96, 124, 0.95);
    box-shadow: 0 0 0 2px rgba(118, 196, 255, 0.16);
    transform: none !important;
  }
  .rl-bot-opponent-choice img {
    width: 64px;
    height: 88px;
    flex: 0 0 auto;
    border-radius: 6px;
    object-fit: cover;
  }
  .rl-bot-opponent-choice span {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: 4px;
  }
  .rl-bot-opponent-choice strong {
    font-size: 18px;
  }
  .rl-bot-opponent-choice small {
    color: #c7ccd5;
    font-size: 12px;
  }
  .azuki-game-setup {
    display: flex;
    flex-direction: column;
    gap: 14px;
  }
  .azuki-game-setup > .azuki-source-radio {
    position: absolute !important;
    width: 1px !important;
    height: 1px !important;
    margin: 0 !important;
    overflow: hidden;
    opacity: 0;
    pointer-events: none;
  }
  .azuki-owned-decks {
    min-width: 0;
  }
  .azuki-deck-section-heading {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 12px;
  }
  .azuki-deck-section-heading h3 {
    margin: 0;
    color: #fff4d6;
    font-size: 17px;
  }
  .azuki-deck-section-heading p {
    margin: 3px 0 0;
    color: #9fb0c3;
    font-size: 12px;
  }
  .azuki-deck-import {
    margin-bottom: 14px;
  }
  .azuki-deck-import > label,
  .azuki-link-source-body > label {
    display: block;
    margin-bottom: 6px;
    color: #dce6f2;
    font-size: 12px;
    font-weight: 700;
  }
  .azuki-deck-import-row {
    display: flex;
    gap: 8px;
    min-width: 0;
  }
  .azuki-deck-import-row input,
  .azuki-link-source-body > input,
  .azuki-starter-source select {
    min-width: 0;
    box-sizing: border-box;
    padding: 9px 11px;
    color: #fff;
    background: rgba(7, 20, 37, 0.88);
    border: 1px solid rgba(118, 196, 255, 0.3);
    border-radius: 7px;
    font: inherit;
  }
  .azuki-deck-import-row input {
    flex: 1;
  }
  .azuki-deck-import-row input:focus,
  .azuki-link-source-body > input:focus,
  .azuki-starter-source select:focus {
    border-color: #76c4ff;
    outline: 2px solid rgba(118, 196, 255, 0.16);
    outline-offset: 1px;
  }
  .azuki-field-help,
  .azuki-import-status {
    display: block;
    font-size: 11px;
    line-height: 1.35;
  }
  .azuki-field-help {
    margin-top: 5px;
    color: #8295aa;
  }
  .azuki-import-status {
    min-height: 15px;
    margin-top: 2px;
    color: #9ed9b4;
  }
  .azuki-import-status.is-error {
    color: #ffadad;
  }
  .azuki-builder-deck-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(300px, 100%), 1fr));
    gap: 10px;
    max-height: 300px;
    padding: 2px 7px 2px 2px;
    contain: layout paint;
    overflow-y: auto;
  }
  .azuki-builder-deck-option {
    position: relative;
    display: flex;
    min-width: 0;
    flex-direction: column;
    padding: 6px;
    background: rgba(8, 22, 40, 0.6);
    border: 1px solid rgba(118, 196, 255, 0.2);
    border-radius: 11px;
    transition: border-color 0.16s ease, box-shadow 0.16s ease, background 0.16s ease;
  }
  .azuki-builder-deck-option:has(> label > input:checked) {
    background: rgba(25, 55, 86, 0.76);
    border-color: rgba(240, 201, 108, 0.78);
    box-shadow: 0 0 0 1px rgba(240, 201, 108, 0.12);
  }
  .azuki-builder-deck-option > label {
    display: block;
    cursor: pointer;
  }
  .azuki-builder-deck-option > label > input {
    position: absolute;
    width: 1px;
    height: 1px;
    opacity: 0;
    pointer-events: none;
  }
  .azuki-builder-deck-tile {
    position: relative;
    display: flex;
    min-height: 92px;
    align-items: center;
    overflow: hidden;
    border-radius: 7px;
    transition: background 0.16s ease;
  }
  .azuki-builder-deck-option > label:hover .azuki-builder-deck-tile {
    background: rgba(118, 196, 255, 0.06);
  }
  .azuki-builder-deck-option > label > input:focus-visible + .azuki-builder-deck-tile {
    outline: 2px solid #f0c96c;
    outline-offset: 1px;
  }
  .azuki-builder-deck-art {
    position: relative;
    align-self: stretch;
    width: 104px;
    min-width: 104px;
    overflow: hidden;
    background: radial-gradient(circle at 44% 45%, rgba(118,196,255,0.19), transparent 66%);
  }
  .azuki-builder-deck-art img {
    position: absolute;
    top: 4px;
    left: 20px;
    z-index: 1;
    width: 62px;
    height: 88px;
    object-fit: cover;
    border: 1px solid rgba(255,255,255,0.34);
    border-radius: 5px;
    box-shadow: 0 5px 12px rgba(0,0,0,0.48);
    transform: rotate(-2deg);
  }
  .azuki-builder-deck-art.has-two img:first-child {
    left: 7px;
    z-index: 2;
    transform: rotate(-3deg);
  }
  .azuki-builder-deck-art.has-two img:last-child {
    top: 7px;
    left: 38px;
    z-index: 1;
    transform: rotate(4deg);
  }
  .azuki-builder-deck-copy {
    display: flex;
    flex: 1;
    flex-direction: column;
    justify-content: center;
    min-width: 0;
    padding: 13px 74px 13px 4px;
  }
  .azuki-builder-deck-copy strong {
    display: block;
    overflow: hidden;
    color: #fff;
    font-size: 15px;
    line-height: 1.2;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .azuki-builder-deck-copy > span {
    display: -webkit-box;
    margin-top: 5px;
    overflow: hidden;
    color: #aebed0;
    font-size: 11px;
    line-height: 1.3;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
  }
  .azuki-builder-deck-selected {
    position: absolute;
    top: 8px;
    right: 7px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 7px;
    color: #f0c96c;
    background: rgba(240, 201, 108, 0.1);
    border-radius: 999px;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.02em;
    opacity: 0;
    transition: opacity 0.16s ease;
  }
  .azuki-builder-deck-option > label > input:checked + .azuki-builder-deck-tile .azuki-builder-deck-selected {
    opacity: 1;
  }
  .azuki-deck-management-button,
  .azuki-deck-edit-action,
  .azuki-deck-more summary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
    color: #fff;
    background: rgba(47, 78, 111, 0.95);
    border: 1px solid rgba(118, 196, 255, 0.35);
    border-radius: 6px;
    cursor: pointer;
    font: inherit;
    text-decoration: none;
  }
  .azuki-deck-management-button {
    min-height: 38px;
    padding: 7px 12px;
    white-space: nowrap;
  }
  .azuki-deck-management-button.primary {
    background: #1769aa;
  }
  .azuki-deck-auth-actions {
    display: flex;
    gap: 8px;
    margin-top: 8px;
  }
  .azuki-builder-deck-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 5px 3px 1px;
  }
  .azuki-deck-edit-action,
  .azuki-deck-more summary {
    min-height: 32px;
    padding: 5px 10px;
    font-size: 12px;
    font-weight: 700;
  }
  .azuki-deck-edit-action:hover,
  .azuki-deck-more summary:hover {
    border-color: #76c4ff;
    background: rgba(58, 101, 143, 0.98);
    transform: none !important;
  }
  .azuki-deck-more {
    min-width: 0;
  }
  .azuki-deck-more summary {
    list-style: none;
    user-select: none;
  }
  .azuki-deck-more summary::-webkit-details-marker {
    display: none;
  }
  .azuki-deck-more-menu {
    display: flex;
    width: 190px;
    flex-direction: column;
    margin-top: 6px;
    padding: 6px;
    background: #0d2138;
    border: 1px solid rgba(118, 196, 255, 0.38);
    border-radius: 8px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.36);
  }
  .azuki-deck-more-menu button {
    min-height: 32px;
    padding: 7px 9px;
    color: #e5eef8;
    background: transparent;
    border: 0;
    border-radius: 5px;
    font: inherit;
    font-size: 12px;
    text-align: left;
    transform: none !important;
  }
  .azuki-deck-more-menu button:hover {
    background: rgba(118, 196, 255, 0.1);
  }
  .azuki-deck-more-menu .danger {
    color: #ffd0d0;
  }
  .azuki-deck-source-empty {
    padding: 14px;
    color: #b9b9b9;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 8px;
    font-size: 13px;
    line-height: 1.4;
  }
  .saved-decks-panel {
    margin: 12px 0 0;
  }
  .azuki-starter-source {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(170px, auto);
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: rgba(7, 20, 37, 0.58);
    border: 1px solid rgba(118, 196, 255, 0.18);
    border-radius: 9px;
    cursor: pointer;
    transition: border-color 0.16s ease, background 0.16s ease;
  }
  .azuki-starter-source.is-source-active {
    background: rgba(27, 61, 94, 0.72);
    border-color: rgba(240, 201, 108, 0.65);
  }
  .azuki-starter-source label {
    cursor: pointer;
  }
  .azuki-starter-source label span {
    display: flex;
    flex-direction: column;
    gap: 3px;
  }
  .azuki-starter-source strong {
    color: #edf4fc;
    font-size: 13px;
  }
  .azuki-starter-source small {
    color: #8fa3b8;
    font-size: 11px;
  }
  .azuki-starter-source select {
    width: 100%;
  }
  .azuki-link-source {
    border-top: 1px solid rgba(255,255,255,0.08);
  }
  .azuki-link-source > summary {
    width: fit-content;
    padding: 10px 0 0;
    color: #9fb7ce;
    cursor: pointer;
    font-size: 12px;
    font-weight: 700;
  }
  .azuki-link-source.is-source-active > summary {
    color: #f0c96c;
  }
  .azuki-link-source-body {
    padding-top: 12px;
  }
  .azuki-link-source-body > input {
    width: 100%;
  }
  .azuki-link-source-body > p {
    margin: 5px 0 0;
    color: #8295aa;
    font-size: 11px;
  }
  .azuki-game-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    padding-top: 2px;
  }
  .azuki-game-actions > button {
    min-height: 38px;
    padding: 7px 13px;
    background: rgba(39, 74, 108, 0.9);
    border-color: rgba(118, 196, 255, 0.32);
  }
  .azuki-game-actions > .azuki-game-action-primary {
    min-width: 128px;
    background: linear-gradient(180deg, #2d84c5, #1769aa);
    border-color: rgba(150, 214, 255, 0.72);
  }
  .azuki-game-actions > .azuki-game-action-learn {
    margin-left: auto;
    color: #c7d5e4;
    background: transparent;
    border-color: transparent;
  }
  .azuki-game-actions > #join-private-invite-btn {
    display: none !important;
    background: #2d8a57;
  }
  .azuki-game-actions > #join-private-invite-btn.is-visible {
    display: flex !important;
  }
  .azuki-info-panel {
    display: none;
    flex-direction: column;
    gap: 16px;
  }
  .azuki-info-panel.is-active {
    display: flex;
  }
  .ga-replay-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }
  .hotkey-badge {
    display: inline-block; min-width: 28px; text-align: center;
    padding: 2px 7px; border-radius: 5px;
    background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.2);
    font-family: var(--zendo-font-code); font-size: 13px; font-weight: 700; color: #fff;
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
  .home-header {
    height: 92px;
    padding: 10px 0 6px 40px;
  }
  .home-header h1 {
    font-size: 42px;
    margin: 0 0 2px;
    line-height: 1;
  }
  .home-header p {
    margin: 0;
  }
  .azuki-menu-grid {
    display: grid !important;
    grid-template-columns: minmax(260px, 0.9fr) minmax(360px, 1.2fr) minmax(300px, 1fr);
    gap: 14px;
    align-items: start;
    margin: 0 10px 10px;
  }
  .azuki-active-card,
  .azuki-queue-card,
  .azuki-info-card {
    color: white;
    border-radius: 12px;
    position: relative;
    margin: 0 !important;
    padding: 18px !important;
  }
  .azuki-info-card {
    display: flex;
    flex-direction: column;
    gap: 16px;
  }
  .azuki-glass-card {
    background: linear-gradient(165deg, rgba(9, 23, 44, 0.82) 0%, rgba(6, 17, 34, 0.74) 100%) !important;
    border: 1px solid rgba(118, 196, 255, 0.24) !important;
    box-shadow: 0 14px 36px rgba(2, 8, 20, 0.45), inset 0 1px 0 rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(10px) saturate(115%);
    -webkit-backdrop-filter: blur(10px) saturate(115%);
  }
  .azuki-queue-card h2,
  .azuki-active-card h2,
  .azuki-info-card h2 {
    margin-top: 0;
  }
  #did-you-know-box {
    background: linear-gradient(135deg, rgba(85, 166, 225, 0.14) 0%, rgba(18, 31, 50, 0.42) 100%);
    border: 1px solid rgba(118, 196, 255, 0.28);
    border-radius: 8px;
    padding: 14px 16px;
    position: relative;
  }
  #did-you-know-box button {
    position: absolute !important;
    top: 10px !important;
    right: 10px !important;
    padding: 3px 8px !important;
    border-radius: 5px !important;
    font-size: 12px !important;
  }
  .saved-decks-panel .deck-library-empty {
    color: #b9b9b9;
    font-size: 13px;
    margin-top: 8px;
  }
  .saved-decks-panel .dl-dropdown-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 8px;
  }
  .saved-decks-panel .dl-act {
    padding: 5px 9px;
    font-size: 12px;
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
    border: 1px solid rgba(118, 196, 255, 0.22);
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
  .ga-replay-list {
    min-height: 72px;
    max-height: 360px;
    overflow-y: auto;
    padding-right: 4px;
  }

  /* Zendo menu treatment */
  body {
    --zendo-font-display: Georgia, 'Times New Roman', serif;
    --zendo-font-ui: Barlow, Arial, sans-serif;
    --zendo-font-code: ui-monospace, SFMono-Regular, Consolas, 'Liberation Mono', monospace;
    min-height: 100vh;
    background:
      linear-gradient(90deg, rgba(3, 14, 27, 0.2), rgba(5, 20, 35, 0.04) 46%, rgba(3, 13, 24, 0.18)),
      url('/TCGEngine/Assets/Images/Zendo/zendo-temple-background.webp') center top / cover fixed no-repeat,
      #061524 !important;
    color: #e7dfcf;
  }
  body::before {
    content: "";
    position: fixed;
    inset: 0;
    z-index: -1;
    pointer-events: none;
    background:
      radial-gradient(circle at 54% -4%, rgba(100, 137, 159, 0.12), transparent 36%),
      linear-gradient(180deg, transparent 58%, rgba(0, 7, 14, 0.72));
  }
  .zendo-icon {
    display: block;
    width: 18px;
    height: 18px;
    flex: 0 0 auto;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.7;
    stroke-linecap: round;
    stroke-linejoin: round;
  }
  .zendo-raster-icon {
    display: block;
    width: 24px;
    height: 24px;
    flex: 0 0 auto;
    object-fit: contain;
  }
  .home-header {
    position: relative;
    width: min(570px, 52vw);
    height: 102px;
    padding: 18px 0 8px 116px;
    box-sizing: border-box;
  }
  .home-header::before {
    content: "";
    position: absolute;
    top: 10px;
    left: 27px;
    width: 78px;
    height: 78px;
    background: url('/TCGEngine/Assets/Images/Zendo/zendo-enso.png') center / contain no-repeat;
    filter: drop-shadow(0 2px 8px rgba(0,0,0,0.42));
  }
  .home-header h1 {
    color: #f2ebdc;
    font-family: var(--zendo-font-display);
    font-size: 42px;
    font-weight: 500;
    letter-spacing: 0.14em;
    text-shadow: 0 2px 10px rgba(0,0,0,0.6);
  }
  .home-header h1::after {
    content: "禅堂";
    display: inline-grid;
    width: 50px;
    height: 31px;
    margin-left: 8px;
    place-items: center;
    color: #b73a32;
    border: 1px solid #b73a32;
    border-radius: 3px;
    font-size: 19px;
    letter-spacing: 0;
    vertical-align: 5px;
  }
  .home-header p {
    color: #d6ad69;
    font-family: var(--zendo-font-ui);
    font-size: 14px;
    font-weight: 400;
    letter-spacing: 0.01em;
  }
  .nav-bar {
    top: 24px !important;
    right: 30px !important;
    display: flex;
    align-items: center;
    gap: 16px;
  }
  .nav-bar-user {
    height: 58px;
    padding: 0 10px;
    box-sizing: border-box;
    background:
      linear-gradient(180deg, rgba(11, 27, 43, 0.7), rgba(3, 15, 28, 0.78)) !important;
    border: 1px solid rgba(190, 143, 73, 0.32) !important;
    border-radius: 13px !important;
    box-shadow:
      inset 0 1px 0 rgba(255, 239, 204, 0.025),
      0 10px 28px rgba(0, 5, 12, 0.2);
    backdrop-filter: blur(10px);
  }
  .nav-bar-user .rightnav {
    display: flex !important;
    height: 100%;
    align-items: center;
    margin: 0 !important;
    padding: 0 !important;
    list-style: none;
  }
  .nav-bar-user .rightnav > li {
    position: relative;
    display: flex;
    height: 100%;
    align-items: center;
    margin: 0 !important;
    padding: 0 !important;
  }
  .nav-bar-user .rightnav > li + li::before {
    content: "";
    position: absolute;
    top: 17px;
    left: 0;
    width: 1px;
    height: 24px;
    background: linear-gradient(transparent, rgba(209, 165, 92, 0.3), transparent);
  }
  .nav-bar a,
  .nav-bar .NavBarItem {
    color: #e7dfcf !important;
  }
  .nav-bar-user .NavBarItem {
    display: flex !important;
    height: 56px;
    align-items: center;
    gap: 9px;
    padding: 0 14px !important;
    box-sizing: border-box;
    font-family: var(--zendo-font-ui);
    font-size: 13px;
    font-weight: 400;
    letter-spacing: 0.01em;
    line-height: 1.1;
    text-decoration: none !important;
    white-space: nowrap;
    transition: color 150ms ease, background-color 150ms ease;
  }
  .nav-bar-user .nav-item-icon {
    display: block;
    width: 19px;
    height: 19px;
    flex: 0 0 auto;
  }
  .nav-bar-user .NavBarItem:hover,
  .nav-bar-user .NavBarItem:focus-visible {
    color: #f2cf88 !important;
    background: linear-gradient(180deg, rgba(213, 167, 89, 0.075), rgba(213, 167, 89, 0.025));
    outline: none;
  }
  .nav-bar-links {
    position: relative;
    height: 58px;
    padding-left: 17px;
    background: transparent !important;
    border: 0 !important;
    border-radius: 0 !important;
    box-shadow: none !important;
  }
  .nav-bar-links::before {
    content: "";
    position: absolute;
    top: 14px;
    left: 0;
    width: 1px;
    height: 30px;
    background: linear-gradient(transparent, rgba(209, 165, 92, 0.32), transparent);
  }
  .nav-bar-links > ul {
    display: flex !important;
    height: 58px;
    align-items: center;
    gap: 10px;
    margin: 0 !important;
    padding: 0 !important;
    list-style: none;
  }
  .nav-bar-links > ul > li {
    display: block;
    width: 56px;
    height: 56px;
    margin: 0 !important;
    padding: 0 !important;
  }
  .nav-bar-links a {
    display: grid !important;
    width: 56px;
    height: 56px;
    padding: 0 !important;
    place-items: center;
    box-sizing: border-box;
    background:
      linear-gradient(180deg, rgba(11, 27, 43, 0.72), rgba(3, 15, 28, 0.8));
    border: 1px solid rgba(190, 143, 73, 0.27);
    border-radius: 12px;
    box-shadow:
      inset 0 1px 0 rgba(255, 239, 204, 0.025),
      0 8px 22px rgba(0, 5, 12, 0.16);
    backdrop-filter: blur(10px);
    transition: border-color 150ms ease, background-color 150ms ease, transform 150ms ease;
  }
  .nav-bar-links a:hover,
  .nav-bar-links a:focus-visible {
    background: linear-gradient(180deg, rgba(19, 40, 58, 0.93), rgba(6, 22, 37, 0.95));
    border-color: rgba(222, 178, 101, 0.58);
    outline: none;
    transform: translateY(-1px);
  }
  .nav-bar-links img {
    display: block;
    width: 23px;
    height: 23px;
    margin: 0;
  }
  .azuki-menu-grid {
    grid-template-columns: minmax(250px, 0.82fr) minmax(520px, 1.62fr) minmax(300px, 0.92fr);
    gap: 16px;
    margin: 8px 20px 14px;
  }
  .azuki-active-card,
  .azuki-queue-card,
  .azuki-info-card {
    min-height: 0;
    box-sizing: border-box;
    border: 1px solid rgba(198, 148, 76, 0.46) !important;
    border-radius: 14px;
  }
  .azuki-queue-card {
    overflow: hidden;
  }
  .azuki-queue-card::before {
    content: "";
    position: absolute;
    top: -28px;
    right: 10px;
    width: 126px;
    height: 126px;
    pointer-events: none;
    background: url('/TCGEngine/Assets/Images/Zendo/zendo-enso.png') center / contain no-repeat;
    opacity: 0.09;
    transform: rotate(-24deg);
  }
  .azuki-queue-card > * {
    position: relative;
    z-index: 1;
  }
  .azuki-glass-card {
    background: linear-gradient(150deg, rgba(8, 25, 42, 0.93), rgba(3, 15, 28, 0.91)) !important;
    box-shadow: 0 18px 44px rgba(0,0,0,0.42), inset 0 1px 0 rgba(255,255,255,0.035);
    backdrop-filter: blur(12px);
  }
  .azuki-active-card {
    display: flex;
    flex-direction: column;
    background: linear-gradient(155deg, rgba(7, 23, 39, 0.82), rgba(3, 15, 28, 0.62)) !important;
  }
  .azuki-active-card.is-empty {
    background: linear-gradient(180deg, rgba(4, 18, 32, 0.72), rgba(4, 18, 32, 0.28) 58%, rgba(4, 18, 32, 0.12)) !important;
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
  }
  .azuki-active-card h2,
  .azuki-prepare-heading h2,
  .azuki-deck-section-heading h3,
  .azuki-selected-deck-label {
    color: #e8d6b4;
    font-family: var(--zendo-font-display);
    letter-spacing: 0.08em;
    text-transform: uppercase;
  }
  .azuki-active-card h2 {
    display: flex;
    min-height: 26px;
    align-items: center;
    gap: 8px;
    margin: 0;
    padding-left: 31px;
    font-size: 15px;
    line-height: 1;
  }
  .azuki-active-card h2::before {
    content: "";
    position: absolute;
    left: 18px;
    width: 18px;
    height: 18px;
    background: url('/TCGEngine/Assets/Images/Zendo/active-games-users.svg') center / contain no-repeat;
  }
  .azuki-active-count {
    display: inline-grid;
    min-width: 18px;
    height: 18px;
    place-items: center;
    color: #071525;
    background: #c79859;
    border-radius: 999px;
    font-family: var(--zendo-font-ui);
    font-size: 10px;
    letter-spacing: 0;
  }
  .azuki-active-refresh {
    position: absolute !important;
    top: 17px !important;
    right: 15px !important;
    z-index: 2;
    width: 28px;
    height: 28px;
    min-height: 0 !important;
    padding: 6px !important;
    background: transparent !important;
    border: 0 !important;
    border-radius: 50% !important;
    clip-path: none !important;
    cursor: pointer;
    transform: none !important;
  }
  .azuki-active-refresh::before,
  .azuki-active-refresh::after {
    content: none !important;
  }
  .azuki-active-refresh:hover,
  .azuki-active-refresh:focus-visible {
    background: rgba(199, 152, 89, 0.12) !important;
    outline: 1px solid rgba(199, 152, 89, 0.45);
  }
  .azuki-active-refresh-icon {
    display: block;
    width: 16px;
    height: 16px;
    filter: invert(86%) sepia(20%) saturate(809%) hue-rotate(356deg) brightness(94%);
  }
  .azuki-active-refresh-spinner,
  .azuki-active-refresh-check {
    display: none;
  }
  .azuki-active-refresh-spinner {
    width: 15px;
    height: 15px;
    border: 2px solid rgba(199, 152, 89, 0.3);
    border-top-color: #c79859;
    border-radius: 50%;
    animation: azuki-active-refresh-spin 0.7s linear infinite;
  }
  .azuki-active-refresh-check {
    color: #c79859;
    font-family: Arial, sans-serif;
    font-size: 18px;
    font-weight: 700;
    line-height: 1;
  }
  .azuki-active-refresh.is-loading .azuki-active-refresh-icon,
  .azuki-active-refresh.is-complete .azuki-active-refresh-icon {
    display: none;
  }
  .azuki-active-refresh.is-loading .azuki-active-refresh-spinner,
  .azuki-active-refresh.is-complete .azuki-active-refresh-check {
    display: block;
  }
  .azuki-active-refresh:disabled {
    cursor: default;
  }
  @keyframes azuki-active-refresh-spin {
    to { transform: rotate(360deg); }
  }
  .azuki-active-card .active-games-list {
    display: flex;
    min-height: 0;
    max-height: none;
    flex: 1;
    flex-direction: column;
    gap: 9px;
    margin-top: 18px;
    padding: 0 2px 0 0;
    overflow-y: auto;
  }
  .azuki-active-card.is-empty .active-games-list {
    margin-top: 0;
    overflow: hidden;
  }
  .azuki-active-card .active-game-empty {
    display: flex;
    flex: 1;
    align-items: center;
    flex-direction: column;
    padding-top: 58px;
    color: #b9c2cc;
    text-align: center;
  }
  .azuki-active-card .active-game-empty img {
    width: 92px;
    height: 71px;
    margin-bottom: 22px;
    opacity: 0.78;
  }
  .azuki-active-card .active-game-empty p {
    margin: 0;
    font-size: 12px;
    line-height: 1.6;
  }
  .azuki-active-card .active-game-empty p > span {
    display: block;
    margin-top: 6px;
    color: #9aa7b3;
  }
  .azuki-active-card .active-game-card {
    padding: 12px;
    background: linear-gradient(145deg, rgba(16, 40, 60, 0.95), rgba(6, 23, 39, 0.96));
    border: 1px solid rgba(199, 152, 89, 0.42);
    border-radius: 7px;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.03);
  }
  .azuki-active-card .active-game-meta {
    color: #e7dfcf;
    font-family: var(--zendo-font-ui);
  }
  .azuki-active-card .active-game-badge {
    border: 1px solid currentColor;
    background: transparent;
    border-radius: 3px;
    font-size: 9px;
  }
  .azuki-active-card .spectate-button {
    min-height: 32px;
    padding: 6px 9px;
    color: #e4d7c2;
    background: rgba(21, 48, 68, 0.88);
    border: 1px solid rgba(131, 157, 175, 0.36);
    border-radius: 4px;
    clip-path: none !important;
    font-family: var(--zendo-font-ui);
    font-size: 11px;
    transform: none !important;
  }
  .azuki-active-card .spectate-button::before,
  .azuki-active-card .spectate-button::after {
    content: none !important;
  }
  .azuki-prepare-heading {
    display: flex;
    align-items: baseline;
    gap: 12px;
    margin-bottom: 10px;
  }
  .azuki-prepare-heading::after {
    content: "";
    min-width: 28px;
    flex: 1;
    border-top: 1px solid rgba(201, 164, 98, 0.58);
    transform: translateY(-3px);
  }
  .azuki-prepare-heading h2 {
    margin: 0;
    flex: 0 0 auto;
    font-size: 18px;
  }
  .azuki-prepare-heading p {
    margin: 0;
    flex: 0 1 auto;
    color: #c4a77d;
    font-family: var(--zendo-font-ui);
    font-size: 12px;
    font-weight: 600;
  }
  .azuki-owned-decks {
    position: relative;
    padding: 0;
    overflow: visible;
    border: 1px solid rgba(138, 156, 172, 0.25);
    border-radius: 10px;
    background: rgba(3, 14, 26, 0.42);
  }
  .azuki-deck-section-heading {
    align-items: center;
    margin: 0;
    padding: 8px 14px;
    border-bottom: 1px solid rgba(138, 156, 172, 0.18);
  }
  .azuki-deck-section-heading h3 {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
  }
  .azuki-deck-section-heading h3 .zendo-icon,
  .azuki-deck-section-heading h3 .zendo-raster-icon {
    width: 19px;
    height: 19px;
    color: #c9944c;
  }
  .azuki-deck-section-heading p {
    display: none;
  }
  .azuki-deck-section-actions {
    display: flex;
    flex: 0 0 auto;
    align-items: center;
    gap: 8px;
  }
  .azuki-deck-management-button {
    border-color: rgba(202, 157, 87, 0.5);
    border-radius: 4px;
  }
  .azuki-deck-management-button.primary {
    min-width: 164px;
    min-height: 34px;
    color: #13202c;
    background: linear-gradient(#e0bd7b, #b8833e);
    border-color: #e0bd7b;
    font-family: var(--zendo-font-ui);
    font-size: 12px;
    letter-spacing: 0.05em;
    text-transform: uppercase;
  }
  .azuki-deck-management-button.primary::before {
    content: "+";
    margin-right: 7px;
  }
  .azuki-import-toggle {
    display: grid !important;
    width: 38px;
    height: 34px;
    min-height: 34px !important;
    padding: 0 !important;
    place-items: center;
    color: #f2e9d7;
    background: linear-gradient(180deg, #3d8192, #28586b) !important;
    border: 1px solid rgba(116, 200, 210, 0.56) !important;
    border-radius: 5px !important;
    clip-path: none !important;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.12), 0 3px 9px rgba(0,0,0,0.16);
    cursor: pointer;
    transform: none !important;
    transition: border-color 150ms ease, background 150ms ease, box-shadow 150ms ease;
  }
  .azuki-import-toggle::before,
  .azuki-import-toggle::after {
    content: none !important;
  }
  .azuki-import-toggle:hover,
  .azuki-import-toggle:focus-visible,
  .azuki-import-toggle.is-active {
    background: linear-gradient(180deg, #4b93a3, #31677a) !important;
    border-color: #8ed2d8 !important;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.16), 0 0 0 2px rgba(96, 185, 197, 0.12);
    outline: none;
  }
  .azuki-import-toggle .zendo-raster-icon {
    width: 22px;
    height: 22px;
  }
  .azuki-import-popover {
    position: absolute;
    top: 50px;
    right: 12px;
    z-index: 30;
    display: none;
    width: min(610px, calc(100% - 24px));
    padding: 13px;
    box-sizing: border-box;
    color: #e8e0d2;
    background: linear-gradient(145deg, rgba(15, 40, 59, 0.97), rgba(5, 20, 34, 0.98));
    border: 1px solid rgba(111, 191, 202, 0.55);
    border-radius: 9px;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.06), 0 18px 38px rgba(0,0,0,0.48);
    backdrop-filter: blur(13px) saturate(110%);
    -webkit-backdrop-filter: blur(13px) saturate(110%);
  }
  .azuki-import-popover.is-open {
    display: block;
  }
  .azuki-import-popover::before {
    content: "";
    position: absolute;
    top: -7px;
    right: 14px;
    width: 12px;
    height: 12px;
    background: #0d2639;
    border-top: 1px solid rgba(111, 191, 202, 0.55);
    border-left: 1px solid rgba(111, 191, 202, 0.55);
    transform: rotate(45deg);
  }
  .azuki-deck-import {
    margin: 0;
    padding: 0;
  }
  .azuki-library-tabs {
    display: flex;
    gap: 24px;
    padding: 0 18px;
    border-bottom: 1px solid rgba(138, 156, 172, 0.18);
  }
  .azuki-library-tabs > button {
    min-height: 32px;
    padding: 6px 4px;
    color: #8f9cab;
    background: transparent !important;
    border: 0 !important;
    border-bottom: 2px solid transparent !important;
    border-radius: 0 !important;
    clip-path: none !important;
    font-family: var(--zendo-font-ui);
    font-size: 12px;
    transform: none !important;
  }
  .azuki-library-tabs > button::before,
  .azuki-library-tabs > button::after {
    content: none !important;
  }
  .azuki-library-tabs > button.is-active {
    color: #dfb565;
    border-bottom-color: #d3a151 !important;
  }
  .azuki-library-panel {
    display: none;
  }
  .azuki-library-panel.is-active {
    display: block;
  }
  .azuki-import-popover-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 10px;
  }
  .azuki-import-popover-heading > label {
    display: flex;
    min-width: 0;
    align-items: center;
    gap: 10px;
    margin: 0;
    cursor: text;
    font-family: var(--zendo-font-ui);
    font-size: 13px;
  }
  .azuki-import-popover-close {
    display: grid !important;
    width: 28px;
    height: 28px;
    min-height: 28px !important;
    flex: 0 0 auto;
    padding: 0 !important;
    place-items: center;
    color: #aebbc5 !important;
    background: transparent !important;
    border: 1px solid transparent !important;
    border-radius: 50% !important;
    clip-path: none !important;
    font-family: var(--zendo-font-ui);
    font-size: 21px;
    font-weight: 400;
    line-height: 1;
    transform: none !important;
  }
  .azuki-import-popover-close::before,
  .azuki-import-popover-close::after {
    content: none !important;
  }
  .azuki-import-popover-close:hover,
  .azuki-import-popover-close:focus-visible {
    color: #f0d59f !important;
    background: rgba(255,255,255,0.06) !important;
    border-color: rgba(201, 166, 110, 0.32) !important;
    outline: none;
  }
  .azuki-import-copy {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: 4px;
  }
  .azuki-import-copy strong {
    color: #ece4d6;
    font-size: 13px;
    font-weight: 600;
  }
  .azuki-import-copy small {
    color: #8796a6;
    font-family: var(--zendo-font-ui);
    font-size: 10.5px;
    font-weight: 400;
    line-height: 1.25;
  }
  .azuki-import-icon {
    display: grid;
    width: 38px;
    height: 34px;
    flex: 0 0 auto;
    place-items: center;
    color: #e8e2d4;
    background: linear-gradient(#367284, #244a5e);
    border: 1px solid rgba(112, 196, 204, 0.35);
    border-radius: 5px;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.08);
  }
  .azuki-import-icon .zendo-icon,
  .azuki-import-icon .zendo-raster-icon {
    width: 22px;
    height: 22px;
  }
  .azuki-deck-import-row {
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .azuki-deck-import-row input {
    height: 38px;
    margin: 0 !important;
    border-color: rgba(116, 149, 172, 0.38);
    border-radius: 4px;
  }
  .azuki-deck-import-row .azuki-deck-management-button {
    min-width: 96px;
    min-height: 38px;
    height: 38px;
    padding: 7px 11px;
    color: #f0e8d9;
    background: linear-gradient(#376f7f, #24495d) !important;
    border-color: rgba(112, 196, 204, 0.5) !important;
    font-family: var(--zendo-font-ui);
    font-size: 11px;
    letter-spacing: 0.06em;
    text-transform: uppercase;
  }
  .azuki-field-help {
    grid-column: 1;
    margin: -7px 0 0 48px;
  }
  .azuki-import-status {
    margin: 7px 0 0;
    padding-left: 0;
  }
  .azuki-import-status:empty {
    display: none;
  }
  .azuki-selected-deck-label {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 12px 18px 8px;
    color: #c8a46a;
    font-family: var(--zendo-font-ui);
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.08em;
  }
  .azuki-selected-deck-label::before,
  .azuki-ready-label::before {
    content: "";
    width: 12px;
    height: 1px;
    flex: 0 0 auto;
    margin-right: 1px;
    background: linear-gradient(90deg, rgba(201, 164, 98, 0.15), #c9a462);
  }
  .azuki-selected-deck-label .zendo-icon,
  .azuki-selected-deck-label .zendo-raster-icon {
    width: 16px;
    height: 16px;
    stroke-width: 1.35;
  }
  #azuki-library-panel-decks > .azuki-builder-deck-grid {
    display: none;
  }
  .azuki-selected-deck-section {
    min-width: 0;
  }
  #azuki-source-starter:checked ~ .azuki-selected-deck-section,
  #azuki-source-link:checked ~ .azuki-selected-deck-section {
    display: none;
  }
  .azuki-selected-deck-heading {
    display: flex;
    min-height: 27px;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding-left: 10px;
    border-bottom: 1px solid rgba(138, 156, 172, 0.16);
  }
  .azuki-selected-deck-heading .azuki-selected-deck-label {
    margin: 0 0 7px;
  }
  .azuki-change-deck-button {
    min-height: 28px;
    margin-bottom: 6px;
    padding: 4px 9px;
    color: #c9a66e;
    background: transparent !important;
    border: 1px solid rgba(201, 166, 110, 0.38) !important;
    border-radius: 4px !important;
    clip-path: none !important;
    font-family: var(--zendo-font-ui);
    font-size: 10.5px;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    transform: none !important;
  }
  .azuki-change-deck-button::before,
  .azuki-change-deck-button::after {
    content: none !important;
  }
  .azuki-change-deck-button:hover,
  .azuki-change-deck-button:focus-visible {
    color: #f0cf92;
    background: rgba(201, 166, 110, 0.08) !important;
    border-color: #c9a66e !important;
  }
  .azuki-selected-deck-preview {
    min-height: 122px;
    margin-top: 7px;
  }
  .azuki-selected-deck-card {
    position: relative;
    min-width: 0;
    padding: 7px;
    background: linear-gradient(110deg, rgba(18, 45, 68, 0.96), rgba(6, 22, 38, 0.97));
    border: 1px solid #c9944c;
    border-radius: 9px;
    box-shadow: 0 0 0 2px rgba(201, 148, 76, 0.13), 0 10px 24px rgba(0,0,0,0.28);
  }
  .azuki-selected-deck-card > label {
    display: block;
  }
  .azuki-selected-deck-card .azuki-builder-deck-tile {
    min-height: 102px;
  }
  .azuki-selected-deck-card .azuki-builder-deck-art {
    width: 136px;
    min-width: 136px;
  }
  .azuki-selected-deck-card .azuki-builder-deck-art img {
    top: 4px;
    left: 32px;
    width: 67px;
    height: 95px;
  }
  .azuki-selected-deck-card .azuki-builder-deck-art.has-two img:first-child {
    left: 16px;
  }
  .azuki-selected-deck-card .azuki-builder-deck-art.has-two img:last-child {
    top: 7px;
    left: 53px;
  }
  .azuki-selected-deck-card .azuki-builder-deck-copy {
    padding: 11px 60px 39px 7px;
  }
  .azuki-selected-deck-card .azuki-builder-deck-copy strong {
    overflow: visible;
    font-size: 15px;
    line-height: 1.25;
    text-overflow: clip;
    white-space: normal;
  }
  .azuki-selected-deck-card .azuki-builder-deck-actions {
    position: absolute;
    left: 151px;
    bottom: 9px;
    z-index: 2;
    padding: 0;
  }
  .azuki-selected-deck-actions {
    position: absolute;
    left: 151px;
    bottom: 9px;
    z-index: 3;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .azuki-selected-deck-actions > a,
  .azuki-selected-deck-actions > button {
    display: grid;
    width: 34px;
    height: 31px;
    min-height: 31px;
    padding: 0 !important;
    place-items: center;
    color: #e5dccd !important;
    background: rgba(13, 31, 48, 0.94) !important;
    border: 1px solid rgba(130, 155, 174, 0.42) !important;
    border-radius: 4px !important;
    clip-path: none !important;
    text-decoration: none;
    transform: none !important;
  }
  .azuki-selected-deck-actions > a::before,
  .azuki-selected-deck-actions > a::after,
  .azuki-selected-deck-actions > button::before,
  .azuki-selected-deck-actions > button::after {
    content: none !important;
  }
  .azuki-selected-deck-actions > a:hover,
  .azuki-selected-deck-actions > button:hover,
  .azuki-selected-deck-actions > a:focus-visible,
  .azuki-selected-deck-actions > button:focus-visible {
    color: #f2cc83 !important;
    background: rgba(31, 57, 76, 0.98) !important;
    border-color: #c9944c !important;
  }
  .azuki-selected-deck-actions > .danger {
    color: #df7770 !important;
    border-color: rgba(187, 78, 70, 0.42) !important;
  }
  .azuki-selected-deck-actions .zendo-icon,
  .azuki-selected-deck-actions .zendo-raster-icon {
    width: 18px;
    height: 18px;
  }
  .azuki-selected-deck-actions > .danger .zendo-raster-icon {
    filter: sepia(1) saturate(3) hue-rotate(312deg) brightness(1.02);
  }
  .azuki-selected-deck-card .azuki-builder-deck-selected {
    top: 5px;
    right: 5px;
    width: 48px;
    height: 48px;
    padding: 0;
    background: url('/TCGEngine/Assets/Images/Zendo/zendo-selected-wax-seal.webp?v=1') center / contain no-repeat;
    border: 0;
    border-radius: 0;
    box-shadow: none;
    filter: drop-shadow(0 4px 5px rgba(0, 0, 0, 0.34));
    opacity: 1;
  }
  .azuki-selected-deck-card .azuki-builder-deck-selected .zendo-icon {
    display: none;
  }
  .azuki-selected-deck-stats {
    display: flex !important;
    align-items: center;
    gap: 16px;
    margin-top: 7px;
    color: #b7b1a7 !important;
  }
  .azuki-selected-deck-stats > span {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
  }
  .azuki-selected-deck-stats .zendo-icon,
  .azuki-selected-deck-stats .zendo-raster-icon {
    width: 15px;
    height: 15px;
    color: #c9a66e;
  }
  .azuki-builder-deck-grid {
    grid-template-columns: 1fr;
    max-height: 238px;
    padding: 0 12px 12px;
  }
  .azuki-builder-deck-option {
    padding: 7px;
    background: rgba(8, 25, 42, 0.66);
    border-color: rgba(121, 151, 171, 0.28);
    border-radius: 9px;
  }
  .azuki-builder-deck-option:has(> label > input:checked) {
    background: linear-gradient(110deg, rgba(18, 45, 68, 0.94), rgba(6, 22, 38, 0.95));
    border-color: #c9944c;
    box-shadow: 0 0 0 2px rgba(201, 148, 76, 0.13), 0 10px 24px rgba(0,0,0,0.28);
  }
  .azuki-builder-deck-tile {
    min-height: 98px;
  }
  .azuki-builder-deck-copy strong {
    font-family: var(--zendo-font-display);
    font-size: 17px;
  }
  .azuki-builder-deck-copy > span {
    color: #aeb8c2;
    font-size: 11px;
  }
  .azuki-builder-deck-selected {
    display: grid;
    width: 30px;
    height: 30px;
    padding: 0;
    place-items: center;
    color: #dfb565;
    border: 1px solid #c9944c;
    border-radius: 50%;
    background: rgba(201, 148, 76, 0.08);
  }
  .azuki-deck-edit-action,
  .azuki-deck-more summary {
    color: #e5dccd;
    background: rgba(13, 31, 48, 0.92);
    border-color: rgba(130, 155, 174, 0.42);
    border-radius: 4px;
    font-family: var(--zendo-font-ui);
    letter-spacing: 0.04em;
    text-transform: uppercase;
  }
  .azuki-deck-picker-modal {
    position: fixed;
    inset: 0;
    z-index: 6100;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 24px;
    box-sizing: border-box;
  }
  .azuki-deck-picker-modal.is-open {
    display: flex;
  }
  body.azuki-modal-open {
    overflow: hidden !important;
  }
  .azuki-deck-picker-backdrop {
    position: absolute !important;
    inset: 0;
    width: 100%;
    height: 100%;
    padding: 0 !important;
    background: rgba(1, 8, 16, 0.84) !important;
    border: 0 !important;
    border-radius: 0 !important;
    clip-path: none !important;
    backdrop-filter: blur(6px);
    transform: none !important;
  }
  .azuki-deck-picker-backdrop::before,
  .azuki-deck-picker-backdrop::after {
    content: none !important;
  }
  .azuki-deck-picker-dialog {
    position: relative;
    z-index: 1;
    width: min(880px, 100%);
    max-height: min(760px, 88vh);
    padding: 24px;
    overflow: hidden;
    color: #e7dfcf;
    background:
      radial-gradient(circle at 92% -12%, rgba(201, 148, 76, 0.13), transparent 32%),
      linear-gradient(155deg, #0c2236, #061626);
    border: 1px solid rgba(201, 148, 76, 0.58);
    border-radius: 12px;
    box-shadow: 0 28px 80px rgba(0,0,0,0.72);
  }
  .azuki-deck-picker-kicker {
    color: #c9a66e;
    font-family: var(--zendo-font-ui);
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
  }
  .azuki-deck-picker-dialog h2 {
    margin: 4px 36px 5px 0;
    color: #f0e5d1;
    font-family: var(--zendo-font-display);
    font-size: 24px;
  }
  .azuki-deck-picker-dialog > p {
    margin: 0 0 18px;
    color: #9daab7;
    font-size: 12px;
  }
  .azuki-deck-picker-close {
    position: absolute !important;
    top: 12px;
    right: 14px;
    z-index: 2;
    min-width: 34px;
    min-height: 34px;
    padding: 3px 8px;
    color: #c9b899;
    background: transparent !important;
    border: 0 !important;
    border-radius: 50% !important;
    clip-path: none !important;
    font-size: 27px;
    transform: none !important;
  }
  .azuki-deck-picker-close::before,
  .azuki-deck-picker-close::after {
    content: none !important;
  }
  #azuki-deck-picker-content .azuki-builder-deck-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    max-height: min(560px, 66vh);
    padding: 2px 8px 2px 2px;
    overflow-y: auto;
  }
  #azuki-deck-picker-content .azuki-builder-deck-option {
    cursor: pointer;
  }
  #azuki-deck-picker-content .azuki-builder-deck-actions {
    display: none;
  }
  .azuki-starter-source {
    display: grid;
    margin: 16px;
    min-height: 106px;
  }
  .azuki-link-source {
    margin: 14px 16px 16px;
    border-top: 0;
  }
  .azuki-link-source-body {
    padding-top: 0;
  }
  .azuki-link-source > summary {
    color: #7e91a3;
  }
  .azuki-game-setup {
    gap: 10px;
  }
  .azuki-ready-label {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 1px;
    padding: 0 18px 7px;
    color: #c8a46a;
    border-bottom: 1px solid rgba(138, 156, 172, 0.18);
    font-family: var(--zendo-font-ui);
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
  }
  .azuki-ready-label .zendo-icon,
  .azuki-ready-label .zendo-raster-icon {
    width: 18px;
    height: 18px;
    stroke-width: 1.4;
  }
  .azuki-game-actions {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 7px;
    padding-top: 0;
    border-top: 0;
  }
  .azuki-game-actions > button {
    display: grid !important;
    grid-template-columns: 24px minmax(0, 80px);
    width: 100%;
    height: 54px;
    min-height: 54px;
    box-sizing: border-box;
    align-items: center;
    justify-content: center;
    column-gap: 8px;
    padding: 7px 9px;
    color: #e9e4da;
    background: linear-gradient(#172d40, #102334);
    border: 1px solid rgba(126, 150, 168, 0.38);
    border-radius: 5px;
    clip-path: none !important;
    text-align: left;
    transform: none !important;
  }
  .azuki-game-actions > button > .zendo-icon,
  .azuki-game-actions > button > .zendo-raster-icon {
    width: 24px;
    height: 24px;
    stroke-width: 1.5;
  }
  .azuki-game-actions > button::before,
  .azuki-game-actions > button::after {
    content: none !important;
  }
  .azuki-game-actions > button span {
    display: flex;
    width: 100% !important;
    min-width: 0;
    flex-direction: column;
    align-items: flex-start;
    gap: 2px;
    text-align: left;
  }
  .azuki-game-actions > button strong {
    font-family: var(--zendo-font-ui);
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.015em;
    line-height: 1.1;
    text-transform: uppercase;
    white-space: nowrap;
  }
  .azuki-game-actions > button small {
    color: #9ca8b3;
    font-family: var(--zendo-font-ui);
    font-size: 9.5px;
    line-height: 1.15;
    white-space: nowrap;
  }
  .azuki-game-actions > .azuki-game-action-primary {
    min-width: 0;
    background: linear-gradient(#2f6d79, #1d4e5b) !important;
    border-color: rgba(105, 181, 190, 0.48) !important;
  }
  .azuki-game-actions > .azuki-game-action-learn {
    margin: 0;
    color: #e9e4da;
    background: linear-gradient(#172d40, #102334) !important;
    border-color: rgba(126, 150, 168, 0.38) !important;
  }
  .azuki-game-actions > .azuki-game-action-bot {
    color: #e9e4da;
    background: linear-gradient(#172d40, #102334) !important;
    border-color: rgba(126, 150, 168, 0.38) !important;
  }
  .azuki-game-actions > .azuki-game-action-private {
    background: linear-gradient(#a77d43, #74532c) !important;
    border-color: #b98d50 !important;
  }
  @media (min-width: 1181px) {
    .azuki-menu-grid {
      height: clamp(530px, calc(100vh - 168px), 690px);
      align-items: stretch;
    }
    .azuki-active-card,
    .azuki-queue-card,
    .azuki-info-card {
      height: 100%;
      max-height: 100%;
    }
    .azuki-queue-card {
      padding: 16px !important;
    }
    .azuki-info-card {
      overflow-y: auto;
    }
  }
  .azuki-info-card {
    color: #162435;
    background:
      linear-gradient(90deg, rgba(245, 226, 188, 0.15), rgba(229, 200, 146, 0.08)),
      url('/TCGEngine/Assets/Images/Zendo/zendo-parchment-panel.webp') center / cover no-repeat !important;
    border-color: #b88a4b !important;
    box-shadow: inset 0 0 50px rgba(89, 57, 25, 0.16), 0 18px 44px rgba(0,0,0,0.42);
  }
  .azuki-info-tabs {
    padding: 2px 0 0;
    background: linear-gradient(90deg, rgba(255, 247, 226, 0.62), rgba(244, 222, 179, 0.46));
    border: 1px solid rgba(103, 76, 44, 0.2);
    border-bottom-color: rgba(78, 57, 33, 0.3);
    border-radius: 9px 9px 0 0;
    box-shadow:
      inset 0 1px 0 rgba(255, 253, 243, 0.62),
      0 2px 8px rgba(76, 48, 21, 0.05);
    backdrop-filter: blur(7px) saturate(90%);
    -webkit-backdrop-filter: blur(7px) saturate(90%);
  }
  .azuki-info-tabs > button.azuki-info-tab {
    color: #4b3925;
    font-family: var(--zendo-font-ui);
    font-weight: 600;
    text-shadow:
      0 1px 0 rgba(255, 248, 227, 0.78),
      0 0 10px rgba(255, 239, 199, 0.48);
  }
  .azuki-info-tabs > button.azuki-info-tab.is-active {
    color: #122438;
    background: transparent !important;
    border-color: transparent transparent #795326 !important;
    box-shadow: none !important;
    font-weight: 600;
  }
  .azuki-info-card .azuki-info-panel h2 {
    color: #13263b;
    font-family: var(--zendo-font-display);
    font-size: 31px;
    line-height: 1.1;
  }
  .azuki-info-card .login-message,
  .azuki-info-card #did-you-know-text,
  .azuki-info-card .hotkey-row {
    color: #392f25 !important;
  }
  .azuki-info-card #azuki-info-panel-replays > p {
    color: #4a3d30 !important;
  }
  .azuki-info-card #azuki-info-panel-replays {
    gap: 12px;
  }
  .azuki-info-card #match-replay-menu-list {
    gap: 9px;
    margin-top: 2px;
    padding-right: 2px;
  }
  .azuki-info-card #match-replay-menu-list .match-replay-row {
    grid-template-columns: minmax(0, 1fr) auto auto;
    gap: 7px;
    min-height: 58px;
    padding: 10px 11px;
    box-sizing: border-box;
    background: linear-gradient(90deg, rgba(255, 249, 231, 0.74), rgba(247, 226, 184, 0.5));
    border: 1px solid rgba(98, 70, 38, 0.27);
    border-radius: 8px;
    box-shadow:
      inset 0 1px 0 rgba(255, 254, 246, 0.72),
      0 3px 10px rgba(78, 49, 22, 0.08);
    backdrop-filter: blur(7px) saturate(88%);
    -webkit-backdrop-filter: blur(7px) saturate(88%);
  }
  .azuki-info-card #match-replay-menu-list .match-replay-meta {
    color: #574632 !important;
    font-size: 12px;
    line-height: 1.45;
  }
  .azuki-info-card #match-replay-menu-list .match-replay-meta > span {
    color: #54422f !important;
  }
  .azuki-info-card #match-replay-menu-list .match-replay-meta strong {
    color: #182b3e !important;
    font-family: var(--zendo-font-display);
    font-size: 14px;
    font-weight: 700;
  }
  .azuki-info-card #match-replay-menu-list .match-replay-button {
    min-width: 56px;
    min-height: 32px;
    padding: 6px 10px !important;
    color: #1d2d3b !important;
    background: linear-gradient(180deg, rgba(239, 208, 145, 0.82), rgba(205, 157, 82, 0.72)) !important;
    border: 1px solid rgba(113, 76, 32, 0.55) !important;
    border-radius: 6px !important;
    box-shadow:
      inset 0 1px 0 rgba(255, 245, 216, 0.72),
      0 2px 5px rgba(76, 48, 19, 0.12);
    font-family: var(--zendo-font-ui);
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.035em;
    text-transform: uppercase;
    transition: background 150ms ease, border-color 150ms ease, transform 150ms ease;
  }
  .azuki-info-card #match-replay-menu-list .match-replay-button:hover,
  .azuki-info-card #match-replay-menu-list .match-replay-button:focus-visible {
    background: linear-gradient(180deg, #f2d99f, #d4a158) !important;
    border-color: #765026 !important;
    outline: none;
    transform: translateY(-1px);
  }
  .azuki-info-card #match-replay-menu-list .match-replay-button:last-child {
    color: #7b302a !important;
    background: rgba(255, 247, 225, 0.52) !important;
    border-color: rgba(126, 55, 45, 0.5) !important;
    box-shadow: inset 0 1px 0 rgba(255, 252, 240, 0.55);
  }
  .azuki-info-card #match-replay-menu-list .match-replay-button:last-child:hover,
  .azuki-info-card #match-replay-menu-list .match-replay-button:last-child:focus-visible {
    color: #68231f !important;
    background: rgba(213, 135, 116, 0.2) !important;
    border-color: rgba(112, 39, 32, 0.7) !important;
  }
  .azuki-info-card #match-replay-menu-list .match-replay-muted {
    color: #514331 !important;
  }
  .azuki-info-card hr {
    border-color: rgba(78, 57, 33, 0.38) !important;
  }
  .azuki-info-card #did-you-know-box {
    min-height: 112px;
    background: rgba(255, 247, 227, 0.18);
    border-color: rgba(93, 66, 34, 0.28);
  }
  .azuki-info-card .azuki-inline-section-title {
    color: #67533a;
  }
  .azuki-info-card .hotkey-badge {
    color: #eee2ca;
    background: #2b2b28;
    border-color: #1d1d1a;
  }
  .azuki-quick-reference-title {
    margin-bottom: 8px;
    color: #58462f;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    text-shadow: 0 1px 0 rgba(255, 246, 220, 0.65);
  }
  .azuki-info-card #hotkey-list .hotkey-row > span:last-child {
    color: #30271f;
    font-weight: 500;
    text-shadow: 0 1px 0 rgba(255, 247, 225, 0.5);
  }
  .azuki-info-card #did-you-know-box button {
    color: #6d5433 !important;
    background: transparent !important;
  }
  .disclaimer {
    position: relative !important;
    inset: auto !important;
    width: fit-content !important;
    max-width: calc(100% - 40px);
    min-height: 32px;
    margin: 0 auto 4px !important;
    padding: 3px 0 3px 38px;
    box-sizing: border-box;
    display: flex;
    align-items: center;
  }
  .disclaimer::before {
    content: "";
    position: absolute;
    left: 0;
    top: 50%;
    width: 30px;
    height: 27px;
    background: url('/TCGEngine/Assets/Images/Zendo/zendo-footer-lotus.svg') center / contain no-repeat;
    filter: drop-shadow(0 1px 4px rgba(0, 0, 0, 0.25));
    transform: translateY(-50%);
  }
  .disclaimer p {
    margin: 0 !important;
    color: #8d98a4 !important;
    font-family: var(--zendo-font-ui);
    font-size: 10.5px !important;
    font-weight: 400;
    line-height: 1.45;
    text-align: left !important;
  }
  .disclaimer a {
    margin-left: 18px;
    color: #c69a50 !important;
    font-size: inherit;
    font-weight: 500;
    text-decoration-color: rgba(198, 154, 80, 0.55);
    text-underline-offset: 2px;
  }
  .disclaimer a + a {
    position: relative;
    margin-left: 24px;
  }
  .disclaimer a + a::before {
    content: "";
    position: absolute;
    left: -13px;
    top: 50%;
    width: 1px;
    height: 11px;
    background: rgba(198, 154, 80, 0.35);
    transform: translateY(-50%);
  }
  .disclaimer a:hover,
  .disclaimer a:focus-visible {
    color: #e0b86d !important;
    text-decoration-color: currentColor;
  }
  @media (max-width: 1180px) {
    .azuki-rejoin-banner {
      position: relative;
      top: auto;
      left: auto;
      width: auto;
      min-width: 0;
      margin: 0 10px 12px;
      transform: none;
    }
    .azuki-menu-grid {
      display: flex !important;
      flex-direction: column !important;
    }
  }
  @media (max-width: 768px) {
    .azuki-rejoin-banner {
      margin-top: 8px;
    }
    .azuki-rejoin-banner-action {
      display: none;
    }
    .azuki-menu-grid {
      width: auto;
      gap: 12px;
      margin: 12px 10px 0;
    }
    .azuki-active-card,
    .azuki-queue-card,
    .azuki-info-card {
      width: 100%;
      padding: 16px !important;
      box-sizing: border-box;
    }
    .azuki-queue-card h2,
    .azuki-active-card h2,
    .azuki-info-card h2 {
      font-size: 22px;
      line-height: 1.15;
      margin-bottom: 16px;
    }
    #starter-deck-select {
      min-width: 0 !important;
      width: 100%;
    }
    .azuki-deck-import-row,
    .azuki-starter-source {
      grid-template-columns: 1fr;
    }
    .azuki-deck-section-heading {
      gap: 10px;
      padding-inline: 10px;
    }
    .azuki-deck-section-actions {
      gap: 6px;
    }
    .azuki-deck-management-button.primary {
      min-width: 126px;
      padding-inline: 10px;
    }
    .azuki-import-popover {
      right: 8px;
      left: 8px;
      width: auto;
    }
    .azuki-deck-import-row {
      flex-direction: column;
    }
    .azuki-deck-import-row .azuki-deck-management-button {
      width: 100%;
    }
    .azuki-game-actions {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 8px;
    }
    .azuki-game-actions > button {
      width: 100%;
      min-height: 42px;
      padding: 8px 6px;
      white-space: normal;
      line-height: 1.15;
    }
    .azuki-game-actions > .azuki-game-action-learn {
      margin-left: 0;
    }
    .azuki-selected-deck-card .azuki-builder-deck-art {
      width: 104px;
      min-width: 104px;
    }
    .azuki-selected-deck-card .azuki-builder-deck-copy {
      padding-left: 4px;
    }
    .azuki-selected-deck-card .azuki-builder-deck-actions {
      position: static;
      padding: 6px 3px 1px;
    }
    .azuki-selected-deck-card .azuki-builder-deck-copy {
      padding-bottom: 8px;
    }
    .azuki-selected-deck-actions {
      position: static;
      justify-content: flex-end;
      flex-wrap: wrap;
      margin: 6px 3px 1px;
    }
    .azuki-deck-picker-modal {
      padding: 12px;
    }
    .azuki-deck-picker-dialog {
      padding: 18px;
    }
    #azuki-deck-picker-content .azuki-builder-deck-grid {
      grid-template-columns: 1fr;
    }
    .saved-decks-panel .dl-dropdown-actions {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
    }
    .saved-decks-panel .dl-act {
      width: 100%;
      min-width: 0;
    }
    #did-you-know-box {
      min-height: 120px;
      padding: 14px !important;
    }
    #did-you-know-text {
      min-height: 58px;
      max-height: none;
    }
    .hotkey-row {
      align-items: flex-start;
      line-height: 1.35;
    }
    .rl-bot-opponent-grid {
      grid-template-columns: 1fr;
    }
    .rl-bot-opponent-modal__dialog {
      padding: 18px;
    }
  }
  @media (max-width: 370px) {
    .azuki-game-actions {
      grid-template-columns: 1fr;
    }
  }
</style>

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
    var isReplays = tab === 'replays';
    var welcomeTab = document.getElementById('azuki-info-tab-welcome');
    var replaysTab = document.getElementById('azuki-info-tab-replays');
    var welcomePanel = document.getElementById('azuki-info-panel-welcome');
    var replaysPanel = document.getElementById('azuki-info-panel-replays');
    if (!welcomeTab || !replaysTab || !welcomePanel || !replaysPanel) return;
    welcomeTab.classList.toggle('is-active', !isReplays);
    replaysTab.classList.toggle('is-active', isReplays);
    welcomeTab.setAttribute('aria-selected', isReplays ? 'false' : 'true');
    replaysTab.setAttribute('aria-selected', isReplays ? 'true' : 'false');
    welcomePanel.classList.toggle('is-active', !isReplays);
    replaysPanel.classList.toggle('is-active', isReplays);
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
  var _waitingEscHandler = null;
  var _lastSimGameStorageKey = 'tcgengine:lastSimGame:' + rootName;
  var _rejoinFreshnessMs = 5 * 60 * 1000;
  var _rejoinExpiryTimer = null;

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
          return;
        }
        banner.style.display = '';
        note.textContent = 'Game ' + record.gameName + ' · Player ' + record.playerID + ' · Active within 5 minutes';
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

      function initializePrivateInviteFromUrl() {
        try {
          var params = new URLSearchParams(window.location.search || '');
          var joinBtn = document.getElementById('join-private-invite-btn');
          var notice = document.getElementById('private-invite-notice');

          _privateInviteCode = '';
          if (joinBtn) joinBtn.classList.remove('is-visible');
          if (notice) {
            notice.style.display = 'none';
            notice.textContent = '';
          }

          if (!params.has('privateInvite')) return;
          _privateInviteCode = (params.get('privateInvite') || '').trim();
          if (!_privateInviteCode) return;

          if (joinBtn) joinBtn.classList.add('is-visible');
          if (notice) {
            notice.style.display = '';
            notice.textContent = 'Private invite detected. Choose your deck, then click Join Private Invite.';
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
        opponent = opponent === 'zero' ? 'zero' : 'raizan';
        closeRlBotOpponentModal();
        submitQueueJoin({
          createRlBot: true,
          rlBotOpponent: opponent,
          waitingMessage: 'Starting RL bot game...'
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

        var deckLink = submission.deckLink;
        var preconstructedDeck = submission.preconstructedDeck;
        var params = 'deckLink=' + encodeURIComponent(deckLink) + '&game_type=' + encodeURIComponent(submission.gameType);
        params += '&preconstructedDeck=' + encodeURIComponent(preconstructedDeck);
        params += "&rootName=" + encodeURIComponent(rootName);
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
        var gameCountElement = document.getElementById('active-game-count');
        var gameListElement = document.getElementById('active-games-list');
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
            gameCountElement.textContent = totalCount;
            renderActiveGames(data.data);
          } else {
            gameCountElement.textContent = '0';
            renderActiveGames([]);
          }
          finishRefreshFeedback(true);
          } else {
          console.error('Error fetching open games:', xhr.statusText);
          gameCountElement.textContent = '0';
          renderActiveGames([]);
          finishRefreshFeedback(false);
          }
        };

        xhr.onerror = function() {
          console.error('Error fetching open games:', xhr.statusText);
          gameCountElement.textContent = '0';
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
          if (activeCard) activeCard.classList.add('is-empty');
          gameListElement.innerHTML =
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
          var visibilityLabel = game.isPrivate ? 'Private' : 'Public';
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
