<?php // GrandArchiveSim/Sideboard.php — between-games sideboard screen (card-image editor).
// Per GA tournament rules, players move cards between Main Deck, Material Deck, and Sideboard. Champion/
// Regalia cards belong in the material deck and cost 3 sideboard points; all others go in main (1 pt).
include_once __DIR__ . '/../Core/NetworkingLibraries.php';
include_once __DIR__ . '/../Core/HTTPLibraries.php';
include_once __DIR__ . '/Match.php';
include_once __DIR__ . '/GeneratedCode/GeneratedCardDictionaries.php'; // CardName + $typeData

$matchId = preg_replace('/[^A-Za-z0-9_]/', '', $_GET['matchId'] ?? '');
$seat    = intval($_GET['playerID'] ?? 0);
$authKey = strval($_GET['authKey'] ?? '');
$m = GAReadMatch($matchId);
if (!is_array($m) || ($seat !== 1 && $seat !== 2)
    || !hash_equals(strval($m['players'][strval($seat)]['authKey'] ?? ''), $authKey)) {
    http_response_code(404); echo 'Invalid match / seat / auth.'; exit;
}
$deck = $m['players'][strval($seat)]['originalDeck'] ?? ['material' => [], 'mainDeck' => [], 'sideboard' => []];

$matCounts  = array_count_values($deck['material']  ?? []);
$mainCounts = array_count_values($deck['mainDeck']  ?? []);
$sideCounts = array_count_values($deck['sideboard'] ?? []);

