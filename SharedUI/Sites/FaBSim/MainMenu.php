<?php
include_once './MenuBar.php';
include_once '../../../AccountFiles/AccountSessionAPI.php';
include_once 'Header.php';
?>
<style>
.fab-home{max-width:1050px;margin:30px auto;padding:0 18px;display:grid;grid-template-columns:1fr 1fr;gap:22px}.fab-panel{padding:24px;border:1px solid rgba(211,169,95,.35);border-radius:14px;background:rgba(18,13,10,.92);color:#f3e6d2}.fab-panel textarea,.fab-panel input{width:100%;box-sizing:border-box;margin:8px 0 14px;padding:11px;background:#0e0a08;color:#f3e6d2;border:1px solid #705432;border-radius:7px}.fab-panel button,.fab-panel a.fab-button{display:inline-block;margin:4px;padding:10px 16px;border:1px solid #d0a35b;border-radius:7px;background:#5a351f;color:#fff;text-decoration:none;cursor:pointer}@media(max-width:760px){.fab-home{grid-template-columns:1fr}}
</style>
<main class="fab-home">
  <section class="fab-panel">
    <h2>Play FaBSim</h2>
    <p>Paste a public Fabrary/FaBDB URL, exported JSON, or a text deck list.</p>
    <textarea id="deck-input" rows="10" placeholder="Hero&#10;1 Ira, Crimson Haze&#10;&#10;Weapons&#10;2 Harmonized Kodachi&#10;&#10;Deck&#10;3 Whelming Gustwave (Red)"></textarea>
    <button onclick="fabJoin(false)">Find Match</button>
    <button onclick="fabJoin(true)">Goldfish</button>
  </section>
  <section class="fab-panel">
    <h2>FaBDeck</h2>
    <p>Create a deck in the shared visual builder. FaBDeck and FaBSim use the same functional card IDs and image corpus.</p>
    <form action="/TCGEngine/FaBDeck/CreateDeck.php" method="get">
      <textarea name="deckLink" rows="8" placeholder="Optional: paste a deck to import"></textarea>
      <button type="submit">Create / Import Deck</button>
    </form>
    <p><small>Card data: the-fab-cube/flesh-and-blood-cards. Flesh and Blood and card artwork belong to Legend Story Studios.</small></p>
  </section>
</main>
<script>
const fabRoot='FaBSim'; let fabLobby='';
function fabJoin(goldfish){
  const deck=document.getElementById('deck-input').value.trim();
  if(!deck){StyledAlert('Paste a deck first.');return;}
  const body=new URLSearchParams({rootName:fabRoot,deckLink:deck,game_type:'casual'});
  if(goldfish) body.set('createGoldfish','1');
  fetch('/TCGEngine/APIs/Lobbies/JoinQueue.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body})
    .then(r=>r.json()).then(data=>{if(data.error)throw new Error(data.error);fabLobby=data.lobbyID||'';if(data.ready)fabOpen(data);else fabPoll(data.playerID,data.authKey);})
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
