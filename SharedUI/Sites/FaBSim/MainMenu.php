<?php
require_once __DIR__ . '/../../Render/PageEntry.php';
_SitePageSessionBootstrap();
$fabSite = LoadSiteDef('FaBSim');
echo RenderHead($fabSite);
?>
<body class="fab-menu-page">
<style>
body.fab-menu-page{
  --fab-menu-background:url('/TCGEngine/SharedUI/Sites/FaBSim/assets/talishar-lost-prince.webp');
  background:linear-gradient(180deg,#110d0a99,#13100d70 45%,#14110ece),var(--fab-menu-background) center 40% / cover no-repeat #14110e !important;
  color:#eee9df;
  display:flex;flex-direction:column;min-height:100vh;min-height:100dvh;
}
body.fab-menu-page > .disclaimer{margin-top:auto !important}
.fab-home{--gold:#d4b477;--muted:#a9b3b3;box-sizing:border-box;width:100%;max-width:1496px;margin:0 auto;padding:20px 28px 24px;font-family:Barlow,Segoe UI,sans-serif}
.fab-home-header{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:16px}
.fab-home-header h1{margin:0}
.fab-home-logo{display:block;width:clamp(116px,12vw,160px);height:auto}
.fab-home-links{display:flex;gap:8px;list-style:none;padding:0;margin:0}
.fab-home-links a{display:grid;place-items:center;width:40px;height:40px;box-sizing:border-box;border:1px solid #655638;border-radius:8px;background:#120c0ad1}
.fab-home-links a:hover{border-color:var(--gold)}
.fab-home-links .fab-support-link{width:auto;padding:0 10px;color:var(--gold);font-size:13px;font-weight:650;text-decoration:none;white-space:nowrap}
.fab-home-links img{width:23px;height:23px}
.fab-menu-grid{display:grid;grid-template-columns:minmax(280px,.8fr) minmax(0,1.2fr);gap:12px;align-items:stretch}
.fab-home .fab-panel{min-width:0;padding:14px 16px;border:1px solid #d8c7ab38;border-radius:13px;background:linear-gradient(145deg,#273537a8,#161f23b8);box-shadow:0 12px 32px #0005,inset 0 1px #ffffff12;-webkit-backdrop-filter:blur(12px) saturate(120%);backdrop-filter:blur(12px) saturate(120%)}
.fab-panel-header{display:flex;align-items:center;flex-wrap:wrap;gap:6px 12px;min-height:30px;margin-bottom:8px}
.fab-panel h2{margin:0;font-size:20px;line-height:1.2;color:#f1eade;font-weight:650}
.fab-bot-picker{display:flex;align-items:center;gap:8px;flex:0 1 244px;min-width:0;color:#eee5d8;font-size:13px;font-weight:600}
.fab-bot-picker label{white-space:nowrap}
.fab-bot-picker select{min-width:0;flex:1;width:auto;margin:0;padding:4px 24px 4px 8px;min-height:30px;color:#f1eade;background-color:#11191bdf;border:1px solid #bbc8c14d;border-radius:6px;font:inherit}
.fab-deck-input-label{position:absolute;width:1px;height:1px;overflow:hidden;clip-path:inset(50%);white-space:nowrap}
.fab-panel textarea{display:block;width:100%;box-sizing:border-box;resize:vertical;min-height:64px;height:86px;margin:0;padding:9px 11px;color:#efe9df;background:#11191be0;border:1px solid #bbc8c14d;border-radius:8px;font:13px/1.5 ui-monospace,Consolas,monospace}
.fab-panel textarea::placeholder{color:#a3b0b1;opacity:1}
.fab-home :is(button,a,textarea,select):focus-visible{outline:2px solid var(--gold);outline-offset:3px}
.fab-default-deck{display:flex;align-items:center;flex-wrap:wrap;gap:3px 8px;margin:0 0 7px;padding:5px 9px;border:1px solid #8c784c;border-radius:7px;background:#211e17b3;color:#e2d9ca;font-size:13px;transition:opacity 180ms ease}
.fab-default-deck strong,.fab-default-deck a{color:var(--gold)}
.fab-default-deck[inert]{opacity:0}
.fab-deck-note{display:flex;gap:5px;flex-wrap:wrap;color:#d0d8d5;font-size:11px}
.fab-deck-note span{padding:2px 6px;border:1px solid #c6d1ca44;border-radius:4px;background:#11191b70}
.fab-game-panel{display:flex;flex-direction:column}
.fab-modes{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;flex:1}
.fab-home .fab-panel button.fab-mode{width:100%;box-sizing:border-box;display:flex;align-items:center;gap:8px;text-align:left;white-space:normal;margin:0;padding:10px 12px;min-height:66px;background-color:#192426;background-image:linear-gradient(90deg,#11191bf5 0%,#11191bde 53%,#11191b4d 100%),var(--fab-mode-art);background-position:center,right var(--fab-mode-position);background-size:cover,max(65%,152px) auto;background-repeat:no-repeat;border:1px solid #687470;border-radius:8px;color:#f1eade;cursor:pointer;font-family:inherit;transition:border-color .15s}
.fab-home .fab-panel button.fab-mode-bot{--fab-mode-art:url('/TCGEngine/SharedUI/Sites/FaBSim/assets/data-doll.webp');--fab-mode-position:32%;border-color:#8d7549}
.fab-home .fab-panel button.fab-mode-upf{--fab-mode-art:url('/TCGEngine/SharedUI/Sites/FaBSim/assets/deathmatch-arena.webp');--fab-mode-position:53%}
.fab-home .fab-panel button.fab-mode:hover{border-color:var(--gold);transform:none}
.fab-mode strong{min-width:0;font-size:16px;font-weight:650}
.fab-mode strong,.fab-mode-arrow{text-shadow:0 1px 3px #11191b}
.fab-mode-arrow{flex-shrink:0;margin-left:auto;color:var(--gold);font-size:20px}
@media(min-width:761px) and (max-width:1000px){.fab-menu-grid{grid-template-columns:minmax(260px,.85fr) minmax(0,1.15fr)}}
@media(max-width:760px){
  body.fab-menu-page{--fab-menu-background:url('/TCGEngine/SharedUI/Sites/FaBSim/assets/talishar-lost-prince-mobile.webp')}
  .fab-home{padding:16px 18px 24px}.fab-menu-grid{grid-template-columns:1fr}
}
@media(max-width:480px){.fab-modes{grid-template-columns:1fr}}
@media(prefers-reduced-motion:reduce){.fab-home .fab-panel button.fab-mode,.fab-default-deck{transition:none}}
</style>
<main class="fab-home">
  <header class="fab-home-header">
    <h1><img class="fab-home-logo" src="/TCGEngine/SharedUI/Sites/FaBSim/assets/talishar-logo.webp" alt="Talishar" width="1080" height="483"></h1>
    <ul class="fab-home-links">
      <li><a class="fab-support-link" href="https://talishar.net/premium" target="_blank" rel="noopener">Support Us!</a></li>
      <?php foreach ($fabSite['navLinks'] as $link) echo _RenderNavLink($link); ?>
    </ul>
  </header>
  <div class="fab-menu-grid">
    <section class="fab-panel" aria-labelledby="fab-deck-title">
      <header class="fab-panel-header">
        <h2 id="fab-deck-title">Your deck</h2>
        <div class="fab-deck-note" aria-label="Supported deck formats"><span>Fabrary</span><span>Text list</span><span>JSON</span></div>
      </header>
      <label class="fab-deck-input-label" for="deck-input">Deck link or list</label>
      <div class="fab-default-deck" id="fab-default-deck">
        <strong>Young Fai</strong>
        <span>Leave the box empty to play it.</span>
        <a href="https://fabrary.net/decks/01M29JXFK960C5JW8YV9YQPBA9" target="_blank" rel="noopener">View on Fabrary</a>
      </div>
      <textarea id="deck-input" rows="3" spellcheck="false" placeholder="Paste a Fabrary link,&#10;a text deck list, or exported JSON."></textarea>
    </section>
    <section class="fab-panel fab-game-panel" aria-labelledby="fab-play-title">
      <header class="fab-panel-header">
        <h2 id="fab-play-title">Choose a game</h2>
        <div class="fab-bot-picker">
          <label for="fab-bot-profile">Bot opponent</label>
          <select id="fab-bot-profile"><option value="fai">Fai</option><option value="goldfish">Goldfish</option><option value="ira">Ira · Crouching Tiger</option><option value="boltyn">Boltyn · Light Warrior</option><option value="maxx">Maxx &middot; Armory Deck (60 cards)</option><option value="uzuri">Uzuri · Stealth</option><option value="arakni">Arakni · Contracts</option><option value="dromai">Dromai · Ashwing Swarm</option><option value="lexi">Lexi · Elemental Ranger</option><option value="prism">Prism · Illusionist</option><option value="levia">Levia · Shadow Brute</option><option value="professor">Professor Teklovossen</option></select>
        </div>
      </header>
      <div class="fab-modes">
        <button class="fab-mode fab-mode-bot" onclick="fabJoin()"><strong>Challenge a bot</strong><span class="fab-mode-arrow" aria-hidden="true">→</span></button>
        <button class="fab-mode fab-mode-upf" onclick="fabJoin(true)"><strong>Ultimate Pit Fight</strong><span class="fab-mode-arrow" aria-hidden="true">→</span></button>
      </div>
    </section>
  </div>
</main>
<script>
const fabRoot='FaBSim'; let fabLobby='';
const fabDeckInput=document.getElementById('deck-input');
const fabDefaultDeck=document.getElementById('fab-default-deck');
const fabDefaultDeckLink=fabDefaultDeck.querySelector('a');
function fabUpdateDefaultDeck(){
  const hasCustomDeck=fabDeckInput.value.trim()!=='';
  fabDefaultDeck.inert=hasCustomDeck;
  fabDefaultDeck.setAttribute('aria-hidden',String(hasCustomDeck));
}
fabDeckInput.addEventListener('input',fabUpdateDefaultDeck);
window.addEventListener('pageshow',fabUpdateDefaultDeck);
fabUpdateDefaultDeck();
function fabJoin(upf=false){
  const deck=fabDeckInput.value.trim()||fabDefaultDeckLink.href;
  const body=new URLSearchParams({rootName:fabRoot,deckLink:deck,game_type:'casual'});
  if(upf){
    body.set('createPrivate','1');
    body.set('format','upf');
  }else{
    const botProfile=document.getElementById('fab-bot-profile').value;
    if(botProfile==='goldfish') body.set('createGoldfish','1');
    else {body.set('format','bot');body.set('botProfile',botProfile);}
  }
  fetch('/TCGEngine/APIs/Lobbies/JoinQueue.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body})
    .then(r=>r.json()).then(data=>{if(data.error||data.success===false)throw new Error(data.error||data.message);fabLobby=data.lobbyID||'';if(upf){localStorage.setItem('tcg:lobbyAuth:'+fabLobby,JSON.stringify({authKey:data.authKey,ts:Date.now()}));location.href='/TCGEngine/SharedUI/Sites/FaBSim/WaitingRoom.php?lobby='+encodeURIComponent(fabLobby);return;}if(data.ready)fabOpen(data);else fabPoll(data.playerID,data.authKey);})
    .catch(e=>StyledAlert(e.message||'Unable to join queue.'));
}
function fabPoll(playerID,authKey){
  const body=new URLSearchParams({rootName:fabRoot,playerID:String(playerID),lobbyID:fabLobby,authKey:String(authKey||'')});
  fetch('/TCGEngine/APIs/Lobbies/PollLobbyUpdates.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body})
    .then(r=>r.json()).then(data=>{if(data.ready)fabOpen({...data,authKey:data.authKey||authKey});else setTimeout(()=>fabPoll(playerID,authKey),1500);})
    .catch(()=>setTimeout(()=>fabPoll(playerID,authKey),3000));
}
function fabOpen(data){
  const u=new URL('/TCGEngine/NextTurn.php',location.origin);u.searchParams.set('folderPath',fabRoot);u.searchParams.set('gameName',data.gameName);u.searchParams.set('playerID',data.playerID);if(data.authKey)u.searchParams.set('authKey',data.authKey);location.href=u;
}
</script>
<?php include_once __DIR__ . '/Disclaimer.php'; ?>
