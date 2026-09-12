<?php
include_once './MenuBar.php';
include_once '../../../AccountFiles/AccountSessionAPI.php';
include_once 'Header.php';
?>
<style>
.home-header{display:none}
body{background:radial-gradient(ellipse at 85% 0,rgba(136,77,35,.18),transparent 48%),#101719;color:#eee9df}
.fab-home{--gold:#d4b477;--muted:#a9b3b3;max-width:1440px;margin:0 auto;padding:80px 28px 24px;font-family:Barlow,Segoe UI,sans-serif}
.fab-hero{display:flex;justify-content:space-between;align-items:end;gap:16px;margin-bottom:20px}
.fab-home .fab-eyebrow{margin:0 0 12px;color:var(--gold);font-size:12px;font-weight:700;letter-spacing:.2em;text-transform:uppercase}
.fab-hero h1{margin:0;color:#f5eee0;font-size:32px;font-weight:700;text-transform:none;line-height:1.08;letter-spacing:-.035em}
.fab-hero p:last-child{margin:8px 0 0;color:var(--muted);font-size:15px;line-height:1.5}
.fab-hero-mark{color:var(--gold);border:1px solid #655638;border-radius:50%;width:76px;height:76px;display:grid;place-items:center;font:italic 30px Georgia;flex-shrink:0;background:radial-gradient(circle,#393123,#161d1e)}
.fab-menu-grid{display:grid;grid-template-columns:minmax(280px,.8fr) minmax(0,1.2fr);gap:16px;align-items:stretch}
.fab-panel{min-width:0;padding:20px;border:1px solid #364142;border-radius:16px;background:linear-gradient(145deg,#202a2c,#182123);box-shadow:0 14px 40px #0002}
.fab-panel h2{margin:0 0 10px;font-size:21px;color:#f1eade;font-weight:650}
.fab-panel p{color:var(--muted);line-height:1.55;margin:0 0 14px;font-size:15px}
.fab-panel label{display:block;color:#e8dfce;font-size:14px;font-weight:600;margin-bottom:10px}
.fab-panel textarea{display:block;width:100%;box-sizing:border-box;resize:vertical;min-height:130px;height:150px;margin:0;padding:16px;color:#efe9df;background:#111a1c;border:1px solid #455252;border-radius:10px;font:14px/1.65 ui-monospace,Consolas,monospace}
.fab-panel textarea::placeholder{color:#7e9092;opacity:1}
.fab-home :is(button,a,textarea):focus-visible{outline:2px solid var(--gold);outline-offset:4px}
.fab-deck-note{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px;color:#96a5a6;font-size:12px}
.fab-deck-note span{padding:4px 9px;border:1px solid #354445;border-radius:5px}
.fab-modes{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
.fab-home .fab-panel button.fab-mode{width:100%;box-sizing:border-box;display:flex;align-items:center;gap:12px;text-align:left;white-space:normal;padding:14px;min-height:104px;background:#192426;border:1px solid #3c4b4c;border-radius:11px;color:#f1eade;cursor:pointer;font-family:inherit;transition:background .15s,border-color .15s}
.fab-home .fab-panel button.fab-mode:hover{background:#283537;border-color:#a68d60}
.fab-home .fab-panel button.fab-mode.featured{background:linear-gradient(115deg,#403727,#2a2c27);border-color:#8d7549}
.fab-home .fab-panel button.fab-mode.featured:hover{background:#49402e;border-color:var(--gold)}
.fab-mode-icon{display:grid;place-items:center;width:40px;height:40px;flex-shrink:0;border:1px solid #647071;border-radius:9px;font:20px Georgia;color:var(--gold)}
.fab-mode>span:nth-child(2){min-width:0;flex:1}.fab-mode strong{display:block;font-size:18px;font-weight:600}
.fab-mode small{display:block;margin-top:5px;color:#b3bebc;font:13px/1.4 Barlow,Segoe UI,sans-serif}
.fab-mode-arrow{flex-shrink:0;margin-left:auto;color:var(--gold);font-size:23px}
.fab-home .fab-upf-note{font-size:13px;margin:16px 0 0;color:#99a9a9}
.fab-builder{grid-column:1/-1;display:flex;align-items:center;justify-content:space-between;gap:20px;padding:16px 20px;background:#182022;border:1px solid #344142;border-radius:14px}
.fab-builder h2{font-size:20px;margin:0 0 7px;color:#eee4d1}
.fab-builder p{margin:0;color:var(--muted);font-size:15px;line-height:1.5}
.fab-builder a{flex-shrink:0;border:1px solid #6d634e;padding:12px 18px;border-radius:8px;color:#e3ca98;text-decoration:none;font-weight:600}
.fab-builder a:hover{background:#30312a;border-color:var(--gold)}
.fab-builder details{margin-top:12px;color:#d4b477;font-size:14px}.fab-builder summary{cursor:pointer}.fab-builder textarea{display:block;width:100%;min-width:240px;box-sizing:border-box;margin:12px 0;padding:12px;background:#111a1c;color:#eee9df;border:1px solid #455252;border-radius:8px}.fab-builder button{margin-top:4px}.fab-builder summary:focus-visible{outline:2px solid var(--gold);outline-offset:4px}
.fab-home .fab-credit{font-size:12px;line-height:1.6;color:#7e9294;margin:14px 0 0;text-align:center}
@media(min-width:761px) and (max-width:1000px){.fab-menu-grid{grid-template-columns:minmax(260px,.85fr) minmax(0,1.15fr)}.fab-mode-icon,.fab-mode-arrow{display:none}.fab-home .fab-panel button.fab-mode{min-height:110px}}
@media(max-width:760px){.fab-home{padding:72px 18px 28px}.fab-menu-grid{grid-template-columns:1fr}.fab-hero{margin-bottom:16px}.fab-hero-mark{display:none}.fab-panel{padding:18px}.fab-builder{flex-direction:column;align-items:start;padding:22px;gap:18px}.fab-builder a{box-sizing:border-box;text-align:center;width:100%}}
@media(max-width:480px){.fab-modes{grid-template-columns:1fr}.fab-home .fab-panel button.fab-mode{min-height:72px}.fab-builder textarea{min-width:0}}
@media(prefers-reduced-motion:reduce){.fab-home .fab-panel button.fab-mode{transition:none}}
</style>
<main class="fab-home">
  <header class="fab-hero">
    <div><p class="fab-eyebrow">FaBSim · Flesh and Blood</p><h1>Play Flesh and Blood</h1></div>
  </header>
  <div class="fab-menu-grid">
    <section class="fab-panel" aria-labelledby="fab-deck-title">
      <h2 id="fab-deck-title">Your deck</h2>
      <p>Bring a deck from Fabrary or FaBDB, or paste an exported deck list.</p>
      <label for="deck-input">Deck link or list</label>
      <textarea id="deck-input" rows="5" spellcheck="false" placeholder="Paste a Fabrary or FaBDB link,&#10;a text deck list, or exported JSON."></textarea>
      <div class="fab-deck-note" aria-label="Supported deck formats"><span>Fabrary</span><span>FaBDB</span><span>Text list</span><span>JSON</span></div>
    </section>
    <section class="fab-panel" aria-labelledby="fab-play-title">
      <h2 id="fab-play-title">Choose a game</h2><p>Use your deck to start playing.</p>
      <label for="fab-bot-profile">Bot opponent</label><select id="fab-bot-profile" style="width:100%;padding:8px;margin:6px 0 10px;background:#172428;color:#eee;border:1px solid #56605a;border-radius:6px"><option value="fai">Fai</option><option value="ira">Ira · Crouching Tiger</option><option value="professor">Professor Teklovossen</option></select>
      <div class="fab-modes">
        <button class="fab-mode featured" onclick="fabJoin(false,false,document.getElementById('fab-bot-profile').value)"><span class="fab-mode-icon" aria-hidden="true">B</span><span><strong>Challenge a bot</strong><small>1v1 with your selected opponent.</small></span><span class="fab-mode-arrow" aria-hidden="true">→</span></button>
        <button class="fab-mode" onclick="fabJoin(false)"><span class="fab-mode-icon" aria-hidden="true">2</span><span><strong>Find a match</strong><small>Queue for a 1v1 game against another player.</small></span><span class="fab-mode-arrow" aria-hidden="true">→</span></button>
        <button class="fab-mode" onclick="fabJoin(false,true)"><span class="fab-mode-icon" aria-hidden="true">4</span><span><strong>Ultimate Pit Fight</strong><small>Host a four-player table with friends or bots.</small></span><span class="fab-mode-arrow" aria-hidden="true">→</span></button>
        <button class="fab-mode" onclick="fabJoin(true)"><span class="fab-mode-icon" aria-hidden="true">1</span><span><strong>Goldfish</strong><small>Practice your deck against a passive opponent.</small></span><span class="fab-mode-arrow" aria-hidden="true">→</span></button>
      </div>
      <p class="fab-upf-note">UPF: young hero · 40-card deck · up to two copies per pitch.</p>
    </section>
    <section class="fab-builder" aria-labelledby="fab-builder-title">
      <div><h2 id="fab-builder-title">A new deck starts here</h2><p>Build a deck or import an existing list with FaBDeck.</p><details><summary>Import a deck list</summary><form action="/TCGEngine/FaBDeck/CreateDeck.php" method="get"><label for="fab-import-deck">Deck link or list to import</label><textarea id="fab-import-deck" name="deckLink" rows="3" placeholder="Paste a deck to import"></textarea><button type="submit">Import into builder</button></form></details></div>
      <a href="/TCGEngine/FaBDeck/CreateDeck.php">Open deck builder <span aria-hidden="true">↗</span></a>
    </section>
  </div>
  <p class="fab-credit">Fan-made, for the love of the game. Card data from The Fab Cube. Flesh and Blood and card artwork belong to Legend Story Studios.</p>
</main>
<script>
const fabRoot='FaBSim'; let fabLobby='';
function fabJoin(goldfish,upf=false,botProfile=false){
  const deck=document.getElementById('deck-input').value.trim();
  if(!deck){StyledAlert('Paste a deck first.');return;}
  const body=new URLSearchParams({rootName:fabRoot,deckLink:deck,game_type:'casual'});
  if(goldfish) body.set('createGoldfish','1');
  if(botProfile){body.set('format','bot');body.set('botProfile',botProfile===true?'fai':botProfile);}
  if(upf){body.set('createPrivate','1');body.set('format','upf');}
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
<?php include_once './Disclaimer.php'; ?>
