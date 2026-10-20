'use strict';
let state = null, token = '', catalog = new Map(), styles = {}, busy = false, selectedMain = null, selectedBonuses = new Set(), browseColor = null;
let selectedAdvantages = new Set(), extraSwitch = null;
let opponentColor = null;
let fightLogTrigger = null;
let activeMotion = [];
let liquidImpacts = [null, null];
let timelineState = null, timelineAnimation = null;
let roundDisplayState = null, roundAnimations = [];
const orbMotionPreference = matchMedia('(prefers-reduced-motion: reduce)');
orbMotionPreference.addEventListener('change', () => {
  liquidImpacts = [null, null];
  if (state) renderTimeline(state, true);
  if (state) renderRound(state, true);
  document.querySelectorAll('.orb-slosh, .health-slosh').forEach(surface => surface.getAnimations().forEach(animation => animation.cancel()));
});
const $ = id => document.getElementById(id);
const esc = value => String(value ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch]));
const card = id => catalog.get(id);
const classColor = color => color.toLowerCase();
const cardHand = (player, id) => player.cardHands?.[id] ?? card(id).color;
function orbMeter(count) {
  const extra=Math.max(0,count-6);
  const wave='<path d="M0 5 Q50 4 100 5 T200 5 V100 H0 Z" fill="currentColor"/><path d="M0 5 Q50 4 100 5 T200 5" fill="none" stroke="white" stroke-opacity=".35" stroke-width=".8"/>';
  return `<span class="orb-meter ${extra?'overfilled':''}"><span class="orb-sphere"><span class="orb-fill" data-empty="${count===0}" data-full="${count>=6}" style="height:${Math.min(100,count/6*100)}%"><span class="orb-slosh" aria-hidden="true"><span class="orb-liquid"><svg class="orb-wave orb-wave-back" viewBox="0 0 200 100" preserveAspectRatio="none">${wave}</svg><svg class="orb-wave orb-wave-front" viewBox="0 0 200 100" preserveAspectRatio="none">${wave}</svg></span></span></span><span class="orb-count">${count}</span></span>${extra?`<span class="orb-extra" aria-hidden="true">+${extra}</span>`:''}</span>`;
}
function sloshFighterLiquid(seat, amount) {
  if(orbMotionPreference.matches || !Number.isFinite(amount) || amount<=0) return;
  // A damped impulse keeps small hits soft, while big hits rock farther and settle longer.
  const strength=Math.min(amount,20), direction=seat===0?1:-1;
  const duration=1500+strength*85, angle=(5+strength*1.8)*direction;
  const frames=Array.from({length:81},(_,index)=>{
    const progress=index/80, decay=Math.pow(1-progress,2.3);
    const tilt=Math.sin(progress*Math.PI*10)*angle*decay;
    const lift=Math.sin(progress*Math.PI*10+Math.PI/2)*(1+strength*.18)*decay*Math.sin(progress*Math.PI);
    return {offset:progress,transform:`translateY(${lift}px) rotate(${tilt}deg)`};
  });
  const healthFrames=Array.from({length:81},(_,index)=>{
    const progress=index/80, decay=Math.pow(1-progress,2.3);
    const wave=Math.sin(progress*Math.PI*10)*decay;
    const edge=66.667+wave*Math.min(20,3+strength*1.1);
    const lean=wave*Math.min(7,1+strength*.4);
    // Deform the liquid boundary, keeping the reservoir itself level.
    return {offset:progress,clipPath:`polygon(0 0, ${edge+lean}% 0, ${edge+lean*.3+.5*wave}% 33%, ${edge-lean*.3+.5*wave}% 67%, ${edge-lean}% 100%, 0 100%)`};
  });
  liquidImpacts[seat]={start:performance.now(),duration,frames,healthFrames};
  document.querySelectorAll(`#fighters .fighter:nth-child(${seat+1}) .orb-slosh, #fighters .fighter:nth-child(${seat+1}) .health-slosh`).forEach(surface=>surface.getAnimations().filter(animation=>!(animation instanceof CSSAnimation)).forEach(animation=>animation.cancel()));
  restoreLiquidImpacts();
  if(window.DFGTutorial) DFGTutorial.render();
}
function restoreLiquidImpacts() {
  if(orbMotionPreference.matches) return;
  liquidImpacts.forEach((impact,seat)=>{
    if(!impact) return;
    const elapsed=performance.now()-impact.start;
    if(elapsed>=impact.duration) {liquidImpacts[seat]=null;return;}
    document.querySelectorAll(`#fighters .fighter:nth-child(${seat+1}) .orb-fill:not([data-empty="true"]):not([data-full="true"]) .orb-slosh, #fighters .fighter:nth-child(${seat+1}) .health-fill:not([data-empty="true"]):not([data-full="true"]) .health-slosh`).forEach(surface=>{
      if(surface.getAnimations().some(animation=>!(animation instanceof CSSAnimation))) return;
      const animation=surface.animate(surface.classList.contains('health-slosh')?impact.healthFrames:impact.frames,{duration:impact.duration,easing:'linear'});
      animation.currentTime=elapsed;
    });
  });
}
let previewId = null, previewTimer = null, previewAnchor = null;
function closePreview() {
  clearTimeout(previewTimer); previewId = null; previewAnchor = null; $('cardPreview').hidden = true;
}
function cardDetails(c) { return DFGCardRenderer.details(c, styles, {textOnly:true}); }
function positionPreview() {
  const preview = $('cardPreview'); if(preview.hidden || !previewAnchor) return;
  if (!previewAnchor.isConnected) { closePreview(); return; }
  const rect = previewAnchor.getBoundingClientRect();
  const gap = 12, margin = 12, width = preview.offsetWidth, height = preview.offsetHeight;
  // Hand cards read upward from their row instead of jumping between left and
  // right placement as the pointer crosses the middle of the hand.
  const hand = !!previewAnchor.closest('#cards');
  let x = hand ? rect.left + (rect.width-width)/2 : rect.right + gap;
  if(!hand && x + width > innerWidth - margin) x = rect.left - width - gap;
  x = Math.max(margin, Math.min(x, innerWidth - width - margin));
  const y = Math.max(margin, Math.min(hand ? rect.top-height-gap : rect.top, innerHeight - height - margin));
  preview.style.left = `${x}px`; preview.style.top = `${y}px`;
}
function showPreview(id, anchor) {
  if(!card(id)) return;
  clearTimeout(previewTimer);
  // Pointer events bubble from each child. Keep the existing DOM (and scroll
  // position) when the same card receives another hover or focus event.
  if (previewId !== id) {
    $('previewContent').innerHTML = cardDetails(card(id));
    $('cardPreview').scrollTop = 0;
  }
  previewId = id; previewAnchor = anchor;
  $('cardPreview').hidden = false;
  positionPreview();
}
function delayClosePreview() {
  clearTimeout(previewTimer); previewTimer = setTimeout(closePreview, 100);
}
function showError(message) {
  const target = $('tutorialError') && !$('gameView').hidden ? $('tutorialError') : $('error');
  for (const id of ['error', 'tutorialError']) if ($(id)) { $(id).textContent = ''; $(id).hidden = true; }
  target.textContent = message; target.hidden = !message;
}
async function request(body = null) {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), 12000);
  try {
    const options = body ? {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({...body,token})} : {};
    const response = await fetch($('tutorialGuide') ? 'api.php?tutorial=1' : 'api.php', {...options, cache:'no-store', signal:controller.signal});
    const data = await response.json();
    if (!data.ok) { const error = new Error(data.error || 'Request failed'); error.status = response.status; throw error; }
    return data;
  } catch (error) {
    if (controller.signal.aborted) throw new Error('The connection timed out. Try again; your progress is saved.');
    throw error;
  } finally { clearTimeout(timer); }
}
function accept(data) {
  if(window.DFGTutorial) DFGTutorial.accept(data.tutorial);
  const previousState=state;
  const moves=[];
  if(state && data.state && state.matchId===data.state.matchId) {
    for(const seat of [0,1]) for(const id of data.state.players[seat].discarded.filter(id=>!state.players[seat].discarded.includes(id))) {
      if(seat===0&&state.phase==='planning'&&selectedBonuses.has(id)) continue;
      const burnedMain=state.commitments[seat]?.main===id;
      moves.push({type:'ZONE_MOVE',source:burnedMain?`played-main-${seat}`:seat===0?`hand-${id}`:`orb-${cardHand(state.players[seat],id)}`,previewCardID:seat===1&&!burnedMain?id:null,destination:`burn-${seat}-${id}`,durationMs:340});
    }
    if(state.phase==='reactions'&&['relocating','routing','directing','switching'].includes(data.state.phase)&&!data.state.players[0].discarded.includes(state.commitments[0].main)) moves.push({type:'ZONE_MOVE',source:'played-main-0',destination:`hand-${state.commitments[0].main}`,durationMs:340});
  }
  const prepared=prepareMotion(moves);
  if(!state || state.matchId !== data.state?.matchId) { DFGFighterReactions.reset(); liquidImpacts=[null,null]; opponentColor=null; ClosePopup(); HideCardDetail(true); $('damageEffects').replaceChildren(); }
  const changed = !state || !data.state || state.matchId !== data.state.matchId || state.round !== data.state.round || state.phase !== data.state.phase;
  token = data.token; state = data.state; styles = data.colors; catalog = new Map(data.cards.map(c => [c.id,c]));
  if (changed) { selectedMain = null; selectedBonuses.clear(); selectedAdvantages.clear(); extraSwitch=null; browseColor = state?.players[0].color ?? 'Red'; }
  if (changed) closePreview();
  render();
  DFGAssetPreloader.warmCards(data.cards, [state?.players[0].color ?? 'Red', state?.players[1].color ?? 'Purple']);
  animateMeterChanges(previousState,state);
  TCGCardMotion.playPreparedZoneMoves(prepared,1);
}
function animateMeterChanges(previous,next) {
  if(next?.phase==='finished') DFGFighterReactions.syncOutcome(next.winner,{animate:!!previous&&previous.matchId===next.matchId&&previous.phase!=='finished'});
  if(!previous||!next||previous.matchId!==next.matchId) return;
  const reduced=matchMedia('(prefers-reduced-motion: reduce)').matches;
  const resolved=previous.phase==='reactions'&&['stripping','relocating','routing','directing','switching','finished'].includes(next.phase);
  const damage=next.players.map((player,seat)=>Math.max(0,Number(resolved?next.stats?.[1-seat]?.damage??previous.players[seat].life-player.life:previous.players[seat].life-player.life)));
  damage.forEach((amount,seat)=>{if(resolved||amount>0) DFGFighterReactions.play(seat,amount);});
  damage.forEach((amount,seat)=>{if(amount>0) showDamageImpact(seat,amount,reduced);});
  if(reduced) return;
  damage.forEach((amount,seat)=>{if(amount>0) sloshFighterLiquid(seat,amount);});
  const shake=(element,amount,className)=>{
    if(!element||!amount) return;
    const hand=className==='hand-impact', heavy=amount>=5;
    element.style.setProperty('--impact-x',`${hand?Math.min(28,heavy?11+(amount-5)*1.8:2+amount*.9):Math.min(6.25,1+amount*.35)}px`);
    element.style.setProperty('--impact-y',`${hand?Math.min(12,heavy?5+(amount-5)*.7:1+amount*.4):Math.min(2.5,.5+amount*.13)}px`);
    element.style.setProperty('--impact-duration',`${hand?Math.min(1000,heavy?680+(amount-5)*32:320+amount*40):Math.min(560,280+amount*18)}ms`);
    if(hand) element.style.setProperty('--impact-tilt',`${Math.min(.75,.15+amount*.05)}deg`);
    element.classList.add(className);
    element.addEventListener('animationend',()=>element.classList.remove(className),{once:true});
  };
  shake(document.querySelector('.arena-art'),Math.max(...damage),'art-impact');
  shake($('cards'),damage[0],'hand-impact');
  const pulse=selector=>{
    const fill=document.querySelector(selector);
    if(!fill) return;
    fill.classList.add('meter-change');
    fill.addEventListener('animationend',event=>{if(event.animationName==='arcade-response') fill.classList.remove('meter-change');},{once:true});
  };
  for(const seat of [0,1]) {
    const before=previous.players[seat],after=next.players[seat];
    if(before.life>after.life) {
      const track=document.querySelector(`#fighters .fighter:nth-child(${seat+1}) .health-track`);
      if(track) {
        const trail=document.createElement('span');
        trail.className='health-trail';trail.setAttribute('aria-hidden','true');
        trail.style.width=`${Math.min(100,Math.max(0,before.life)/40*100)}%`;
        track.append(trail);trail.addEventListener('animationend',()=>trail.remove(),{once:true});
      }
    }
    if(before.life!==after.life||before.color!==after.color) pulse(`#fighters .fighter:nth-child(${seat+1}) .health-fill`);
    for(const color of Object.keys(styles)) {
      const count=player=>player.available.filter(id=>cardHand(player,id)===color).length;
      if(count(before)!==count(after)||(before.color!==after.color&&after.color===color)) pulse(`#${seat===0?'your-orb':'orb'}-${color} .orb-fill`);
    }
  }
}
function showDamageImpact(seat,amount,reduced) {
  const number=document.createElement('span');
  number.className=`damage-number damage-seat-${seat}${reduced?' reduced-impact':''}`;
  number.textContent=`−${amount}`;
  number.style.setProperty('--damage-scale',String(1+Math.min(amount/24,.5)));
  $('damageEffects').append(number);
  if(reduced) setTimeout(()=>number.remove(),1500);
  else {
    number.addEventListener('animationend',()=>number.remove(),{once:true});
    const track=document.querySelector(`#fighters .fighter:nth-child(${seat+1}) .health-track`);
    if(track) {track.classList.add('damage-hit');setTimeout(()=>track.classList.remove('damage-hit'),1150);}
  }
}
function prepareMotion(moves) {
  TCGCardMotion.discardPrepared(activeMotion); activeMotion=[];
  if(!TCGCardMotion.isEnabled('DFGSim')) return [];
  const proxies=[];
  for(const move of moves) if(move.previewCardID) {
    const source=$(move.source); if(!source) continue;
    const rect=source.getBoundingClientRect(), c=card(move.previewCardID), proxy=document.createElement('div');
    proxy.id=`motion-proxy-${move.previewCardID}`;proxy.className=`tile-face ${classColor(c.color)}`;
    proxy.style.cssText=`position:fixed;left:${rect.left+rect.width/2-48}px;top:${rect.top+rect.height/2-48}px;width:96px;height:96px;pointer-events:none;`;
    proxy.innerHTML=DFGCardRenderer.mini(c, {badge:"BURNED"});
    document.body.append(proxy);proxies.push(proxy);move.source=proxy.id;
  }
  const prepared=TCGCardMotion.prepareZoneMoves(moves,1);proxies.forEach(el=>el.remove());
  for(const item of prepared) {
    item.clone.setAttribute('aria-hidden','true');
    for(const el of [item.clone,...item.clone.querySelectorAll('*')]) for(const name of ['data-opponent','data-hand-action','data-inspect','data-opponent-card']) el.removeAttribute(name);
  }
  activeMotion=prepared;return prepared;
}
function changeSelection(update,moves=[]) {
  const prepared=prepareMotion(moves);
  update(); closePreview(); render();
  TCGCardMotion.playPreparedZoneMoves(prepared,1);
}
function clickHandCard(id,button) {
  if(busy||!state||state.needsNewMatch) return;
  const p=state.players[0], c=card(id);
  const playable=cardHand(p,id)===p.color&&!p.discarded.includes(id);
  if(playable&&state.phase==='reactions'&&state.reactionTurn===0&&state.reactionOptions.includes(id)) { closePreview();act({type:'react',card:id});return; }
  if(playable&&state.phase==='planning'&&!state.commitments[0]) {
    if(selectedMain===id) changeSelection(()=>{selectedMain=null;selectedAdvantages.clear();extraSwitch=null;},[{type:'ZONE_MOVE',source:'played-main-0',destination:`hand-${id}`}]);
    else if(selectedBonuses.has(id)) changeSelection(()=>selectedBonuses.delete(id),[{type:'ZONE_MOVE',source:`burn-0-${id}`,destination:`hand-${id}`}]);
    else if(!selectedMain) changeSelection(()=>{selectedMain=id;selectedAdvantages.clear();extraSwitch=null;},[{type:'ZONE_MOVE',source:`hand-${id}`,destination:'played-main-0'}]);
    else if(!selectedBonuses.size&&c.bonus&&p.burnLockedRound!==state.round) changeSelection(()=>selectedBonuses.add(id),[{type:'ZONE_MOVE',source:`hand-${id}`,destination:`burn-0-${id}`}]);
    return;
  }
}
async function act(action) {
  if (busy || !state) return;
  busy = true; showError(''); render();
  let accepted=false;
  try { const data=await request({command:'action',action,revision:state.revision,matchId:state.matchId});busy=false;accept(data);accepted=true; }
  catch(error) { if(error.status===409 || !error.status) { try { accept(await request()); } catch {} } showError(error.message); }
  finally { busy = false; if(!accepted) render(); }
}
function fighter(seat) {
  const p = state.players[seat];
  const advantage=`<div class="advantage-tray" aria-label="${seat===0?'Your':'Bot'} advantage tokens"><span>ADV</span>${(p.advantages??[]).length?p.advantages.map(color=>`<span class="advantage-token ${classColor(color)}" title="${esc(color)} advantage" aria-label="${esc(color)} advantage"></span>`).join(''):'<span class="muted" aria-label="No advantage">—</span>'}</div>`;
  return `<div class="fighter ${classColor(p.color)}"><div class="fighter-head"><div class="fighter-identity"><h2>${seat===0?'YOU':'SPARRING BOT'}</h2></div><div class="fighter-vitals">${advantage}<div class="life">${p.life}<small> / 40</small></div></div></div><div class="health-track"><div class="health-fill" data-full="${p.life>=40}" data-empty="${p.life<=0}" style="width:${Math.min(40,Math.max(0,p.life))/40*100}%"><span class="health-slosh" aria-hidden="true"><svg class="health-meniscus" viewBox="0 0 18 100" preserveAspectRatio="none"><defs><linearGradient id="health-glass-${seat}" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="white" stop-opacity=".22"/><stop offset=".42" stop-color="white" stop-opacity=".04"/><stop offset=".55" stop-color="black" stop-opacity="0"/><stop offset="1" stop-color="black" stop-opacity=".2"/></linearGradient></defs><rect width="18" height="100" fill="currentColor"/><rect width="18" height="100" fill="url(#health-glass-${seat})"/></svg></span></div></div>${handStatus(seat)}</div>`;
}
function reveal(seat) {
  const staged=seat===0&&state.phase==='planning'&&!state.commitments[0];
  const plan = staged&&selectedMain?{main:selectedMain,bonuses:[...selectedBonuses]}:state.commitments[seat], owner = seat===0?'Your played card':'Opponent played card';
  const burns=staged?[...selectedBonuses].map(id=>({id,type:'BONUS'})):(plan&&!plan.hidden?[...plan.bonuses.map(id=>({id,type:'BONUS'})),...state.reactions.filter(r=>r.seat===seat).map(r=>({id:r.card,type:'REACTION'})),...(plan.cancelledFrom?[{id:plan.cancelledFrom,type:'CANCEL BURN'}]:[]),...(state.players[seat].discarded.includes(plan.main)?[{id:plan.main,type:'MAIN BURN'}]:[])]:[]);
  const burnTile=({id,type})=>`<button id="burn-${seat}-${id}" class="played-burn tile-face ${classColor(card(id).color)}" ${staged?`data-hand-action="${id}"`:''} data-inspect="${id}" aria-label="${staged?'Undo bonus':`Inspect ${type.toLowerCase()} burn`} ${esc(card(id).name)}">${DFGCardRenderer.mini(card(id), {badge:staged?"UNDO":type})}</button>`;
  const bonuses=burns.filter(burn=>burn.type==='BONUS'), reactions=burns.filter(burn=>burn.type!=='BONUS');
  const emptyReaction='<div class="burn-placeholder"><span class="slot-label">Reactions</span></div>';
  const reactionMarkup=`<div class="reaction-slots slot-group" aria-label="Reactions"><div class="reaction-cards">${reactions.map(burnTile).join('')}${emptyReaction}</div></div>`;
  const bonusMarkup=`<div class="bonus-slot slot-group" aria-label="Bonus burn">${bonuses.map(burnTile).join('')||'<div class="burn-placeholder"><span class="slot-label">Bonus burn</span></div>'}</div>`;
  let mainMarkup;
  if (!plan || plan.hidden) mainMarkup=`<div id="played-main-${seat}" class="played-back" aria-label="${owner}: ${plan?'face down':'no main selected'}"><span class="slot-label">${seat===0?'Your main':'Opponent main'}</span></div>`;
  else {
    const main=card(plan.main), stats=state.stats?.[seat];
    mainMarkup=`<button id="played-main-${seat}" class="tile-face ${classColor(main.color)}" data-inspect="${main.id}" ${staged?`data-hand-action="${main.id}"`:''} aria-label="${staged?'Undo main selection: ':'Inspect '}${esc(main.name)}">${DFGCardRenderer.mini(main, {attack:stats?.attack??main.attack, block:stats?.block??main.block})}</button>${stats?`<span class="main-status">${['cancelling','reactions'].includes(state.phase)?'Projected':'Dealt'}: <strong>${stats.damage}</strong>${stats.directDamage?` · ${stats.directDamage} direct`:''}${stats.pierce?` · pierce ${stats.pierce}`:''}</span>`:''}${plan.advantages?.length?`<span class="main-status">Spent${plan.cancelledFrom?' on Darting':''}: ${plan.advantages.map(esc).join(' + ')}</span>`:''}`;
  }
  const mainSlot=`<div class="main-slot slot-group" aria-label="${seat===0?'Your main':'Opponent main'}">${mainMarkup}</div>`;
  return `<div class="reveal combat-slots" aria-label="${owner}"><div class="played-burns" id="burns-${seat}">${reactionMarkup}${bonusMarkup}</div>${mainSlot}</div>`;
}
function handStatus(seat) {
  const p = state.players[seat], own=seat===0;
  return `<div id="${own?'yourOrbs':'opponentOrbs'}" class="hand-orbs" aria-label="${own?'Browse your hands':'Opponent hand inventory'}">${Object.keys(styles).map(color=>{
    const remaining = p.available.filter(id=>cardHand(p,id)===color), burned = p.discarded.filter(id=>cardHand(p,id)===color);
    return `<button id="${own?'your-orb':'orb'}-${color}" class="hand-orb ${classColor(color)} ${color===p.color?'active-hand':''}" ${own?`data-browse="${color}" aria-pressed="${(browseColor??p.color)===color}" aria-controls="cards"`:`data-opponent="${color}" aria-haspopup="dialog" aria-expanded="${opponentColor===color}"`} aria-label="${own?'Browse your':'Opponent'} ${color} hand: ${remaining.length} remaining (starting size 6), ${burned.length} burned${color===p.color?', active':''}${!remaining.length?', exhausted':''}" title="${color}: ${remaining.length} remaining · starting size 6 · ${burned.length} burned${color===p.color?' · Active':''}">${orbMeter(remaining.length)}<span class="orb-label">${color}</span></button>`;
  }).join('')}</div>`;
}
function renderOpponentPopup() {
  const p=state.players[1], color=opponentColor;
  const remaining=p.available.filter(id=>cardHand(p,id)===color), burned=p.discarded.filter(id=>cardHand(p,id)===color);
  const tile=id=>{const c=card(id);return `<button class="tile-face ${classColor(c.color)}" data-opponent-card="${id}" aria-label="Inspect ${esc(c.name)}">${DFGCardRenderer.mini(c, {badge:burned.includes(id)?"BURNED":""})}</button>`;};
  const html=`<p class="muted">${remaining.length} remaining · starting size 6 · ${burned.length} permanently burned. Hover or focus a card for details.</p><div class="opponent-popup-cards remaining-cards">${remaining.map(tile).join('')||'<p>This hand is exhausted.</p>'}</div>${burned.length?`<h3 class="burned-label">PERMANENTLY BURNED</h3><div class="opponent-popup-cards burned-cards">${burned.map(tile).join('')}</div>`:''}<p class="muted">Face-down commitments stay hidden. Main cards return after damage; Bait and Slip can change their hand.</p>`;
  $('popupContainer').innerHTML=createPopupHTML('opponentHand','',{titleHTML:`Opponent · ${esc(color)} hand`,contentHTML:html});
  const popup=$('opponentHandPopup'); popup.setAttribute('role','dialog'); popup.setAttribute('aria-label',`Opponent ${color} hand`);
  _openPopup='opponentHand';
}
function closeOpponentPopup() {
  ClosePopup(); HideCardDetail(true); const color=opponentColor; opponentColor=null;
  document.querySelector(`[data-opponent="${color}"]`)?.setAttribute('aria-expanded','false');
  document.querySelector(`[data-opponent="${color}"]`)?.focus();
}
function showRulesPopup() {
  if(opponentColor) closeOpponentPopup();
  closePreview();HideCardDetail(true);
  $('popupContainer').innerHTML=createPopupHTML('rules','',{titleHTML:'Prototype rules',contentHTML:$('rulesContent').innerHTML});
  const popup=$('rulesPopup');popup.setAttribute('role','dialog');popup.setAttribute('aria-label','Prototype rules');
  _openPopup='rules';$('showRules').setAttribute('aria-expanded','true');
  popup.querySelector('button').setAttribute('aria-label','Close rules');popup.querySelector('button').focus();
}
function closeRulesPopup() {
  ClosePopup();$('showRules').setAttribute('aria-expanded','false');($('home').hidden?$('matchMenu').querySelector('summary'):$('homePlay')).focus();
}
function showScreen(play) {
  closePreview(); HideCardDetail(true); ClosePopup(); opponentColor=null;
  $('home').hidden=play; $('gameView').hidden=!play;
  if(play) $('matchMenu').querySelector('summary').focus(); else $('homeTitle').focus();
  window.scrollTo(0,0);
}
function advantageControls() {
  const c=card(selectedMain), rule=c?.advantage?.spend;
  if(!rule) return '';
  const held=(state.players[0].advantages??[]).filter(color=>rule.colors.includes(color));
  const limit=rule.flexible?2:1;
  const extraColors=Object.keys(styles).filter(color=>color!==state.players[0].color&&!c.switches.includes(color)&&state.players[0].available.some(id=>cardHand(state.players[0],id)===color));
  const choices=held.length?held:rule.colors;
  const payoff=rule.byCount?'Any one color: +1 attack. Any two different colors instead: +4 attack, +2 block, ignore 2 opposing block.':rule.flexible?'Red/Purple +1 attack · Green/Blue +1 block · Orange ignore 1 block · Yellow extra switch':c.ability.split('\n').filter(line=>/spend/i.test(line)).join(' ');
  const earners=!held.length?[...catalog.values()].filter(candidate=>rule.colors.includes(candidate.advantage?.earn?.color)):[];
  const selected=[...selectedAdvantages];
  return `<details id="advantagePicker" class="advantage-picker ${held.length?'':'unavailable'}" ${$('advantagePicker')?.open?'open':''}><summary>Spend advantage ${selected.length?`<span class="spend-selection">${selected.map(color=>`<i class="${classColor(color)}" title="${esc(color)}"></i>`).join('')}<span>${selected.length}/${limit}</span></span>`:`<span class="spend-count">${held.length?'Optional':'None held'}</span>`}</summary><div class="advantage-popover"><strong>${esc(c.name)} · optional spend</strong><div class="token-choices">${choices.map(color=>`<button class="advantage-token ${classColor(color)}" data-advantage="${color}" aria-pressed="${selectedAdvantages.has(color)}" ${!held.includes(color)||busy||(!selectedAdvantages.has(color)&&selectedAdvantages.size===limit)||(color==='Yellow'&&rule.flexible&&!rule.byCount&&!extraColors.length)?'disabled':''}>${selectedAdvantages.has(color)?'✓ ':''}${color}${!held.includes(color)?' · not held':''}</button>`).join('')}</div><p>${esc(payoff)}</p>${!held.length?`<p>Earn ${rule.colors.map(esc).join(' / ')} advantage through a main card’s objective.</p><details class="advantage-earning"><summary>How to earn it</summary><p>${earners.map(candidate=>`${esc(candidate.name)}: ${esc(candidate.ability.split('\n').find(line=>line.startsWith('Objective:'))?.replace('Objective: ',''))}`).join(' ')}</p></details>`:''}${rule.flexible&&!rule.byCount&&selectedAdvantages.has('Yellow')?`<label>Yellow: extra switch<select id="extraSwitch" ${busy?'disabled':''}>${extraColors.map(color=>`<option value="${color}" ${extraSwitch===color?'selected':''}>${color}</option>`).join('')}</select></label>`:''}<small>Choose before committing. Bonus and reaction burns cannot spend advantage.</small></div></details>`;
}
function cardMarkup(c) {
  const p = state.players[0], active = cardHand(p,c.id) === p.color, discarded = p.discarded.includes(c.id), committed = state.commitments[0]?.main === c.id;
  const canPlan = !state.needsNewMatch && active && !discarded && state.phase==='planning' && !state.commitments[0];
  const canReact = !state.needsNewMatch && state.phase==='reactions' && state.reactionTurn===0 && state.reactionOptions.includes(c.id);
  const status=discarded?'Permanently burned':canPlan?(selectedMain===c.id||selectedBonuses.has(c.id)?'Click to undo':!selectedMain?'Click for main':p.burnLockedRound===state.round?'Burns locked this round':!selectedBonuses.size?(c.bonus?'Click for bonus burn':'No bonus effect'):'Main and bonus staged'):canReact?'Click to burn reaction':committed&&state.phase==='reactions'?'Main · reserved':active?'Inspect only':'Inactive · inspect';
  const selected=selectedMain===c.id||selectedBonuses.has(c.id);
  const playable=!busy&&!selected&&(canReact||(canPlan&&(!selectedMain||(!selectedBonuses.size&&c.bonus&&p.burnLockedRound!==state.round))));
  const badge = discarded?'BURNED':selectedMain===c.id?'MAIN':selectedBonuses.has(c.id)?'BONUS BURN':committed&&state.phase==='reactions'?'RESERVED':'';
  return `<article class="card-tile ${classColor(c.color)} ${playable?'playable':''} ${selectedMain===c.id?'main-selected':''} ${selectedBonuses.has(c.id)?'bonus-selected':''} ${discarded?'discarded':''}" data-card-id="${c.id}"><button id="hand-${c.id}" class="tile-face" data-hand-action="${c.id}" data-inspect="${c.id}" aria-pressed="${selectedMain===c.id||selectedBonuses.has(c.id)}" aria-label="${esc(c.name)}: ${status}" ${busy?'disabled':''}>${DFGCardRenderer.mini(c, {badge})}</button></article>`;
}
function controls() {
  const phase = state.phase, p = state.players[0];
  $('matchControls').dataset.phase=state.needsNewMatch?'rules-updated':phase;
  $('phaseTag').hidden=phase!=='finished'||state.needsNewMatch;
  if(state.needsNewMatch) {
    $('phaseTag').textContent='RULES UPDATED'; $('phaseTitle').textContent='Start a fresh match.';
    $('phaseHint').textContent='Rules or card balance changed. Start a new match to use the updated cards.';
    $('controls').innerHTML='<button id="playAgain" class="primary">Start new match</button>'; return;
  }
  $('phaseTag').textContent = `ROUND ${state.round} · ${phase==='planning'?'COMMIT':phase==='cancelling'?'CANCEL':phase==='reactions'?'REACT':phase==='stripping'?'REMOVE ADVANTAGE':phase==='relocating'?'MOVE MAIN':phase==='routing'?'CUT ROUTE':phase==='directing'?'DIRECT HAND':phase==='switching'?'SWITCH':'COMPLETE'}`;
  if(phase==='planning') {
    $('phaseTitle').textContent = 'Commit your move.';
    const burnsLocked=p.burnLockedRound===state.round;
    $('phaseHint').textContent = '';
    const help=burnsLocked?'Select a main card from your active hand. Bonus burns are locked this round.':'Select a main card from your active hand, then optionally select a second card as a bonus burn. Commit when ready.';
    $('controls').innerHTML = `<div class="commit-row"><div class="commit-prompt ${!selectedMain?'needs-main':''}" ${!selectedMain?'tabindex="0" role="group" aria-label="Commit unavailable: select a main card" aria-describedby="commitHelp"':''}><button id="commit" class="primary" ${busy||!selectedMain?'disabled':''}>${busy?'Committing…':'Commit'}</button>${!selectedMain?`<div id="commitHelp" class="commit-help" role="tooltip"><span class="eyebrow">CHOOSE YOUR MOVE</span><strong>Select your main</strong><p>${help}</p></div>`:''}</div><span class="commit-reminder">${burnsLocked?'Main only · burns locked':'Main + optional bonus burn'}</span>${advantageControls()}${selectedMain?`<button id="clear" class="text-button" ${busy?'disabled':''}>Clear</button>`:''}</div>`;
  } else if(phase==='cancelling') {
    $('phaseTitle').textContent=state.cancelOptions.length?'Keep Darting Strike or cancel.':'Waiting for the opposing cancel choice.';
    $('phaseHint').textContent='Yellow advantage is already spent. Replacing permanently burns Darting Strike; your committed bonus stays. Both choices reveal together before reactions. The replacement cannot spend advantage.';
    $('controls').innerHTML=state.cancelOptions.map(id=>`<button data-cancel="${id}" ${busy?'disabled':''}>${id===state.commitments[0].main?'Keep':'Cancel into'} ${esc(card(id).name)} · ${card(id).attack}/${card(id).block}</button>`).join('')||'<p class="muted">Your choice stays hidden until the opponent decides.</p>';
  } else if(phase==='reactions') {
    $('phaseTitle').textContent = '';
    $('phaseHint').textContent = '';
    $('controls').innerHTML = `<span class="reaction-summary" aria-live="polite"><strong>${state.passes}/2</strong> passes · ${state.reactionOptions.length} reactions</span><button id="pass" class="primary" title="Pass without burning a reaction. Two consecutive passes resolve damage." ${busy||state.reactionTurn!==0?'disabled':''}>${state.reactionTurn===0?'Pass reaction':'Bot reacting…'}</button>`;
  } else if(phase==='stripping') {
    $('phaseTitle').textContent=state.stripOptions.length?'Remove an opposing advantage.':'Opponent is removing advantage.';
    $('phaseHint').textContent='Mirror Step: dealing damage and taking no damage each remove one held opposing token. New objective rewards are awarded afterward.';
    $('controls').innerHTML=state.stripOptions.map(color=>`<button class="advantage-token ${classColor(color)}" data-strip="${esc(color)}" ${busy?'disabled':''}>Remove ${esc(color)}</button>`).join('')||'<p class="muted">Waiting for the opposing token choice.</p>';
  } else if(phase==='relocating') {
    $('phaseTitle').textContent=state.relocateOptions.length?'Move their main to a different hand.':'Opponent is choosing where your main returns.';
    $('phaseHint').textContent='Bait and Slip caught a bonus burn. Move the surviving opposing main before route cuts and next-hand choices, or leave it in its current hand. Its printed color and abilities stay the same.';
    $('controls').innerHTML=state.relocateOptions.length?`<div class="hand-orbs switch-orbs" role="group" aria-label="Move opposing main">${state.relocateOptions.map(color=>{
      const count=state.players[1].available.filter(id=>cardHand(state.players[1],id)===color).length;
      return `<button class="hand-orb ${classColor(color)}" data-relocate="${color}" aria-label="Return opposing main to ${color}: ${count} cards, ${count+1} after moving" ${busy?'disabled':''}>${orbMeter(count+1)}<span class="orb-label">${color}</span></button>`;
    }).join('')}</div><button data-relocate="keep" ${busy?'disabled':''}>Keep current hand</button>`:'<p class="muted">Waiting for the opposing return choice.</p>';
  } else if(phase==='routing') {
    $('phaseTitle').textContent=state.cutOptions.length?'Cut off a destination':'Opponent is cutting a route';
    $('phaseHint').textContent='';
    $('controls').innerHTML=state.cutOptions.length?`<div class="hand-orbs switch-orbs" role="group" aria-label="Cut off an opposing destination">${state.cutOptions.map(color=>{
      const remaining=state.players[1].available.filter(id=>cardHand(state.players[1],id)===color).length;
      const label=`Cut off ${color}: ${remaining} cards remaining`;
      return `<button class="hand-orb ${classColor(color)}" data-cut="${esc(color)}" aria-label="${esc(label)}" title="${esc(label)}" ${busy?'disabled':''}>${orbMeter(remaining)}<span class="orb-label">${esc(color)}</span></button>`;
    }).join('')}</div>`:'';
  } else if(phase==='directing') {
    $('phaseTitle').textContent=state.forceOptions.length?'Choose their next hand.':'Opponent is choosing your next hand.';
    $('phaseHint').textContent='Driving Kick connected with Yellow advantage. Choose from the opponent’s remaining legal destinations.';
    $('controls').innerHTML=state.forceOptions.map(color=>`<button class="switch-button ${classColor(color)}" data-direct="${esc(color)}" ${busy?'disabled':''}>Send opponent to ${esc(color)}${!state.players[1].available.some(id=>cardHand(state.players[1],id)===color)?' · empty: defeats opponent':''}</button>`).join('')||'<p class="muted">Waiting for the opposing destination choice.</p>';
  } else if(phase==='switching') {
    $('phaseTitle').textContent=state.forcedColors?.[0]?'Confirm next hand':'Choose next hand';
    $('phaseHint').textContent=state.switchOptions.some(color=>!p.available.some(id=>cardHand(p,id)===color))?'Entering a hand with zero cards loses the fight.':'';
    $('controls').innerHTML=`<div class="hand-orbs switch-orbs" role="group" aria-label="Legal next hands">${state.switchOptions.map(color=>{
      const remaining=p.available.filter(id=>cardHand(p,id)===color).length;
      const label=`${color} · ${styles[color]}: ${remaining} cards remaining${color===p.color?' · stay in this hand':''}${state.forcedColors?.[0]?' · forced by Driving Kick':''}${remaining===0?' · EMPTY HAND: entering loses the fight':''}`;
      return `<button class="hand-orb ${classColor(color)} ${color===p.color?'active-hand':''}" data-switch="${esc(color)}" aria-label="Choose ${esc(label)}" title="${esc(label)}" ${busy?'disabled':''}>${orbMeter(remaining)}<span class="orb-label">${esc(color)}</span></button>`;
    }).join('')}</div>`;
  } else {
    $('matchControls').dataset.outcome=state.winner==='draw'?'draw':state.winner===0?'victory':'defeat';
    $('phaseTitle').textContent=state.winner==='draw'?'Draw':state.winner===0?'You win!':'Bot wins';
    $('phaseHint').textContent='';
    $('controls').innerHTML='<button id="playAgain" class="primary">Play again <span aria-hidden="true">↻</span></button><button id="reviewFight" aria-haspopup="dialog">Fight log</button>';
  }
}
function renderRound(next, immediate = false) {
  const number=$('roundNumber'), previous=roundDisplayState;
  if(!immediate && previous && previous.matchId===next.matchId && previous.round===next.round) return;
  roundAnimations.forEach(animation=>animation.cancel());
  roundAnimations=[];
  roundDisplayState={matchId:next.matchId,round:next.round};
  const incoming=document.createElement('i');
  incoming.className='round-digit';incoming.textContent=next.round;
  number.replaceChildren(incoming);
  if(immediate || !previous || previous.matchId!==next.matchId || next.round<=previous.round || orbMotionPreference.matches) return;
  const outgoing=document.createElement('i');
  outgoing.className='round-digit';outgoing.textContent=previous.round;outgoing.setAttribute('aria-hidden','true');
  number.prepend(outgoing);
  const timing={duration:440,easing:'cubic-bezier(.22,.7,.25,1)'};
  roundAnimations=[
    outgoing.animate([{transform:'translateY(0)',opacity:1},{transform:'translateY(110%)',opacity:0}],{...timing,fill:'forwards'}),
    incoming.animate([{transform:'translateY(-110%)',opacity:.35},{transform:'translateY(0)',opacity:1}],timing)
  ];
  roundAnimations[0].finished.then(()=>outgoing.remove()).catch(()=>{});
}
function renderTimeline(next, immediate = false) {
  const rail=$('timeline');
  const previous=timelineState;
  const signature=[next.matchId,next.round,next.phase].join(':');
  if(!immediate && previous?.signature===signature) return;
  const oldHighlight=rail.querySelector('.phase-highlight');
  const oldOpacity=oldHighlight?getComputedStyle(oldHighlight).opacity:'1';
  timelineAnimation?.cancel();
  timelineAnimation=null;
  timelineState={signature,matchId:next.matchId,round:next.round};
  const phases = [['planning','COMMIT FACE DOWN'],...(next.phase==='cancelling'?[['cancelling','CHOOSE CANCEL']]:[]),['reactions','REVEAL & REACT'],...(next.phase==='stripping'?[['stripping','REMOVE ADVANTAGE']]:[]),...(next.phase==='relocating'?[['relocating','MOVE MAIN']]:[]),...(next.phase==='routing'?[['routing','CUT ROUTE']]:[]),...(next.phase==='directing'?[['directing','DIRECT HAND']]:[]),['switching','DAMAGE & SWITCH']];
  const currentPhase = phases.findIndex(([phase])=>phase===next.phase);
  const markup=phases.map(([phase,label],index)=>{
    const current=phase===next.phase, complete=next.phase==='finished'||(currentPhase>=0&&index<currentPhase);
    const marker=complete?'<svg viewBox="0 0 16 16" aria-hidden="true"><path d="m4 8 3 3 5-6"/></svg>':String(index+1).padStart(2,'0');
    return `<span class="phase-step${current?' current':''}${complete?' complete':''}"${current?' aria-current="step"':''}><i class="phase-marker" aria-hidden="true">${marker}</i><b class="phase-label">${label}</b></span>`;
  }).join('');
  const commit = () => {
    rail.innerHTML='<div class="phase-highlight" aria-hidden="true"></div>'+markup;
    const highlight=rail.querySelector('.phase-highlight'), current=rail.querySelector('.current');
    highlight.hidden=!current;
    if(!current) return;
    highlight.innerHTML=current.innerHTML;
    const target={left:current.offsetLeft,top:current.offsetTop,width:current.offsetWidth,height:current.offsetHeight};
    Object.entries(target).forEach(([key,value])=>highlight.style[key]=`${value}px`);
  };
  const sameMatch=previous && previous.matchId===next.matchId;
  if(!immediate && sameMatch && oldHighlight && !oldHighlight.hidden && !orbMotionPreference.matches){
    const fade=oldHighlight.animate([{opacity:oldOpacity},{opacity:0}],{duration:150,fill:'forwards'});
    timelineAnimation=fade;
    fade.finished.then(()=>{
      if(timelineAnimation!==fade) return;
      commit(); fade.cancel();
      const highlight=rail.querySelector('.phase-highlight');
      if(!highlight.hidden) timelineAnimation=highlight.animate([{opacity:0},{opacity:1}],{duration:210,easing:'ease-out'});
    }).catch(()=>{});
  } else commit();
}
new ResizeObserver(()=>{
  const rail=$('timeline'),current=rail.querySelector('.current'),highlight=rail.querySelector('.phase-highlight');
  if(!highlight || !current) return;
  for(const [key,value] of Object.entries({left:current.offsetLeft,top:current.offsetTop,width:current.offsetWidth,height:current.offsetHeight})) highlight.style[key]=`${value}px`;
}).observe($('timeline'));

