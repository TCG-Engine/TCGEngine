<?php // SWUSim/Sideboard.php — between-games sideboard screen (card-image editor)
include_once __DIR__ . '/MatchFlow.php';
// LoggedInUser() is not otherwise defined on this standalone page — needed below for the card
// language wiring (Step 5, localized card art).
include_once __DIR__ . '/../AccountFiles/AccountSessionAPI.php';
include_once __DIR__ . '/GeneratedCode/GeneratedCardDictionaries.php'; // CardTitle/CardSubtitle
// The one seam that knows where SWU art lives and how it is named. This page is STANDALONE — it does
// not load Core's Card()/resolveCardImageID — so without it the art paths get hand-built relative to
// /TCGEngine/SWUSim/ and preview (mock_-prefixed) cards never resolve.
include_once __DIR__ . '/../AppCore/SWU/CardImagePath.php';
require_once __DIR__ . '/PlayerSettings.php';
// Login state MUST be resolved here, before any HTML is echoed below. LoggedInUser() -> CheckSession()
// only calls session_start() when !headers_sent() (AccountFiles/AccountSessionAPI.php), and by the
// time this page reaches the <script> globals near the bottom, ~5KB of static HTML has already
// flushed past output_buffering=4096 — headers are sent, so a session started only there would never
// see the login cookie. Call CheckSession() explicitly up front (this page starts no session of its
// own) and snapshot both values now; they are only ever PRINTED later, not recomputed.
CheckSession();
$sbLoggedIn = function_exists('LoggedInUser') && intval(LoggedInUser()) > 0;
$sbCardLang = function_exists('LoggedInUser') ? SWUSimAccountCardLanguage(LoggedInUser()) : null;
$matchId = preg_replace('/[^A-Za-z0-9_]/','', $_GET['matchId'] ?? '');
$seat    = intval($_GET['playerID'] ?? 0);
$m = SWUReadMatch($matchId);
if (!is_array($m) || ($seat!==1 && $seat!==2)) { http_response_code(404); echo 'No such match/seat.'; exit; }
// SWUSim Limited: between games the Limited deckbuilder IS the sideboard step — the pool on the left, leader and base free
// to change (docs/superpowers/specs/2026-11-04-swusim-limited-sealed-draft-design.md §3.4).
if (!empty($m['limited'])) {
  header('Location: ./LimitedBuilder.php?' . http_build_query(['matchId' => $matchId, 'playerID' => $seat, 'authKey' => strval($_GET['authKey'] ?? '')]));
  exit;
}
// A High Ground hill match the host ended (no result) — back to the room rather than sideboarding for a game that won't come.
if (($m['state'] ?? '') === 'abandoned' && strval($m['roomId'] ?? '') !== '') {
  header('Location: ../SharedUI/WaitingRoom.php?lobby=' . rawurlencode(strval($m['roomId'])));
  exit;
}
// A match that ended between games (a forfeit) has no next game to sideboard for: back to the last game's board, whose end
// screen shows the result and the way out (the room, a rematch, the menu).
if (($m['state'] ?? '') === 'complete' && !empty($m['games'])) {
  header('Location: ../NextTurn.php?' . http_build_query(['gameName' => strval($m['games'][count($m['games']) - 1]['gameName'] ?? ''),
    'playerID' => $seat, 'authKey' => strval($_GET['authKey'] ?? ''), 'folderPath' => 'SWUSim', 'viewerPerspective' => $seat]));
  exit;
}
// Seed from the deck this seat MOST RECENTLY PLAYED, falling back to the match-start list for the
// first sideboard of the match. Reading 'originalDeck' unconditionally meant the game-3 menu showed
// the game-1 configuration and discarded everything the player did before game 2.
$__p  = $m['players'][strval($seat)] ?? [];
$lastDeck = $__p['currentDeck'] ?? $__p['originalDeck'] ?? ['leader'=>'','base'=>'','mainDeck'=>[],'sideboard'=>[]];
// The two-step sideboard (owner, 2026-11-03): a list this seat already SUBMITTED this round (a reload, a second tab) is what
// the editor shows, and the page opens in its submitted / ready state.
$__sb = is_array($m['sideboard'][strval($seat)] ?? null) ? $m['sideboard'][strval($seat)] : [];
$deck = $__sb['deck'] ?? $lastDeck;
$sbState = ['submitted' => ($__sb['deck'] ?? null) !== null, 'ready' => !empty($__sb['ready']),
            'oppReady' => !empty($m['sideboard'][strval(3 - $seat)]['ready'])];
