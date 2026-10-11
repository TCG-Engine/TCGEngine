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
// Seed from the deck this seat MOST RECENTLY PLAYED, falling back to the match-start list for the
// first sideboard of the match. Reading 'originalDeck' unconditionally meant the game-3 menu showed
// the game-1 configuration and discarded everything the player did before game 2.
$__p  = $m['players'][strval($seat)] ?? [];
$deck = $__p['currentDeck'] ?? $__p['originalDeck'] ?? ['leader'=>'','base'=>'','mainDeck'=>[],'sideboard'=>[]];

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
<p class="hint">Click a Deck card to move one copy to your Sideboard. Click a Sideboard card to move it back. Then submit — the next game starts when both players are ready.</p>

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
  <button id="submit" class="swu2-btn swu2-btn--primary ch" type="button">Submit &amp; Ready</button> <span id="status"></span>
</div>

<script>
var matchId=<?= json_encode($matchId) ?>, seat=<?= json_encode($seat) ?>, authKey=<?= json_encode($_GET['authKey'] ?? '') ?>;
// If this sideboard round already advanced (opponent submitted while we were away/refreshing),
// jump straight into the spawned game instead of showing a stale, un-submittable screen.
var alreadyAdvanced=<?= json_encode(($m['state'] ?? '') === 'in_progress' && !empty($m['games'])) ?>;
var advancedGameName=<?= json_encode(!empty($m['games']) ? strval($m['games'][count($m['games'])-1]['gameName']) : '') ?>;
var leader=<?= json_encode($deck['leader']) ?>, base=<?= json_encode($deck['base']) ?>;
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
<script>
var deck=<?= json_encode((object)array_map('intval',$mainCounts), JSON_FORCE_OBJECT) ?>;
var side=<?= json_encode((object)array_map('intval',$sideCounts), JSON_FORCE_OBJECT) ?>;

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
  if(y+h>window.innerHeight) y=window.innerHeight-h-8;
  if(y<8) y=8;
  sbPreview.style.left=x+'px'; sbPreview.style.top=y+'px';
}
function hideSbPreview(){ if(sbPreview) sbPreview.style.display='none'; }

// Wire the fixed Leader / Base slots too (rendered as static <img> above).
(function(){
  document.querySelectorAll('.fixed .slot img').forEach(function(img){
    // Read the CardID from data, never back out of the src: the corpus URL is absolute and a
    // preview card's file is mock_-prefixed, so string-stripping the path cannot recover it.
    var id=img.getAttribute('data-card-id')||'';
    if(!id) return;
    img.parentNode.onmouseenter=function(ev){ showSbPreview(id, ev); };
    img.parentNode.onmousemove=function(ev){ positionSbPreview(ev); };
    img.parentNode.onmouseleave=hideSbPreview;
  });
})();

function move(id, from, to){
  if(!from[id]) return;
  from[id]--; if(from[id]<=0) delete from[id];
  to[id]=(to[id]||0)+1;
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
    c.onclick=function(){ if(submitting) return; move(id, from, to); };
    // Zoomed hover preview — this standalone page doesn't load the Core ShowCardDetail system, so cards
    // were only readable via the tiny thumbnail + native title tooltip ("hover does not work in sideboard").
    c.onmouseenter=function(ev){ showSbPreview(id, ev); };
    c.onmousemove=function(ev){ positionSbPreview(ev); };
    c.onmouseleave=hideSbPreview;
    el.appendChild(c);
  });
}

function render(){
  renderGrid(document.getElementById('deckGrid'), deck, deck, side);
  renderGrid(document.getElementById('sideGrid'), side, side, deck);
  document.getElementById('deckCount').textContent=totalOf(deck)+' cards';
  document.getElementById('sideCount').textContent=totalOf(side)+' cards';
}

function buildText(){
  var s='Leader\n'+leader+'\nBase\n'+base+'\nDeck\n';
  Object.keys(deck).sort().forEach(function(id){ s+=deck[id]+' '+id+'\n'; });
  var sk=Object.keys(side).sort();
  if(sk.length){ s+='Sideboard\n'; sk.forEach(function(id){ s+=side[id]+' '+id+'\n'; }); }
  return s;
}

var submitting=false;
function go(next){ var u=new URL(window.location.origin+window.location.pathname.replace(/SWUSim\/Sideboard\.php$/,'NextTurn.php'));
  u.searchParams.set('gameName',next); u.searchParams.set('playerID',seat); u.searchParams.set('authKey',authKey);
  u.searchParams.set('folderPath','SWUSim'); u.searchParams.set('viewerPerspective',seat); window.location.replace(u.toString()); }
function send(){ var fd=new URLSearchParams(); fd.set('matchId',matchId); fd.set('playerID',seat); fd.set('authKey',authKey); fd.set('deck',buildText());
  return fetch('./SubmitSideboard.php',{method:'POST',body:fd}).then(r=>r.json()); }
document.getElementById('submit').onclick=function(){
  send().then(j=>{
    if(!j.success){ document.getElementById('status').textContent='Error: '+j.message; return; }
    submitting=true; document.getElementById('submit').disabled=true;
    document.getElementById('status').textContent='Submitted — waiting for opponent…';
    if(j.nextGameName){ go(j.nextGameName); } else { poll(); }
  }).catch(function(){ // transient error/500 on submit — fall into polling (poll re-submits the deck), don't strand
    submitting=true; document.getElementById('submit').disabled=true;
    document.getElementById('status').textContent='Submitted — waiting for opponent…';
    poll();
  });
};
function poll(){ // re-submit is a no-op (first-submit-wins) but returns nextGameName once both are in / timeout fires.
  // A rejected request (transient 500 / non-JSON under load) MUST reschedule — otherwise the waiting player hangs forever.
  send().then(j=>{ if(j&&j.nextGameName){go(j.nextGameName);} else { setTimeout(poll,2000);} })
        .catch(function(){ setTimeout(poll,2000); });
}
if(alreadyAdvanced && advancedGameName){ go(advancedGameName); } else { render(); }

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
