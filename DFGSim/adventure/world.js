'use strict';
(()=>{
const $=id=>document.getElementById(id),esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const canvas=$('worldCanvas'),ctx=canvas.getContext('2d'),panel=$('panel'),reduced=matchMedia('(prefers-reduced-motion: reduce)');
const colors={Red:'#c4503e',Orange:'#c98739',Yellow:'#b4a13d',Green:'#5b8657',Blue:'#4787a0',Purple:'#9363a2'};
let data=null,j=null,images=new Map(),visual={x:352,y:928},view={x:0,y:0,scale:1,w:800,h:600},fit=false,hover=null,near=null;
let movement=null,walking=false,cancelWalk=false,pendingGoal=null,keys=new Set(),network=Promise.resolve(),pending=0,toastTimer=null,lastTime=0;
let panelMode=null,draft=null,deckColor='Red',focusedQuest=null,ready=false;
const center=p=>({x:p.x*64+32,y:p.y*64+32}),mapURL='adventure/api.php';
function toast(text){$('toast').textContent=text;$('toast').hidden=false;clearTimeout(toastTimer);toastTimer=setTimeout(()=>$('toast').hidden=true,4300);}
function fail(error){$('worldError').textContent=error.message||String(error);$('worldError').hidden=false;}
function accept(next){data=next;j=next.journey;$('saveStatus').textContent=pending?'Saving…':'Journey saved';$('worldError').hidden=true;renderHUD();}
async function refresh(){const r=await fetch(mapURL,{cache:'no-store'}),d=await r.json();if(!r.ok||!d.ok)throw Error(d.error||'Unable to load your journey.');accept(d);}
function send(action){
  pending++;$('saveStatus').textContent='Saving…';
  const task=network.catch(()=>{}).then(async()=>{
    const controller=new AbortController(),timeout=setTimeout(()=>controller.abort(),20000);
    try{
      const r=await fetch(mapURL,{method:'POST',cache:'no-store',headers:{'Content-Type':'application/json'},signal:controller.signal,body:JSON.stringify({command:'action',token:data.token,runId:j.id,revision:j.revision,action})}),d=await r.json();
      if(!r.ok||!d.ok){const e=Error(d.error||'Unable to save your action.');e.status=r.status;throw e;}
      accept(d);return d;
    }catch(e){cancelWalk=true;keys.clear();try{await refresh();visual=center(j.position);}catch{}fail(e.name==='AbortError'?Error('Saving took too long. Your latest saved position is preserved.'):e);throw e;}
    finally{clearTimeout(timeout);pending--;$('saveStatus').textContent=pending?'Saving…':'Journey saved';}
  });network=task;return task;
}
function image(src){return images.get(src);}
async function loadImage(src){if(images.has(src))return image(src);const im=new Image();images.set(src,im);im.src=src;try{await im.decode();}catch{}return im;}
function resize(){const r=canvas.getBoundingClientRect(),dpr=Math.min(devicePixelRatio||1,2);canvas.width=Math.round(r.width*dpr);canvas.height=Math.round(r.height*dpr);view.w=r.width;view.h=r.height;ctx.setTransform(dpr,0,0,dpr,0,0);}
new ResizeObserver(resize).observe(canvas);
function walkable(x,y){
  if(!data||x<0||y<0||x>=data.map.width||y>=data.map.height)return false;
  if(data.map.blocked.some(([a,b,w,h])=>x>=a&&x<a+w&&y>=b&&y<b+h))return false;
  return !Object.values(j.entities).some(e=>['npc','champion'].includes(e.kind)&&x===e.x&&y===e.y);
}
function pathTo(x,y,id=null){
  const start=j.position,key=(x,y)=>x+','+y,queue=[[start.x,start.y]],seen=new Map([[key(start.x,start.y),null]]);
  const goal=(a,b)=>id?Math.abs(a-x)+Math.abs(b-y)<=1:Math.abs(a-x)+Math.abs(b-y)===0;
  if(!id&&!walkable(x,y))return null;
  for(let n=0;n<queue.length;n++){
    const [cx,cy]=queue[n];if(goal(cx,cy)){
      const path=[];let at=[cx,cy];while(seen.get(key(...at))!==null){path.push(at);at=seen.get(key(...at));}return path.reverse();
    }
    for(const [dx,dy] of [[0,-1],[-1,0],[1,0],[0,1]]){const nx=cx+dx,ny=cy+dy,k=key(nx,ny);if(!seen.has(k)&&walkable(nx,ny)){seen.set(k,[cx,cy]);queue.push([nx,ny]);}}
  }return null;
}
function animateStep(tile){
  const end=center({x:tile[0],y:tile[1]});if(reduced.matches){visual=end;return Promise.resolve();}
  return new Promise(resolve=>movement={from:{...visual},to:end,start:performance.now(),duration:150,resolve});
}
async function go(x,y,id=null){
  if(!j||j.phase!=='world'){toast('Resume and finish your current bout before exploring.');return;}
  if(panel.open)closePanel(false);
  if(walking){pendingGoal={x,y,id};cancelWalk=true;return;}
  const path=pathTo(x,y,id);if(!path){toast('That way is blocked. Try the open paths or the bridge.');return;}
  walking=true;cancelWalk=false;pendingGoal=null;
  try{
    for(const tile of path){if(cancelWalk)break;await Promise.all([send({type:'move',path:[tile]}),animateStep(tile)]);}
    if(!cancelWalk&&id){const d=await send({type:'interact',id});openDialogue(d.journey.dialog);}
  }catch{}finally{
    walking=false;movement=null;visual=center(j.position);renderNearby();const next=pendingGoal;pendingGoal=null;
    if(next)go(next.x,next.y,next.id);
  }
}
function stopWalking(){cancelWalk=true;pendingGoal=null;keys.clear();}
function entityAt(x,y){
  return Object.entries(j.entities).reverse().find(([id,e])=>{const p=center(e),height=e.kind==='champion'?(j.defeated.includes(id)?115:155):e.kind==='npc'?125:85,width=e.kind==='champion'?Math.max(55,e.name.length*4):45;return Math.abs(p.x-x)<width&&y>p.y-height&&y<p.y+18;})?.[0]??null;
}
function choosePoint(event){
  if(!ready||panel.open)return;const r=canvas.getBoundingClientRect(),x=(event.clientX-r.left)/view.scale+view.x,y=(event.clientY-r.top)/view.scale+view.y,id=entityAt(x,y);
  if(id){const e=j.entities[id];go(e.x,e.y,id);}else go(Math.floor(x/64),Math.floor(y/64));canvas.focus({preventScroll:true});
}
canvas.addEventListener('pointerup',choosePoint);
canvas.addEventListener('pointermove',e=>{if(!j)return;const r=canvas.getBoundingClientRect();hover=entityAt((e.clientX-r.left)/view.scale+view.x,(e.clientY-r.top)/view.scale+view.y);canvas.style.cursor=hover?'pointer':'crosshair';});
canvas.addEventListener('pointerleave',()=>hover=null);
function nearest(){if(!j)return null;return Object.entries(j.entities).filter(([,e])=>Math.abs(e.x-j.position.x)+Math.abs(e.y-j.position.y)<=1).sort((a,b)=>(a[1].kind==='champion'?-1:1)-(b[1].kind==='champion'?-1:1))[0]?.[0]??null;}
async function interact(){if(walking||panel.open||j?.phase!=='world')return;const id=nearest();if(id){try{const d=await send({type:'interact',id});openDialogue(d.journey.dialog);}catch{}}else toast('Walk beside a person, mailbox, or marked object to interact.');}
$('interact').addEventListener('click',interact);
const dirs={ArrowUp:[0,-1],w:[0,-1],W:[0,-1],ArrowDown:[0,1],s:[0,1],S:[0,1],ArrowLeft:[-1,0],a:[-1,0],A:[-1,0],ArrowRight:[1,0],d:[1,0],D:[1,0]};
addEventListener('keydown',e=>{
  if(panel.open||document.activeElement?.closest('button,a,input,select,textarea'))return;
  if(dirs[e.key]){e.preventDefault();keys.add(e.key);pendingGoal=null;}
  if(['e','E',' '].includes(e.key)){e.preventDefault();interact();}
  if(e.key==='Escape')stopWalking();
});
addEventListener('keyup',e=>keys.delete(e.key));addEventListener('blur',stopWalking);
document.addEventListener('visibilitychange',()=>{if(document.hidden)stopWalking();});
document.querySelectorAll('[data-dir]').forEach(b=>{
  const key={up:'ArrowUp',down:'ArrowDown',left:'ArrowLeft',right:'ArrowRight'}[b.dataset.dir];
  b.addEventListener('pointerdown',e=>{e.preventDefault();b.setPointerCapture(e.pointerId);keys.add(key);});
  for(const name of ['pointerup','pointercancel','lostpointercapture'])b.addEventListener(name,()=>keys.delete(key));
});
$('overview').addEventListener('click',()=>{fit=!fit;$('overview').setAttribute('aria-pressed',String(fit));canvas.focus({preventScroll:true});});
function roundRect(x,y,w,h,r,fill,stroke=null){ctx.beginPath();ctx.roundRect(x,y,w,h,r);ctx.fillStyle=fill;ctx.fill();if(stroke){ctx.strokeStyle=stroke;ctx.lineWidth=1.5;ctx.stroke();}}
function label(text,x,y,emphasis=false){
  ctx.font='600 13px system-ui';const w=ctx.measureText(text).width+20;roundRect(x-w/2,y-20,w,25,6,emphasis?'#fff1c9f5':'#fff6e3e8','#775b3c');ctx.fillStyle='#493524';ctx.textAlign='center';ctx.fillText(text,x,y-3);
}
function sprite(src,x,y,h,flip=false,bob=0){
  const im=image(src);if(!im?.naturalWidth)return;const w=h*im.naturalWidth/im.naturalHeight;
  ctx.save();ctx.translate(x,y-bob);if(flip)ctx.scale(-1,1);ctx.drawImage(im,-w/2,-h,w,h);ctx.restore();
}
function draw(time){
  requestAnimationFrame(draw);if(!ready)return;
  const elapsed=Math.min(40,time-lastTime);lastTime=time;
  if(movement){const t=Math.min(1,(time-movement.start)/movement.duration);visual={x:movement.from.x+(movement.to.x-movement.from.x)*t,y:movement.from.y+(movement.to.y-movement.from.y)*t};if(t>=1){const done=movement.resolve;movement=null;done();}}
  if(!walking&&keys.size&&!panel.open&&j.phase==='world'){const key=[...keys].at(-1),[dx,dy]=dirs[key];if(walkable(j.position.x+dx,j.position.y+dy))go(j.position.x+dx,j.position.y+dy);}
  view.scale=fit?Math.min(view.w/1536,view.h/1024):(view.w<700?.78:1);
  const vw=view.w/view.scale,vh=view.h/view.scale;
  view.x=fit?(1536-vw)/2:Math.max(0,Math.min(1536-vw,visual.x-vw/2));view.y=fit?(1024-vh)/2:Math.max(0,Math.min(1024-vh,visual.y-vh/2));
  ctx.clearRect(0,0,view.w,view.h);ctx.fillStyle='#97ab7b';ctx.fillRect(0,0,view.w,view.h);ctx.save();ctx.scale(view.scale,view.scale);ctx.translate(-view.x,-view.y);
  const bg=image(data.map.art);if(bg?.naturalWidth)ctx.drawImage(bg,0,0,1536,1024);
  if(j.zone!=='sanctuary'&&j.flags.canalRestored){ctx.strokeStyle='#90ece2';ctx.lineWidth=3;for(let n=0;n<3;n++){const y=715+n*11+(reduced.matches?0:(time/85)%11);ctx.beginPath();ctx.moveTo(685,y);ctx.quadraticCurveTo(716,y+6,748,y);ctx.stroke();}}
  if(focusedQuest&&j.entities[focusedQuest]){const p=center(j.entities[focusedQuest]);ctx.strokeStyle='#fff6b0';ctx.lineWidth=4;ctx.beginPath();ctx.ellipse(p.x,p.y-4,34,17,0,0,Math.PI*2);ctx.stroke();}
  const actors=Object.entries(j.entities).map(([id,e])=>({id,e,y:center(e).y}));actors.push({id:'player',y:visual.y});actors.sort((a,b)=>a.y-b.y);
  for(const actor of actors){
    if(actor.id==='player'){
      const walkingBob=walking&&!reduced.matches?Math.sin(time/65)*2:0;
      ctx.fillStyle='#4d3b2330';ctx.beginPath();ctx.ellipse(visual.x,visual.y-4,20,7,0,0,Math.PI*2);ctx.fill();
      sprite('adventure/assets/challenger-overworld-v1.png',visual.x,visual.y,86,movement?.to.x<movement?.from.x,walkingBob);continue;
    }
    const {id,e}=actor,p=center(e),defeated=j.defeated.includes(id),selected=hover===id||near===id||focusedQuest===id;
    if(e.kind==='champion'){
      const kneel=id==='huwawa'?'huwawa-kneeling-v2.png':id+'-kneeling.png',src=defeated?'adventure/assets/'+kneel:e.portrait;
      sprite(src,p.x,p.y,defeated?82:(id==='bull-of-heaven'?125:109));
      label(defeated?'✓ '+e.name:e.name,p.x,p.y-(defeated?84:113),selected);
      if(!defeated){ctx.fillStyle=j.canChallenge.includes(id)?'#ffe7a0':'#d1c2a4';ctx.beginPath();ctx.arc(p.x+28,p.y-83,10,0,Math.PI*2);ctx.fill();ctx.fillStyle='#654427';ctx.textAlign='center';ctx.font='bold 12px system-ui';ctx.fillText(j.canChallenge.includes(id)?'◇':'…',p.x+28,p.y-79);}
    }else if(e.kind==='npc'){
      sprite('adventure/assets/elder-overworld-v1.png',p.x,p.y,83,false);if(selected)label(e.name,p.x,p.y-89,true);
      else{ctx.fillStyle='#fff1b0';ctx.font='bold 22px Georgia';ctx.textAlign='center';ctx.fillText('!',p.x,p.y-82);}
    }else{
      const needed=(id==='stopper'||id==='reed')&&j.flags.gardener;
      if(e.kind==='pickup'&&!needed)continue;
      const hiddenMailbox=id==='mailbox'&&j.flags.invited;
      if(!hiddenMailbox||selected){ctx.fillStyle='#fff2cce8';ctx.strokeStyle='#8b6138';ctx.lineWidth=2;ctx.beginPath();ctx.ellipse(p.x,p.y-25,17,15,0,0,Math.PI*2);ctx.fill();ctx.stroke();ctx.fillStyle='#805332';ctx.textAlign='center';ctx.font='bold 19px Georgia';ctx.fillText(e.icon,p.x,p.y-18);}
      if(selected)label(e.name,p.x,p.y-54,true);
    }
  }
  ctx.restore();
  const nextNear=nearest();if(nextNear!==near){near=nextNear;renderNearby();}
  const area=j.zone==='sanctuary'?'Inside the Sanctuary':visual.x>1000?'Court of Heaven':visual.y>770&&visual.x<430?'Sanctuary of First Light':visual.x<500&&visual.y<350?'Cedar Grove':visual.y<430&&visual.x>540?'Training Terrace':visual.y>640?'Canal Gardens':'The Clay Market';
  if($('areaName').textContent!==area)$('areaName').textContent=area;
}
function renderNearby(){
  const e=near&&j.entities[near];$('interact').disabled=!e||j.phase!=='world'||walking;
  $('interact').innerHTML='<kbd>E</kbd> '+(e?.kind==='champion'?'Speak':e?.kind==='pickup'?'Examine':e?.kind==='gate'?'Inspect':'Interact');
  $('nearbyText').textContent=e?.name??'';
  $('interact').closest('.interaction-bar').hidden=!e||j.phase!=='world'||walking;
}
function renderHUD(){
  const next=j.quests.find(q=>!q.done);$('objectiveTitle').textContent=j.zone==='sanctuary'?'A moment to catch your breath':next?.title??'The courtyard knows your name';$('objectiveDetail').textContent=j.zone==='sanctuary'?'Aya can offer a little advice. The door leads back to your next task.':next?.detail??'Visit the road beyond the temple to see the wider world.';
  $('journalCount').textContent=j.quests.filter(q=>q.done).length+'/'+j.quests.length;
  $('resumeFight').hidden=j.phase!=='fight';renderNearby();
}
function showPanel(mode,title,body,kicker='THE FIRST COURTYARD'){
  stopWalking();panelMode=mode;$('panelTitle').textContent=title;$('panelKicker').textContent=kicker;$('panelContent').innerHTML=body;if(!panel.open)panel.showModal();panel.scrollTop=0;panel.style.setProperty('--heading-height',document.querySelector('.panel-heading').getBoundingClientRect().height+'px');
}
function closePanel(save=true){panel.close();panelMode=null;canvas.focus({preventScroll:true});if(save&&j?.phase==='world'&&j.dialog)send({type:'close'}).catch(()=>{});}
$('closePanel').addEventListener('click',()=>{if(j?.phase==='result')continueJourney();else closePanel();});
panel.addEventListener('cancel',e=>{e.preventDefault();if(j?.phase==='result')continueJourney();else closePanel();});
function openDialogue(dialog){
  if(!dialog)return;const options=dialog.options.map(o=>`<button ${o.action==='challenge'?'class="primary"':''} data-dialog-action="${esc(o.action)}" data-person="${esc(dialog.id)}">${esc(o.label)}</button>`).join('');
  const opening=dialog.options.some(o=>o.action==='challenge')?`<label>Opening hand <select id="openingColor">${Object.keys(colors).map(c=>`<option>${c}</option>`).join('')}</select></label>`:'';
  showPanel('dialog',dialog.title,`<div class="dialogue-body">${dialog.portrait?`<img class="dialogue-portrait" src="${esc(dialog.portrait)}" alt="${esc(dialog.title)}">`:''}<p class="dialogue-text${dialog.id==='mailbox'?' tablet':''}">${esc(dialog.text)}</p></div><div class="dialogue-actions">${options}${opening}<button data-dismiss>Back to the courtyard</button></div>`,dialog.id==='mailbox'?'A RATHER HEAVY INVITATION':'A MOMENT BETWEEN CHALLENGERS');
}
function openJournal(){showPanel('journal','A tablet full of possibilities',`<p class="panel-note">Your tasks, techniques, and seals are saved automatically. Defeat never takes them away.</p><ol class="quest-list">${j.quests.map(q=>`<li class="${q.done?'done':''}"><span class="check">${q.done?'✓':'◇'}</span><div><h3>${esc(q.title)}</h3><p>${esc(q.detail)}</p>${q.done?'':`<button data-target="${esc(q.target)}">Find on the map →</button>`}</div></li>`).join('')}</ol><section class="journal-log"><h3>Notes along the way</h3>${j.journal.slice().reverse().map(t=>`<p>${esc(t)}</p>`).join('')}</section>`,'YOUR JOURNEY');}
function openDeck(fresh=true){
  if(j.phase!=='world'){showPanel('locked','A bout is still in progress','<p class="resume-note">Your deck and equipment can be changed freely outside fights. Finish your current bout first.<br><button class="primary" data-resume>Return to your challenge →</button></p>');return;}
  if(fresh)draft=new Set(j.deck);
  const all=data.cards.filter(c=>j.collection.includes(c.id)),count=color=>[...draft].filter(id=>data.cards.find(c=>c.id===id)?.color===color).length;
  showPanel('deck','Your six hands',`<p class="panel-note">You begin with all 36 PvP techniques. The 12 marked Baseline are shared foundations. Signature moves offer new tradeoffs—replace an equipped card to make room in a full hand. <a href="adventure/technique-art.php" target="_blank" rel="noopener">Browse champion techniques ↗</a></p><div class="deck-toolbar"><div><strong>${draft.size} techniques equipped</strong><br><span>One to six unique cards in each color. Freely change them between bouts.</span></div><button class="primary" id="saveDeck">Save deck</button></div><div class="color-tabs" role="tablist" aria-label="Technique colors">${Object.keys(colors).map(color=>`<button role="tab" aria-selected="${color===deckColor}" data-color="${color}" style="--color:${colors[color]}">${color} ${count(color)}/6</button>`).join('')}</div><div class="card-library">${all.filter(c=>c.color===deckColor).map(c=>`<article class="library-entry${c.artPath?' champion-technique':''}${c.headline?' headline-technique':''}"><label><input type="checkbox" data-card="${c.id}" ${draft.has(c.id)?'checked':''} ${!draft.has(c.id)&&count(deckColor)>=6?'disabled':''}><span data-equip-label>${draft.has(c.id)?'Equipped':'Add to '+deckColor}</span></label>${cardOrigin(c)}${DFGCardRenderer.details(c,data.colors)}</article>`).join('')}</div>`,'TECHNIQUE LIBRARY · PERMANENT UNLOCKS');
}
function cardOrigin(c){return `<div class="card-origin${c.baseline?' baseline-origin':''}"><strong>${esc(c.roleTitle??'')}</strong>${c.tradeoff?`<span>${esc(c.tradeoff)}</span>`:''}</div>`;}
function openEquipment(){
  if(j.phase!=='world'){openDeck();return;}
  showPanel('equipment','A little help from the world',`<p class="panel-note">Equipment grants abilities, not levels. One piece per slot. Change it freely outside fights; each ability resets for a fresh bout.</p><div class="equipment-grid">${Object.entries(j.equipment).map(([slot,id])=>`<section class="equipment-slot"><h3>${esc(slot)}</h3><select data-slot="${slot}" aria-label="${slot} equipment"><option value="">None</option>${j.inventory.filter(key=>data.items[key].slot===slot).map(key=>`<option value="${key}" ${id===key?'selected':''}>${esc(data.items[key].name)}</option>`).join('')}</select><p>${esc(id?data.items[id].text:'No piece equipped in this slot yet.')}</p></section>`).join('')}</div>`,'EQUIPMENT');
}
function openWorld(){
  showPanel('world','A world worth wandering',`<p class="panel-note">After the First Courtyard, regions can be explored in any order. Complete every region to qualify for El’s divine tournament. This playable chapter contains Sumer; the other regions are future chapters.</p><div class="world-routes"><article><span class="eyebrow">${j.flags.regionComplete?'REGIONAL SEAL EARNED':'YOUR FIRST REGION'}</span><h3>The First Courtyard</h3><p>Sumer · canals, cedar guardians, and a sky with opinions.</p><button data-dismiss>Return to Sumer</button></article>${[['Jade Terraces','Beyond the eastern mountains'],['Gardens of the Nile','Beyond the southern river'],['The Cedar Coast','Beyond the western sea']].map(([title,where])=>`<article class="future"><span class="eyebrow">FUTURE CHAPTER</span><h3>${title}</h3><p>${where}. ${j.flags.regionComplete?'Your first seal has opened this road.':'Earn the First Courtyard’s seal to open the wider world.'}</p><small>Not yet explorable</small></article>`).join('')}</div><p class="panel-note" style="margin:22px 0 0">“Take your time on the way here. That is rather the point.” — a note attributed to El</p>`,'THE ROAD TO THE DIVINE TOURNAMENT');
}
function openMoves(id){const npc=data.enemies[id],cards=npc.moves.map(key=>data.cards.find(c=>c.id===key)),signature=data.cards.find(c=>c.id===npc.signature);cards.sort((a,b)=>Number(b.id===npc.signature)-Number(a.id===npc.signature));showPanel('moves',npc.name,`<p class="panel-note">${npc.life} life · Opening hand: ${esc(npc.color)} · 36 cards. Their complete moveset is public; their choice in each exchange stays hidden. They share your 12 baseline techniques and replace six PvP moves with their own variants.</p><div class="unlock-list"><strong>Signature: ${esc(signature.name)}</strong><br>${esc(signature.tradeoff)}</div><p class="panel-note">Defeat them once to unlock their six signature techniques, including this signature.</p>${npc.armor.map(key=>`<p class="panel-note"><b>${esc(data.items[key].name)}</b>: ${esc(data.items[key].text)}</p>`).join('')}<div class="card-library">${cards.map(c=>`<article class="library-entry${c.artPath?' champion-technique':''}${c.headline?' headline-technique':''}">${cardOrigin(c)}${DFGCardRenderer.details(c,data.colors)}</article>`).join('')}</div>`,'STUDY THE CHALLENGER');}
function openResult(){
  const r=j.lastResult;if(!r)return;showPanel('result',r.title,`<div class="dialogue-body"><img class="dialogue-portrait" src="${esc(data.enemies[r.enemy].art)}" alt="${esc(data.enemies[r.enemy].name)}"><p class="dialogue-text">${esc(r.text)}</p></div>${r.first?`<div class="unlock-list"><strong>Permanent unlocks</strong><br>${r.cards.map(id=>esc(data.cards.find(c=>c.id===id).name)).join(' · ')}<br><strong>${esc(data.items[r.item].name)}</strong></div>`:''}<div class="dialogue-actions"><button class="primary" data-continue>Return to the courtyard →</button></div>`,r.won?'A RESPECTFUL BOW':'THE JOURNEY CONTINUES');
}
async function continueJourney(){try{await send({type:'continue'});visual=center(j.position);closePanel(false);}catch{}}
document.querySelectorAll('[data-panel]').forEach(b=>b.addEventListener('click',()=>{if(!j)return;({journal:openJournal,deck:openDeck,equipment:openEquipment,world:openWorld})[b.dataset.panel]();}));
$('followQuest').addEventListener('click',()=>{const q=j.quests.find(q=>!q.done);markTarget(q?.target??'gate');});
function markTarget(id){if(!j.entities[id]&&j.zone==='sanctuary')id='exit';focusedQuest=id;closePanel(false);fit=true;$('overview').setAttribute('aria-pressed','true');toast(j.entities[id]?.name+' is highlighted. Click it to walk there.');}
$('resumeFight').addEventListener('click',()=>location.href='index.php?adventure=1');
$('panelContent').addEventListener('click',async e=>{
  const b=e.target.closest('button');if(!b||b.disabled)return;
  if(b.dataset.dismiss!==undefined){closePanel();return;}
  if(b.dataset.target){markTarget(b.dataset.target);return;}
  if(b.dataset.color){deckColor=b.dataset.color;openDeck(false);return;}
  if(b.dataset.resume!==undefined){location.href='index.php?adventure=1';return;}
  if(b.dataset.continue!==undefined){await continueJourney();return;}
  if(b.id==='saveDeck'){b.disabled=true;try{await send({type:'deck',cards:[...draft]});closePanel(false);toast('Your six hands are saved.');}catch{b.disabled=false;}return;}
  if(b.dataset.dialogAction==='world'){openWorld();return;}
  if(b.dataset.dialogAction==='door'){
    b.disabled=true;$('loading').querySelector('h2').textContent='Stepping through…';$('loading').querySelector('p').textContent='A quiet moment, then back to the journey.';$('loading').hidden=false;
    try{await send({type:'door',id:b.dataset.person});await loadImage(data.map.art);visual=center(j.position);focusedQuest=null;closePanel(false);}catch{b.disabled=false;}
    finally{$('loading').hidden=true;}return;
  }
  if(b.dataset.dialogAction==='moves'){openMoves(b.dataset.person);return;}
  if(b.dataset.dialogAction==='challenge'){
    b.disabled=true;try{await send({type:'challenge',id:b.dataset.person,color:$('openingColor').value});location.href='index.php?adventure=1';}catch{b.disabled=false;}
  }
});
$('panelContent').addEventListener('change',async e=>{
  if(e.target.dataset.card){
    const id=Number(e.target.dataset.card);if(e.target.checked)draft.add(id);else draft.delete(id);
    const count=color=>[...draft].filter(key=>data.cards.find(c=>c.id===key)?.color===color).length;
    document.querySelector('.deck-toolbar strong').textContent=draft.size+' techniques equipped';
    document.querySelectorAll('[data-color]').forEach(tab=>tab.textContent=tab.dataset.color+' '+count(tab.dataset.color)+'/6');
    document.querySelectorAll('[data-card]').forEach(input=>{const selected=draft.has(Number(input.dataset.card));input.disabled=!selected&&count(deckColor)>=6;input.closest('label').querySelector('[data-equip-label]').textContent=selected?'Equipped':'Add to '+deckColor;});
  }
  if(e.target.dataset.slot){const slot=e.target.dataset.slot;try{e.target.disabled=true;await send({type:'equip',slot,item:e.target.value||null});openEquipment();document.querySelector('[data-slot="'+slot+'"]').focus();toast(slot+' equipment saved.');}catch{e.target.disabled=false;}}
});
async function boot(){
  try{
    await refresh();visual=center(j.position);
    const sources=[data.map.art,'adventure/assets/challenger-overworld-v1.png','adventure/assets/elder-overworld-v1.png',...Object.values(data.enemies).map(e=>e.art),...Object.keys(data.enemies).map(id=>'adventure/assets/'+(id==='huwawa'?'huwawa-kneeling-v2.png':id+'-kneeling.png'))];
    await Promise.all(sources.map(loadImage));resize();ready=true;$('loading').hidden=true;canvas.focus({preventScroll:true});
    if(j.phase==='result')openResult();else if(j.phase==='fight')toast('Your challenge is saved. Use “Resume your challenge” to return.');else if(j.dialog)openDialogue(j.dialog);
  }catch(e){fail(e);$('loading').innerHTML='<h2>Your courtyard is still here.</h2><p>Reload to try arriving again.</p>';}
}
requestAnimationFrame(draw);boot();
})();
