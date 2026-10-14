'use strict';
let state = null, token = '', catalog = new Map(), styles = {}, busy = false, selectedMain = null, selectedBonuses = new Set(), browseColor = null;
let selectedAdvantages = new Set(), extraSwitch = null;
let opponentColor = null;
let activeMotion = [];
const $ = id => document.getElementById(id);
const esc = value => String(value ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch]));
const card = id => catalog.get(id);
const classColor = color => color.toLowerCase();
let previewId = null, previewPinned = false, previewTimer = null, previewAnchor = null;
function closePreview() {
  clearTimeout(previewTimer); previewId = null; previewPinned = false; $('cardPreview').hidden = true;
}
function cardDetails(c) { return DFGCardRenderer.details(c, styles); }
function positionPreview() {
  const preview = $('cardPreview'); if(preview.hidden || !previewAnchor) return;
  const gap = 12, margin = 12, width = preview.offsetWidth, height = preview.offsetHeight;
  let x = previewAnchor.right + gap;
  if(x + width > innerWidth - margin) x = previewAnchor.left - width - gap;
  x = Math.max(margin, Math.min(x, innerWidth - width - margin));
  const y = Math.max(margin, Math.min(previewAnchor.top, innerHeight - height - margin));
  preview.style.left = `${x}px`; preview.style.top = `${y}px`;
}
function showPreview(id, anchor, pin = false) {
  if(!card(id) || (previewPinned && !pin)) return;
  clearTimeout(previewTimer);
  previewId = id; previewPinned = pin; previewAnchor = anchor.getBoundingClientRect();
  $('previewContent').innerHTML = cardDetails(card(id)); $('cardPreview').hidden = false;
  $('cardPreview').classList.toggle('pinned', pin); positionPreview();
}
function delayClosePreview() {
  if(!previewPinned) { clearTimeout(previewTimer); previewTimer = setTimeout(closePreview, 160); }
}
function showError(message) { $('error').textContent = message; $('error').hidden = !message; }
async function request(body = null) {
  const response = await fetch('api.php', body ? {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({...body,token})} : {cache:'no-store'});
  const data = await response.json();
  if (!data.ok) { const error = new Error(data.error || 'Request failed'); error.status = response.status; throw error; }
  return data;
}
function accept(data) {
  const moves=[];
  if(state && data.state && state.matchId===data.state.matchId) {
    for(const seat of [0,1]) for(const id of data.state.players[seat].discarded.filter(id=>!state.players[seat].discarded.includes(id))) {
      if(seat===0&&state.phase==='planning'&&selectedBonuses.has(id)) continue;
      moves.push({type:'ZONE_MOVE',source:seat===0?`hand-${id}`:`orb-${card(id).color}`,previewCardID:seat===1?id:null,destination:`burn-${seat}-${id}`,durationMs:340});
    }
    if(state.phase==='reactions'&&data.state.phase==='switching') moves.push({type:'ZONE_MOVE',source:'played-main-0',destination:`hand-${state.commitments[0].main}`,durationMs:340});
  }
  const prepared=prepareMotion(moves);
  if(!state || state.matchId !== data.state?.matchId) { opponentColor=null; ClosePopup(); HideCardDetail(true); }
  const changed = !state || !data.state || state.matchId !== data.state.matchId || state.round !== data.state.round || state.phase !== data.state.phase;
  token = data.token; state = data.state; styles = data.colors; catalog = new Map(data.cards.map(c => [c.id,c]));
  if (changed) { selectedMain = null; selectedBonuses.clear(); selectedAdvantages.clear(); extraSwitch=null; browseColor = state?.players[0].color ?? 'Red'; }
  if (changed) closePreview();
  render();
  TCGCardMotion.playPreparedZoneMoves(prepared,1);
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
    proxy.innerHTML=`<span class="tile-badge">BURNED</span><strong class="tile-name">${esc(c.name)}</strong><span class="tile-stats"><span>${c.attack}</span><span>${c.block}</span></span>`;
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
  const playable=c.color===p.color&&!p.discarded.includes(id);
  if(playable&&state.phase==='reactions'&&state.reactionTurn===0&&state.reactionOptions.includes(id)) { closePreview();act({type:'react',card:id});return; }
  if(playable&&state.phase==='planning'&&!state.commitments[0]) {
    if(selectedMain===id) changeSelection(()=>{selectedMain=null;selectedAdvantages.clear();extraSwitch=null;},[{type:'ZONE_MOVE',source:'played-main-0',destination:`hand-${id}`}]);
    else if(selectedBonuses.has(id)) changeSelection(()=>selectedBonuses.delete(id),[{type:'ZONE_MOVE',source:`burn-0-${id}`,destination:`hand-${id}`}]);
    else if(!selectedMain) changeSelection(()=>{selectedMain=id;selectedAdvantages.clear();extraSwitch=null;},[{type:'ZONE_MOVE',source:`hand-${id}`,destination:'played-main-0'}]);
    else if(!selectedBonuses.size&&c.bonus) changeSelection(()=>selectedBonuses.add(id),[{type:'ZONE_MOVE',source:`hand-${id}`,destination:`burn-0-${id}`}]);
    return;
  }
  if(previewPinned&&previewId===id) closePreview();else showPreview(id,button,true);
}
async function act(action) {
  if (busy || !state) return;
  busy = true; showError(''); render();
  let accepted=false;
  try { const data=await request({command:'action',action,revision:state.revision,matchId:state.matchId});busy=false;accept(data);accepted=true; }
  catch(error) { if(error.status===409) { try { accept(await request()); } catch {} } showError(error.message); }
  finally { busy = false; if(!accepted) render(); }
}
function fighter(seat) {
  const p = state.players[seat];
  return `<div class="fighter ${classColor(p.color)}"><div class="fighter-head"><div><h2>${seat===0?'YOU':'SPARRING BOT'}</h2><small>${esc(p.color)} · ${esc(styles[p.color])}</small></div><div class="life">${p.life}<small> / 40</small></div></div>${seat===1?opponentStatus():''}<div class="health-track"><div class="health-fill" style="width:${Math.max(0,p.life)/40*100}%"></div></div><small>${p.available.length} cards remaining · ${p.discarded.length} permanently burned <a class="hand-status-link" href="${seat===0?'#yourHands':'#opponentOrbs'}">View hands &amp; burns ↓</a></small><div class="advantage-tray" aria-label="${seat===0?'Your':'Bot'} advantage tokens"><span>ADVANTAGE</span>${(p.advantages??[]).length?(p.advantages??[]).map(color=>`<span class="advantage-token ${classColor(color)}" title="${esc(color)} advantage">${esc(color)}</span>`).join(''):'<span class="muted">None</span>'}</div></div>`;
}
function reveal(seat) {
  const staged=seat===0&&state.phase==='planning'&&!state.commitments[0];
  const plan = staged&&selectedMain?{main:selectedMain,bonuses:[...selectedBonuses]}:state.commitments[seat], owner = seat===0?'Your played card':'Opponent played card';
  const burns=staged?[...selectedBonuses]:(plan&&!plan.hidden?[...plan.bonuses,...state.reactions.filter(r=>r.seat===seat).map(r=>r.card)]:[]);
  const burnMarkup=`<div class="played-burns" id="burns-${seat}">${burns.map(id=>`<button id="burn-${seat}-${id}" class="played-burn tile-face ${classColor(card(id).color)}" ${staged?`data-hand-action="${id}"`:''} data-inspect="${id}" aria-label="${staged?'Undo bonus':'Inspect burned'} ${esc(card(id).name)}"><span class="tile-badge">${staged?'BONUS · UNDO':'BURNED'}</span><strong class="tile-name">${esc(card(id).name)}</strong><span class="tile-stats"><span>${card(id).attack}</span><span>${card(id).block}</span></span></button>`).join('')}</div>`;
  if (!plan || plan.hidden) return `<div class="reveal hidden-card empty-play"><span class="owner">${owner}</span><div class="played-content"><div id="played-main-${seat}" class="played-back" aria-hidden="true"></div><div><h3>${plan?'Face down':'Select a main card'}</h3></div></div>${burnMarkup}</div>`;
  const main = card(plan.main), stats = state.stats?.[seat];
  return `<div class="reveal ${classColor(main.color)}"><span class="owner">${owner}${staged?' · not locked in':''}</span><div class="played-content"><button id="played-main-${seat}" class="tile-face ${classColor(main.color)}" data-inspect="${main.id}" ${staged?`data-hand-action="${main.id}"`:''} aria-label="${staged?'Undo main selection':'Inspect '+esc(main.name)}"><span class="tile-badge">${staged?'MAIN · UNDO':'MAIN'}</span><strong class="tile-name">${esc(main.name)}</strong><span class="tile-stats"><span><small>ATK</small>${main.attack}</span><span><small>BLK</small>${main.block}</span></span></button><div class="played-details"><h3>${esc(main.name)}</h3><div class="stat-row"><span>Attack <strong>${stats?.attack??main.attack}</strong></span><span>Block <strong>${stats?.block??main.block}</strong></span>${stats?`<span>${state.phase==='reactions'?'Projected damage':'Damage dealt'} <strong>${stats.damage}</strong></span>`:''}</div><p>${main.ability?esc(main.ability).replace(/\n/g,'<br>'):'No additional main ability.'}</p><div class="played-switches">Switch to ${main.switches.map(color=>`<span class="color-chip ${classColor(color)}">${color}</span>`).join(' ')}</div>${plan.advantages?.length?`<p>Spent: ${plan.advantages.map(esc).join(' + ')}</p>`:''}${stats?.pierce?`<p>${stats.pierce} opposing block ignored</p>`:''}</div></div>${burnMarkup}</div>`;
}
function opponentStatus() {
  const p = state.players[1];
  return `<div id="opponentOrbs" class="hand-orbs" aria-label="Opponent hand inventory">${Object.keys(styles).map(color=>{
    const remaining = p.available.filter(id=>card(id).color===color), burned = p.discarded.filter(id=>card(id).color===color);
    return `<button id="orb-${color}" class="hand-orb ${classColor(color)} ${color===p.color?'active-hand':''}" data-opponent="${color}" aria-haspopup="dialog" aria-expanded="${opponentColor===color}" aria-label="Opponent ${color} hand: ${remaining.length} of 6 remaining, ${burned.length} burned${color===p.color?', active':''}${!remaining.length?', exhausted':''}" title="${color}: ${remaining.length}/6 remaining · ${burned.length} burned${color===p.color?' · Active':''}"><span class="orb-sphere"><span class="orb-fill" style="height:${remaining.length/6*100}%"></span><span class="orb-count">${remaining.length}</span></span><span class="orb-label">${color}</span></button>`;
  }).join('')}</div>`;
}
function renderOpponentPopup() {
  const p=state.players[1], color=opponentColor;
  const remaining=p.available.filter(id=>card(id).color===color), burned=p.discarded.filter(id=>card(id).color===color);
  const tile=id=>{const c=card(id);return `<button class="tile-face ${classColor(c.color)}" data-opponent-card="${id}" aria-label="Inspect ${esc(c.name)}"><span class="tile-badge">${burned.includes(id)?'BURNED':'REMAINING'}</span><strong class="tile-name">${esc(c.name)}</strong><span class="tile-stats"><span><small>ATK</small>${c.attack}</span><span><small>BLK</small>${c.block}</span></span></button>`;};
  const html=`<p class="muted">${remaining.length}/6 remaining · ${burned.length} permanently burned. Hover or focus a card for details; tap to pin.</p><div class="opponent-popup-cards remaining-cards">${remaining.map(tile).join('')||'<p>This hand is exhausted.</p>'}</div>${burned.length?`<h3 class="burned-label">PERMANENTLY BURNED</h3><div class="opponent-popup-cards burned-cards">${burned.map(tile).join('')}</div>`:''}<p class="muted">Face-down commitments stay hidden. Main cards return after damage.</p>`;
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
  ClosePopup();$('showRules').setAttribute('aria-expanded','false');$('showRules').focus();
}
function advantageControls() {
  const c=card(selectedMain), rule=c?.advantage?.spend;
  if(!rule) return '';
  const held=(state.players[0].advantages??[]).filter(color=>rule.colors.includes(color));
  if(!held.length) {
    const earners=[...catalog.values()].filter(candidate=>rule.colors.includes(candidate.advantage?.earn?.color));
    return `<div class="token-spending"><span class="muted">Spend advantage · ${esc(c.name)}</span><div class="token-choices">${rule.colors.map(color=>`<button class="advantage-token ${classColor(color)}" disabled>${color} · not held</button>`).join('')}</div><p class="muted token-hint">You need an earned ${rule.colors.map(esc).join(' / ')} advantage token. ${earners.map(candidate=>`${esc(candidate.name)}: ${esc(candidate.ability.split('\n').find(line=>line.startsWith('Objective:'))?.replace('Objective: ',''))}`).join(' ')}</p><p class="muted token-hint">Spend through your main card before committing; bonus and reaction burns cannot spend advantage.</p></div>`;
  }
  const limit=rule.flexible?2:1;
  const extraColors=Object.keys(styles).filter(color=>color!==state.players[0].color&&!c.switches.includes(color)&&state.players[0].available.some(id=>card(id).color===color));
  return `<div class="token-spending"><span class="muted">Optional advantage (${selectedAdvantages.size}/${limit})${rule.flexible&&selectedAdvantages.size===2?' · +1 combo attack':''}</span><div class="token-choices">${held.map(color=>`<button class="advantage-token ${classColor(color)}" data-advantage="${color}" aria-pressed="${selectedAdvantages.has(color)}" ${busy||(!selectedAdvantages.has(color)&&selectedAdvantages.size===limit)||(color==='Yellow'&&rule.flexible&&!extraColors.length)?'disabled':''}>${selectedAdvantages.has(color)?'✓ ':''}${color}</button>`).join('')}</div>${rule.flexible?'<small>Red/Purple +1 attack · Green/Blue +1 block · Orange ignore 1 block · Yellow extra switch</small>':''}${rule.flexible&&selectedAdvantages.has('Yellow')?`<label>Yellow: extra switch<select id="extraSwitch" ${busy?'disabled':''}>${extraColors.map(color=>`<option value="${color}" ${extraSwitch===color?'selected':''}>${color}</option>`).join('')}</select></label>`:''}</div>`;
}
function cardMarkup(c) {
  const p = state.players[0], active = c.color === p.color, discarded = p.discarded.includes(c.id), committed = state.commitments[0]?.main === c.id;
  const canPlan = !state.needsNewMatch && active && !discarded && state.phase==='planning' && !state.commitments[0];
  const canReact = !state.needsNewMatch && state.phase==='reactions' && state.reactionTurn===0 && state.reactionOptions.includes(c.id);
  const status=discarded?'Permanently burned':canPlan?(selectedMain===c.id||selectedBonuses.has(c.id)?'Click to undo':!selectedMain?'Click for main':!selectedBonuses.size?(c.bonus?'Click for bonus burn':'No bonus effect'):'Main and bonus staged'):canReact?'Click to burn reaction':committed&&state.phase==='reactions'?'Main · reserved':active?'Inspect only':'Inactive · inspect';
  const actions=`<div class="card-status">${status}</div>`;
  const badge = discarded?'BURNED':selectedMain===c.id?'MAIN':selectedBonuses.has(c.id)?'BONUS BURN':committed&&state.phase==='reactions'?'RESERVED':c.color.toUpperCase();
  return `<article class="card-tile ${classColor(c.color)} ${selectedMain===c.id?'main-selected':''} ${selectedBonuses.has(c.id)?'bonus-selected':''} ${discarded?'discarded':''}" data-card-id="${c.id}"><button id="hand-${c.id}" class="tile-face" data-hand-action="${c.id}" data-inspect="${c.id}" aria-pressed="${selectedMain===c.id||selectedBonuses.has(c.id)}" aria-label="${esc(c.name)}: ${status}" ${busy?'disabled':''}><span class="tile-badge">${badge}</span><strong class="tile-name">${esc(c.name)}</strong><span class="tile-stats"><span><small>ATK</small>${c.attack}</span><span><small>BLK</small>${c.block}</span></span><span class="tile-switches" aria-label="Switch colors: ${esc(c.switches.join(', '))}">${c.switches.map(color=>`<i class="${classColor(color)}"></i>`).join('')}</span></button>${actions}</article>`;
}
function controls() {
  const phase = state.phase, p = state.players[0];
  if(state.needsNewMatch) {
    $('phaseTag').textContent='RULES UPDATED'; $('phaseTitle').textContent='Start a fresh match.';
    $('phaseHint').textContent='Rules or card balance changed. Start a new match to use the updated cards.';
    $('controls').innerHTML='<button id="playAgain" class="primary">Start new match</button>'; return;
  }
  $('phaseTag').textContent = `ROUND ${state.round} · ${phase==='planning'?'COMMIT':phase==='reactions'?'REACT':phase==='switching'?'SWITCH':'COMPLETE'}`;
  if(phase==='planning') {
    $('phaseTitle').textContent = 'Commit your move.';
    $('phaseHint').textContent = 'Click main, then optional bonus. Click again to undo.';
    $('controls').innerHTML = `${advantageControls()}<button id="commit" class="primary" ${busy||!selectedMain?'disabled':''}>Commit face down</button><button id="clear" ${busy?'disabled':''}>Clear selection</button>`;
  } else if(phase==='reactions') {
    $('phaseTitle').textContent = state.reactionTurn===0?'React or pass.':'Bot is reacting.';
    $('phaseHint').textContent = 'Burn one remaining active-hand card for its reaction, or pass. Two consecutive passes resolve damage.';
    $('controls').innerHTML = `<div class="selection-summary">Consecutive passes: <strong>${state.passes} / 2</strong><br>${state.reactionOptions.length} reaction cards available.</div><button id="pass" class="primary" ${busy||state.reactionTurn!==0?'disabled':''}>Pass reaction</button><p class="muted">Attack/block above include current effects. Damage can still change.</p>`;
  } else if(phase==='switching') {
    $('phaseTitle').textContent='Choose your next hand.';
    $('phaseHint').textContent='You must choose one of your main card’s bottom colors. The bot’s next hand stays hidden until you choose.';
    $('controls').innerHTML=state.switchOptions.map(color=>`<button class="switch-button ${classColor(color)}" data-switch="${esc(color)}" ${busy?'disabled':''}>${esc(color)} · ${esc(styles[color])}<small>${p.available.filter(id=>card(id).color===color).length} cards remaining${color===p.color?' · stay in this hand':''}</small></button>`).join('');
  } else {
    $('phaseTitle').textContent=state.winner==='draw'?'Double knockout.':state.winner===0?'You win.':'Bot wins.';
    $('phaseHint').textContent='Review the fight log, inspect your remaining cards, or try another opening.';
    $('controls').innerHTML='<button id="playAgain" class="primary">Play again</button>';
  }
}
function render() {
  $('showRules').setAttribute('aria-expanded',_openPopup==='rules'?'true':'false');
  $('resign').hidden=!state||state.phase==='finished'||state.needsNewMatch;
  $('resign').disabled=busy;
  if(!state) { $('fighters').innerHTML='<p class="muted">Choose your opening hand to start a local match against the bot.</p>'; $('phaseHint').textContent='36 reusable moves. Permanent burns. Every round changes your next options.'; $('controls').innerHTML='<button id="playAgain" class="primary">Start a match</button>'; return; }
  $('fighters').innerHTML=fighter(1)+'<div class="versus">VS</div>'+fighter(0);
  $('timeline').innerHTML=[['planning','COMMIT FACE DOWN'],['reactions','REVEAL & REACT'],['switching','DAMAGE & SWITCH']].map(([phase,label])=>`<span class="${state.phase===phase?'current':''}">${label}</span>`).join('');
  $('reveals').innerHTML=reveal(1)+reveal(0);
  $('result').hidden=state.phase!=='finished'; $('result').textContent=state.winner==='draw'?'Double knockout · draw':state.winner===0?'Victory. Well played.':'Defeat. Try a different line.';
  const p=state.players[0];browseColor=browseColor??p.color;
  $('hands').innerHTML=Object.keys(styles).map(color=>`<button class="hand-tab ${classColor(color)}" data-browse="${color}" aria-pressed="${browseColor===color}">${color}${color===p.color?' · Active':''}<small>${p.available.filter(id=>card(id).color===color).length}/6 · ${p.discarded.filter(id=>card(id).color===color).length} burned</small></button>`).join('');
  $('cards').innerHTML=[...catalog.values()].filter(c=>c.color===browseColor).map(cardMarkup).join('');
  if(opponentColor && _openPopup==='opponentHand') renderOpponentPopup();
  controls();
  $('log').innerHTML=state.log.slice().reverse().map(entry=>`<div class="log-entry"><small>R${entry.round}</small>${esc(entry.text)}</div>`).join('');
}
document.addEventListener('click',event=>{
  const button=event.target.closest('button');if(!button||button.disabled)return;
  if(button.id==='closePreview'){closePreview();return;}
  if(button.id==='showRules'){showRulesPopup();return;}
  if(button.closest('#rulesPopup')&&button.getAttribute('onclick')==='ClosePopup()'){closeRulesPopup();return;}
  if(button.dataset.handAction){clickHandCard(Number(button.dataset.handAction),button);return;}
  if(button.dataset.inspect){const id=Number(button.dataset.inspect);if(previewPinned&&previewId===id)closePreview();else showPreview(id,button,true);return;}
  if(button.dataset.browse){closePreview();browseColor=button.dataset.browse;render();return;}
  if(button.dataset.opponent){closePreview();HideCardDetail(true);opponentColor=button.dataset.opponent;render();renderOpponentPopup();$('opponentHandPopup').querySelector('button').focus();return;}
  if(button.dataset.opponentCard){HideCardDetail(true);showOpponentDetail(event,button,true);return;}
  if(button.dataset.closeShared){HideCardDetail(true);return;}
  if(button.closest('#opponentHandPopup') && button.getAttribute('onclick')==='ClosePopup()'){closeOpponentPopup();return;}
  if(button.dataset.advantage){const color=button.dataset.advantage;if(selectedAdvantages.has(color))selectedAdvantages.delete(color);else selectedAdvantages.add(color);if(card(selectedMain).advantage.spend.flexible&&selectedAdvantages.has('Yellow'))extraSwitch=extraSwitch??Object.keys(styles).find(c=>c!==state.players[0].color&&!card(selectedMain).switches.includes(c)&&state.players[0].available.some(id=>card(id).color===c));else extraSwitch=null;render();return;}
  if(button.dataset.switch){act({type:'switch',color:button.dataset.switch});return;}
  if(button.id==='commit')act({type:'commit',main:selectedMain,bonuses:[...selectedBonuses],advantages:[...selectedAdvantages],...(extraSwitch?{extraSwitch}: {})});
  if(button.id==='pass')act({type:'pass'});
  if(button.id==='clear'){const moves=[...(selectedMain?[{type:'ZONE_MOVE',source:'played-main-0',destination:`hand-${selectedMain}`}]:[]),...[...selectedBonuses].map(id=>({type:'ZONE_MOVE',source:`burn-0-${id}`,destination:`hand-${id}`}))];changeSelection(()=>{selectedMain=null;selectedBonuses.clear();selectedAdvantages.clear();extraSwitch=null;},moves);}
  if(button.id==='resign')act({type:'resign'});
  if(['newMatch','playAgain'].includes(button.id))$('setup').showModal();
  if(button.id==='cancelSetup')$('setup').close();
  if(button.id==='refresh')request().then(accept).catch(error=>showError(error.message));
});
document.addEventListener('change',event=>{if(event.target.id==='extraSwitch')extraSwitch=event.target.value;});
function showOpponentDetail(event, button, pin=false) {
  const c=card(Number(button.dataset.opponentCard)), rect=button.getBoundingClientRect();
  ShowHTMLCardDetail({type:event.type,clientX:event.clientX??rect.right,clientY:event.clientY??rect.top},`${pin?'<button class="preview-close" style="display:block" data-close-shared="true" aria-label="Close card details">×</button>':''}${cardDetails(c)}`,button,{skipDelay:pin,allowTouch:pin,pin});
}
document.addEventListener('pointerover',event=>{const button=event.target.closest('[data-opponent-card]');if(button&&event.pointerType!=='touch')showOpponentDetail(event,button);});
document.addEventListener('pointerout',event=>{const button=event.target.closest('[data-opponent-card]');if(button&&!button.contains(event.relatedTarget))HideCardDetail();});
document.addEventListener('focusin',event=>{const button=event.target.closest('[data-opponent-card]');if(button)showOpponentDetail(event,button);});
document.addEventListener('focusout',event=>{if(event.target.closest('[data-opponent-card]'))HideCardDetail();});
document.addEventListener('keydown',event=>{if(event.key==='Escape'){if(IsCardDetailOpen())HideCardDetail(true);else if(_openPopup==='rules')closeRulesPopup();else if(opponentColor)closeOpponentPopup();}});
document.addEventListener('pointerover', event => {
  const tile = event.target.closest('[data-inspect]');
  if(tile && event.pointerType!=='touch') showPreview(Number(tile.dataset.inspect), tile);
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
document.addEventListener('keydown', event => { if(event.key==='Escape') closePreview(); });
document.addEventListener('click', event => { if(previewPinned && !event.target.closest('#cardPreview, [data-inspect]')) closePreview(); });
window.addEventListener('resize', positionPreview);
window.addEventListener('scroll', () => { if(!previewPinned) closePreview(); }, {passive:true});
$('setupForm').addEventListener('submit',async event=>{
  event.preventDefault();if(busy)return;busy=true;
  const submit=$('setupForm').querySelector('[type=submit]');submit.disabled=true;
  const form=new FormData(event.target);const settings={humanColor:form.get('humanColor'),botColor:form.get('botColor'),difficulty:form.get('difficulty'),first:Number(form.get('first'))};
  if(form.get('seed')!=='')settings.seed=Number(form.get('seed'));
  try{accept(await request({command:'new',settings}));$('setup').close();showError('');}
  catch(error){showError(error.message);$('setup').close();}
  finally{busy=false;submit.disabled=false;render();}
});
request().then(data=>{
  accept(data);
  const options=Object.entries(styles).map(([color,style])=>`<option value="${color}">${color} · ${esc(style)}</option>`).join('');
  $('humanColor').innerHTML=options;$('botColor').innerHTML=options;$('humanColor').value='Red';$('botColor').value='Purple';
  if(!state)$('setup').showModal();
}).catch(error=>showError(error.message));