// A match from a room (a lobby room or High Ground) offers Return to Lobby; only the fact, never the room id — this page
// is not authenticated, and the id leads to the room's invite code. The forfeit's authenticated answer carries it.
$sbHasRoom = strval($m['roomId'] ?? '') !== '';

// Chat + the previous game's log, pinned left (owner, 2026-09-22). The panel is shared with the
// Waiting Room; only the mode and the scope differ.
require_once __DIR__ . '/../SharedUI/Render/ChatPanel.php';
require_once __DIR__ . '/../Core/ChatPolicy.php';
$sbAuthKey = strval($_GET['authKey'] ?? '');
// A sideboard seat is always an authenticated match participant, so this is effectively always
// allowed — asked through the policy anyway so this screen can never drift from the send endpoint.
$sbChatRefusal = ChatSendRefusal('SWUSim', ['viewerSeat' => $seat, 'isSpectator' => false,
                                            'userId' => intval(LoggedInUser())], 'm:' . $matchId);

$mainCounts = array_count_values($deck['mainDeck'] ?? []);
$sideCounts = array_count_values($deck['sideboard'] ?? []);

// id -> "Title - Subtitle" for tooltips.
$titleFor = function($id) {
    $t = CardTitle($id);
    if ($t === '' || $t === null) return $id;
    $s = CardSubtitle($id);
    return ($s !== '' && $s !== null) ? "$t - $s" : $t;
};
$titles = [];
foreach (array_merge(array_keys($mainCounts), array_keys($sideCounts), [$deck['leader'], $deck['base']]) as $id) {
    if ($id !== '' && !isset($titles[$id])) $titles[$id] = $titleFor($id);
}
?><?php
// The theme comes from the SITE, not from a private copy. This page carried its own
// self-contained cyan-on-navy CSS (--swu-cyan, Aptos/Bahnschrift) and so sat out the whole
// Petranaki redesign -- it 404s without a live match, so nobody opened it.
// RenderHead gives it the same font + stylesheet stack every other page gets; RenderHeader gives
// it the title plate, which is ALSO the scope hook (`body:has(.home-header)`) that the redesign's
// rules and the Petranaki chat skin key off.
// ⚠ The MENU BAR is deliberately NOT rendered: you are mid-match here, and a nav link out of a
// sideboard abandons the game.
require_once __DIR__ . '/../SharedUI/Render/SiteDef.php';
require_once __DIR__ . '/../SharedUI/Render/Head.php';
require_once __DIR__ . '/../SharedUI/Render/Header.php';
$sbDef = LoadSiteDef('SWUSim');
$sbDef['branding'] = ['headTitle' => 'Sideboard'] + $sbDef['branding'];
echo RenderHead($sbDef);
?>
<body>
<?= RenderHeader($sbDef) ?>
<div class="sb-row">
<?php /* ⚠ A PLAIN & — RenderChatPanel htmlspecialchars() the title, so passing "&amp;" prints "&amp;amp;". */ ?>
<?= RenderChatPanel(['mode' => 'chat+log', 'title' => 'GAME LOG & CHAT', 'folderPath' => 'SWUSim']) ?>
<div class="sb-main">
<h2>Sideboard — game <?= count($m['games'])+1 ?> of best-of-<?= intval($m['bestOf']) ?></h2>
<p class="hint">Click a Deck card to move one copy to your Sideboard. Click a Sideboard card to move it back. Submit your sideboard, then Ready — or Ready straight away to play the deck from last game. The next game starts when both players are ready; until then you can Cancel and keep editing.</p>

<div class="fixed">
  <div class="slot"><img data-card-id="<?= htmlspecialchars($deck['leader']) ?>" src="<?= htmlspecialchars(SWUCardImagePath($deck['leader'], 'card')) ?>" alt="Leader: <?= htmlspecialchars($titles[$deck['leader']] ?? $deck['leader']) ?>" title="<?= htmlspecialchars($titles[$deck['leader']] ?? $deck['leader']) ?>"></div>
  <div class="slot"><img data-card-id="<?= htmlspecialchars($deck['base']) ?>" src="<?= htmlspecialchars(SWUCardImagePath($deck['base'], 'card')) ?>" alt="Base: <?= htmlspecialchars($titles[$deck['base']] ?? $deck['base']) ?>" title="<?= htmlspecialchars($titles[$deck['base']] ?? $deck['base']) ?>"></div>
