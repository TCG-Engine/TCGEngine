<?php // SWUSim/LimitedDraft.php — the Limited Draft pick screen.
// Spec: docs/superpowers/specs/2026-11-04-swusim-limited-sealed-draft-design.md §3.2; mockup
// docs/superpowers/specs/2026-11-04-limited-mockups/draft-pick.html (owner: "lgtm. actually perfect").
//   ?lobbyID=<id>   the seat key is the room key the waiting room keeps in localStorage (tcg:lobbyAuth:<lobbyID>)
// Everything — your pack, your picks, the pod, the clock — comes from SWUSim/LimitedDraftAction.php and is drawn by
// SWUSim/Limited/ui/limited-draft.js. This file is only the shell, the chat, and the script globals.
include_once __DIR__ . '/../AccountFiles/AccountSessionAPI.php';
include_once __DIR__ . '/../AppCore/SWU/CardImagePath.php';
require_once __DIR__ . '/PlayerSettings.php';
CheckSession();   // before any output (see Sideboard.php)
$ldLoggedIn = function_exists('LoggedInUser') && intval(LoggedInUser()) > 0;
$ldCardLang = function_exists('LoggedInUser') ? SWUSimAccountCardLanguage(LoggedInUser()) : null;
$lobbyID = preg_replace('/[^A-Za-z0-9_]/', '', strval($_GET['lobbyID'] ?? ''));
if ($lobbyID === '') { http_response_code(404); echo 'No such draft.'; exit; }

require_once __DIR__ . '/../SharedUI/Render/ChatPanel.php';
require_once __DIR__ . '/../Core/ChatPolicy.php';
$ldChatRefusal = ChatSendRefusal('SWUSim', ['viewerSeat' => 0, 'isSpectator' => false, 'userId' => intval(LoggedInUser())], 'l:' . $lobbyID);

require_once __DIR__ . '/../SharedUI/Render/SiteDef.php';
require_once __DIR__ . '/../SharedUI/Render/Head.php';
require_once __DIR__ . '/../SharedUI/Render/Header.php';
$ldDef = LoadSiteDef('SWUSim');
$ldDef['branding'] = ['headTitle' => 'Draft'] + $ldDef['branding'];
echo RenderHead($ldDef);
?>
<body>
<?= RenderHeader($ldDef) /* no menu bar: a nav link out of a draft is a walk-out */ ?>
<div class="ld-row">
<?= RenderChatPanel(['mode' => 'chat', 'title' => 'CHAT', 'folderPath' => 'SWUSim']) ?>
<main class="ld-root" data-lobby-id="<?= htmlspecialchars($lobbyID) ?>">
  <div class="ld-top">
    <h2 class="ld-title" id="ldTitle">Draft</h2>
    <span class="ld-dir" id="ldDir"></span>
    <span class="ld-clock" id="ldClock" aria-live="polite"></span>
  </div>
  <p class="ld-status" id="ldStatus"></p>
  <div class="ld-kicks" id="ldKicks"></div>
  <ol class="ld-pod" id="ldPod" aria-label="The pod, in seating order"></ol>
  <section class="ld-podleads" id="ldPodLeads" aria-label="Leaders drafted">
    <button type="button" class="btn ld-podleads-toggle" id="ldPodLeadsToggle" aria-expanded="false">Leaders drafted</button>
    <h3 class="ld-h ld-podleads-h">Leaders drafted</h3>
    <div class="ld-podleads-body" id="ldPodLeadsBody"></div>
  </section>
  <div class="ld-cols">
    <section class="ld-packwrap" aria-label="Your pack">
      <div class="ld-pack" id="ldPack"></div>
      <div class="ld-confirm">
        <button id="ldPick" class="swu2-btn swu2-btn--primary ch" type="button" disabled>Pick</button>
        <span class="ld-hint">Click a card to select it, then confirm — a misclick never costs a pick.</span>
      </div>
    </section>
    <aside class="ld-side" id="ldSide" aria-label="Your picks">
      <button type="button" class="btn ld-side-toggle" id="ldSideToggle" aria-expanded="false">Your picks (<span id="ldPickCount">0</span>)</button>
      <div class="ld-side-body" id="ldSideBody">
        <h3 class="ld-h">Your picks (<span id="ldPickCount2">0</span>)</h3>
        <p class="ld-leaders" id="ldLeaders"></p>
        <div class="ld-lean" id="ldLean" aria-label="Aspect lean"></div>
        <p class="ld-leaning" id="ldLeaning"></p>
        <button type="button" class="btn ld-all-toggle" id="ldAllToggle" aria-expanded="false">Show all picks</button>
        <div class="ld-all" id="ldAll" hidden></div>
        <p class="ld-hint">Only you can see your picks.</p>
      </div>
    </aside>
  </div>
  <p class="ld-error" id="ldError" role="alert"></p>
</main>
</div>
<?= SWUCardArtScript(false) ?>
<script>
  window.SWU_LOGGED_IN = <?= $ldLoggedIn ? 'true' : 'false' ?>;
  window.SWU_ACCOUNT_CARD_LANGUAGE = <?= $ldCardLang === null ? 'null' : json_encode($ldCardLang) ?>;
  window.LD_CONTEXT = <?= json_encode(['lobbyID' => $lobbyID, 'canChat' => $ldChatRefusal === null, 'cannotChatReason' => $ldChatRefusal ?? '']) ?>;
</script>
<script src="../Core/SWUCardI18n.js?v=<?= filemtime(__DIR__ . '/../Core/SWUCardI18n.js') ?>"></script>
<script src="../Core/SWUSimCardPreviewDelay.js?v=<?= filemtime(__DIR__ . '/../Core/SWUSimCardPreviewDelay.js') ?>"></script>
<script src="Limited/ui/limited-draft.js?v=<?= filemtime(__DIR__ . '/Limited/ui/limited-draft.js') ?>"></script>
</body></html>