// Per-card metadata for every id in the pool: display name + whether it is a material-type card
// (Champion/Regalia → lives in material, worth 3 sideboard points).
$names = []; $isMat = [];
foreach (array_keys($matCounts + $mainCounts + $sideCounts) as $id) {
    $nm = CardName($id);
    $names[$id] = ($nm === '' || $nm === null) ? $id : $nm;
    $t = strtoupper(strval($typeData[$id] ?? ''));
    $isMat[$id] = (strpos($t, 'CHAMPION') !== false || strpos($t, 'REGALIA') !== false);
}
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sideboard — GrandArchive</title>
<style>
  :root { --bg:#0f1216; --panel:#171b22; --panel2:#1d2430; --rim:rgba(211,168,76,0.55);
          --gold:#d3a84c; --gold-soft:#c9b183; --text:rgba(232,235,240,0.94); --muted:rgba(150,160,175,0.7);
          --bad:#e06666; --ok:#7bc47f; --font:"Segoe UI",system-ui,sans-serif; }
  * { box-sizing:border-box; }
  body { margin:0; padding:26px; min-height:100vh; color:var(--text); font-family:var(--font);
         background: radial-gradient(1000px 520px at 50% -10%, rgba(90,72,32,0.22), transparent 60%),
                     linear-gradient(180deg,#12161c,var(--bg)); }
  h2 { margin:0 0 4px; font-size:21px; letter-spacing:0.02em; color:var(--gold); }
  .hint { color:var(--muted); margin:0 0 18px; font-size:13px; max-width:760px; }
  .section { margin-bottom:20px; }
  .section h3 { margin:0 0 9px; font-size:14px; text-transform:uppercase; letter-spacing:0.1em;
                color:var(--gold-soft); display:flex; align-items:center; gap:10px; }
  .section h3 .ct { color:var(--muted); font-size:12px; letter-spacing:0.04em; text-transform:none; }
  .section h3 .ct.bad { color:var(--bad); font-weight:600; }
  .grid { display:flex; flex-wrap:wrap; gap:9px; min-height:132px; align-content:flex-start;
          border:1px solid rgba(211,168,76,0.20); border-radius:9px; padding:13px;
          background: linear-gradient(180deg, var(--panel2), var(--panel));
          box-shadow: inset 0 0 22px rgba(0,0,0,0.4); }
  .card { position:relative; width:96px; cursor:pointer; transition:transform .08s; }
  .card:hover { transform:translateY(-3px); }
  .card img { width:100%; border-radius:6px; display:block; box-shadow:0 2px 6px rgba(0,0,0,.5); }
  .card.mat img { border:1px solid rgba(211,168,76,0.6); }
  .card .qty { position:absolute; bottom:5px; right:5px; min-width:20px; height:20px; line-height:20px;
               padding:0 5px; border-radius:11px; background:rgba(10,12,16,0.95); color:#fff; font-size:13px;
               font-weight:bold; text-align:center; border:1px solid rgba(211,168,76,0.5); }
  .empty { color:var(--muted); font-style:italic; align-self:center; }
  #submit { position:relative; border:1px solid var(--rim); border-radius:7px; background:linear-gradient(180deg,#2a2113,#1c160c);
            padding:11px 26px; cursor:pointer; font-weight:700; font-size:14px; text-transform:uppercase; letter-spacing:0.1em;
            color:var(--gold); transition:filter .15s,transform .1s; }
  #submit:not(:disabled):hover { filter:brightness(1.3); color:#fff; }
  #submit:not(:disabled):active { transform:translateY(1px); }
  #submit:disabled { opacity:.42; cursor:default; }
  #status { margin-left:14px; color:var(--gold-soft); font-size:13px; }
  .reset { margin-left:10px; background:none; border:1px solid rgba(150,160,175,0.3); color:var(--muted);
           border-radius:6px; padding:9px 14px; cursor:pointer; font-size:12px; text-transform:uppercase; letter-spacing:0.08em; }
  .reset:hover { color:var(--text); border-color:rgba(150,160,175,0.6); }
</style></head>
<body>
<h2>Sideboard — game <?= count($m['games']) + 1 ?> of best-of-<?= intval($m['bestOf']) ?></h2>
<p class="hint">Click a <b>Main Deck</b> or <b>Material</b> card to move one copy to your Sideboard. Click a
<b>Sideboard</b> card to send it back to its deck (Champions &amp; Regalia return to Material). Sideboard is
capped at 15 cards and 15 points (Champion/Regalia = 3 pts each). Submit when ready — the next game starts
once both players are in.</p>

<div class="section"><h3>Material <span class="ct" id="matCount"></span></h3><div class="grid" id="matGrid"></div></div>
<div class="section"><h3>Main Deck <span class="ct" id="mainCount"></span></h3><div class="grid" id="mainGrid"></div></div>
<div class="section"><h3>Sideboard <span class="ct" id="sideCount"></span></h3><div class="grid" id="sideGrid"></div></div>

<div style="margin-top:6px;">
  <button id="submit">Submit &amp; Ready</button>
  <button class="reset" id="reset" type="button">Reset</button>
  <span id="status"></span>
</div>

<script>
var matchId=<?= json_encode($matchId) ?>, seat=<?= json_encode($seat) ?>, authKey=<?= json_encode($authKey) ?>;
var names=<?= json_encode($names, JSON_UNESCAPED_UNICODE) ?>;
var isMat=<?= json_encode((object)$isMat, JSON_FORCE_OBJECT) ?>;
// Starting composition (mutable working copies + a pristine snapshot for Reset).
var START={ mat:<?= json_encode((object)array_map('intval',$matCounts), JSON_FORCE_OBJECT) ?>,
            main:<?= json_encode((object)array_map('intval',$mainCounts), JSON_FORCE_OBJECT) ?>,
            side:<?= json_encode((object)array_map('intval',$sideCounts), JSON_FORCE_OBJECT) ?> };
var mat={}, main={}, side={};
function clone(o){ var r={}; for(var k in o) r[k]=o[k]; return r; }
function resetDecks(){ mat=clone(START.mat); main=clone(START.main); side=clone(START.side); render(); }
// If this round already advanced (opponent submitted while we were away), jump into the spawned game.
var advanced=<?= json_encode(($m['state'] ?? '') === 'in_progress' && !empty($m['games'])) ?>;
var advancedGame=<?= json_encode(!empty($m['games']) ? strval($m['games'][count($m['games'])-1]['gameName']) : '') ?>;

function total(m){ var n=0; for(var k in m) n+=m[k]; return n; }
function sidePoints(){ var p=0; for(var k in side) p += side[k]*(isMat[k]?3:1); return p; }
function move(id, from, to){ if(!from[id]) return; from[id]--; if(from[id]<=0) delete from[id]; to[id]=(to[id]||0)+1; render(); }

function renderGrid(el, map, onClick){
  el.innerHTML='';
  var ids=Object.keys(map).sort(function(a,b){ return (names[a]||a).localeCompare(names[b]||b); });
  if(ids.length===0){ el.innerHTML='<div class="empty">(empty)</div>'; return; }
  ids.forEach(function(id){
    var c=document.createElement('div'); c.className='card'+(isMat[id]?' mat':''); c.title=names[id]||id;
    var img=document.createElement('img'); img.src='./concat/'+id+'.webp'; img.alt=id; c.appendChild(img);
    if(map[id]>1){ var q=document.createElement('div'); q.className='qty'; q.textContent=map[id]; c.appendChild(q); }
    c.onclick=function(){ if(submitting) return; onClick(id); };
    el.appendChild(c);
  });
}
function setCount(el, txt, bad){ el.textContent=txt; el.className='ct'+(bad?' bad':''); }

function render(){
  renderGrid(document.getElementById('matGrid'),  mat,  function(id){ move(id, mat,  side); });
  renderGrid(document.getElementById('mainGrid'), main, function(id){ move(id, main, side); });
  renderGrid(document.getElementById('sideGrid'), side, function(id){ move(id, side, isMat[id]?mat:main); });
  var mt=total(mat), mn=total(main), sc=total(side), sp=sidePoints();
  setCount(document.getElementById('matCount'),  mt+' / 12', mt>12);
  setCount(document.getElementById('mainCount'), mn+' cards (min 60)', mn<60);
  setCount(document.getElementById('sideCount'), sc+' / 15 cards · '+sp+' / 15 pts', sc>15 || sp>15);
  var legal = mt<=12 && mn>=60 && sc<=15 && sp<=15;
  document.getElementById('submit').disabled = submitting || !legal;
}

var submitting=false;
function flat(m){ var a=[]; Object.keys(m).forEach(function(id){ for(var i=0;i<m[id];i++) a.push(id); }); return a; }
function go(next){
  var u=new URL(window.location.origin + window.location.pathname.replace(/GrandArchiveSim\/Sideboard\.php$/,'NextTurn.php'));
  u.searchParams.set('gameName',next); u.searchParams.set('playerID',seat);
  u.searchParams.set('authKey',authKey); u.searchParams.set('folderPath','GrandArchiveSim');
  window.location.replace(u.toString());
}
function send(){
  var fd=new URLSearchParams();
  fd.set('matchId',matchId); fd.set('playerID',seat); fd.set('authKey',authKey);
  fd.set('material', JSON.stringify(flat(mat)));
  fd.set('mainDeck', JSON.stringify(flat(main)));
  fd.set('sideboard', JSON.stringify(flat(side)));
  return fetch('./SubmitSideboard.php',{method:'POST',body:fd}).then(function(r){ return r.json(); });
}
document.getElementById('submit').onclick=function(){
  send().then(function(j){
    if(!j.success){ document.getElementById('status').textContent='Error: '+(j.message||'submit failed'); return; }
    submitting=true; document.getElementById('submit').disabled=true;
    document.getElementById('status').textContent='Submitted — waiting for opponent…';
    if(j.nextGameName){ go(j.nextGameName); } else { poll(); }
  }).catch(function(){ document.getElementById('status').textContent='Network error.'; });
};
function poll(){ send().then(function(j){ if(j&&j.nextGameName){ go(j.nextGameName); } else { setTimeout(poll,2000); } }); }
document.getElementById('reset').onclick=function(){ if(!submitting) resetDecks(); };

if(advanced && advancedGame){ go(advancedGame); } else { resetDecks(); }
</script></body></html>