</div>

<div class="section">
  <h3>Deck <span class="ct" id="deckCount"></span></h3>
  <div class="grid" id="deckGrid"></div>
</div>
<div class="section">
  <h3>Sideboard <span class="ct" id="sideCount"></span></h3>
  <div class="grid" id="sideGrid"></div>
</div>

<div class="sb-actions">
  <button id="submit" class="swu2-btn ch" type="button">Submit Sideboard</button>
  <button id="ready" class="swu2-btn swu2-btn--primary ch" type="button">Ready for Game <?= count($m['games'])+1 ?></button>
  <button id="cancel" class="swu2-btn swu2-btn--quiet ch" type="button" hidden>Cancel</button>
  <span class="sb-status"><span id="status"></span><span id="oppStatus"></span></span>
  <span class="sb-leave">
<?php if ($sbHasRoom): ?>    <button id="leaveLobby" class="swu2-btn swu2-btn--quiet ch" type="button">Return to Lobby</button>
<?php endif; ?>    <button id="leaveMenu" class="swu2-btn swu2-btn--quiet ch" type="button">Return to Main Menu</button>
  </span>
</div>

<script>
var matchId=<?= json_encode($matchId) ?>, seat=<?= json_encode($seat) ?>, authKey=<?= json_encode($_GET['authKey'] ?? '') ?>;
// If this sideboard round already advanced (opponent submitted while we were away/refreshing),
// jump straight into the spawned game instead of showing a stale, un-submittable screen.
var alreadyAdvanced=<?= json_encode(($m['state'] ?? '') === 'in_progress' && !empty($m['games'])) ?>;
var advancedGameName=<?= json_encode(!empty($m['games']) ? strval($m['games'][count($m['games'])-1]['gameName']) : '') ?>;
var leader=<?= json_encode($deck['leader']) ?>, base=<?= json_encode($deck['base']) ?>;
var sbState=<?= json_encode($sbState) ?>;
var nextGameNo=<?= count($m['games'])+1 ?>;
var titles=<?= json_encode($titles, JSON_UNESCAPED_UNICODE) ?>;
</script>
<?= SWUCardArtScript(false) /* lite: SWUSim ids are already SET_NNN, so the UUID map is dead weight */ ?>
<script>
  window.SWU_LOGGED_IN = <?= $sbLoggedIn ? 'true' : 'false' ?>;
  window.SWU_ACCOUNT_CARD_LANGUAGE = <?= $sbCardLang === null ? 'null' : json_encode($sbCardLang) ?>;
  // The composer's visibility comes from the SAME seam SubmitChat.php enforces (Core/ChatPolicy.php).
  var SB_CAN_CHAT = <?= json_encode($sbChatRefusal === null) ?>;
  var SB_CANNOT_CHAT_REASON = <?= json_encode($sbChatRefusal ?? '') ?>;
</script>
<script src="../Core/SWUCardI18n.js?v=<?= filemtime(__DIR__ . '/../Core/SWUCardI18n.js') ?>"></script>
<script src="../Core/StyledDialog.js?v=<?= filemtime(__DIR__ . '/../Core/StyledDialog.js') ?>"></script>
<script>
var deck=<?= json_encode((object)array_map('intval',$mainCounts), JSON_FORCE_OBJECT) ?>;
var side=<?= json_encode((object)array_map('intval',$sideCounts), JSON_FORCE_OBJECT) ?>;
// The deck from the last game: what Ready plays when nothing is submitted (and what a Cancel falls back to).
var lastList={deck:<?= json_encode((object)array_map('intval',array_count_values($lastDeck['mainDeck'] ?? [])), JSON_FORCE_OBJECT) ?>,
              side:<?= json_encode((object)array_map('intval',array_count_values($lastDeck['sideboard'] ?? [])), JSON_FORCE_OBJECT) ?>};

function totalOf(m){ var n=0; for(var k in m) n+=m[k]; return n; }

