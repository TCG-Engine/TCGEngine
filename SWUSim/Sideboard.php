<?php // SWUSim/Sideboard.php — between-games sideboard screen (card-image editor)
include_once __DIR__ . '/MatchFlow.php';
include_once __DIR__ . '/GeneratedCode/GeneratedCardDictionaries.php'; // CardTitle/CardSubtitle
$matchId = preg_replace('/[^A-Za-z0-9_]/','', $_GET['matchId'] ?? '');
$seat    = intval($_GET['playerID'] ?? 0);
$m = SWUReadMatch($matchId);
if (!is_array($m) || ($seat!==1 && $seat!==2)) { http_response_code(404); echo 'No such match/seat.'; exit; }
$deck = $m['players'][strval($seat)]['originalDeck'] ?? ['leader'=>'','base'=>'','mainDeck'=>[],'sideboard'=>[]];

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
?><!doctype html><html><head><meta charset="utf-8"><title>Sideboard</title>
<style>
  /* SWUSim HUD aesthetic — cyan-on-navy, chamfered panels/buttons. Self-contained
     (this is a standalone page, so the in-game CSS vars aren't available here). */
  :root {
    --swu-bg:        #0b0f14;
    --swu-rim:       rgba(140,210,255,0.85);
    --swu-fill:      rgba(20,42,70,0.95);
    --swu-cyan:      rgba(205,238,255,0.98);
    --swu-cyan-soft: rgba(160,200,235,0.78);
    --swu-font-ui:    "Aptos","Segoe UI Variable","Trebuchet MS",sans-serif;
    --swu-font-label: "Bahnschrift","Aptos Display","Franklin Gothic Medium",sans-serif;
  }
  body {
    margin:0; padding:28px; min-height:100vh; box-sizing:border-box;
    background:
      radial-gradient(1100px 560px at 50% -12%, rgba(46,98,150,0.28), transparent 62%),
      linear-gradient(180deg, #0c1622, var(--swu-bg));
    color: rgba(225,238,250,0.92); font-family: var(--swu-font-ui);
  }
  h2 {
    margin:0 0 6px; font-family: var(--swu-font-label);
    text-transform:uppercase; letter-spacing:0.14em; font-size:22px; font-weight:700;
    color: var(--swu-cyan); text-shadow:0 0 12px rgba(120,200,255,0.35);
  }
  .hint { color: var(--swu-cyan-soft); margin:0 0 20px; font-size:13px; }
  .fixed { display:flex; gap:16px; align-items:flex-end; margin-bottom:18px; flex-wrap:wrap; }
  .fixed .slot { text-align:center; }
  .fixed .slot img { height:96px; border-radius:6px; display:block;
                     border:1px solid rgba(120,200,255,0.25); box-shadow:0 0 10px rgba(0,0,0,.5); }
  .fixed .slot .lbl { font-family:var(--swu-font-label); text-transform:uppercase; letter-spacing:0.12em;
                      font-size:11px; color: var(--swu-cyan-soft); margin-top:6px; }
  .section { margin-bottom:24px; }
  .section h3 { margin:0 0 10px; font-family:var(--swu-font-label); text-transform:uppercase; letter-spacing:0.12em;
                font-size:15px; font-weight:700; color: rgba(195,228,255,0.95); display:flex; align-items:center; gap:8px; }
  .section h3 .ct { color: rgba(150,185,220,0.65); font-weight:normal; letter-spacing:0.06em; font-size:12px; }
  .grid { display:flex; flex-wrap:wrap; gap:10px; min-height:150px; align-content:flex-start;
          border:1px solid rgba(120,200,255,0.28); border-radius:8px; padding:14px;
          background: linear-gradient(180deg, rgba(18,34,54,0.62), rgba(12,22,34,0.62));
          box-shadow: inset 0 0 24px rgba(8,18,30,0.6); }
  .card { position:relative; width:104px; cursor:pointer; transition:transform .08s; }
  .card:hover { transform:translateY(-3px); }
  .card img { width:100%; border-radius:6px; display:block; box-shadow:0 2px 6px rgba(0,0,0,.5); }
  .card .qty { position:absolute; bottom:6px; right:6px; min-width:20px; height:20px; line-height:20px;
               padding:0 5px; border-radius:11px; background:rgba(11,22,34,0.95); color:#fff; font-size:13px; font-weight:bold;
               text-align:center; border:1px solid rgba(120,200,255,0.45); box-shadow:0 1px 3px rgba(0,0,0,.6); }
  .empty { color: rgba(120,160,200,0.55); font-style:italic; align-self:center; }
  /* Chamfered HUD button — ::before = cyan rim, ::after = flat fill, text on top. */
  #submit {
    position:relative; z-index:0; isolation:isolate; border:0; border-radius:0; background:transparent; box-shadow:none;
    padding:11px 26px; cursor:pointer; font-family:var(--swu-font-label); font-weight:700; font-size:14px;
    text-transform:uppercase; letter-spacing:0.12em; color: var(--swu-cyan);
    text-shadow:0 0 6px rgba(120,200,255,0.5); filter:drop-shadow(0 0 5px rgba(110,190,255,0.45));
    transition: filter 150ms, color 150ms, transform 110ms;
  }
  #submit::before { content:''; position:absolute; inset:0; z-index:-2; background: var(--swu-rim);
    clip-path: polygon(8px 0, 100% 0, 100% calc(100% - 8px), calc(100% - 8px) 100%, 0 100%, 0 8px); }
  #submit::after { content:''; position:absolute; inset:1.5px; z-index:-1; background: var(--swu-fill);
    clip-path: polygon(7px 0, 100% 0, 100% calc(100% - 7px), calc(100% - 7px) 100%, 0 100%, 0 7px); }
  #submit:not(:disabled):hover { color:#fff; filter:drop-shadow(0 0 10px rgba(125,205,255,0.65)); transform:translateY(-1px); }
  #submit:not(:disabled):hover::before { background: rgba(180,228,255,1); }
  #submit:not(:disabled):active { transform:translateY(1px); filter:drop-shadow(0 0 4px rgba(110,190,255,0.4)); }
  #submit:disabled { opacity:.4; filter:none; transform:none; cursor:default; color: rgba(200,225,245,0.7); }
  #submit:disabled::before { background: rgba(120,200,255,0.30); }
  #status { margin-left:14px; color: var(--swu-cyan-soft); font-family:var(--swu-font-label); letter-spacing:0.06em; font-size:13px; }
