<?php
require_once __DIR__ . '/../../Render/AssetVersion.php';   // _VersionAsset() — ?v=<filemtime> cache busting
// Use __DIR__-relative includes (matching the SWUSim/SWUDeck pilot): this page is reached via the
// SharedUI/MainMenu.php pointer (which include()s it), so the cwd is SharedUI/, not this dir.
// Bare './'/'../../../' paths resolved against the wrong cwd → missing-file warnings AND silently
// pulled the ROOT SharedUI/MenuBar.php + Header.php (wrong chrome) instead of the GrandArchiveSim ones.
include_once __DIR__ . '/MenuBar.php';
include_once __DIR__ . '/../../../AccountFiles/AccountSessionAPI.php';
include_once __DIR__ . '/../../../Database/ConnectionManager.php';
include_once __DIR__ . '/../../../GrandArchiveSim/GeneratedCode/GeneratedCardDictionaries.php';
include_once __DIR__ . '/../../../GrandArchiveSim/Formats.php';
require_once __DIR__ . '/../../Render/DeckLibrary.php';

include_once __DIR__ . '/Header.php';

$gaSiteDef = require __DIR__ . '/SiteDef.php';
$gaDeckLibraryConfig = DeckLibraryConfigFromSiteDef($gaSiteDef, ['actionButtons' => true]);

?>
<main class="row-wrapper ga-menu-grid" aria-label="Clarent game lobby">
  <!-- Active Games Section -->
  <div class="card ga-glass-card ga-active-card">
    <button type="button" aria-label="Refresh active games" title="Refresh active games" style="position: absolute; top: 10px; right: 10px; background: none; border: none; cursor: pointer;" onclick="refreshOpenGames()">
      <img src='/TCGEngine/Assets/Icons/refresh.svg' width='16' height='16' alt='Refresh' style='filter: invert(100%);' />
    </button>
    <h2>Active Games (<span id="active-game-count">0</span>)</h2>
    <div id="active-games-list" class="active-games-list"></div>
  </div>

  <!-- Deck & Queue Section -->
  <div class="card ga-glass-card ga-queue-card">
    <h2>Create a New Game</h2>
    <div>
      <div style="margin-bottom: 10px;">
        <label for="ga-format-select" style="display:block; margin-bottom:6px; font-weight:500; font-size:13px;">Format:</label>
        <select id="ga-format-select" class="ga-queue-select">
          <?php foreach (GAListFormats() as $fid => $fname): ?>
          <option value="<?php echo htmlspecialchars($fid, ENT_QUOTES); ?>"<?php echo $fid === 'standard' ? ' selected' : ''; ?>><?php echo htmlspecialchars($fname, ENT_QUOTES); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
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
      <div role="tablist" aria-label="Player 1 deck source" style="display: flex; flex-wrap: wrap; gap: 0; margin-bottom: 10px; border-bottom: 2px solid rgba(100,100,100,0.4);">
        <button type="button" id="tab-link" role="tab" aria-selected="true" onclick="switchDeckTab('link')" style="flex: 1; min-width: 120px; padding: 8px; background: rgba(52,152,219,0.25); color: white; border: none; border-bottom: 2px solid #3498db; cursor: pointer; font-size: 13px; font-weight: 600;">Deck Link</button>
        <button type="button" id="tab-text" role="tab" aria-selected="false" onclick="switchDeckTab('text')" style="flex: 1; min-width: 120px; padding: 8px; background: rgba(40,40,40,0.7); color: #aaa; border: none; border-bottom: 2px solid transparent; cursor: pointer; font-size: 13px;">Free Text</button>
        <button type="button" id="tab-archetype" role="tab" aria-selected="false" onclick="switchDeckTab('archetype')" style="display: none; flex: 1; min-width: 120px; padding: 8px; background: rgba(40,40,40,0.7); color: #aaa; border: none; border-bottom: 2px solid transparent; cursor: pointer; font-size: 13px;">Archetype Prefill</button>
        <button type="button" id="tab-official" role="tab" aria-selected="false" onclick="switchDeckTab('official')" style="display: none; flex: 1; min-width: 120px; padding: 8px; background: rgba(40,40,40,0.7); color: #aaa; border: none; border-bottom: 2px solid transparent; cursor: pointer; font-size: 13px;">Official Deck</button>
      </div>
      <div id="deck-input-link">
        <label for="deck-link" style="display: block; margin-bottom: 8px; font-weight: 500;">Paste a deck link:</label>
        <input type="text" id="deck-link" name="deck_link" placeholder="https://shoutatyourdecks.com/decks/..." style="width: 100%; padding: 10px 15px; background-color: rgba(40, 40, 40, 0.95); color: white; border: 2px solid rgba(100, 100, 100, 0.5); border-radius: 8px; font-size: 14px; outline: none; box-sizing: border-box;">
        <div style="margin-top: 8px; color: #b9b9b9; font-size: 12px; line-height: 1.35;">
          Supported deck links: DungeonGUI, Shout At Your Decks, sleeved.gg, TCGArchitect
        </div>
        <div class="saved-decks-panel">
          <div class="ga-inline-section-title">Saved Decks</div>
          <?php echo RenderDeckLibrary(0, $gaDeckLibraryConfig); ?>
        </div>
      </div>
      <div id="deck-input-text" style="display: none;">
        <label for="deck-text" style="display: block; margin-bottom: 8px; font-weight: 500;">Paste deck list (e.g. from fractalofin.site):</label>
        <textarea id="deck-text" name="deck_text" rows="12" placeholder="# Material Deck&#10;1 Lorraine, Wandering Warrior&#10;&#10;# Main Deck&#10;4 Fireball&#10;..." style="width: 100%; padding: 10px 15px; background-color: rgba(40, 40, 40, 0.95); color: white; border: 2px solid rgba(100, 100, 100, 0.5); border-radius: 8px; font-size: 13px; font-family: monospace; outline: none; box-sizing: border-box; resize: vertical;"></textarea>
      </div>
      <!-- Bot format only: one-click sample decks (current meta archetypes, sourced from Fan of
           Insight) so a player can start a practice game without hunting down a decklist first. -->
      <div id="ga-bot-sample-decks-group" role="tabpanel" aria-labelledby="tab-archetype" style="display: none; margin-top: 10px;">
        <div class="ga-inline-section-title" style="margin: 0 0 8px;">Choose an archetype to prefill Player 1’s deck</div>
        <div id="ga-bot-sample-decks-list" style="display: flex; flex-wrap: wrap; gap: 8px;"></div>
        <div id="ga-deck1-selection-summary" aria-live="polite" style="display: none; margin-top: 8px; color: #9ed9b4; font-size: 12px;"></div>
      </div>
      <!-- Bot format only: every official preconstructed/starter product decklist, also sourced
           from Fan of Insight, as printed (no tuning) — grouped by product release. -->
      <div id="ga-official-decks-group" role="tabpanel" aria-labelledby="tab-official" style="display: none; margin-top: 10px;">
        <label for="ga-official-deck-select" style="display: block; margin-bottom: 8px; font-weight: 500;">Choose an official product deck for Player 1:</label>
        <select id="ga-official-deck-select" class="ga-queue-select">
          <option value="" selected>-- Select an official deck --</option>
        </select>
      </div>
      <!-- Hotseat: a second deck link for Player 2. Bot: an optional deck for the bot to pilot
           (defaults to a copy of your own deck if left blank). Revealed only for those two formats. -->
      <div id="ga-deck2-group" style="display: none; margin-top: 10px;">
        <div role="tablist" aria-label="Player 2 deck source" style="display: flex; flex-wrap: wrap; gap: 0; margin-bottom: 10px; border-bottom: 2px solid rgba(100,100,100,0.4);">
          <button type="button" id="ga-deck2-tab-link" role="tab" aria-selected="true" onclick="switchDeck2Tab('link')" style="flex: 1; min-width: 120px; padding: 8px; background: rgba(52,152,219,0.25); color: white; border: none; border-bottom: 2px solid #3498db; cursor: pointer; font-size: 13px; font-weight: 600;">Deck Link</button>
          <button type="button" id="ga-deck2-tab-text" role="tab" aria-selected="false" onclick="switchDeck2Tab('text')" style="flex: 1; min-width: 120px; padding: 8px; background: rgba(40,40,40,0.7); color: #aaa; border: none; border-bottom: 2px solid transparent; cursor: pointer; font-size: 13px;">Free Text</button>
          <button type="button" id="ga-deck2-tab-archetype" role="tab" aria-selected="false" onclick="switchDeck2Tab('archetype')" style="flex: 1; min-width: 120px; padding: 8px; background: rgba(40,40,40,0.7); color: #aaa; border: none; border-bottom: 2px solid transparent; cursor: pointer; font-size: 13px;">Archetype Prefill</button>
          <button type="button" id="ga-deck2-tab-official" role="tab" aria-selected="false" onclick="switchDeck2Tab('official')" style="flex: 1; min-width: 120px; padding: 8px; background: rgba(40,40,40,0.7); color: #aaa; border: none; border-bottom: 2px solid transparent; cursor: pointer; font-size: 13px;">Official Deck</button>
        </div>
        <div id="ga-deck2-input-panel" role="tabpanel" aria-labelledby="ga-deck2-tab-link">
          <label for="ga-deck2-input" id="ga-deck2-label" style="display: block; margin-bottom: 8px; font-weight: 500;">Player 2 deck link (Hotseat):</label>
          <input type="text" id="ga-deck2-input" placeholder="Second deck link" style="width: 100%; padding: 10px 15px; background-color: rgba(40, 40, 40, 0.95); color: white; border: 2px solid rgba(100, 100, 100, 0.5); border-radius: 8px; font-size: 14px; outline: none; box-sizing: border-box;">
        </div>
        <div id="ga-deck2-text-panel" role="tabpanel" aria-labelledby="ga-deck2-tab-text" style="display: none;">
          <label for="ga-deck2-text" id="ga-deck2-text-label" style="display: block; margin-bottom: 8px; font-weight: 500;">Paste Player 2’s deck list:</label>
          <textarea id="ga-deck2-text" rows="12" placeholder="# Material&#10;1 Lorraine, Wandering Warrior&#10;&#10;# Main&#10;4 Fireball" style="width: 100%; padding: 10px 15px; background-color: rgba(40, 40, 40, 0.95); color: white; border: 2px solid rgba(100, 100, 100, 0.5); border-radius: 8px; font-size: 13px; font-family: monospace; outline: none; box-sizing: border-box; resize: vertical;"></textarea>
        </div>
        <div id="ga-deck2-archetype-panel" role="tabpanel" aria-labelledby="ga-deck2-tab-archetype" style="display: none;">
          <div class="ga-inline-section-title" id="ga-deck2-picker-title" style="margin: 0 0 8px;">Choose an archetype to prefill Player 2’s deck</div>
          <div id="ga-bot-sample-decks-list-2" style="display: flex; flex-wrap: wrap; gap: 8px;"></div>
          <div id="ga-deck2-selection-summary" aria-live="polite" style="display: none; margin-top: 8px; color: #9ed9b4; font-size: 12px;"></div>
        </div>
        <div id="ga-deck2-official-panel" role="tabpanel" aria-labelledby="ga-deck2-tab-official" style="display: none;">
          <label for="ga-official-deck-select-2" id="ga-official-deck2-label" style="display: block; margin: 10px 0 8px; font-weight: 500;">Or load an official product deck for Player 2:</label>
          <select id="ga-official-deck-select-2" class="ga-queue-select">
            <option value="" selected>-- Select an official deck --</option>
          </select>
        </div>
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
      <div style="margin-bottom: 10px;">
        <label for="ga-queuetype-select" style="display:block; margin-bottom:6px; font-weight:500; font-size:13px;">Match Type:</label>
        <select id="ga-queuetype-select" class="ga-queue-select">
          <?php foreach (GAQueueTypeDefinitions() as $qid => $qdef): if (empty($qdef['enabled'])) continue; ?>
          <option value="<?php echo htmlspecialchars($qid, ENT_QUOTES); ?>"<?php echo $qid === 'bo1' ? ' selected' : ''; ?>><?php echo htmlspecialchars($qdef['displayName'] ?? $qid, ENT_QUOTES); ?></option>
          <?php endforeach; ?>
        </select>
        <div id="ga-bo1-note" style="display: none; margin-top: 5px; color: #aab8c8; font-size: 12px;">This game mode currently uses Best of 1.</div>
      </div>
      <label for="ga-share-anonymized-gameplay" style="display:flex; align-items:flex-start; gap:8px; margin:0 0 12px; color:#ddd; font-size:13px; line-height:1.35; cursor:pointer;">
        <input type="checkbox" id="ga-share-anonymized-gameplay" checked style="margin-top:2px;">
        <span>
          Share anonymized gameplay data
          <span style="display:block; color:#999; font-size:12px;">Helps improve aggregate simulator statistics. Deck, card, turn, combat, and match data may be shared; account and contact details are not included.</span>
        </span>
      </label>
      <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <button id="ga-join-queue-btn" class="ga-primary-action" onclick="joinQueue()">Join Queue</button>
        <button id="ga-create-private-btn" class="ga-secondary-action" onclick="createPrivateGame()">Create Private Game</button>
        <button id="rejoin-last-game-btn" onclick="rejoinLastGame()" style="display: none; background-color: #5b4aa3;">Rejoin Last Game</button>
        <button id="join-private-invite-btn" onclick="joinPrivateInvite()" style="display: none; background-color: #2d8a57;">Join Private Invite</button>
      </div>
      <div id="queue-inline-error" style="display: none; margin-top: 10px; color: #ff6b6b; font-size: 13px; line-height: 1.35;"></div>
      <div id="private-invite-notice" style="display: none; margin-top: 10px; color: #9ed9b4; font-size: 13px;"></div>
      <div id="rejoin-last-game-note" style="display: none; margin-top: 10px; color: #b9b9b9; font-size: 13px;"></div>
    </div>
  </div>
  <!-- Tips & Info Section -->
  <div class="card ga-glass-card ga-info-card" style="flex-grow: 1; margin: 10px; padding: 20px; color: white; border-radius: 12px; display: flex; flex-direction: column; gap: 16px;">
    <div class="ga-info-tabs" role="tablist" aria-label="Clarent information">
      <button type="button" id="ga-info-tab-welcome" class="ga-info-tab is-active" onclick="switchInfoTab('welcome')" role="tab" aria-selected="true" aria-controls="ga-info-panel-welcome">Welcome</button>
      <button type="button" id="ga-info-tab-replays" class="ga-info-tab" onclick="switchInfoTab('replays')" role="tab" aria-selected="false" aria-controls="ga-info-panel-replays">Replays</button>
    </div>
    <div id="ga-info-panel-welcome" class="ga-info-panel is-active" role="tabpanel" aria-labelledby="ga-info-tab-welcome">
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
    <div id="ga-info-panel-replays" class="ga-info-panel" role="tabpanel" aria-labelledby="ga-info-tab-replays">
      <h2 style="margin: 0;">Your Replays</h2>
      <p style="margin: 0; color: #ccc; font-size: 13px; line-height: 1.4;">Saved in this browser.</p>
      <div id="match-replay-menu-list" class="ga-replay-list"></div>
    </div>
  </div>