// ── Zoomed card hover preview (self-contained; the Core hover system isn't on this page) ──
var sbPreview=null;
function ensureSbPreview(){
  if(sbPreview) return sbPreview;
  sbPreview=document.createElement('img'); sbPreview.id='sbPreview'; document.body.appendChild(sbPreview);
  return sbPreview;
}
function showSbPreview(id, ev){
  var p=ensureSbPreview(); p.src=window.swuCardArtUrl(id, 'card'); p.style.display='block'; positionSbPreview(ev);
}
function positionSbPreview(ev){
  if(!sbPreview || sbPreview.style.display!=='block') return;
  var pad=18, w=sbPreview.offsetWidth||300, h=sbPreview.offsetHeight||420;
  var x=ev.clientX+pad, y=ev.clientY+pad;
  if(x+w>window.innerWidth)  x=ev.clientX-w-pad;   // flip left near the right edge
  if(x<8) x=8;   // a phone is narrower than card + finger: keep it on screen
  if(y+h>window.innerHeight) y=window.innerHeight-h-8;
  if(y<8) y=8;
  sbPreview.style.left=x+'px'; sbPreview.style.top=y+'px';
}
function hideSbPreview(){ clearTimeout(sbPreviewTimer); sbPreviewTimer=0; if(sbPreview) sbPreview.style.display='none'; }
// The preview waits (owner, 2026-11-04: a click — above all a tap on a phone — left it open over the other cards). A mouse
// shows it after SB_PREVIEW_DELAY of hovering and a click hides it; a finger never gets it on a tap, only by holding the
// card for SB_PREVIEW_DELAY, and lifting the finger hides it without moving the card.
var SB_PREVIEW_DELAY=500, sbPreviewTimer=0, sbLongPressed=false;
function wireSbPreview(node, id){
  var last=null;
  var later=function(ev){ last={clientX:ev.clientX, clientY:ev.clientY}; clearTimeout(sbPreviewTimer);
    sbPreviewTimer=setTimeout(function(){ sbPreviewTimer=0; showSbPreview(id, last); if(ev.pointerType==='touch') sbLongPressed=true; }, SB_PREVIEW_DELAY); };
  node.addEventListener('pointerenter', function(ev){ if(ev.pointerType!=='touch') later(ev); });
  node.addEventListener('pointermove', function(ev){ if(ev.pointerType==='touch') return; last={clientX:ev.clientX, clientY:ev.clientY}; positionSbPreview(ev); });
  node.addEventListener('pointerleave', function(ev){ if(ev.pointerType!=='touch') hideSbPreview(); });
  node.addEventListener('pointerdown', function(ev){ if(ev.pointerType==='touch'){ sbLongPressed=false; later(ev); } });
  ['pointerup','pointercancel'].forEach(function(t){ node.addEventListener(t, function(ev){ if(ev.pointerType==='touch') hideSbPreview(); }); });
  node.addEventListener('click', hideSbPreview);
  node.addEventListener('contextmenu', function(ev){ if(ev.pointerType==='touch' || sbLongPressed) ev.preventDefault(); });   // Android's long-press menu
}
// A held finger is a look, not a move: the click that follows it must not move the card. True once per long press.
function sbTookLongPress(){ var was=sbLongPressed; sbLongPressed=false; return was; }

// Wire the fixed Leader / Base slots too (rendered as static <img> above).
(function(){
  document.querySelectorAll('.fixed .slot img').forEach(function(img){
    // Read the CardID from data, never back out of the src: the corpus URL is absolute and a
    // preview card's file is mock_-prefixed, so string-stripping the path cannot recover it.
    var id=img.getAttribute('data-card-id')||'';
    if(!id) return;
    wireSbPreview(img.parentNode, id);
  });
})();

function move(id, from, to){
  if(!from[id]) return;
  from[id]--; if(from[id]<=0) delete from[id];
  to[id]=(to[id]||0)+1;
  lastError='';
  render();
}

function renderGrid(el, map, from, to){
  el.innerHTML='';
  var ids=Object.keys(map).sort();
  if(ids.length===0){ el.innerHTML='<div class="empty">(empty)</div>'; return; }
  ids.forEach(function(id){
    var c=document.createElement('div'); c.className='card'; c.title=titles[id]||id;
    var img=document.createElement('img'); img.src=window.swuCardArtUrl(id, 'tile'); img.alt=titles[id]||id;
    c.appendChild(img);
    if(map[id]>1){ var q=document.createElement('div'); q.className='qty'; q.textContent=map[id]; c.appendChild(q); }
    c.onclick=function(){ if(sbTookLongPress() || sbState.ready) return; move(id, from, to); };   // a ready seat's list is locked: Cancel first
    // Zoomed hover preview — this standalone page doesn't load the Core ShowCardDetail system, so cards
    // were only readable via the tiny thumbnail + native title tooltip ("hover does not work in sideboard").
    wireSbPreview(c, id);
    el.appendChild(c);
  });
}

