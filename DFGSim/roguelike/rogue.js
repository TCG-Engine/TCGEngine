'use strict';
(()=>{
  const $=id=>document.getElementById(id),esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const colors=['Red','Orange','Yellow','Green','Blue','Purple'],inks={Red:'#a44b3d',Orange:'#b87637',Yellow:'#b79c40',Green:'#47755c',Blue:'#446c88',Purple:'#7d638f'};
  let data=null,cards={},busy=false;
  const button=(label,attrs='',cls='')=>`<button type="button" class="${cls}" ${attrs}>${label}</button>`;
  const colorDots=routes=>routes.map(c=>`<span class="route"><i style="background:${inks[c]}"></i>${esc(c)}</span>`).join(' ');
  function card(id,actions=''){
    const c=cards[id];return `<article class="move" style="--hand:${inks[c.color]}" data-card="${id}"><div class="move-top"><span>${esc(c.color)} / ${c.enemy?'CREATURE':'TECHNIQUE'}</span><strong>${c.attack}<small> ATK</small> / ${c.block}<small> BLK</small></strong></div><h3>${esc(c.name)}</h3><p class="ability">${esc(c.ability||'A basic technique.').replace(/\n/g,'<br>')}${c.extraText?`<br><b>${esc(c.extraText)}</b>`:''}</p><div class="burns"><p><b>BONUS</b> ${esc(c.bonus||'Unavailable')}</p><p><b>REACTION</b> ${esc(c.reaction||'Unavailable')}</p></div><div class="routes">${colorDots(c.switches)}</div>${actions?`<div class="move-actions">${actions}</div>`:''}</article>`;
  }
  const item=(id,actions='')=>{const i=data.items[id];return `<article class="item"><span class="eyebrow">${esc(i.slot)} / ${esc(i.kind.replaceAll('_',' '))}</span><h3>${esc(i.name)}</h3><p>${esc(i.text)}</p>${actions}</article>`;};
  const sprite=(key,extra='')=>`<img class="creature ${key} ${extra}" src="assets/${key}-v2.png" alt="${esc(data.enemies[key].name)}">`;
  function pack(){
    const r=data.run;return `<details class="pack"><summary>Moveset & armor <span>${r.deck.length} techniques · ${r.inventory.length} pieces</span></summary><div class="equipment-slots">${Object.entries(r.equipment).map(([slot,id])=>`<div><span class="eyebrow">${slot}</span><b>${id?esc(data.items[id].name):'Empty'}</b></div>`).join('')}</div>${['map','shop','camp','shrine'].includes(r.phase)?`<div class="item-grid">${r.inventory.map(id=>item(id,button(Object.values(r.equipment).includes(id)?'Equipped':'Equip',`data-equip="${id}" ${Object.values(r.equipment).includes(id)?'disabled':''}`))).join('')}</div>`:(r.phase==='fight'?'<p>Armor is fixed until this fight ends.</p>':'')}<details><summary>Inspect all owned techniques</summary>${colors.map(c=>`<details><summary>${c} · ${r.deck.filter(id=>cards[id].color===c).length} moves</summary><div class="card-grid">${r.deck.filter(id=>cards[id].color===c).map(id=>card(id)).join('')}</div></details>`).join('')}</details></details>`;
  }
  function welcome(){return `<section class="welcome"><div><p class="eyebrow">A DETERMINISTIC FIGHTING ROGUELIKE</p><h1>The Cloudbreak<br><em>Stair.</em></h1><p>Three guardians. One dragon. Every move is a read.</p><p class="intro-copy">Begin with eighteen humble techniques. Find your path through a mountain of martial spirits, reshape your moveset, and forge armor that changes the very shape of your life bar.</p>${button('Begin a pilgrimage','data-new','primary')}<small>One short act · four duels · no permanent upgrades</small></div><div class="welcome-art">${sprite('dragon')}<span>ACT I / THE NINTH CURRENT</span></div></section><section class="roster">${['fox','tortoise','tengu'].map(k=>`<article>${sprite(k)}<h3>${esc(data.enemies[k].name)}</h3><p>${esc(data.enemies[k].style)}</p></article>`).join('')}</section>`;}
  function map(){
    const r=data.run,accessible=new Set(r.accessible.map(n=>n.id)),rows=[...r.map].reverse();
    const x=n=>n.type==='boss'?150:[50,150,250][n.lane];
    const edges=rows.slice(0,-1).flatMap((row,i)=>row.flatMap(n=>rows[i+1].filter(prev=>n.type==='boss'||Math.abs(n.lane-prev.lane)<=1).map(prev=>`<line x1="${x(n)}" y1="${53+i*134}" x2="${x(prev)}" y2="${53+(i+1)*134}" class="${r.visited.includes(n.id)&&r.visited.includes(prev.id)?'traveled':''}" />`))).join('');
    return `<div class="map-layout"><section class="map-panel"><p class="eyebrow">ACT I / CHOOSE YOUR ASCENT</p><h1>The mountain<br>remembers.</h1><p class="muted">Move to the same or an adjacent lane on the next tier. Life persists. Burns and advantage reset each fight.</p><label class="opening">Opening hand for your next fight<select id="opening">${colors.map(c=>`<option>${c}</option>`).join('')}</select></label><div class="path-map"><svg class="map-edges" viewBox="0 0 300 910" preserveAspectRatio="none" aria-hidden="true">${edges}</svg>${rows.map(row=>`<div class="map-row">${row.map(n=>`<div class="node-wrap ${r.visited.includes(n.id)?'visited':''}">${button(`<span>${n.type==='fight'?'◈':n.type==='boss'?'✦':n.type==='camp'?'♨':n.type==='shop'?'◇':'⌁'}</span><b>${esc(n.title)}</b><small>${n.type==='boss'?'BOSS':n.type.toUpperCase()}</small>`,`data-travel="${n.id}" ${accessible.has(n.id)?'':'disabled'}`,accessible.has(n.id)?'node available':'node')}</div>`).join('')}</div>`).join('')}</div></section><aside class="map-aside"><p class="eyebrow">KNOW YOUR OPPONENT</p><h2>Public moves.<br>Hidden intentions.</h2>${['fox','tortoise','tengu','dragon'].map(k=>`<details class="enemy-book"><summary>${esc(data.enemies[k].name)} <small>${data.enemies[k].life} life</small></summary>${sprite(k)}<p>${esc(data.enemies[k].style)}</p><div class="card-grid small">${Object.values(cards).filter(c=>c.enemy===k).map(c=>card(c.id)).join('')}</div></details>`).join('')}</aside></div>`;
  }
  function room(){const r=data.run;
    if(r.phase==='fight')return `<section class="room"><p class="eyebrow">DUEL IN PROGRESS</p><h1>${esc(data.enemies[r.enemy].name)}</h1><p>Your fight is saved. Inspect your moveset or journal here, then return to the arena.</p><a class="primary" href="../index.php?roguelike=1">Resume duel</a></section>`;
    if(r.phase==='starter')return `<section class="room"><p class="eyebrow">YOUR FIRST CHOICE</p><h1>How will you<br><em>weather a hit?</em></h1><p>Take one chest piece. It will reshape your life bar. All eighteen starting techniques are restored for each fight.</p><div class="item-grid">${r.starterArmor.map(id=>item(id,button('Take & equip',`data-starter="${id}"`,'primary'))).join('')}</div></section>`;
    if(r.phase==='cardReward')return `<section class="room"><div class="reward-heading"><div><p class="eyebrow">VICTORY / TECHNIQUE REWARD</p><h1>Carry a new lesson.</h1><p>Choose one move. It is added directly to its printed hand. Then choose an armor reward.</p></div>${sprite(r.enemy,'motion-defeat')}</div><div class="card-grid">${r.reward.cards.map(id=>card(id,button('Learn this move',`data-reward-card="${id}"`,'primary'))).join('')}</div>${button('Skip technique','data-skip-card')}</section>`;
    if(r.phase==='armorReward')return `<section class="room"><p class="eyebrow">VICTORY / ARMOR REWARD</p><h1>Shape your defenses.</h1><p>Choose one piece. It auto-equips in its slot; your previous piece stays in your pack.</p><div class="item-grid">${r.reward.armor.map(id=>item(id,button('Take & equip',`data-reward-armor="${id}"`,'primary'))).join('')}</div>${button('Keep current equipment','data-skip-armor')}</section>`;
    if(['dead','victory'].includes(r.phase))return `<section class="ending room"><p class="eyebrow">${r.phase==='victory'?'ACT I COMPLETE':'THE ASCENT ENDS'}</p><h1>${r.phase==='victory'?'The dragon<br><em>bows.</em>':'Even falling<br><em>is a lesson.</em>'}</h1><p>${r.phase==='victory'?'You have crossed the Cloudbreak Stair.':'A new pilgrimage begins with eighteen basic moves and no inherited upgrades.'}</p><p>${r.wins} duels won · ${r.deck.length} techniques · ${r.gold} jade remaining</p>${button('Begin a new pilgrimage','data-new','primary')}${r.phase==='victory'?sprite('dragon','motion-defeat'):'<div class="hero motion-defeat" role="img" aria-label="Your defeated fighter"></div>'}</section>`;
    if(r.phase==='shop')return `<section class="room"><p class="eyebrow">THE WANDERING SMITH / ${r.gold} JADE</p><h1>A different kind<br>of preparation.</h1><div class="card-grid">${r.shop.cards.map(id=>card(id,button(r.shop.bought.includes('cards:'+id)?'Purchased':'Learn · 20 jade',`data-buy="${id}" data-kind="cards" ${r.gold<20||r.shop.bought.includes('cards:'+id)?'disabled':''}`))).join('')}</div><div class="item-grid">${r.shop.armor.map(id=>item(id,button(r.shop.bought.includes('armor:'+id)?'Purchased':`Buy & equip · ${data.items[id].price} jade`,`data-buy="${id}" data-kind="armor" ${r.gold<data.items[id].price||r.shop.bought.includes('armor:'+id)?'disabled':''}`))).join('')}</div>${removal()}${button('Continue the ascent','data-leave','primary')}</section>`;
    return `<section class="room"><p class="eyebrow">${r.phase==='camp'?'LANTERN CAMP':'SHRINE OF UNBURDENING'}</p><h1>Let something<br><em>go.</em></h1><p>Choose recovery or permanently unlearn one technique. Your other cards stay. At least one move must remain in every color.</p>${button(`Rest · recover ${r.phase==='camp'?12:6} life`,'data-rest','primary')}${removal()}${button('Leave without resting','data-leave')}</section>`;
  }
  function removal(){const r=data.run;return `<details class="training"><summary>Unlearn a technique ${r.phase==='shop'?'· 16 jade':'· instead of resting'}</summary><div class="card-grid">${r.removeOptions.map(id=>card(id,button('Permanently remove',`data-remove="${id}" ${r.phase==='shop'&&r.gold<16?'disabled':''}`,'danger'))).join('')}</div></details>`;}
  function render(){
    if(!data)return;const r=data.run;
    let body='';if(!r)body=welcome();else{
      const life=r.phase==='fight'?r.battle.players[0].life:r.life;
      body=`<div class="run-strip"><span>ACT I <b>THE CLOUDBREAK STAIR</b></span><span>♥ ${life} / 40</span><span>◇ ${r.gold} jade</span><span>${r.wins} / 4 duels</span></div>${r.phase==='map'?map():room()}${pack()}`;
    }
    $('app').innerHTML=body;
    $('journal-content').innerHTML=r?`<p>Run ${esc(r.id.slice(0,8))} · ${r.deck.length} moves · ${r.life} life between rooms</p><ol>${r.journal.map(t=>`<li>${esc(t)}</li>`).join('')}</ol>`:'<p>No pilgrimage has begun.</p>';
    $('abandon').hidden=!r||['dead','victory'].includes(r.phase);
  }
  async function request(command=null,action=null){
    if(busy)return;busy=true;$('error').hidden=true;document.body.classList.add('busy');
    const controller=new AbortController(),timer=setTimeout(()=>controller.abort(),20000);
    try{
      const options=command?{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({command,action,token:data.token,runId:data.run?.id??null,revision:data.run?.revision??null}),signal:controller.signal}:{signal:controller.signal};
      const response=await fetch('api.php',options),result=await response.json();
      if(!response.ok||!result.ok)throw new Error(result.error||'Unable to load the run');
      const previousPhase=data?.run?.phase;data=result;cards=Object.fromEntries(data.cards.map(c=>[c.id,c]));
      if(data.run?.phase==='fight'&&!new URLSearchParams(location.search).has('overview')){location.replace('../index.php?roguelike=1');return;}
      render();
      if(command&&previousPhase!==data.run?.phase){window.scrollTo(0,0);if(data.run?.phase==='map')document.querySelector('.node.available')?.scrollIntoView({block:'center'});}
    }catch(e){$('error').textContent=e.name==='AbortError'?'The request timed out. Reload to check saved progress before trying again.':e.message;$('error').hidden=false;}
    finally{clearTimeout(timer);busy=false;document.body.classList.remove('busy');}
  }
  const act=action=>request('action',action);
  $('app').addEventListener('click',e=>{
    const b=e.target.closest('button');if(!b||b.disabled||busy)return;const a=b.dataset;
    if('new'in a)return request('new');if(a.starter)return act({type:'starter',item:a.starter});
    if(a.travel)return act({type:'travel',node:a.travel,color:$('opening').value});
    if(a.equip)return act({type:'equip',item:a.equip});
    if(a.rewardCard)return act({type:'cardReward',card:Number(a.rewardCard)});if('skipCard'in a)return act({type:'cardReward',card:null});
    if(a.rewardArmor)return act({type:'armorReward',item:a.rewardArmor});if('skipArmor'in a)return act({type:'armorReward',item:null});
    if(a.buy)return act({type:'buy',kind:a.kind,id:a.kind==='cards'?Number(a.buy):a.buy});
    if(a.remove)return act({type:'remove',card:Number(a.remove)});if('rest'in a)return act({type:'rest'});if('leave'in a)return act({type:'leave'});
  });
  $('rules-button').onclick=()=>$('rules').showModal();$('journal-button').onclick=()=>$('journal').showModal();
  document.querySelectorAll('[data-close]').forEach(b=>b.onclick=()=>$(b.dataset.close).close());
  $('abandon').onclick=()=>{$('abandon-confirm').hidden=false;$('abandon').hidden=true;};
  $('cancel-new').onclick=()=>{$('abandon-confirm').hidden=true;$('abandon').hidden=false;};
  $('confirm-new').onclick=()=>{$('abandon-confirm').hidden=true;$('journal').close();request('new');};
  request();
})();
