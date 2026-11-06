<?php // SWUSim/LimitedBuilder.php — the Limited deckbuilder (Sealed): build from your own pool.
// Spec: docs/superpowers/specs/2026-11-04-swusim-limited-sealed-draft-design.md §3.4; mockup
// docs/superpowers/specs/2026-11-04-limited-mockups/deckbuilder.html. Two contexts, one page:
//   ?lobbyID=<id>                              the room's 30-minute build. The seat key is the room key the waiting room keeps
//                                              in localStorage (tcg:lobbyAuth:<lobbyID>) — never in the URL.
//   ?matchId=<id>&playerID=<seat>&authKey=<k>  between the games of a Bo3 (SWUSim/Sideboard.php sends a Limited match here),
//                                              authenticated the way Sideboard.php is.
// Everything else — the pool, the deck, the clock — comes from SWUSim/LimitedBuilderAction.php and is drawn by
// SWUSim/Limited/ui/limited-builder.js. This file is only the shell, the chat, and the script globals.
include_once __DIR__ . '/../AccountFiles/AccountSessionAPI.php';
include_once __DIR__ . '/../AppCore/SWU/CardImagePath.php';
require_once __DIR__ . '/PlayerSettings.php';
// Login state BEFORE any output (see Sideboard.php): CheckSession() only starts a session while headers are unsent.
CheckSession();
$lbLoggedIn = function_exists('LoggedInUser') && intval(LoggedInUser()) > 0;
$lbCardLang = function_exists('LoggedInUser') ? SWUSimAccountCardLanguage(LoggedInUser()) : null;
$lobbyID = preg_replace('/[^A-Za-z0-9_]/', '', strval($_GET['lobbyID'] ?? ''));
$matchId = preg_replace('/[^A-Za-z0-9_]/', '', strval($_GET['matchId'] ?? ''));
$seat    = intval($_GET['playerID'] ?? 0);
$authKey = strval($_GET['authKey'] ?? '');
if ($lobbyID === '' && ($matchId === '' || ($seat !== 1 && $seat !== 2))) { http_response_code(404); echo 'No such event.'; exit; }

require_once __DIR__ . '/../SharedUI/Render/ChatPanel.php';
require_once __DIR__ . '/../Core/ChatPolicy.php';
$scope = $matchId !== '' ? 'm:' . $matchId : 'l:' . $lobbyID;
$lbChatRefusal = ChatSendRefusal('SWUSim', ['viewerSeat' => $seat, 'isSpectator' => false, 'userId' => intval(LoggedInUser())], $scope);

require_once __DIR__ . '/../SharedUI/Render/SiteDef.php';
require_once __DIR__ . '/../SharedUI/Render/Head.php';
require_once __DIR__ . '/../SharedUI/Render/Header.php';
$lbDef = LoadSiteDef('SWUSim');
$lbDef['branding'] = ['headTitle' => 'Deckbuilder'] + $lbDef['branding'];
echo RenderHead($lbDef);
$icon = fn($a) => '/TCGEngine/Assets/Images/icons/SWU/' . $a . '.webp';
?>
<body>
<?= RenderHeader($lbDef) /* no menu bar: a nav link out of the build is a walk-out (Sideboard.php does the same) */ ?>
<div class="lb-row">
<?php /* The action bar sits ABOVE the chat in one sticky column, so Submit / Ready never scroll away (owner 2026-11-05).
         On a phone the column dissolves (display: contents) and the bar is the sticky strip at the bottom again. */ ?>
<div class="lb-side">
  <div class="lb-foot">
    <span class="lb-foot-count"><b id="lbFootCount">0</b>/30</span>
    <span class="lb-foot-timer" id="lbFootTimer"></span>
    <button id="lbSubmit" class="swu2-btn ch" type="button" disabled>Submit</button>
    <button id="lbReady" class="swu2-btn swu2-btn--primary ch" type="button" disabled>Ready</button>
    <span class="lb-status" id="lbStatus"></span>
    <?php if ($lobbyID !== ''): ?><a class="swu2-btn swu2-btn--quiet ch lb-back" href="../SharedUI/WaitingRoom.php?lobby=<?= rawurlencode($lobbyID) ?>">Back to the room</a><?php endif; ?>
  </div>