function render(){
  renderGrid(document.getElementById('deckGrid'), deck, deck, side);
  renderGrid(document.getElementById('sideGrid'), side, side, deck);
  document.getElementById('deckCount').textContent=totalOf(deck)+' cards';
  document.getElementById('sideCount').textContent=totalOf(side)+' cards';
  paint();
}

function buildText(){
  var s='Leader\n'+leader+'\nBase\n'+base+'\nDeck\n';
  Object.keys(deck).sort().forEach(function(id){ s+=deck[id]+' '+id+'\n'; });
  var sk=Object.keys(side).sort();
  if(sk.length){ s+='Sideboard\n'; sk.forEach(function(id){ s+=side[id]+' '+id+'\n'; }); }
  return s;
}

function go(next){ var u=new URL(window.location.origin+window.location.pathname.replace(/SWUSim\/Sideboard\.php$/,'NextTurn.php'));
  u.searchParams.set('gameName',next); u.searchParams.set('playerID',seat); u.searchParams.set('authKey',authKey);
  u.searchParams.set('folderPath','SWUSim'); u.searchParams.set('viewerPerspective',seat); window.location.replace(u.toString()); }

// ── Submit, Ready, Cancel (owner, 2026-11-03): three separate steps against SubmitSideboard.php's `action` ──
// `baseline` is the list Ready would play right now: the submitted one, else the deck from last game. The editor is
// "changed" when it differs from that.
function copyList(l){ return {deck:Object.assign({}, l.deck), side:Object.assign({}, l.side)}; }
function listKey(l){ var n=function(m){ var o={}; Object.keys(m).sort().forEach(function(k){ o[k]=m[k]; }); return o; };
  return JSON.stringify([n(l.deck), n(l.side)]); }