function render() {
  $('homePlay').disabled=busy||!catalog.size;
  $('setupTitle').textContent=state?'Start a new fight.':'Choose your opening.';
  $('setupForm').querySelector('p').textContent=state?'Starting a new fight replaces your saved match. All six hands will be available again.':'All six hands are yours. Only your active hand can play or burn cards.';
  const canResume=!!state && state.phase!=='finished' && !state.needsNewMatch;
  $('savedMatch').hidden=!canResume;
  $('savedMatch').textContent=canResume?`Continue your match against the bot · Round ${state.round}`:'';
  $('showRules').setAttribute('aria-expanded',_openPopup==='rules'?'true':'false');
  $('resign').hidden=!state||state.phase==='finished'||state.needsNewMatch;
  $('resign').disabled=busy;
  if(!state) { $('fighters').innerHTML='<p class="muted">Choose your opening hand to start a local match against the bot.</p>'; $('phaseHint').textContent='36 reusable moves. Permanent burns. Every round changes your next options.'; $('controls').innerHTML='<button id="playAgain" class="primary">Start a match</button>'; return; }
  renderRound(state);
  $('fighters').innerHTML=fighter(0)+fighter(1);
  [...$('fighters').querySelectorAll('.fighter')].forEach((element,index)=>{
    const p=state.players[index];
    const afterDamage=['stripping','relocating','routing','directing','switching'].includes(state.phase);
    if(p.nextAttack&&state.phase!=='finished') element.insertAdjacentHTML('beforeend',`<p class="muted">Pressure: +${p.nextAttack} attack ${afterDamage?'next round':'this round'}.</p>`);
    if(p.nextBlockPenalty&&state.phase!=='finished') element.insertAdjacentHTML('beforeend',`<p class="muted recovery-status">Haymaker recovery: -${p.nextBlockPenalty} block ${afterDamage?'next round':'this round'} (minimum 0).</p>`);
    if(p.burnLockedRound>=state.round&&state.phase!=='finished') element.insertAdjacentHTML('beforeend',`<p class="muted burn-lock-status">Clinch Throw: no bonus or reaction burns ${p.burnLockedRound>state.round?'next round':'this round'}. Cancels are also disabled.</p>`);
    if(state.commitments.some(plan=>plan&&!plan.hidden&&plan.advantages?.length&&card(plan.main)?.advantage?.spend?.special?.includes('brace_reset'))&&state.phase==='reactions') element.insertAdjacentHTML('beforeend','<p class="muted brace-status">Brace: no new attack bonuses for next round; opposing carried attack bonus suppressed this round.</p>');
    const objective=p.pendingObjective;
    if(objective&&state.phase!=='finished'&&objective.round>=state.round) {
      const source=card(objective.source)?.name??'Pending';
      const requirement=objective.requirement??`deal ${objective.minimumDamage??4}+ damage`;
      element.insertAdjacentHTML('beforeend',`<p class="muted pending-objective">${esc(source)} objective: ${esc(requirement)} ${objective.round>state.round?'next round':'this round'} to earn ${esc(objective.color)} advantage.</p>`);
    }
    const details=document.createElement('div');
    details.className='fighter-details';
    const statuses=document.createElement('div');
    statuses.className='fighter-statuses';
    element.querySelectorAll(':scope > p').forEach(status=>statuses.append(status));
    details.append(element.querySelector('.hand-orbs'),statuses);
    element.append(details);
  });
  renderTimeline(state);
  $('reveals').innerHTML=reveal(0)+reveal(1);
  const p=state.players[0];browseColor=browseColor??p.color;
  $('cards').innerHTML=[...catalog.values()].filter(c=>cardHand(p,c.id)===browseColor).map(cardMarkup).join('');
  if(opponentColor && _openPopup==='opponentHand') renderOpponentPopup();
  controls();
  restoreLiquidImpacts();
  if(window.DFGTutorial) DFGTutorial.render();
  $('log').innerHTML=state.log.slice().reverse().map(entry=>`<div class="log-entry"><small>R${entry.round}</small>${esc(entry.text)}</div>`).join('');
}
document.addEventListener('click',event=>{
  const menu=$('matchMenu');
  if(!event.target.closest('#matchMenu') || event.target.closest('.match-actions')) menu.open=false;
  if(!event.target.closest('#advantagePicker') && $('advantagePicker')) $('advantagePicker').open=false;
  event.target.closest('.commit-prompt.needs-main')?.focus({preventScroll:true});
  const button=event.target.closest('button');if(!button||button.disabled)return;
  if(button.id==='showRules'){showRulesPopup();return;}
  if(button.id==='goHome'){if($('tutorialGuide'))location.href='index.php';else showScreen(false);return;}
  if(button.id==='homePlay'){
    if(state && state.phase!=='finished' && !state.needsNewMatch) showScreen(true);
    else $('setup').showModal();
    return;
  }
  if(['showLog','reviewFight'].includes(button.id)){fightLogTrigger=button.id==='reviewFight'?button:$('matchMenu').querySelector('summary');$('fightLog').showModal();$('showLog').setAttribute('aria-expanded','true');return;}
  if(button.id==='closeLog'){$('fightLog').close();return;}
  if(button.closest('#rulesPopup')&&button.getAttribute('onclick')==='ClosePopup()'){closeRulesPopup();return;}
  if(button.dataset.handAction){clickHandCard(Number(button.dataset.handAction),button);return;}
  if(button.dataset.browse){closePreview();browseColor=button.dataset.browse;render();$(`your-orb-${browseColor}`).focus({preventScroll:true});return;}
  if(button.dataset.opponent){closePreview();HideCardDetail(true);opponentColor=button.dataset.opponent;render();renderOpponentPopup();$('opponentHandPopup').querySelector('button').focus();return;}
  if(button.closest('#opponentHandPopup') && button.getAttribute('onclick')==='ClosePopup()'){closeOpponentPopup();return;}
  if(button.dataset.relocate){act({type:'relocate',color:button.dataset.relocate});return;}
  if(button.dataset.cut){act({type:'cut',color:button.dataset.cut});return;}
  if(button.dataset.direct){act({type:'direct',color:button.dataset.direct});return;}
  if(button.dataset.strip){act({type:'strip',color:button.dataset.strip});return;}
  if(button.dataset.cancel){act({type:'cancel',main:Number(button.dataset.cancel)});return;}
  if(button.dataset.advantage){const color=button.dataset.advantage;if(selectedAdvantages.has(color))selectedAdvantages.delete(color);else selectedAdvantages.add(color);if(card(selectedMain).advantage.spend.flexible&&!card(selectedMain).advantage.spend.byCount&&selectedAdvantages.has('Yellow'))extraSwitch=extraSwitch??Object.keys(styles).find(c=>c!==state.players[0].color&&!card(selectedMain).switches.includes(c)&&state.players[0].available.some(id=>cardHand(state.players[0],id)===c));else extraSwitch=null;render();document.querySelector(`[data-advantage="${color}"]`)?.focus({preventScroll:true});return;}
  if(button.dataset.switch){act({type:'switch',color:button.dataset.switch});return;}
  if(button.id==='commit')act({type:'commit',main:selectedMain,bonuses:[...selectedBonuses],advantages:[...selectedAdvantages],...(extraSwitch?{extraSwitch}: {})});
  if(button.id==='pass')act({type:'pass'});
  if(button.id==='clear'){const moves=[...(selectedMain?[{type:'ZONE_MOVE',source:'played-main-0',destination:`hand-${selectedMain}`}]:[]),...[...selectedBonuses].map(id=>({type:'ZONE_MOVE',source:`burn-0-${id}`,destination:`hand-${id}`}))];changeSelection(()=>{selectedMain=null;selectedBonuses.clear();selectedAdvantages.clear();extraSwitch=null;},moves);}
  if(button.id==='resign')act({type:'resign'});
  if(['newMatch','playAgain'].includes(button.id))$('setup').showModal();
  if(button.id==='cancelSetup')$('setup').close();
  if(button.id==='refresh')request().then(accept).catch(error=>showError(error.message));
});
document.addEventListener('change',event=>{
  if(event.target.id==='extraSwitch')extraSwitch=event.target.value;
  if(['humanColor','botColor'].includes(event.target.id)) DFGAssetPreloader.warmCards([...catalog.values()], [$('humanColor').value, $('botColor').value]);
});
function showOpponentDetail(event, button) {
  const c=card(Number(button.dataset.opponentCard)), rect=button.getBoundingClientRect();
  ShowHTMLCardDetail({type:event.type,clientX:event.clientX??rect.right,clientY:event.clientY??rect.top},cardDetails(c),button,{skipDelay:true});
}
document.addEventListener('pointerover',event=>{const button=event.target.closest('[data-opponent-card]');if(button&&event.pointerType!=='touch'&&!button.contains(event.relatedTarget))showOpponentDetail(event,button);});
document.addEventListener('pointerout',event=>{const button=event.target.closest('[data-opponent-card]');if(button&&!button.contains(event.relatedTarget))HideCardDetail();});
document.addEventListener('focusin',event=>{const button=event.target.closest('[data-opponent-card]');if(button)showOpponentDetail(event,button);});
document.addEventListener('focusout',event=>{if(event.target.closest('[data-opponent-card]'))HideCardDetail();});
document.addEventListener('keydown',event=>{if(event.key==='Escape'){if(IsCardDetailOpen())HideCardDetail(true);else if(_openPopup==='rules')closeRulesPopup();else if(opponentColor)closeOpponentPopup();}});
document.addEventListener('pointerover', event => {
  const tile = event.target.closest('[data-inspect]');
  if(tile && event.pointerType!=='touch' && !tile.contains(event.relatedTarget)) showPreview(Number(tile.dataset.inspect), tile);
  if(event.target.closest('#cardPreview')) clearTimeout(previewTimer);
});
document.addEventListener('pointerout', event => {
  const tile = event.target.closest('[data-inspect]');
  if(tile && !tile.contains(event.relatedTarget)) delayClosePreview();
  if(event.target.closest('#cardPreview') && !$('cardPreview').contains(event.relatedTarget)) delayClosePreview();
});
document.addEventListener('focusin', event => {
  const tile = event.target.closest('[data-inspect]');
  if(tile) showPreview(Number(tile.dataset.inspect), tile);
  if(event.target.closest('#cardPreview')) clearTimeout(previewTimer);
});
document.addEventListener('focusout', event => { if(event.target.closest('[data-inspect]')) delayClosePreview(); });
document.addEventListener('keydown', event => { if(event.key==='Escape') { closePreview(); if($('advantagePicker')?.open) { $('advantagePicker').open=false; $('advantagePicker').querySelector('summary').focus(); } if($('matchMenu').open) { $('matchMenu').open=false; $('matchMenu').querySelector('summary').focus(); } } });
window.addEventListener('resize', positionPreview);
$('fightLog').addEventListener('close',()=>{$('showLog').setAttribute('aria-expanded','false');if(fightLogTrigger?.isConnected)fightLogTrigger.focus();});
window.addEventListener('scroll', closePreview, {passive:true});
// Close setup consistently in browsers that do not dispatch native dialog cancellation.
$('setup').addEventListener('keydown',event=>{
  if(event.key==='Escape') { event.preventDefault();$('setup').close(); }
});
$('setupForm').addEventListener('submit',async event=>{
  event.preventDefault();if(busy)return;busy=true;
  const submit=$('setupForm').querySelector('[type=submit]');submit.disabled=true;
  const form=new FormData(event.target);const settings={humanColor:form.get('humanColor'),botColor:form.get('botColor'),difficulty:form.get('difficulty'),first:Number(form.get('first'))};
  if(form.get('seed')!=='')settings.seed=Number(form.get('seed'));
  try{accept(await request({command:'new',settings}));$('setup').close();showScreen(true);showError('');}
  catch(error){showError(error.message);$('setup').close();}
  finally{busy=false;submit.disabled=false;render();}
});
request().then(data=>{
  accept(data);
  const options=Object.entries(styles).map(([color,style])=>`<option value="${color}">${color} · ${esc(style)}</option>`).join('');
  $('humanColor').innerHTML=options;$('botColor').innerHTML=options;$('humanColor').value='Red';$('botColor').value='Purple';
  if($('tutorialGuide'))showScreen(true);
  if(new URLSearchParams(location.search).has('play'))$('setup').showModal();
}).catch(error=>showError(error.message));