</style></head>
<body>
<h2>Sideboard — game <?= count($m['games'])+1 ?> of best-of-<?= intval($m['bestOf']) ?></h2>
<p class="hint">Click a Deck card to move one copy to your Sideboard. Click a Sideboard card to move it back. Then submit — the next game starts when both players are ready.</p>

<div class="fixed">
  <div class="slot"><img src="./concat/<?= htmlspecialchars($deck['leader']) ?>.webp" title="<?= htmlspecialchars($titles[$deck['leader']] ?? $deck['leader']) ?>"><div class="lbl">Leader</div></div>
  <div class="slot"><img src="./concat/<?= htmlspecialchars($deck['base']) ?>.webp" title="<?= htmlspecialchars($titles[$deck['base']] ?? $deck['base']) ?>"><div class="lbl">Base</div></div>
</div>

<div class="section">
  <h3>Deck <span class="ct" id="deckCount"></span></h3>
  <div class="grid" id="deckGrid"></div>
</div>
<div class="section">
  <h3>Sideboard <span class="ct" id="sideCount"></span></h3>
  <div class="grid" id="sideGrid"></div>
</div>

<div style="margin-top:4px;">
  <button id="submit">Submit &amp; Ready</button> <span id="status"></span>
</div>

<script>
var matchId=<?= json_encode($matchId) ?>, seat=<?= json_encode($seat) ?>, authKey=<?= json_encode($_GET['authKey'] ?? '') ?>;
// If this sideboard round already advanced (opponent submitted while we were away/refreshing),
// jump straight into the spawned game instead of showing a stale, un-submittable screen.
var alreadyAdvanced=<?= json_encode(($m['state'] ?? '') === 'in_progress' && !empty($m['games'])) ?>;
var advancedGameName=<?= json_encode(!empty($m['games']) ? strval($m['games'][count($m['games'])-1]['gameName']) : '') ?>;
var leader=<?= json_encode($deck['leader']) ?>, base=<?= json_encode($deck['base']) ?>;
var titles=<?= json_encode($titles, JSON_UNESCAPED_UNICODE) ?>;
var deck=<?= json_encode((object)array_map('intval',$mainCounts), JSON_FORCE_OBJECT) ?>;
var side=<?= json_encode((object)array_map('intval',$sideCounts), JSON_FORCE_OBJECT) ?>;

function totalOf(m){ var n=0; for(var k in m) n+=m[k]; return n; }

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
    var img=document.createElement('img'); img.src='./concat/'+id+'.webp'; img.alt=id;
    c.appendChild(img);
    if(map[id]>1){ var q=document.createElement('div'); q.className='qty'; q.textContent=map[id]; c.appendChild(q); }
    c.onclick=function(){ if(submitting) return; move(id, from, to); };
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
  });
};
function poll(){ // re-submit is a no-op (first-submit-wins) but returns nextGameName once both are in / timeout fires
  send().then(j=>{ if(j&&j.nextGameName){go(j.nextGameName);} else { setTimeout(poll,2000);} });
}
if(alreadyAdvanced && advancedGameName){ go(advancedGameName); } else { render(); }
</script></body></html>