var baseline=copyList({deck:deck, side:side});   // the page opens on the submitted list, else last game's
function changed(){ return listKey({deck:deck, side:side})!==listKey(baseline); }
var busy=false, leaving=false, asking=false, lastError='';   // asking: a confirm is open — one dialog per click, not one per click   // an error stays up until the next action or edit — the poll repaints every 2s
function paint(){
  var ch=changed(), el=function(id){ return document.getElementById(id); };
  el('submit').disabled = busy || sbState.ready || (sbState.submitted && !ch);
  el('ready').hidden = sbState.ready;
  el('ready').disabled = busy;
  el('cancel').hidden = !(sbState.submitted || sbState.ready);
  el('cancel').disabled = busy;
  ['leaveLobby', 'leaveMenu'].forEach(function(id){ if(el(id)) el(id).disabled = busy || leaving; });
  document.body.classList.toggle('sb-is-ready', !!sbState.ready);
  el('status').textContent = lastError ? lastError : sbState.ready
      ? (sbState.oppReady ? 'Ready — starting game ' + nextGameNo + '…' : 'Ready — waiting for your opponent…')
    : ch ? (sbState.submitted ? 'Changes not submitted — your submitted sideboard still stands.' : 'Changes not submitted.')
    : sbState.submitted ? 'Sideboard submitted — Ready when you are.' : '';
  el('oppStatus').textContent = sbState.ready && sbState.oppReady ? '' : (sbState.oppReady ? 'Your opponent is ready.' : 'Your opponent is still sideboarding.');
}
function req(action, extra){
  var fd=new URLSearchParams(); fd.set('matchId',matchId); fd.set('playerID',seat); fd.set('authKey',authKey); fd.set('action',action);
  if(extra) Object.keys(extra).forEach(function(k){ fd.set(k, extra[k]); });
  return fetch('./SubmitSideboard.php',{method:'POST',body:fd}).then(function(r){ return r.json(); });
}
// Every answer carries this seat's state; a match that moved on (next game, a forfeit) takes the page with it.
function follow(j){
  if(!j || leaving) return leaving;
  if(j.matchOver && j.gameName){ leaving=true; go(j.gameName); return true; }
  if(j.nextGameName){ leaving=true; go(j.nextGameName); return true; }
  if(j.mine){ sbState.submitted=!!j.mine.submitted; sbState.ready=!!j.mine.ready; sbState.oppReady=!!j.opponentReady; }
  return false;
}
function act(action, extra, onOk){
  if(busy || leaving) return;
  busy=true; lastError=''; paint();
  req(action, extra).then(function(j){
    busy=false;
    if(follow(j)) return;
    lastError=(j && j.success) ? '' : 'Error: ' + ((j && j.message) || 'try again.');
    if(!lastError && onOk) onOk();
    paint();
  }).catch(function(){ busy=false; lastError='Could not reach the server — try again.'; paint(); });
}
document.getElementById('submit').onclick=function(){ act('save', {deck:buildText()}, function(){ baseline=copyList({deck:deck, side:side}); }); };
document.getElementById('ready').onclick=function(){
  // Ready plays the baseline, so unsubmitted edits would be lost: say so, and show what is actually played.
  var readyNow=function(){ act('ready', null, function(){ deck=copyList(baseline).deck; side=copyList(baseline).side; render(); }); };
  if(!changed()){ readyNow(); return; }
  if(asking) return;
  asking=true;
  StyledConfirm('Your changes are not submitted. Ready anyway with ' + (sbState.submitted ? 'your submitted sideboard' : 'the deck from last game') + '?',
                {title:'Changes not submitted', confirmLabel:'Ready anyway'}).then(function(ok){ asking=false; if(ok) readyNow(); });
};
// Cancel: back to editing, nothing submitted — the edits stay on screen, now as changes against last game's deck.
document.getElementById('cancel').onclick=function(){ act('cancel', null, function(){ baseline=copyList(lastList); }); };
// The waiting poll: the opponent's state, the countdown (the server readies an un-ready seat at the deadline), the next game.
(function poll(){
  if(busy || leaving){ setTimeout(poll, 2000); return; }   // a failed leave resumes it
  req('status').then(function(j){ if(!busy){ follow(j); paint(); } setTimeout(poll, 2000); })
               .catch(function(){ setTimeout(poll, 2000); });   // a transient 500 under load must not strand the page
})();
// Return to Lobby / Return to Main Menu: leaving mid-series forfeits the Bo3, as on the end screen — ask first, forfeit,
// then go where the end screen's buttons go.
function leaveMatch(to){
  if(busy || leaving || asking) return;
  asking=true;
  StyledConfirm('Leave now? This forfeits the best-of-3.', {title:'Forfeit the match', danger:true, confirmLabel:'Forfeit & leave'}).then(function(ok){
    asking=false;
    if(!ok || busy || leaving) return;
    leaving=true; lastError=''; paint();
    var failed=function(msg){ leaving=false; lastError=msg; paint(); };
    req('forfeit').then(function(j){
      if(!j || !j.success){ failed('Error: ' + ((j && j.message) || 'try again.')); return; }
      if(to==='menu'){ window.location.href='../SharedUI/MainMenu.php'; return; }
      if(j.highGround && j.roomId){   // High Ground: back to the hill's room, as its end screen does
        try { localStorage.setItem('tcg:hg:lastRoom', j.roomId); } catch(e) {}
        window.location.replace('../SharedUI/WaitingRoom.php?lobby=' + encodeURIComponent(j.roomId)); return;
      }
      // A lobby room: the end screen's own Return to Lobby re-seats this player, with its key handed over in localStorage
      // (never the URL). If the room is gone, the end screen says so and offers the menu.
      var f=new FormData(); f.append('gameName', j.gameName); f.append('playerID', seat); f.append('authKey', authKey); f.append('action', 'lobby');
      fetch('./EndScreenAction.php', {method:'POST', body:f}).then(function(r){ return r.json(); }).then(function(k){
        var g=k && k.go;
        if(g && g.kind==='room' && g.lobbyID && g.authKey){
          try { localStorage.setItem('tcg:lobbyAuth:' + g.lobbyID, JSON.stringify({authKey:g.authKey, ts:Date.now()})); } catch(e) {}
          window.location.replace('../SharedUI/WaitingRoom.php?lobby=' + encodeURIComponent(g.lobbyID)); return;
        }
        go(j.gameName);
      }).catch(function(){ go(j.gameName); });
    }).catch(function(){ failed('Could not reach the server — try again.'); });
  });
}
if(document.getElementById('leaveLobby')) document.getElementById('leaveLobby').onclick=function(){ leaveMatch('lobby'); };
document.getElementById('leaveMenu').onclick=function(){ leaveMatch('menu'); };
if(alreadyAdvanced && advancedGameName){ go(advancedGameName); } else { render(); }
// High Ground: the host may end the match while someone is still choosing cards — follow them back to the room.
// Any match: the opponent may forfeit it between games — back to the last game's board, whose end screen has the result.
(function watchEnded(){
  var fd=new URLSearchParams(); fd.set('matchId',matchId); fd.set('playerID',seat); fd.set('authKey',authKey); fd.set('statusOnly','1');
  fetch('./SubmitSideboard.php',{method:'POST',body:fd}).then(function(r){ return r.json(); }).then(function(j){
    if (leaving) return;   // the poll (or a leave) is already taking the page somewhere: one navigation, not two
    if (j && j.roomLobbyID) { leaving=true; window.location.replace('../SharedUI/WaitingRoom.php?lobby=' + encodeURIComponent(j.roomLobbyID)); return; }
    if (j && j.matchOver && j.gameName) { leaving=true; go(j.gameName); return; }
    setTimeout(watchEnded, 4000);
  }).catch(function(){ setTimeout(watchEnded, 4000); });
})();