<?= RenderChatPanel(['mode' => 'chat', 'title' => 'CHAT', 'folderPath' => 'SWUSim']) ?>
</div>
<main class="lb-root" data-lobby-id="<?= htmlspecialchars($lobbyID) ?>" data-match-id="<?= htmlspecialchars($matchId) ?>" data-player-id="<?= $seat ?>">
  <div class="lb-top">
    <h2 class="lb-title"><?= $matchId !== '' ? 'Between games — your deck' : 'Build your deck' ?></h2>
    <span class="lb-timer" id="lbTimer" aria-live="polite"></span>
    <span class="lb-opp" id="lbOpp"></span>
  </div>
  <div class="seg lb-switch" role="radiogroup" aria-label="Show">
    <input class="seg__in u-vh" type="radio" name="lb-view" id="lb-view-pool" value="pool" checked>
    <label class="seg__opt ch" for="lb-view-pool">Pool</label>
    <input class="seg__in u-vh" type="radio" name="lb-view" id="lb-view-deck" value="deck">
    <label class="seg__opt ch" for="lb-view-deck"><span>Deck (<span id="lbSwitchCount">0</span>)</span></label>
  </div>
  <div class="lb-cols" data-view="pool">
    <section class="lb-pool" aria-label="Your pool">
      <div class="seg lb-tabs" role="radiogroup" aria-label="Pool">
        <input class="seg__in u-vh" type="radio" name="lb-tab" id="lb-tab-leaders" value="leaders">
        <label class="seg__opt ch" for="lb-tab-leaders"><span>Leaders (<span id="lbLeaderCount">0</span>)</span></label>
        <input class="seg__in u-vh" type="radio" name="lb-tab" id="lb-tab-bases" value="bases">
        <label class="seg__opt ch" for="lb-tab-bases"><span>Bases (<span id="lbBaseCount">0</span>)</span></label>
        <input class="seg__in u-vh" type="radio" name="lb-tab" id="lb-tab-cards" value="cards" checked>
        <label class="seg__opt ch" for="lb-tab-cards"><span>Cards (<span id="lbCardsLeft">0</span> left)</span></label>
      </div>
      <span class="inwrap ch lb-search"><input class="input" id="lbSearch" type="search" placeholder="Search name, trait, text…" aria-label="Search the pool"></span>
      <div class="lb-filters" role="group" aria-label="Filters">
        <?php foreach (['Vigilance', 'Command', 'Aggression', 'Cunning', 'Heroism', 'Villainy'] as $a): ?>
        <button type="button" class="btn lb-tog lb-tog--icon" data-f="aspect" data-v="<?= $a ?>" aria-pressed="false" title="<?= $a ?>"><img src="<?= $icon($a) ?>" alt="<?= $a ?>" width="24" height="24"></button>
        <?php endforeach; ?>
        <?php foreach ([['type', 'Unit'], ['type', 'Event'], ['type', 'Upgrade'], ['arena', 'Ground'], ['arena', 'Space'], ['cost', '0-2'], ['cost', '3-4'], ['cost', '5+']] as [$f, $v]): ?>
        <button type="button" class="btn lb-tog" data-f="<?= $f ?>" data-v="<?= $v ?>" aria-pressed="false"><?= $f === 'cost' ? 'Cost ' . $v : $v ?></button>
        <?php endforeach; ?>
      </div>
      <div class="lb-grid" id="lbPoolGrid"></div>
      <p class="lb-hint">Click a card to add one copy. Cards you have used up stay here, greyed out at ×0.</p>
    </section>
    <section class="lb-deck" aria-label="Your deck">
      <div class="lb-slots">
        <div role="button" tabindex="0" class="lb-slot" id="lbLeaderSlot" data-kind="leader" aria-label="Leader">Leader</div>
        <div role="button" tabindex="0" class="lb-slot" id="lbBaseSlot" data-kind="base" aria-label="Base">Base</div>
        <div class="lb-curve" id="lbCurve" aria-label="Cost curve"></div>
      </div>
      <div class="lb-bar"><span>Main deck <b id="lbDeckCount">0</b> / <span id="lbDeckMin">30</span> min <span id="lbLegal" class="lb-legal"></span></span>
        <span>Sideboard: <b id="lbSideCount">0</b></span></div>
      <div class="lb-grid lb-grid--deck" id="lbDeckGrid"></div>
      <p class="lb-hint">Click a deck card to send it back to the pool. Click the leader or base to change it.</p>
    </section>
  </div>
  <ul class="lb-errors" id="lbErrors" role="alert"></ul>
</main>
</div>
<?= SWUCardArtScript(false) ?>
<script>
  window.SWU_LOGGED_IN = <?= $lbLoggedIn ? 'true' : 'false' ?>;
  window.SWU_ACCOUNT_CARD_LANGUAGE = <?= $lbCardLang === null ? 'null' : json_encode($lbCardLang) ?>;
  window.LB_CONTEXT = <?= json_encode(['lobbyID' => $lobbyID, 'matchId' => $matchId, 'seat' => $seat, 'authKey' => $authKey,
                                       'canChat' => $lbChatRefusal === null, 'cannotChatReason' => $lbChatRefusal ?? '']) ?>;
</script>
<script src="../Core/SWUCardI18n.js?v=<?= filemtime(__DIR__ . '/../Core/SWUCardI18n.js') ?>"></script>
<script src="../Core/StyledDialog.js?v=<?= filemtime(__DIR__ . '/../Core/StyledDialog.js') ?>"></script>
<script src="Limited/ui/limited-builder.js?v=<?= filemtime(__DIR__ . '/Limited/ui/limited-builder.js') ?>"></script>
</body></html>
