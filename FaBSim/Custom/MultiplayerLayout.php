<style>
#fab-upf-board{display:none}.fab-upf-active #fab-duel-layout{display:none}
.fab-upf-active #fab-upf-board{display:flex;flex:1;min-height:0;flex-direction:column;position:relative;z-index:40;overflow:auto;color:#eee9de;box-sizing:border-box}
.fab-upf-status{margin:0;color:#dcc997;font:600 12px/1.5 system-ui}
#fab-upf-board .fab-upf-inspect{font:600 12px system-ui;border:1px solid #b28c4555;border-radius:6px;background:#292d30;color:#eee4cf;padding:7px 11px;cursor:pointer}
#fab-upf-board .fab-upf-inspect:hover{background:#b89552;color:#10191e}
#fab-upf-board .fab-upf-inspect:focus-visible{outline:2px solid #f6d68b;outline-offset:3px}
.fab-upf-seat{min-width:0;padding:12px;box-sizing:border-box}
.fab-upf-seat-header{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:10px}.fab-upf-seat h2{margin:0;font:700 14px system-ui;letter-spacing:.03em}.fab-upf-badges{font:600 11px system-ui;color:#eac778;margin-top:3px}.fab-upf-summary{font:500 11px system-ui;color:#a6b9b8;margin:0 0 12px;line-height:1.7}
.fab-upf-zones{display:flex;flex-wrap:wrap;gap:10px;align-items:flex-start}.fab-upf-zone{min-width:50px;max-width:100%;padding:6px;border:1px solid #9eb6ae18;border-radius:7px;background:#00000012;box-sizing:border-box}.fab-upf-zone h3{font:600 10px system-ui;letter-spacing:.06em;text-transform:uppercase;color:#91a9a7;margin:0 0 6px}.fab-upf-zone [id$=Wrapper]{position:relative!important;overflow:visible!important;max-width:100%}.fab-upf-zone [data-mzid]{position:relative!important}.fab-upf-zone>div{overflow:auto;max-width:100%}.fab-upf-zone [id$=Hand]{flex-wrap:wrap!important}
.fab-upf-seat.is-summary .fab-upf-zone{border:0;background:none;padding:0}.fab-upf-seat.is-summary .fab-upf-zone:not([data-zone=Hero]):not([data-zone=Weapons]):not([data-zone=Equipment]):not([data-zone=Arena]){display:none}.fab-upf-seat.is-summary .fab-upf-zones{gap:8px}.fab-upf-seat.is-summary .fab-upf-zone[data-empty=true]{display:none}
.fab-upf-seat.is-mine{min-height:46vh;padding:16px 20px}.fab-upf-seat.is-mine h2{font-size:17px}.fab-upf-seat.is-mine .fab-upf-zones{gap:12px}.fab-upf-seat.is-mine [data-zone=Hand]{flex-basis:100%;order:20;background:#0a181b55;border-color:#9eb6ae20;padding:12px}
.fab-upf-zone[data-zone=Health],.fab-upf-zone[data-zone=Resources],.fab-upf-zone[data-zone=Chi],.fab-upf-zone[data-zone=ActionPoints]{font:700 20px system-ui;min-width:70px;background:#0b171c70}.fab-upf-seat[hidden],.fab-upf-zone[hidden]{display:none!important}
.fab-upf-panel{position:fixed;z-index:150;left:15vw;top:24vh;width:min(850px,75vw);max-width:calc(100vw - 20px);max-height:65vh;border:1px solid #ad9056;border-radius:12px;background:#112128fa;box-shadow:0 22px 70px #000a;overflow:hidden;color:#eee9de}.fab-upf-panel[hidden]{display:none}.fab-upf-panel header{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 14px;background:#23343a;cursor:grab;touch-action:none;user-select:none}.fab-upf-panel header:active{cursor:grabbing}.fab-upf-panel h2{font:700 14px system-ui;margin:0}.fab-upf-panel header small{font:11px system-ui;color:#a7bab8}.fab-upf-panel button{background:#142329;color:#eee;border:1px solid #9eb6ae55;border-radius:5px;padding:5px 9px;cursor:pointer}
.fab-upf-panel-body{padding:16px;overflow:auto;max-height:calc(65vh - 65px);box-sizing:border-box}.fab-upf-panel [id$=Wrapper]{overflow:visible!important}.fab-upf-panel [data-mzid]{position:relative!important}.fab-upf-empty{color:#94aaa8;font:13px system-ui}
@media(min-width:1100px){
  .fab-upf-opponents{min-height:22vh}.fab-upf-seat.is-mine{min-height:49vh}.fab-upf-seat.is-mine .fab-upf-summary{display:none}
  .fab-upf-seat.is-mine .fab-upf-zones{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));align-items:stretch}
  .is-mine [data-zone=Equipment]{grid-column:1/4;grid-row:1/3}.is-mine [data-zone=Hero]{grid-column:4/6;grid-row:1}.is-mine [data-zone=Weapons]{grid-column:6/9;grid-row:1}
  .is-mine [data-zone=Arena]{grid-column:4/9;grid-row:2}.is-mine [data-zone=Deck]{grid-column:9/11;grid-row:1}.is-mine [data-zone=Graveyard]{grid-column:11/13;grid-row:1}
  .is-mine [data-zone=Arsenal]{grid-column:9/11;grid-row:2}.is-mine [data-zone=Banish]{grid-column:11/13;grid-row:2}
  .is-mine [data-zone=Health]{grid-column:1/3;grid-row:3}.is-mine [data-zone=Resources]{grid-column:3/5;grid-row:3}.is-mine [data-zone=Chi]{grid-column:5/7;grid-row:3}.is-mine [data-zone=ActionPoints]{grid-column:7/9;grid-row:3}.is-mine [data-zone=Pitch]{grid-column:9/13;grid-row:3}
  .is-mine [data-zone=Hand],.is-mine [data-zone=Temp]{grid-column:1/-1}.is-mine [data-zone=Hand]{grid-row:4}
  .is-mine [data-zone=Equipment]>div{max-width:240px;margin:auto}
}
@media(max-width:700px){.fab-upf-opponents{gap:6px;min-height:0}.fab-upf-seat{padding:8px}.fab-upf-seat-header{flex-wrap:wrap}.fab-upf-seat h2{font-size:12px}.fab-upf-summary{font-size:10px}.fab-upf-seat.is-summary .fab-upf-zone:not([data-zone=Hero]){display:none}.fab-upf-seat.is-mine{padding:12px}.fab-upf-panel{left:10px;top:20vh;width:calc(100vw - 20px)}.fab-upf-panel header small{display:none}}
</style>
<style><?php readfile(__DIR__ . '/MultiplayerTable.css'); ?></style>
<main id="fab-upf-board" aria-label="Ultimate Pit Fight">
  <div class="fab-upf-opponents" id="fab-upf-opponents"></div><div id="fab-upf-own"></div>
</main>
<section class="fab-upf-panel" id="fab-upf-stack-panel" aria-label="Stack" hidden>
  <header tabindex="0" aria-label="Move stack panel with arrow keys"><h2>Stack <small>Last in, first out · drag to move</small></h2><button type="button" data-fab-close="stack" aria-label="Hide stack">×</button></header>
  <div class="fab-upf-panel-body" id="fab-upf-stack"></div>
</section>
<section class="fab-upf-panel" id="fab-upf-chain-panel" aria-label="Combat chain" aria-describedby="fab-upf-chain-status" hidden>
  <header tabindex="0" aria-label="Move combat chain panel with arrow keys">
    <div data-fab-chain-totals></div>
    <?php include __DIR__ . '/CombatProgress.php'; ?>
    <button type="button" data-fab-close="chain" aria-label="Hide combat chain">×</button>
  </header>
  <div class="fab-upf-panel-body"><p id="fab-upf-chain-status" class="fab-upf-status is-empty">No active combat chain.</p><div id="fab-upf-chain-view"></div></div>
</section>
<script>
(function() {
  let lastRender=null, focusedSeat=null;
  const counts={stack:0,chain:0};
  const panelButtons = Object.fromEntries([...document.querySelectorAll('#fab-table-tools [data-fab-panel]')]
    .map(button => [button.dataset.fabPanel, button]));
  const panelFor = kind => document.getElementById(panelButtons[kind].getAttribute('aria-controls'));
  function refreshTableControls(active, viewer, seats) {
    const mount = document.getElementById('fab-events-mount');
    if (!mount.firstElementChild && typeof GetMacroCardToastHost === 'function') {
      const host = GetMacroCardToastHost();
      mount.appendChild(host);
      const toggle = host.querySelector('#macro-card-toast-toggle');
      const updateExpanded = () => toggle.setAttribute('aria-expanded', String(host.classList.contains('is-expanded')));
      toggle.setAttribute('aria-controls', 'macro-card-toast-log');
      toggle.addEventListener('click', updateExpanded);
      updateExpanded();
    }
    panelButtons.stack.setAttribute('aria-controls', active ? 'fab-upf-stack-panel' : 'fabLayersWindow');
    const winner = Number(window.WinnerData);
    const priority = winner ? 0 : Number(window.PriorityPlayerData);
    if (!active) {
      const opponent = Number(seats.find(seat => Number(seat) !== viewer));
      document.querySelector('.fab-board-local').classList.toggle('has-priority', priority === viewer);
      document.querySelector('.fab-board-opponent').classList.toggle('has-priority', priority === opponent);
    }
    const announcement = document.getElementById('fab-priority-announcement');
    const message = winner > 0 ? 'Player ' + winner + ' wins' :
      priority === viewer ? 'You have priority' : priority > 0 ? 'Player ' + priority + ' has priority' : '';
    if (announcement.textContent !== message) announcement.textContent = message;
  }
  const label=name=>({CombatChain:'Combat chain',ActionPoints:'Action points',Temp:'Search results',Health:'Life'})[name] || name;
  function raisePanel(panel){document.querySelectorAll('.fab-upf-panel').forEach(p=>p.style.zIndex=p===panel?'151':'150');}
  function panelToggle(kind,open,focus) {
    const panel = panelFor(kind);
    const shouldOpen = open ?? panel.hidden;
    if (shouldOpen && !document.body.classList.contains('fab-upf-active')) {
      panelToggle(kind === 'chain' ? 'stack' : 'chain', false);
    }
    panel.hidden = !shouldOpen;
    panelButtons[kind].setAttribute('aria-expanded', String(shouldOpen));
    if (shouldOpen) {
      raisePanel(panel);
      window.FaBClampPanel(panel);
      if (focus) panel.querySelector('header').focus();
    }
  }

  function updatePanelCount(kind, count, autoOpen = true) {
    panelButtons[kind].querySelector('span').textContent = count;
    if (count > 0 && counts[kind] === 0 && autoOpen) panelToggle(kind, true);
    if (count === 0 && counts[kind] > 0) panelToggle(kind, false);
    counts[kind] = count;
  }

  function readCombatState() {
    const data = window.GameStateData;
    const raw = data && typeof data === 'object' ? (data.Value ?? data.value) : data;
    if (typeof raw !== 'string' || !raw.trim()) return {};
    try { return JSON.parse(raw); } catch (_) {}
    try { return JSON.parse(decodeURIComponent(raw)); } catch (_) { return {}; }
  }

  function renderCombatChain(seats, multiplayer) {
    const zoneNames = multiplayer ? seats.map(seat => 'p' + seat + 'CombatChain') : ['myCombatChain', 'theirCombatChain'];
    const zones = Object.fromEntries(zoneNames.map(name => [name, window[name + 'Data'] || '']));
    const state = readCombatState();
    const panel = panelFor('chain');
    const view = document.getElementById('fab-upf-chain-view');
    window.FaBUpdateCombatProgress(panel, state);
    window.FaBRenderChain(view, zones, state);
    const status = document.getElementById('fab-upf-chain-status');
    status.classList.toggle('is-empty', !state.combatOpen);
    status.textContent = state.combatOpen ? 'Player ' + state.attacker + ' → Player ' + state.defender + ' · ' + String(state.combatStep || '') +
      (['DAMAGE','RESOLUTION'].includes(state.combatStep) ? ' · Attack ' + (state.attackPower || 0) + ' / Defense ' + (state.defenseValue || 0) : '') : 'No active combat chain.';
    updatePanelCount('chain', Number(view.dataset.cardCount));
  }

  function renderStack(multiplayer, cardSize) {
    const data = String(window.StackData || '');
    const count = data.trim() ? data.split('<|>').length : 0;
    const view = document.getElementById(multiplayer ? 'fab-upf-stack' : 'fabLayers');
    view.innerHTML = count ? PopulateZone('Stack', data, cardSize, './FaBSim/concat', '0', 'All') : multiplayer
      ? '<p class="fab-upf-empty">No cards or abilities on the stack.</p>'
      : '<div><strong>No active layers</strong>Instants, triggers, and resolving abilities appear here.</div>';
    updatePanelCount('stack', count, multiplayer || panelFor('chain').hidden);
  }
  function focusSeat(seat){focusedSeat=seat;if(lastRender)window.RenderFaBMultiplayer(...lastRender);}
  function makeZone(seat,zone,row){
    const box=document.createElement('section');box.className='fab-upf-zone';box.dataset.zone=zone.name;
    if(zone.name==='ActionPoints')box.classList.add('fab-action-points');
    if(zone.name==='Arena')box.classList.add('fab-arena');
    if(zone.name==='Arsenal')box.classList.add('fab-arsenal');
    else {const heading=document.createElement('h3');heading.textContent=label(zone.name);box.appendChild(heading);}
    const slot=document.createElement('div');slot.id='p'+seat+zone.name+'Slot';box.appendChild(slot);row.appendChild(box);
  }

  function renderArsenal(slot, zone, data, size) {
    slot.innerHTML = data.trim() ? PopulateZone(zone, data, size, './FaBSim/concat', '0', 'Single') : '';
    const counter = slot.querySelector('.counter-bubble');
    if (counter?.textContent === '1') counter.remove();
  }

  function renderDuelCompactZones() {
    for (const side of ['my', 'their']) {
      const arsenal = document.getElementById('fab-' + side + '-arsenal');
      const arena = document.getElementById('fab-' + side + '-arena');
      const arsenalZone = side + 'Arsenal';
      const arenaZone = side + 'Arena';
      const arenaData = String(window[arenaZone + 'Data'] || '');
      arsenal.onclick = () => ZoneClickHandler(arsenalZone);
      arena.onclick = () => ZoneClickHandler(arenaZone);
      const cardSize = arsenal.clientWidth;
      renderArsenal(arsenal, arsenalZone, String(window[arsenalZone + 'Data'] || ''), cardSize);
      arena.innerHTML = arenaData.trim() ? PopulateZone(arenaZone, arenaData, cardSize, './FaBSim/concat', '0', 'All') : '';
    }
  }
  window.RenderFaBMultiplayer=function(response,stride,zones,size,viewerSeat){
    if(typeof window.FaBRefreshGameOver==='function')window.FaBRefreshGameOver();
    lastRender=[response,stride,zones,size,viewerSeat];
    const seats=String(window.SeatOrderData||'12').match(/[1-4]/g)||[];
    const active=seats.length>2;document.body.classList.toggle('fab-upf-active',active);
    refreshTableControls(active, Number(viewerSeat), seats);
    if(!active){
      if(typeof FaBRefreshOptionalCounters==='function')FaBRefreshOptionalCounters();
      renderDuelCompactZones();
      renderCombatChain(seats, false);
      renderStack(false, size);
      return;
    }
    const viewer=String(viewerSeat),opponents=document.getElementById('fab-upf-opponents'),own=document.getElementById('fab-upf-own');
    const signature=seats.join('')+':'+viewer+':'+zones.map(z=>z.name).join(',');
    if(opponents.dataset.signature!==signature){
      focusedSeat=null;
      opponents.replaceChildren();own.replaceChildren();
      const viewerIndex=seats.indexOf(viewer),ordered=viewerIndex<0?seats:seats.slice(viewerIndex+1).concat(seats.slice(0,viewerIndex+1));
      ordered.forEach(seat=>{
        const section=document.createElement('section');section.id='fab-seat-'+seat;section.className='fab-upf-seat';
        if(seat===viewer)section.setAttribute('aria-label','Your field · Player '+seat);
        const header=document.createElement('header');header.className='fab-upf-seat-header';
        const identity=document.createElement('div'),heading=document.createElement('h2');identity.className='fab-upf-identity';heading.textContent='Player '+seat;identity.appendChild(heading);
        const hand=document.createElement('span');hand.className='fab-upf-hand-count';identity.appendChild(hand);
        const badges=document.createElement('div');badges.className='fab-upf-badges';identity.appendChild(badges);header.appendChild(identity);
        if(seat!==viewer){const button=document.createElement('button');button.type='button';button.className='fab-upf-inspect';button.textContent='Inspect';button.setAttribute('aria-label','Inspect player '+seat);button.onclick=()=>focusSeat(seat);header.appendChild(button);}
        section.appendChild(header);const summary=document.createElement('p');summary.className='fab-upf-summary';section.appendChild(summary);
        const row=document.createElement('div');row.className='fab-upf-zones';section.appendChild(row);
        // Inventory remains in the response data for rules and selection prompts,
        // but has no permanent board slot. CombatChain has its own shared panel.
        zones.filter(z=>!['CombatChain','Inventory'].includes(z.name)).forEach(zone=>makeZone(seat,zone,row));(seat===viewer?own:opponents).appendChild(section);
      });opponents.dataset.signature=signature;
    }
    const live=String(window.LiveSeatsData||seats.join(''));opponents.classList.toggle('is-focused',focusedSeat!==null);document.getElementById('fab-upf-home').hidden=focusedSeat===null;
    const opponentsInPlay=seats.filter(seat=>seat!==viewer&&live.includes(seat)).length;
    const oneSurvivor=focusedSeat===null&&opponentsInPlay===1;
    opponents.classList.toggle('has-one-survivor',oneSurvivor);
    // Apply every seat's state before measuring widths for the card renderer.
    for(const seat of seats){
      const section=document.getElementById('fab-seat-'+seat),mine=seat===viewer,summary=!mine&&focusedSeat!==seat;
      const eliminated=!live.includes(seat);
      section.hidden=!mine&&focusedSeat!==null&&focusedSeat!==seat;
      section.classList.toggle('is-summary',summary);
      section.classList.toggle('is-mine',mine);
      section.classList.toggle('is-memorial',summary&&eliminated);
      section.classList.toggle('has-priority',!eliminated&&!Number(window.WinnerData)&&Number(seat)===Number(window.PriorityPlayerData));
      section.querySelector('.fab-upf-badges').textContent=eliminated?'Eliminated':'';
    }
    seats.forEach(seat=>{
      const section=document.getElementById('fab-seat-'+seat),mine=seat===viewer,summary=!mine&&focusedSeat!==seat;
      const dataByName={};
      // Size against this field's allocated space, not the entire viewport.
      const fieldHeight=(mine?own:opponents).clientHeight;
      // Three side cards, two 9px gaps, two 12px insets and the seat border.
      const fieldCardSize=innerWidth<900?72:Math.max(40,mine
        ? Math.min(160,(fieldHeight-44)/3,section.clientWidth/9)
        : Math.min(110,(fieldHeight-46)/3,section.clientWidth/9));
      const seatCardSize=summary?(innerWidth<700?40:Math.max(20,Math.min(52,
        (fieldHeight-52)/3.4,(section.clientWidth-24)/9))):fieldCardSize;
      section.style.setProperty('--fab-table-card',seatCardSize+'px');
      const compactCardSize=summary?seatCardSize:Math.max(32,Math.round(seatCardSize/2));
      section.style.setProperty('--fab-arsenal-size',compactCardSize+'px');
      const combatCardSize=section.classList.contains('is-memorial')?(oneSurvivor&&innerWidth<700?32:48)
        :summary&&innerWidth>=700?seatCardSize*1.35:seatCardSize;
      section.style.setProperty('--fab-summary-combat-card',combatCardSize+'px');
      zones.forEach(zone=>{
        const name='p'+seat+zone.name,data=String(response[zone.index+(Number(seat)-1)*stride]||'');dataByName[zone.name]=data;
        window[name+'Data']=data;const slot=document.getElementById(name+'Slot');if(!slot)return;slot.onclick=()=>ZoneClickHandler(name);
        const cardSize=zone.name==='Hero'||zone.name==='Weapons'?combatCardSize
          :zone.name==='Arsenal'||zone.name==='Arena'?compactCardSize:seatCardSize;
        if(zone.name==='Arsenal')renderArsenal(slot,name,data,cardSize);
        else slot.innerHTML=data.trim()?PopulateZone(name,data,cardSize,'./FaBSim/concat','0',zone.mode):'';
        if(zone.name==='Equipment'&&typeof FaBArrangeEquipment==='function')FaBArrangeEquipment(name);
        if(!mine)slot.querySelectorAll('button').forEach(button=>button.remove());
        slot.parentElement.dataset.empty=String(!data.trim());if(zone.name==='Temp')slot.parentElement.hidden=!data.trim();
        if(zone.name==='Chi')slot.parentElement.hidden=!(Number(data)>0);
        if(zone.name==='Soul')slot.parentElement.hidden=!data.split('<|>').some(record=>{const card=record.trim().split(' ')[0];return card&&card!=='-';});
        if(zone.name==='ActionPoints')slot.parentElement.classList.toggle('fab-ap-inactive',Number(seat)!==Number(window.TurnPlayerData));
      });
      // A private pile is one CardBack record whose counter carries its size.
      const count=name=>{
        const data=(dataByName[name] || '').trim();
        const pile=/^\S+ (\d+) /.exec(data);
        if(pile && (name==='Deck'||name==='Arsenal'))return Number(pile[1]);
        return data?data.split('<|>').length:0;
      };
      section.querySelector('.fab-upf-summary').textContent='';
      const hand=section.querySelector('.fab-upf-hand-count'),handCount=count('Hand');
      hand.replaceChildren();hand.setAttribute('role','img');hand.setAttribute('aria-label',handCount+' cards in hand');hand.title=handCount+' cards in hand';
      for(let i=0;i<Math.min(handCount,10);i++){const back=document.createElement('img');back.src='./FaBSim/concat/CardBack.webp';back.alt='';hand.appendChild(back);}
      if(handCount>10){const extra=document.createElement('span');extra.textContent='+'+(handCount-10);hand.appendChild(extra);}
      for(const [zoneName,stat,value,label] of [['Hero','health',dataByName.Health||'0','Life'],['Pitch','resource',dataByName.Resources||'0','Available resources'],['Pitch','chi',dataByName.Chi||'0','Chi included in resources']]){
        const zone=section.querySelector('[data-zone="'+zoneName+'"]');
        let badge=zone.querySelector('.fab-upf-summary-counter[data-stat="'+stat+'"]');
        if(!badge){badge=document.createElement('span');badge.className='fab-upf-summary-counter';badge.dataset.stat=stat;zone.appendChild(badge);}
        badge.textContent=value;badge.title=label+': '+value;badge.setAttribute('aria-label',badge.title);
        badge.hidden=stat==='chi'&&!(Number(value)>0);
      }
    });
    renderStack(true, 90);
    renderCombatChain(seats, true);
  };
  document.getElementById('fab-upf-home').onclick=()=>focusSeat(null);
  let resizeFrame=0;
  function redrawAfterLayout(){
    cancelAnimationFrame(resizeFrame);
    resizeFrame=requestAnimationFrame(()=>{if(lastRender)window.RenderFaBMultiplayer(...lastRender);});
  }
  window.addEventListener('resize',redrawAfterLayout);
  document.getElementById('fab-upf-opponents').addEventListener('transitionend',event=>{
    if(event.target.parentElement===event.currentTarget&&event.propertyName==='flex-basis')redrawAfterLayout();
  });
  Object.entries(panelButtons).forEach(([kind, button]) => {
    button.onclick = () => panelToggle(kind, undefined, true);
  });
  document.getElementById('fab-table').addEventListener('click', event => {
    const button = event.target.closest('[data-fab-close]');
    if (!button) return;
    const kind = button.dataset.fabClose;
    panelToggle(kind, false);
    panelButtons[kind].focus();
  });
  document.querySelectorAll('.fab-upf-panel').forEach(panel=>{
    window.FaBMakeDraggablePanel(panel, panel.querySelector('header'));
    if (panel.querySelector('[data-fab-drag-handle]')) window.FaBMakeDraggablePanel(panel, panel.querySelector('[data-fab-drag-handle]'));
    panel.addEventListener('pointerdown',()=>raisePanel(panel));
    panel.addEventListener('keydown',event=>{if(event.key==='Escape')panel.querySelector('[data-fab-close]').click();});
  });
})();
</script>