// ── Chat + the log of the game just played ───────────────────────────────────────────────────────
// ⚠ Card ids render as PLAIN NAMES. This page is standalone and does not load Core's Card()
// renderer, so a raw [[SOR_014]] would reach the reader as an id.
// ⚠ The names come from the ENDPOINT, not from this page's `titles`: `titles` only covers cards in
// YOUR deck, so the opponent's whole board — most of what a log is about — would stay as raw ids.
// `titles` remains the fallback, then the id itself.
// Split a log entry into {text, ts}, keeping its [[SET_NNN]] tokens INTACT -- the panel turns those
// into hoverable elements, given the name map.
//
// ⚠ Field 2 is '@<microtime>' on entries written since 2026-09-26 and the TEXT on anything saved
// before that. The stamp is what lets the panel interleave this log with the chat instead of
// showing one block after the other; a legacy line has no ts and simply keeps its arrival order.
function sbLogLine(entry){
  var parts = entry.split('|');
  if (parts.length < 3) return { text: entry, ts: NaN };
  var stamped = parts[2].charAt(0) === '@' && !isNaN(parseFloat(parts[2].slice(1)));
  return { text: parts.slice(stamped ? 3 : 2).join('|'),
           ts: stamped ? parseFloat(parts[2].slice(1)) : NaN };
}

// Hover a card named in the log and get the same preview the deck grid gives. Delegated, because
// the log arrives after this runs and grows as the panel polls.
document.addEventListener('mouseover', function(ev){
  var el = ev.target.closest && ev.target.closest('.tcgc-card[data-card-id]');
  if (el) showSbPreview(el.getAttribute('data-card-id'), ev);
});
document.addEventListener('mousemove', function(ev){
  if (ev.target.closest && ev.target.closest('.tcgc-card[data-card-id]')) positionSbPreview(ev);
});
document.addEventListener('mouseout', function(ev){
  if (ev.target.closest && ev.target.closest('.tcgc-card[data-card-id]')) hideSbPreview();
});

window.TCGChatPanel.attach({
  scope: { matchId: matchId },
  playerID: seat,
  authKey: authKey,
  folderPath: 'SWUSim',
  canSend: SB_CAN_CHAT,
  cannotSendReason: SB_CANNOT_CHAT_REASON
});

// The log is fetched ONCE — the previous game is over and its log cannot change.
fetch('./GetGameLog.php?matchId=' + encodeURIComponent(matchId) +
      '&playerID=' + encodeURIComponent(seat) + '&authKey=' + encodeURIComponent(authKey))
  .then(function(r){ return r.json(); })
  .then(function(d){
    if(!d || !d.lines || !d.lines.length) return;
    // d.names covers every card in the lines THIS SEAT may see -- including the opponent's, which
    // `titles` (built from your own deck) cannot. Merged so a token is never left as a raw id.
    var cards = Object.assign({}, titles, d.names || {});
    window.TCGChatPanel.appendLog('GAME ' + d.gameNumber + ' LOG',
      d.lines.map(sbLogLine), { cards: cards });
  })
  .catch(function(){ /* no log is a normal state, not an error to put in front of the player */ });
</script></div></div></body></html>