</main>

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
          <option value="paradise">Paradise</option>
          <option value="dawn">Dawn of Ashes</option>
          <option value="classic">Classic Blue</option>
        </select>
      </label>
    </div>
  </div>
</div>

<audio id="ga-player-joined-sound" src="/TCGEngine/Assets/playerJoinedSound.mp3" preload="auto"></audio>
<script src="<?php echo _VersionAsset('/TCGEngine/Core/MatchReplayClient.js'); ?>"></script>
<script src="<?php echo _VersionAsset('/TCGEngine/SharedUI/js/private-invite.js'); ?>"></script>

<style>
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
  .ga-menu-grid {
    display: grid;
    grid-template-columns: minmax(260px, 0.9fr) minmax(360px, 1.2fr) minmax(300px, 1fr);
    gap: 14px;
    align-items: start;
    margin: 0 10px 10px;
  }
  .ga-menu-grid input:not([type="checkbox"]),
  .ga-menu-grid select,
  .ga-menu-grid button:not(.ga-info-tab) {
    min-height: 40px;
  }
  .ga-menu-grid .ga-primary-action {
    background: linear-gradient(135deg, #c99d38, #8d6420);
    border-color: #e6c66f;
    color: #101a28;
    font-weight: 700;
  }
  .ga-menu-grid .ga-secondary-action {
    background: transparent;
    border-color: rgba(214, 184, 109, 0.65);
    color: #f4e2a4;
  }
  .ga-info-tab { min-height: 40px; }
  .row-wrapper > .card {
    min-width: 0;
  }
  .ga-active-card,
  .ga-queue-card,
  .ga-info-card {
    color: white;
    border-radius: 12px;
    position: relative;
    margin: 0 !important;
    padding: 18px !important;
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
  .recent-games-list { margin-top: 14px; border-top: 1px solid rgba(255,255,255,0.12); padding-top: 10px; }
  .recent-games-heading { color: #d6b86d; font-size: 12px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; margin-bottom: 6px; }
  .recent-game-row { display: flex; justify-content: space-between; gap: 8px; color: #c8d2df; font-size: 12px; padding: 5px 0; }
  .recent-game-row span:last-child { color: #93a1b2; white-space: nowrap; }
  .hotkey-row { display: flex; align-items: center; gap: 10px; font-size: 13px; color: #ccc; }
  .hotkey-badge {
    display: inline-block; min-width: 28px; text-align: center;
    padding: 2px 7px; border-radius: 5px;
    background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.2);
    font-family: monospace; font-size: 13px; font-weight: 700; color: #fff;
    flex-shrink: 0;
  }
  #did-you-know-box {
    background: linear-gradient(135deg, rgba(201,168,76,0.13) 0%, rgba(18,31,50,0.42) 100%);
    border: 1px solid rgba(201,168,76,0.3);
    border-radius: 8px;
    padding: 14px 16px;
    position: relative;
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
  .ga-tip-icon {
    display: inline-flex;
    width: 18px;
    height: 18px;
    border-radius: 999px;
    align-items: center;
    justify-content: center;
    background: rgba(201, 168, 76, 0.18);
    color: #f4e2a4;
    border: 1px solid rgba(201, 168, 76, 0.35);
    font-size: 12px;
    font-weight: 700;
    font-family: serif;
  }
  .ga-tip-next {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 3px 8px;
    border-radius: 5px;
    font-size: 12px;
  }
  .ga-inline-section-title {
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #888;
    margin: 14px 0 8px;
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
  .ga-info-tabs {
    display: flex;
    gap: 0;
    border-bottom: 1px solid rgba(201,168,76,0.28);
  }
  .ga-info-tab {
    flex: 1;
    padding: 8px;
    border: 0;
    border-bottom: 2px solid transparent;
    background: rgba(40,40,40,0.55);
    color: #aaa;
    cursor: pointer;
    font-size: 13px;
  }
  .ga-info-tab.is-active {
    background: rgba(201,168,76,0.16);
    color: #fff;
    border-bottom-color: #d6b86d;
  }
  .ga-info-panel {
    display: none;
    flex-direction: column;
    gap: 16px;
  }
  .ga-info-panel.is-active {
    display: flex;
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
      display: flex;
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
          defaultValue: 'paradise'
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
      var validThemes = ['paradise', 'dawn', 'classic'];
      var savedTheme = window.TCGSettings.get('BoardBackgroundTheme', { rootName: 'GrandArchiveSim', type: 'string', defaultValue: 'paradise' });
      boardThemeSelect.value = (validThemes.indexOf(savedTheme) !== -1) ? savedTheme : 'paradise';
      boardThemeSelect.addEventListener('change', function() {
        var value = (validThemes.indexOf(boardThemeSelect.value) !== -1) ? boardThemeSelect.value : 'paradise';
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

  // This page is served at two URL depths (the ActiveSite pointer /TCGEngine/SharedUI/MainMenu.php AND
  // /TCGEngine/SharedUI/Sites/GrandArchiveSim/MainMenu.php), so a fixed '../../../' prefix overshoots
  // to '/APIs/...' (404) from the pointer path. Anchor to /TCGEngine/ from the live URL instead.
  function gaAppBase(){ var p=location.pathname, i=p.indexOf('/TCGEngine/'); return i>=0 ? p.slice(0, i+11) : '/TCGEngine/'; }

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
        var url = new URL(gaAppBase() + 'NextTurn.php', window.location.href);
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
        var tabs = ['link', 'text', 'archetype', 'official'];
        window._gaDeckTab = tab;
        document.getElementById('deck-input-link').style.display = tab === 'link' ? '' : 'none';
        document.getElementById('deck-input-text').style.display = tab === 'text' ? '' : 'none';
        document.getElementById('ga-bot-sample-decks-group').style.display = tab === 'archetype' ? '' : 'none';
        document.getElementById('ga-official-decks-group').style.display = tab === 'official' ? '' : 'none';
        tabs.forEach(function(tabName) {
          var tabButton = document.getElementById('tab-' + tabName);
          if (!tabButton) return;
          var selected = tabName === tab;
          tabButton.setAttribute('aria-selected', selected ? 'true' : 'false');
          tabButton.style.background = selected ? 'rgba(52,152,219,0.25)' : 'rgba(40,40,40,0.7)';
          tabButton.style.color = selected ? 'white' : '#aaa';
          tabButton.style.borderBottom = selected ? '2px solid #3498db' : '2px solid transparent';
        });
        try { localStorage.setItem('ga_deck_tab', tab); } catch(e) {}
      }

      function switchDeck2Tab(tab) {
        window._gaDeck2Tab = tab;
        ['link', 'text', 'archetype', 'official'].forEach(function(tabName) {
          var selected = tabName === tab;
          var button = document.getElementById('ga-deck2-tab-' + tabName);
          var panelId = tabName === 'link' ? 'ga-deck2-input-panel' : 'ga-deck2-' + tabName + '-panel';
          var panel = document.getElementById(panelId);
          if (button) {
            button.setAttribute('aria-selected', selected ? 'true' : 'false');
            button.style.background = selected ? 'rgba(52,152,219,0.25)' : 'rgba(40,40,40,0.7)';
            button.style.color = selected ? 'white' : '#aaa';
            button.style.borderBottom = selected ? '2px solid #3498db' : '2px solid transparent';
          }
          if (panel) panel.style.display = selected ? '' : 'none';
        });
      }

      (function() {
        var saved = '';
        try { saved = localStorage.getItem('ga_deck_tab') || ''; } catch(e) {}
        if (saved === 'text') switchDeckTab('text');
      })();

      // Shared private-invite lobby UI (SharedUI/js/private-invite.js): reveal Join Private Invite,
      // hide the competing Create Private Game / Join Queue actions, and disable the format +
      // match-type selects (the server adopts the HOST lobby's settings for an invite join).
      function initializePrivateInviteFromUrl() {
        try {
          _privateInviteCode = window.PrivateInviteUI ? window.PrivateInviteUI.init({ rootName: 'GrandArchiveSim' }) : '';
        } catch (e) {
          console.error('Failed to parse private invite URL:', e);
        }
      }

      function initializeGoldfishLinkFromUrl() {
        try {
          var params = new URLSearchParams(window.location.search || '');
          var deckLinkParam = (params.get('deckLink') || params.get('deck') || '').trim();
          var deckTextParam = (params.get('deckText') || params.get('list') || '').trim();
          var deck2Param = (params.get('deckLink2') || params.get('deck2') || params.get('deckText2') || '').trim();
          var formatParam = (params.get('format') || '').trim().toLowerCase();
          var queueTypeParam = (params.get('queueType') || '').trim().toLowerCase();
          var goldfishParam = (params.get('goldfish') || '').trim().toLowerCase();
          var shouldAutostart = goldfishParam === '1' || goldfishParam === 'true' || goldfishParam === 'yes';

          var formatEl = document.getElementById('ga-format-select');
          if (formatEl && formatParam && Array.prototype.some.call(formatEl.options, function(option) { return option.value === formatParam; })) {
            formatEl.value = formatParam;
            formatEl.dispatchEvent(new Event('change'));
          }

          var queueTypeEl = document.getElementById('ga-queuetype-select');
          if (queueTypeEl && queueTypeParam && Array.prototype.some.call(queueTypeEl.options, function(option) { return option.value === queueTypeParam; })) {
            queueTypeEl.value = queueTypeParam;
          }

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

          if (deck2Param) {
            var deck2El = document.getElementById('ga-deck2-input');
            if (deck2El && !deck2El.value.trim()) {
              deck2El.value = deck2Param;
            }
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
        if (deckTextEl && (window._gaDeckTab === 'text' || window._gaDeckTab === 'archetype' || window._gaDeckTab === 'official')) {
          deckLink = deckTextEl.value.trim();
        } else if (deckLinkEl) {
          deckLink = deckLinkEl.value.trim();
        }
        if (!deckLink && !preconstructedDeck) {
          StyledAlert('Please enter a deck link or paste a deck list.');
          return null;
        }
        var gameType = 'casual'; // Default game type since select is commented out

        var formatEl = document.getElementById('ga-format-select');
        var format = formatEl ? formatEl.value : 'standard';

        var deck2El = window._gaDeck2Tab === 'text' ? document.getElementById('ga-deck2-text') : document.getElementById('ga-deck2-input');
        var deckLink2 = deck2El ? deck2El.value.trim() : '';

        var qtEl = document.getElementById('ga-queuetype-select');
        var queueType = qtEl ? qtEl.value : 'bo1';
        var analyticsEl = document.getElementById('ga-share-anonymized-gameplay');
        var shareAnonymizedGameplayData = analyticsEl ? analyticsEl.checked : true;

        return {
          preconstructedDeck: preconstructedDeck,
          deckLink: deckLink,
          deckLink2: deckLink2,
          gameType: gameType,
          format: format,
          queueType: queueType,
          shareAnonymizedGameplayData: shareAnonymizedGameplayData
        };
      }

      (function restoreAnalyticsSharingPreference(){
        var analyticsEl = document.getElementById('ga-share-anonymized-gameplay');
        if (!analyticsEl) return;
        try {
          var saved = window.localStorage.getItem('tcgengine:GrandArchiveSim:shareAnonymizedGameplayData');
          if (saved !== null) analyticsEl.checked = saved !== 'false';
          analyticsEl.addEventListener('change', function(){
            window.localStorage.setItem('tcgengine:GrandArchiveSim:shareAnonymizedGameplayData', analyticsEl.checked ? 'true' : 'false');
          });
        } catch (e) {
          // Storage can be unavailable in strict privacy modes; the checked default still applies.
        }
      })();

      // Hotseat needs a second deck (reveal the P2 input); Bot format shows the same input as an
      // optional deck for the bot to pilot. Mode formats (goldfish/hotseat/bot) are Bo1-only.
      (function(){
        var fmt = document.getElementById('ga-format-select');
        if (!fmt) return;
        function applyFmt(){
          var isMode = (fmt.value === 'goldfish' || fmt.value === 'hotseat' || fmt.value === 'bot');
          var isTwoDeckMode = (fmt.value === 'hotseat' || fmt.value === 'bot');
          var secondSeatName = fmt.value === 'bot' ? 'the bot' : 'Player 2';
          var g = document.getElementById('ga-deck2-group');
          var label = document.getElementById('ga-deck2-label');
          if (g) g.style.display = isTwoDeckMode ? '' : 'none';
          if (label) label.textContent = (fmt.value === 'bot')
            ? 'Bot deck (link or deck list; optional — defaults to a copy of your deck):'
            : 'Player 2 deck (link or deck list):';
          var textLabel = document.getElementById('ga-deck2-text-label');
          if (textLabel) textLabel.textContent = fmt.value === 'bot' ? 'Paste the bot’s deck list:' : 'Paste Player 2’s deck list:';
          var archetypeTab = document.getElementById('tab-archetype');
          var officialTab = document.getElementById('tab-official');
          if (archetypeTab) archetypeTab.style.display = '';
          if (officialTab) officialTab.style.display = '';
          var deck2PickerTitle = document.getElementById('ga-deck2-picker-title');
          if (deck2PickerTitle) deck2PickerTitle.textContent = 'Choose an archetype to prefill ' + secondSeatName + '’s deck';
          var officialDeck2Label = document.getElementById('ga-official-deck2-label');
          if (officialDeck2Label) officialDeck2Label.textContent = 'Choose an official product deck for ' + secondSeatName + ':';
          var qt = document.getElementById('ga-queuetype-select');
          if (qt) { if (isMode) { qt.value = 'bo1'; qt.disabled = true; } else { qt.disabled = false; } }
          var bo1Note = document.getElementById('ga-bo1-note');
          if (bo1Note) bo1Note.style.display = isMode ? '' : 'none';
          var joinButton = document.getElementById('ga-join-queue-btn');
          if (joinButton) joinButton.textContent = fmt.value === 'bot' ? 'Start Practice Game' : 'Join Queue';
          var privateButton = document.getElementById('ga-create-private-btn');
          if (privateButton) privateButton.style.display = fmt.value === 'bot' ? 'none' : '';
        }
        fmt.addEventListener('change', applyFmt);
        applyFmt();
      })();

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
        if (!submission || !submission.deckLink) return;
        var linkPanel = document.getElementById('deck-input-link');
        if (!linkPanel || linkPanel.style.display === 'none') return;
        if (!window.TCGDeckLibrarySaveCurrent) {
          return;
        }
        window.TCGDeckLibrarySaveCurrent(submission.deckLink, {
          localStorageKey: 'tcgengine:savedDecks:GrandArchiveSim',
          promptName: false,
          name: submission.deckLink
        });
      }

      function loadSavedDeckInput(input) {
        if (!input) return;
        if (input.indexOf('\n') !== -1 || input.indexOf('\r') !== -1) {
          switchDeckTab('text');
          var textEl = document.getElementById('deck-text');
          if (textEl) textEl.value = input;
        } else {
          switchDeckTab('link');
          var linkEl = document.getElementById('deck-link');
          if (linkEl) linkEl.value = input;
        }
      }

      document.addEventListener('change', function(e) {
        var sel = e.target.closest('.saved-decks-panel .dl-select');
        if (!sel) return;
        var opt = sel.options[sel.selectedIndex];
        loadSavedDeckInput(opt ? opt.getAttribute('data-queue-input') : '');
      });

      // Current-meta sample decks for the Bot format's "Or start with a sample deck" row —
      // pulled from Fan of Insight's own "Playtest in Clarent" export (fanofin.site archetype
      // pages), embedded here rather than fetched live so picking one works with no external
      // dependency at request time.
      var GA_BOT_SAMPLE_DECKS = [
        {
          label: 'Fire Guo Jia (Impact Hammer)',
          text: '# Main\n4 Liminal Guide\n4 Undying Dreams\n3 Blazing Throw\n4 Creative Shock\n3 Demolition\n4 Embercrypt Burn\n4 Fiery Interference\n3 Heated Vengeance\n4 Peppered Chef\n3 Restorative Flame\n4 Rile the Abyss\n4 Searing Truth\n2 Shatter the Brittle\n3 Spark Alight\n4 Three of Hearts\n3 Vengeful Paramour\n4 Vermilion Decree\n\n# Material\n1 Spirit of Fire\n1 Guo Jia, Chosen Disciple\n1 Guo Jia, Blessed Scion\n1 Censer of Restful Peace\n1 Grand Crusader\'s Ring\n1 Portentous Tanggu\n1 Safeguard Amulet\n1 Smoke Bombs\n1 Sword of Seeking\n1 Tariff Ring\n1 Fabled Ruby Fatestone\n1 Impact Hammer\n\n# Sideboard\n1 Nullifying Mirror\n2 Crystallized Destiny\n3 Staggering Strike\n3 Fatestone of Unrelenting\n2 Flamewreath Call\n2 Under Fire'
        },
        {
          label: 'Tera Silvie (Baby Silver Slime)',
          text: '# Main\n4 Baby Gray Slime\n4 Baby Silver Slime\n4 Dungeon Guide\n4 Forest Cake\n4 Limitless Slime\n4 Disorienting Winds\n2 Dream Fairy\n4 Reclaim\n2 Slime Calling\n4 Slimeshield\n2 Song of Return\n2 Stifling Trap\n1 Aella, Zephyr\'s Hand\n4 Imperious Galebind\n4 Storm Slime\n4 Ethereal Slime\n2 Lustrous Slime\n4 Gaia\'s Songbird\n1 Tera Sight\n\n# Material\n1 Spirit of Wind\n1 Silvie, Wilds Whisperer\n1 Silvie, With the Pack\n1 Silvie, Slime Sovereign\n1 Beastbond Boots\n1 Enfeebling Orb\n1 Lost Providence\n1 Purifying Thurible\n1 Gaia\'s Blessing\n1 Horn of Beastcalling\n1 Seed of Nature\n1 Stonescale Band\n\n# Sideboard\n1 Nullifying Lantern\n1 Nullifying Mirror\n1 Viridian Protective Trinket\n1 Stifling Gyre\n2 Psychopomp\'s Gale\n3 Twilight Slime'
        },
        {
          label: 'Water Diao Chan',
          text: '# Main\n4 Burst Asunder\n1 Captivating Opulence\n4 Dissonant Fractal\n4 Fast Cure\n4 Fractal of Insight\n2 Fractal of Intrusion\n4 Fractal of Rain\n4 Fractal of Snow\n4 Fracturize\n4 Frostsworn Paladin\n4 Glimmering Refusal\n4 Lost in Thought\n4 Refracting Missile\n4 Shimmering Refraction\n1 Turbo Charge\n4 Unstable Fractal\n4 Zhang Jiao, Way of Peace\n\n# Material\n1 Spirit of Water\n1 Diao Chan, Enchantress\n1 Backup Charger\n1 Crystalline Mirror\n1 Nullifying Lantern\n1 Nullifying Mirror\n1 Portentous Tanggu\n1 Quicksilver Grail\n1 Safeguard Amulet\n1 Scepter of Fascination\n1 Wand of Frost\n1 Wind Resonance Bauble\n\n# Sideboard\n1 Art of War\n1 Captivating Opulence\n3 Chill to the Bone\n1 Staggering Strike\n1 Jianyu, Fate\'s Premonition\n1 Viridian Protective Trinket\n1 Water Resonance Bauble'
        },
        {
          label: 'Wind Arisanna (Distilled Water)',
          text: '# Main\n4 Distilled Water\n4 Floral Arrangement\n2 Obscured Offering\n3 Tend the Land\n3 Combustible Potion\n3 Soothing Potion\n2 Beseech the Winds\n3 Calming Breeze\n2 Cyclical Breeze\n2 Dream Fairy\n4 Fairy Whispers\n4 Imperial Alchemist\n4 Razorgale Calling\n4 Scout the Land\n4 Speed Potion\n2 Stifling Trap\n3 Three Visits\n3 Veiling Breeze\n4 Windmill Engineer\n\n# Material\n1 Spirit of Wind\n1 Arisanna, Herbalist Prodigy\n1 Alchemist\'s Cauldron\n1 Censer of Restful Peace\n1 Essence Crucible\n1 Grand Crusader\'s Ring\n1 Ingredient Pouch\n1 Polaris, Twinkling Cauldron\n1 Safeguard Amulet\n1 Tariff Ring\n1 Viridian Protective Trinket\n1 Purifying Thurible\n\n# Sideboard\n1 Nullifying Lantern\n1 Orb of Sealing\n1 Obscured Offering\n2 Innervate Agility\n3 Scatter Essence\n1 Stifling Trap\n2 Zephyr'
        }
      ];

      function renderBotSampleDecks(containerId, applyDeck) {
        var container = document.getElementById(containerId);
        if (!container) return;
        GA_BOT_SAMPLE_DECKS.forEach(function(deck, i) {
          var btn = document.createElement('button');
          btn.type = 'button';
          btn.textContent = deck.label;
          btn.setAttribute('aria-pressed', 'false');
          btn.style.cssText = 'min-height: 44px; padding: 8px 12px; font-size: 12px; background: rgba(52,152,219,0.18); color: #cfe8fb; border: 1px solid rgba(52,152,219,0.4); border-radius: 6px; cursor: pointer;';
          btn.addEventListener('click', function() {
            Array.prototype.forEach.call(container.querySelectorAll('button'), function(otherButton) {
              otherButton.setAttribute('aria-pressed', otherButton === btn ? 'true' : 'false');
            });
            applyDeck(deck);
          });
          container.appendChild(btn);
        });
      }
      function setDeckSelectionSummary(summaryId, deckLabel) {
        var summary = document.getElementById(summaryId);
        if (!summary) return;
        summary.textContent = 'Selected: ' + deckLabel;
        summary.style.display = '';
      }

      renderBotSampleDecks('ga-bot-sample-decks-list', function(deck) {
        var textEl = document.getElementById('deck-text');
        if (textEl) textEl.value = deck.text;
        setDeckSelectionSummary('ga-deck1-selection-summary', deck.label);
      });
      renderBotSampleDecks('ga-bot-sample-decks-list-2', function(deck) {
        var textEl = document.getElementById('ga-deck2-input');
        if (textEl) textEl.value = deck.text;
        setDeckSelectionSummary('ga-deck2-selection-summary', deck.label);
      });

      // Every official preconstructed/starter product decklist, as printed — also sourced from
      // Fan of Insight (app/src/features/official-products/decks.json), embedded statically for
      // the same no-external-dependency reason as GA_BOT_SAMPLE_DECKS above. Verified against the
      // live card dictionary via GrandArchiveSim/Custom/DeckTextParser.php's ParseFreeTextDeck()
      // before embedding — every card name here resolves with zero unmatched names.
      var GA_OFFICIAL_DECKS = [
  {
    "label": "Dante, Hemomancer Starter Deck",
    "group": ".asphodel/paradise",
    "text": "# Main\n3 Aenean Ward\n1 Eminence in Fury\n3 Heighten Spellcraft\n4 Magus Initiate\n3 Nascent Blast\n4 Shieldroid\n4 Stalwart Shieldmate\n3 Aenean Frostlance\n3 Aenean Frozen Shunt\n4 Cryogenic Ritual\n4 Frostbind\n4 Keen Tidebinder\n4 Memory Invocation\n2 Pure Cytosynth\n2 Blood Surge\n3 Elysian Aspirant\n2 Exia Sight\n4 Hemoflux Drain\n2 Spellshield: Exia\n2 Unruled Bereavement\n\n# Material\n1 Spirit of Water\n1 Dante, Prodigal Swain\n1 Dante, Aenean Initiate\n1 Dante, Hemomancer\n1 Bauble of Abundance\n1 Gencode Womb\n1 Life Essence Amulet\n1 Safeguard Amulet\n1 Shard of Empowerment\n1 Tariff Ring\n1 Crimson Vein\n1 Venous Core"
  },
  {
    "label": "Lorraine, Arclight Saber Starter Deck",
    "group": ".asphodel/paradise",
    "text": "# Main\n3 Banner Knight\n3 Deflecting Edge\n4 Honorable Vanguard\n4 Blistering Insurgent\n4 Creative Tinder\n4 Emberslash\n2 FlameTech BladeCore\n4 Forging Heat\n4 Package Courier\n3 Stoked Slice\n4 Tindered Soldier\n3 Arcane Sight\n3 Arrest Lightning\n2 Brooch X Ultra\n1 Conductive Strike\n3 Fulgurite Coordinator\n3 Return Stroke\n2 Rumble Coordinator\n4 Surged Coordinator\n\n# Material\n1 Spirit of Fire\n1 Lorraine, Wandering Warrior\n1 Lorraine, Honed Operative\n1 Lorraine, Arclight Saber\n1 Bauble of Abundance\n1 Clarent, Sword of Peace\n1 Life Essence Amulet\n1 Safeguard Amulet\n1 Tariff Ring\n1 Fulminator, Rising Storm\n1 Jovian Hilt X Ultra"
  },
  {
    "label": "Ciel, Mirage's Grave",
    "group": "Distorted Reflections",
    "text": "# Main\n3 Coy Bouclier\n4 Heavy Swing\n4 Idle Thoughts\n2 Lamentation's Toll\n2 Martial Guard\n2 Overpowering Defense\n3 Sablier Guard\n4 Stalwart Shieldmate\n3 Vigil Rempart\n4 Whimsy's Warden\n4 Conflagrant Sentinel\n4 Flamme Sorcel\n3 Tempered Steel\n3 Torch Marshal\n1 Devotion's Price\n2 Nocturne's Oblivion\n4 Ombreux Chevalier\n2 Reverse Affliction\n3 Sinistre Stab\n3 Umbra Sight\n\n# Material\n1 Spirit of Fire\n1 Ciel, Loyal Valet\n1 Ciel, Omenbringer\n1 Ciel, Mirage's Grave\n1 Bauble of Abundance\n1 Bulwark Sword\n1 Grande Aiguille\n1 Leporine Masque\n1 Life Essence Amulet\n1 Manxome Armoire\n1 Tariff Ring\n1 Grande Sonnerie"
  },
  {
    "label": "Diana, Moonpiercer",
    "group": "Distorted Reflections",
    "text": "# Main\n4 Aetheric Calibration\n4 Backstep\n4 Charge the Soul\n3 Idle Thoughts\n4 Prudent Nock\n4 Reposition\n4 Stalwart Shieldmate\n3 Corsair Captain\n4 Dissuading Aether\n4 Drown in Aether\n3 Undercurrent Vantage\n3 Astra Sight\n4 Constellation's Blessing\n2 Guided Starlight\n2 Meteoric Volley\n3 Poised Occlusion\n3 Sidereal Spellshot\n2 Starbirth\n\n# Material\n1 Spirit of Water\n1 Diana, Aether Dilettante\n1 Diana, Judgment's Arrow\n1 Diana, Moonpiercer\n1 Bauble of Abundance\n1 Foresight Lens\n1 Ranger Boots\n1 Refluxal Ribbon\n1 Seeker's Aetherwing\n1 Tariff Ring\n1 Aquamirage Whisper\n1 Pleiades, Celestial Genesis"
  },
  {
    "label": "Jin Starter Deck",
    "group": "Mortal Ambition",
    "text": "# Main\n2 Banner Knight\n4 Idle Thoughts\n4 Pierce the Heavens\n3 Regenerate\n4 Safeguard Paladin\n4 Savage Swing\n3 Trusty Steed\n3 Veteran Soldier\n4 Eminent Commander\n4 Favorable Winds\n3 Materialize Polearm\n2 Reclaim\n4 Swift Recruit\n3 Wind Cutter\n2 Bloodbond Bladesworn\n4 Enrage\n2 Exia Sight\n2 Hemorrhaging Rend\n2 Mend Flesh\n1 Relentless Outburst\n\n# Material\n1 Spirit of Wind\n1 Jin, Fate Defiant\n1 Jin, Zealous Maverick\n1 Jin, Undying Resolve\n1 Bauble of Abundance\n1 Executioner's Spear\n1 Life Essence Amulet\n1 Safeguard Amulet\n1 Slate Whetstone\n1 Steel Halberd\n1 Berserker Plate\n1 Shuang Ji of Sacrifice"
  },
  {
    "label": "Kongming Starter Deck",
    "group": "Mortal Ambition",
    "text": "# Main\n4 Ardent Cloudstriker\n4 Formidable Youxia\n4 Harmonious Mantra\n4 Heighten Spellcraft\n2 Idle Thoughts\n4 Spirited Neophyte\n3 Wisdom's Reprise\n3 Cone of Frost\n4 Coriolis Ward\n3 Hydroguard Retainer\n3 Rising Tides\n4 Taiji of Crystal Strategems\n3 Tsunami of Nanyue\n2 Water Barrier\n4 Devoted Bloomweaver\n3 Leeching Bolt\n1 Planar Abyss\n2 Ruinous Pillars of Qidao\n3 Tera Sight\n\n# Material\n1 Spirit of Water\n1 Kongming, Wayward Maven\n1 Kongming, Ascetic Vice\n1 Kongming, Fel Eidolon\n1 Bauble of Abundance\n1 Fan of Seven Debts\n1 Life Essence Amulet\n1 Shard of Empowerment\n1 Tariff Ring\n1 Sweet Ambrosia\n1 Coronal of Rejuvenation\n1 Entrancing Filigree"
  },
  {
    "label": "Arisanna Starter Deck",
    "group": "Alchemical Revolution",
    "text": "# Main\n3 Academy Attendant\n3 Barter Herbs\n3 Caretaker Drone\n4 Foraging Servant\n3 Potion of Healing\n3 Scry the Skies\n3 Serum of Wisdom\n4 Essence of Blizzards\n3 Flash Freeze\n3 Hypothermia\n2 Krustallan Distiller\n2 Perfect Repulsion\n2 Potion Infusion: Clarity\n3 Potion Infusion: Frostbite\n3 Stream of Consciousness\n2 Water Barrier\n3 Astra Sight\n3 Cometfall\n2 Condensed Supernova\n4 Cosmic Bolt\n1 Potion Infusion: Starlight\n1 Spellshield: Astra\n\n# Material\n1 Spirit of Water\n1 Arisanna, Herbalist Prodigy\n1 Arisanna, Master Alchemist\n1 Arisanna, Astral Zenith\n1 Cleric Robes\n1 Ingredient Pouch\n1 Life Essence Amulet\n1 Necklace of Foresight\n1 Prototype Staff\n1 Scale of Souls\n1 Synth Disrupter\n1 Cosmic Astroscope"
  },
  {
    "label": "Diana Starter Deck",
    "group": "Alchemical Revolution",
    "text": "# Main\n4 Backstep\n4 Evasive Maneuvers\n4 Idle Thoughts\n4 Imperial Rifleman\n4 Materialize Munitions\n3 Reposition\n2 Supply Drone\n3 Take Aim\n1 Take Cover\n3 Trained Sharpshooter\n3 Airship Engineer\n3 Automaton Bomber\n2 Force Load\n4 Incendiary Shot\n2 Rocket Jump\n1 Anathema's End\n4 Creeping Torment\n3 Mindbreak Bullet\n2 Umbra Sight\n3 Umbral Tithe\n1 Violet Haze\n\n# Material\n1 Spirit of Fire\n1 Diana, Keen Huntress\n1 Diana, Deadly Duelist\n1 Diana, Duskstalker\n1 Blastshot Pump\n1 Flash Grenade\n1 Plated Bullet\n1 Prototype Pistol\n1 Quickdraw Piercer\n1 Tasershot\n1 Penetrator Round\n1 Shadow's Twin"
  },
  {
    "label": "Tonoris Starter Deck",
    "group": "Alchemical Revolution",
    "text": "# Main\n4 Fortified Mana Shield\n4 Heavy Swing\n4 Imperial Recruit\n4 Imperial Sentry\n3 Into the Fray\n4 Martial Guard\n4 Novice Mechanist\n4 Stalwart Shieldmate\n4 Take Point\n3 Cyclonic Strike\n4 Recruitment Officer\n3 Rousing Slam\n2 Winds of Retribution\n3 Young Peacekeeper\n1 Assemble the Ancients\n3 Neos Sight\n3 Smash with Obelisk\n3 Summon Sentinels\n\n# Material\n1 Spirit of Wind\n1 Tonoris, Lone Mercenary\n1 Tonoris, Might of Humanity\n1 Tonoris, Genesis Aegis\n1 Bulwark Sword\n1 Charged Manaplate\n1 Crest of the Alliance\n1 Powercharged Shield\n1 Worn Gearblade\n1 Deployment Beacon\n1 Archon Broadsword\n1 Sentinel Fabricator"
  },
  {
    "label": "Lorraine Starter Deck",
    "group": "Dawn of Ashes",
    "text": "# Main\n3 Banner Knight\n4 Crusader of Aesa\n3 Deflecting Edge\n4 Esteemed Knight\n2 Honorable Vanguard\n3 Inspiring Call\n3 Opening Cut\n3 Savage Slash\n2 Sudden Steel\n2 Training Session\n3 Veteran Soldier\n3 Weaponsmith\n2 Favorable Winds\n1 Hurricane Sweep\n1 Phalanx Captain\n2 Reclaim\n4 Swift Recruit\n2 Wind Cutter\n2 Crux Sight\n2 Spirit Blade: Ascension\n1 Spirit Blade: Dispersion\n3 Spirit Blade: Ghost Strike\n3 Spirit Blade: Infusion\n2 Spirit's Blessing\n\n# Material\n1 Spirit of Wind\n1 Lorraine, Wandering Warrior\n1 Lorraine, Blademaster\n1 Lorraine, Crux Knight\n1 Bauble of Abundance\n1 Clarent, Sword of Peace\n1 Fire Resonance Bauble\n1 Life Essence Amulet\n1 Ornamental Greatsword\n1 Sword of Seeking\n1 Warrior's Longsword\n1 Prismatic Edge"
  },
  {
    "label": "Rai Starter Deck",
    "group": "Dawn of Ashes",
    "text": "# Main\n3 Barrier Servant\n2 Careful Study\n2 Idle Thoughts\n4 Library Witch\n3 Magus Disciple\n3 Peer into Mana\n4 Scry the Skies\n3 Blitz Mage\n2 Creative Shock\n2 Cremation Ritual\n1 Disintegrate\n4 Fireball\n2 Flame-Rune Swordsman\n3 Focused Flames\n4 Ignite the Soul\n2 Impassioned Tutor\n2 Purge in Flames\n2 Anger the Skies\n4 Arcane Blast\n3 Arcane Disposition\n3 Arcane Sight\n1 Power Overwhelming\n1 Spellshield: Arcane\n\n# Material\n1 Spirit of Fire\n1 Rai, Spellcrafter\n1 Rai, Archmage\n1 Rai, Storm Seer\n1 Crystal of Empowerment\n1 Endura, Scepter of Ignition\n1 Life Essence Amulet\n1 Mana Limiter\n1 Surveillance Stone\n1 Tome of Knowledge\n1 Water Resonance Bauble\n1 Arcanist's Prism"
  },
  {
    "label": "Silvie Starter Deck",
    "group": "Dawn of Ashes",
    "text": "# Main\n4 Blissful Calling\n2 Empowering Harmony\n2 Gray Wolf\n2 Rebellious Bull\n2 Scry the Skies\n4 Smack with Flute\n3 Song of Nurturing\n2 Trusty Steed\n4 Blue Slime\n4 Deep Sea Beastbonder\n3 Dewdrop Hares\n3 Freezing Hail\n3 Giant Tortoise\n2 Give Bath\n2 Lakeside Serpent\n1 Mist Resonance\n2 Piper's Lullaby\n3 Revitalizing Cleanse\n3 Gaia's Songbird\n2 Invoke Dominance\n3 Meadowbloom Dryad\n3 Tera Sight\n1 Vertus, Gaia's Roar\n\n# Material\n1 Spirit of Water\n1 Silvie, Wilds Whisperer\n1 Silvie, With the Pack\n1 Silvie, Loved by All\n1 Beastbond Boots\n1 Beastbond Ears\n1 Beastbond Paws\n1 Flute of Taming\n1 Life Essence Amulet\n1 Melodious Flute\n1 Wind Resonance Bauble\n1 Seed of Nature"
  },
  {
    "label": "Lorraine Starter Deck (Prelude)",
    "group": "Dawn of Ashes Prelude",
    "text": "# Main\n3 Banner Knight\n4 Crusader of Aesa\n2 Dungeon Guide\n4 Esteemed Knight\n4 Honorable Vanguard\n2 Inspiring Call\n3 Savage Slash\n4 Scry the Skies\n2 Sudden Steel\n4 Weaponsmith\n2 Disorienting Winds\n3 Dream Fairy\n3 Favorable Winds\n2 Hurricane Sweep\n3 Wind Cutter\n3 Crux Sight\n2 Spirit Blade: Ascension\n1 Spirit Blade: Dispersion\n4 Spirit Blade: Ghost Strike\n2 Spirit Blade: Infusion\n3 Spirit's Blessing\n\n# Material\n1 Spirit of Wind\n1 Lorraine, Wandering Warrior\n1 Lorraine, Blademaster\n1 Lorraine, Crux Knight\n1 Clarent, Sword of Peace\n1 Fire Resonance Bauble\n1 Life Essence Amulet\n1 Ornamental Greatsword\n1 Seer's Sword\n1 Sword of Seeking\n1 Warrior's Longsword\n1 Prismatic Edge"
  },
  {
    "label": "Rai Starter Deck (Prelude)",
    "group": "Dawn of Ashes Prelude",
    "text": "# Main\n3 Barrier Servant\n2 Careful Study\n2 Dungeon Guide\n4 Library Witch\n3 Magus Disciple\n4 Peer into Mana\n4 Scry the Skies\n2 Blitz Mage\n4 Creative Shock\n4 Fireball\n2 Focused Flames\n4 Ignite the Soul\n3 Impassioned Tutor\n2 Purge in Flames\n2 Anger the Skies\n4 Arcane Blast\n4 Arcane Disposition\n4 Arcane Sight\n1 Power Overwhelming\n2 Spellshield: Arcane\n\n# Material\n1 Spirit of Fire\n1 Rai, Spellcrafter\n1 Rai, Archmage\n1 Rai, Storm Seer\n1 Crystal of Empowerment\n1 Endura, Scepter of Ignition\n1 Life Essence Amulet\n1 Mana Limiter\n1 Surveillance Stone\n1 Tome of Knowledge\n1 Wind Resonance Bauble\n1 Arcanist's Prism"
  },
  {
    "label": "Arisanna Pantheon Starter",
    "group": "Pantheon",
    "text": "# Main\n1 Alpha Philterbeast\n1 Barter Herbs\n1 Battlefield Benediction\n1 Break Apart\n1 Disenchant\n1 Distilled Atrophy\n1 Distilled Water\n1 Empowering Tincture\n1 Epochal Conqueror\n1 Harvest Herbs\n1 Idle Thoughts\n1 Imperial Countermeasure\n1 Mendcall Mercy\n1 Mnemonic Charm\n1 Nascent Barrier\n1 Nascent Blast\n1 Nimble Court Assassin\n1 Pendant of Accrual\n1 Piquant Shieldbearer\n1 Potion Infusion: Animate\n1 Potion Infusion: Volatility\n1 Potion of Healing\n1 Samaritan's Reach\n1 Sanctified Paladin\n1 Scry the Skies\n1 Serum of Wisdom\n1 Shade Striker\n1 Shared Fervor\n1 Silver Soldier\n1 Stocked Outpost\n1 Tonic of Remembrance\n1 Veteran Soldier\n1 Wisdom's Reprise\n1 Aqua Vitae\n1 Buoyant Driftguard\n1 Convalescent Tonic\n1 Essence of Blizzards\n1 Flash Freeze\n1 Frostbind\n1 Krustallan Distiller\n1 Potion Infusion: Clarity\n1 Potion Infusion: Frostbite\n1 Potion Infusion: Seal\n1 Waterveil Apostle\n1 Astra Sight\n1 Astromech Attendant\n1 Celestial Navigation\n1 Cometfall\n1 Condensed Supernova\n1 Cosmic Bolt\n1 Dwarf Star's Glow\n1 Lunar Seer\n1 Meteor Strike\n1 Redirect Orbit\n1 Refracted Twilight\n1 Spellshield: Astra\n1 Starlit Apothecary\n1 Stellar Bloom\n1 Trine Recursion\n1 Twinstar Tonic\n\n# Material\n1 Spirit of Water\n1 Arisanna, Herbalist Prodigy\n1 Arisanna, Master Alchemist\n1 Arisanna, Astral Zenith\n1 Cleric Robes\n1 Equinox Hour\n1 Essence Crucible\n1 Ingredient Pouch\n1 Polaris, Twinkling Cauldron\n1 Safeguard Amulet\n1 Tariff Ring\n1 Cosmic Astroscope"
  },
  {
    "label": "Kongming Pantheon Starter",
    "group": "Pantheon",
    "text": "# Main\n1 Ardent Cloudstriker\n1 Battlefield Benediction\n1 Break Apart\n1 Disenchant\n1 Epochal Conqueror\n1 Formidable Youxia\n1 Fractal of Mana\n1 Gentle Respite\n1 Harmonious Mantra\n1 Heighten Spellcraft\n1 Idle Thoughts\n1 Imperial Countermeasure\n1 Minister of Ceremony\n1 Mnemonic Charm\n1 Nascent Blast\n1 Nimble Court Assassin\n1 Pendant of Accrual\n1 Piquant Shieldbearer\n1 Retold Fortune\n1 Samaritan's Reach\n1 Sanctified Paladin\n1 Scry the Skies\n1 Shade Striker\n1 Shared Fervor\n1 Silver Soldier\n1 Spirited Neophyte\n1 Stalwart Shieldmate\n1 Stocked Outpost\n1 Venerable Sage\n1 Veteran Soldier\n1 Weaken Resistance\n1 Clumsy Apprentice\n1 Creative Shock\n1 Cremation Ritual\n1 Disintegrate\n1 Fireball\n1 Focused Flames\n1 Hasty Messenger\n1 Increasing Danger\n1 Purge in Flames\n1 Pyretic Prognosis\n1 Ritai Berserker\n1 Set Ablaze\n1 Shizun of the Ash\n1 Solar Pinnacle\n1 Solar Providence\n1 Spurn to Ash\n1 Tenderheart Guard\n1 Wandering Glaivier\n1 Entrenched Fortress\n1 Everlonging Thorns\n1 Flourishing Qi\n1 Leeching Bolt\n1 Meiren of Verdancy\n1 Petalfall Embrace\n1 Planar Abyss\n1 Roots of Tomorrow\n1 Ruinous Pillars of Qidao\n1 Tera Sight\n1 Verdure of Preservation\n\n# Material\n1 Spirit of Fire\n1 Kongming, Wayward Maven\n1 Kongming, Ascetic Vice\n1 Kongming, Fel Eidolon\n1 Equinox Hour\n1 Safeguard Amulet\n1 Tariff Ring\n1 Tome of Sorcery\n1 Worn Diary\n1 Gem of Searing Flame\n1 Coronal of Rejuvenation\n1 Vernal Talisman"
  },
  {
    "label": "Lorraine Pantheon Starter",
    "group": "Pantheon",
    "text": "# Main\n1 Altruistic Blacksmith\n1 Banner Knight\n1 Besieged Slash\n1 Break Apart\n1 Crusader of Aesa\n1 Deflecting Edge\n1 Epochal Conqueror\n1 Esteemed Knight\n1 Imperial Countermeasure\n1 Inspiring Call\n1 Lurking Assailant\n1 Nimble Court Assassin\n1 Pendant of Accrual\n1 Piquant Shieldbearer\n1 Sacred Barrier\n1 Sanctified Paladin\n1 Savage Attack\n1 Savage Slash\n1 Shade Striker\n1 Shared Fervor\n1 Silver Soldier\n1 Stocked Outpost\n1 Sudden Steel\n1 Veteran Soldier\n1 Weaponsmith\n1 Aesan Protector\n1 Attune with the Winds\n1 Bolstering Tempest\n1 Cleansing Reunion\n1 Dematerialize\n1 Disorienting Winds\n1 Favorable Winds\n1 Hurricane Sweep\n1 Phalanx Captain\n1 Rally the Peasants\n1 Reclaim\n1 Scatter Essence\n1 Shred to Ribbons\n1 Swift Recruit\n1 Tactful Sergeant\n1 Unity's Gale\n1 Veiling Breeze\n1 Wind Cutter\n1 Windrider Vanguard\n1 Zephyr\n1 Beacon Knight\n1 Crux Sight\n1 Halcyon Animus\n1 Intangible Geist\n1 Iridescent Resurgence\n1 Numinous Monk\n1 Reaping Legacy\n1 Sabela, Gossamer Penance\n1 Slay the King\n1 Spirit Blade: Ascension\n1 Spirit Blade: Ghost Strike\n1 Spirit Blade: Infusion\n1 Spirit's Blessing\n1 Templar of the Eternal\n1 Wisp's Protection\n\n# Material\n1 Spirit of Wind\n1 Lorraine, Wandering Warrior\n1 Lorraine, Blademaster\n1 Lorraine, Spirit Ruler\n1 Charm of Anticipation\n1 Drawn Blade\n1 Equinox Hour\n1 Safeguard Amulet\n1 Sword of Seeking\n1 Tariff Ring\n1 Prismatic Edge\n1 Quietus Blade"
  },
  {
    "label": "Zander Pantheon Starter",
    "group": "Pantheon",
    "text": "# Main\n1 Accepted Contract\n1 Covert Manipulator\n1 Cunning Broker\n1 Disenchant\n1 Epochal Conqueror\n1 Exploit Vulnerability\n1 Extraction Incision\n1 Gentle Respite\n1 Guerrilla Advantage\n1 Idle Thoughts\n1 Imperial Countermeasure\n1 Imperial Spy\n1 Incapacitate\n1 Juggle Knives\n1 Lurking Assailant\n1 Nimble Court Assassin\n1 Pendant of Accrual\n1 Piquant Shieldbearer\n1 Sable Remnant\n1 Sacred Barrier\n1 Sanctified Paladin\n1 Shade Striker\n1 Shared Fervor\n1 Silver Soldier\n1 Slice and Dice\n1 Stalwart Shieldmate\n1 Stocked Outpost\n1 Swerving Spring\n1 Thieving Cut\n1 Veteran Soldier\n1 Clumsy Apprentice\n1 Corhazi Arsonist\n1 Corhazi Courier\n1 Creative Shock\n1 Dazzling Courtesan\n1 Hasty Messenger\n1 Immolation Trap\n1 Increasing Danger\n1 Mark the Target\n1 Meltdown\n1 Planted Explosive\n1 Rending Flames\n1 Scorching Imperilment\n1 Spurn to Ash\n1 Tenderheart Guard\n1 Wandering Glaivier\n1 Bathe in Light\n1 Birefringence\n1 Corhazi Infiltrator\n1 Corhazi Lightblade\n1 Elucidate Plans\n1 Elyan, Lustre Loyalty\n1 Gleaming Cut\n1 Lightveil Agent\n1 Lightweaver's Assault\n1 Luminous Surge\n1 Luxem Sight\n1 Optical Control\n1 Strike of Singularity\n1 Uncover the Plot\n\n# Material\n1 Spirit of Fire\n1 Zander, Prepared Scout\n1 Zander, Deft Executor\n1 Zander, Blinding Steel\n1 Assassin's Mantle\n1 Curved Dagger\n1 Equinox Hour\n1 Orb of Choking Fumes\n1 Safeguard Amulet\n1 Tariff Ring\n1 Insignia of the Corhazi\n1 Photic Blade"
  },
  {
    "label": "Diao Chan Re:Collection, Idyll Corsage",
    "group": "Re:Collection",
    "text": "# Main\n3 Fractal of Insight\n2 Idle Thoughts\n3 Shimmering Refraction\n3 Unstable Fractal\n3 Acquiescing Rejection\n3 Chill to the Bone\n4 Dissuading Halt\n4 Eventide Lure\n3 Fractal of Refreshment\n2 Frostbind\n3 Frostlorn Caress\n3 Frostnip Pirouette\n2 Glimmering Refusal\n2 Protective Fractal\n1 Redirect Flow\n2 Refracting Missile\n2 Torpid Fractal\n3 Bloom: Summer's Glow\n2 Bloom: Winter's Chill\n3 Blossoming Denial\n2 Ripples of Atrophy\n3 Season's End\n2 Shriveling Vines\n\n# Material\n1 Spirit of Water\n1 Diao Chan, Enchantress\n1 Diao Chan, Dreaming Wish\n1 Diao Chan, Idyll Corsage\n1 Cleric Robes\n1 Glimmer Essence Amulet\n1 Scepter of Fascination\n1 Tariff Ring\n1 Crystalline Mirror\n1 Hairpin of Transience\n1 Kaleidoscope Barrette\n1 Staff of Blossoming Will"
  },
  {
    "label": "Guo Jia Re:Collection, Heaven's Favored",
    "group": "Re:Collection",
    "text": "# Main\n4 Companion Fatestone\n4 Craggy Fatestone\n3 Expel the Departed\n4 Fatestone of Revelations\n4 Foraging Fox\n2 Idle Thoughts\n4 Journey's Beginning\n2 Obscuring Threads\n4 Strengthen the Bonds\n4 Broken Promises\n4 Fatestone of Unrelenting\n2 Flamewreath Call\n4 Lavaplume Fatestone\n2 Shatter the Brittle\n3 Advent of the Shenju\n4 Fatestone of Heaven\n3 Light the Hunt\n3 Peacock of Prosperity\n\n# Material\n1 Spirit of Fire\n1 Guo Jia, Chosen Disciple\n1 Guo Jia, Blessed Scion\n1 Guo Jia, Heaven's Favored\n1 Fated Keepsake\n1 Life Essence Amulet\n1 Portentous Tanggu\n1 Rousing Rattle Drum\n1 Tariff Ring\n1 Band of Burning Verdict\n1 Fabled Azurite Fatestone\n1 Incandescent Reliquary"
  },
  {
    "label": "Silvie Re:Collection, Slime Sovereign",
    "group": "Re:Collection",
    "text": "# Main\n4 Baby Gray Slime\n4 Forest Cake\n3 Idle Thoughts\n4 Limitless Slime\n3 Smack with Flute\n2 Song of Nurturing\n4 Baby Red Slime\n2 Red Slime\n3 Slime Eruption\n3 Baby Blue Slime\n2 Blue Slime\n4 Gather Slimes\n3 Baby Green Slime\n2 Green Slime\n3 Slime's Blessing\n2 Slimeshield\n2 Storm Slime\n2 Ethereal Slime\n3 Lustrous Slime\n2 Slime King\n3 Verdant Slime\n\n# Material\n1 Spirit of Slime\n1 Silvie, Wilds Whisperer\n1 Silvie, With the Pack\n1 Silvie, Slime Sovereign\n1 Bauble of Mending\n1 Beastbond Ears\n1 Beastbond Paws\n1 Key Slime Pudding\n1 Life Essence Amulet\n1 Slime Nexus\n1 Slime Totem\n1 Verdant Scepter"
  },
  {
    "label": "Tristan Re:Collection, Shadowdancer",
    "group": "Re:Collection",
    "text": "# Main\n4 Exploit Vulnerability\n4 Idle Thoughts\n3 Incapacitate\n1 Mastermind Scheme\n4 Sable Remnant\n3 Slice and Dice\n2 Thieving Cut\n2 Arrow Trap\n4 Betraying Blade\n3 Cloaked Executioner\n4 Shimmercloak Assassin\n4 Sirocco Operative\n2 Stifling Trap\n4 Surveil the Winds\n2 Gloamspire Headhunter\n3 Grim Foreboding\n2 Haunting Demise\n3 Shadow Resonance\n3 Shadowstrike\n3 Shifting Mirage\n\n# Material\n1 Spirit of Wind\n1 Tristan, Underhanded\n1 Tristan, Hired Blade\n1 Tristan, Shadowdancer\n1 Assassin's Mantle\n1 Curved Dagger\n1 Life Essence Amulet\n1 Poisoned Dagger\n1 Gearstride Gloves\n1 Dusksoul Stone\n1 Malignant Athame\n1 Shadeblood Coating"
  }
];

      function renderOfficialDeckSelect(selectId, applyDeck) {
        var select = document.getElementById(selectId);
        if (!select) return;
        var groups = {};
        var order = [];
        GA_OFFICIAL_DECKS.forEach(function(deck) {
          if (!groups[deck.group]) { groups[deck.group] = []; order.push(deck.group); }
          groups[deck.group].push(deck);
        });
        order.forEach(function(groupName) {
          var optgroup = document.createElement('optgroup');
          optgroup.label = groupName;
          groups[groupName].forEach(function(deck) {
            var option = document.createElement('option');
            option.value = GA_OFFICIAL_DECKS.indexOf(deck);
            option.textContent = deck.label;
            optgroup.appendChild(option);
          });
          select.appendChild(optgroup);
        });
        select.addEventListener('change', function() {
          if (select.value === '') return;
          var deck = GA_OFFICIAL_DECKS[parseInt(select.value, 10)];
          if (!deck) return;
          applyDeck(deck);
        });
      }
      renderOfficialDeckSelect('ga-official-deck-select', function(deck) {
        var textEl = document.getElementById('deck-text');
        if (textEl) textEl.value = deck.text;
        setDeckSelectionSummary('ga-deck1-selection-summary', deck.label);
      });
      renderOfficialDeckSelect('ga-official-deck-select-2', function(deck) {
        var textEl = document.getElementById('ga-deck2-input');
        if (textEl) textEl.value = deck.text;
        setDeckSelectionSummary('ga-deck2-selection-summary', deck.label);
      });

      function createPrivateGame() {
        submitQueueJoin({
          createPrivate: true,
          waitingMessage: 'Waiting for invited opponent... (Esc to cancel)'
        });
      }

      function startGoldfishGame() {
        var formatEl = document.getElementById('ga-format-select');
        if (formatEl) formatEl.value = 'goldfish';
        submitQueueJoin({ waitingMessage: 'Starting solo game...' });
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

        var xhr = new XMLHttpRequest();
        xhr.open('POST', gaAppBase() + 'APIs/Lobbies/JoinQueue.php', true);
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

        var params = 'deckLink=' + encodeURIComponent(submission.deckLink) + '&game_type=' + encodeURIComponent(submission.gameType);
        params += '&preconstructedDeck=' + encodeURIComponent(submission.preconstructedDeck);
        params += "&rootName=" + encodeURIComponent(rootName);
        params += '&format=' + encodeURIComponent(submission.format || 'standard');
        params += '&queueType=' + encodeURIComponent(submission.queueType || 'bo1');
        params += '&shareAnonymizedGameplayData=' + (submission.shareAnonymizedGameplayData ? '1' : '0');
        if (submission.deckLink2) params += '&deckLink2=' + encodeURIComponent(submission.deckLink2);
        // Bot format: the bot always pilots seat 2, leaving seat 1 (this player) under manual
        // control. Omitting botPlayers would default the backend to both seats (self-play), which
        // would fight the human for control of their own seat.
        if (submission.format === 'bot') params += '&botPlayers=2';
        if (options.createPrivate) {
          params += '&createPrivate=1';
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
            xhr.open('POST', gaAppBase() + 'APIs/Lobbies/LeaveQueue.php', true);
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
        xhr.open('GET', gaAppBase() + 'APIs/Lobbies/GetActiveGames.php?rootName=' + encodeURIComponent(rootName), true);
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
        var url = new URL(gaAppBase() + 'NextTurn.php', window.location.href);
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
          gameListElement.innerHTML = '<div class="active-game-empty">No active games right now. Start one or refresh again in a moment.</div><div id="recent-games-list" class="recent-games-list" aria-live="polite"></div>';
          loadRecentMatches();
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

      function formatRecentMatchTime(timestamp) {
        if (!timestamp) return 'Unknown time';
        var seconds = Math.max(0, Math.floor(Date.now() / 1000) - Number(timestamp));
        if (seconds < 60) return 'just now';
        if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
        if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
        return Math.floor(seconds / 86400) + 'd ago';
      }
      function escapeRecentText(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function(ch) {
          return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch];
        });
      }

      function loadRecentMatches() {
        var recent = document.getElementById('recent-games-list');
        if (!recent) return;
        var xhr = new XMLHttpRequest();
        xhr.open('GET', gaAppBase() + 'APIs/Lobbies/GetRecentMatches.php?rootName=' + encodeURIComponent(rootName), true);
        xhr.responseType = 'json';
        xhr.onload = function() {
          if (xhr.status < 200 || xhr.status >= 300 || !xhr.response || !Array.isArray(xhr.response.data) || !xhr.response.data.length) return;
          var html = '<div class="recent-games-heading">Recent public games</div>';
          xhr.response.data.forEach(function(match) {
            var result = match.winner === 1 || match.winner === 2 ? 'Player ' + match.winner + ' won' : 'Completed';
            var format = match.format ? match.format : 'Game';
            html += '<div class="recent-game-row"><span><strong>' + escapeRecentText(format) + '</strong> · ' + escapeRecentText(result) + '</span><span>' + escapeRecentText(formatRecentMatchTime(match.completedAt)) + '</span></div>';
          });
          recent.innerHTML = html;
        };
        xhr.send();
      }

      function pollLobbyUpdates(playerID, authKey) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', gaAppBase() + 'APIs/Lobbies/PollLobbyUpdates.php', true);
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
