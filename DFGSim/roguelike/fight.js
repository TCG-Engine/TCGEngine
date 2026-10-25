'use strict';
// Progression adapter for the existing fight page. Combat rendering and inputs stay in app.js.
window.DFGMatchMode=(()=>{
  const config=window.DFGCampaignConfig??{api:'roguelike/api.php',root:'roguelike/',title:'Cloudbreak Stair',adventure:false};
  let saved=null,opponentName='',enemyKey=null,animated=false;
  const escape=value=>String(value??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  async function request(body=null){
    const controller=new AbortController(),timer=setTimeout(()=>controller.abort(),12000);
    try{
      if(body&&body.command!=='action')throw new Error('Return to the run map to begin another fight.');
      const options={cache:'no-store',signal:controller.signal};
      if(body)Object.assign(options,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({
        command:'action',token:saved.token,runId:saved.run.id,revision:saved.run.revision,
        action:{type:'battle',move:body.action}
      })});
      const response=await fetch(config.api,options),data=await response.json();
      if(!response.ok||!data.ok){const error=new Error(data.error||'Unable to load the run');error.status=response.status;throw error;}
      saved=data;saved.run=data.journey??data.run;enemyKey=saved.run?.enemy;opponentName=data.enemies[enemyKey]?.name??'Opponent';
      if(!saved.run?.battle){location.replace(config.root);return {...data,state:null};}
      return {...data,state:saved.run.battle};
    }catch(error){if(controller.signal.aborted)throw new Error('The connection timed out. Reload to resume your saved fight.');throw error;}
    finally{clearTimeout(timer);}
  }
  function render(state){
    document.title=config.title+' · '+opponentName;
    document.getElementById('goHome').textContent=config.adventure?'Return to overworld':'Return to run';
    document.getElementById('newMatch').hidden=true;
    document.getElementById('resign').textContent=config.adventure?'Concede · return to sanctuary':'Resign duel · ends run';
    const image=document.querySelector('.arena-fighter-opponent'),animation=saved.animations?.[enemyKey];
    animated=!!animation?.still&&['idle','light','medium','heavy','block','knockout'].every(kind=>animation[kind]?.src&&animation[kind].durationMs>0)&&!!animation.knockout.finalSrc;
    if(animated)DFGFighterReactions.configureSeat(1,Object.fromEntries(['light','medium','heavy','block','knockout'].map(kind=>[kind,animation[kind]])));
    const source=animated?animation.idle.src:(saved.enemies[enemyKey]?.art??`roguelike/assets/${enemyKey}-v2.png`);
    if(image&&(image.dataset.creature!==enemyKey||image.getAttribute('src')!==source&&!image.dataset.reaction)){
      image.dataset.creature=enemyKey;image.src=source;
      image.parentElement.querySelectorAll('source').forEach(source=>source.srcset=animated?animation.still:image.src);
      image.classList.add(enemyKey);image.classList.toggle('rogue-creature',!animated);image.classList.toggle('rogue-generated',animated&&animation.wideCanvas!==false);
    }
    if(state.phase==='finished'){
      const next=document.getElementById('playAgain');if(next)next.textContent=config.adventure?'Return to the First Courtyard':saved.run.phase==='cardReward'?'Claim rewards':saved.run.phase==='victory'?'Complete pilgrimage':'End pilgrimage';
      document.getElementById('phaseTitle').textContent=state.winner===0?'You win!':state.winner==='draw'?'Double knockout':'Defeated';
    }
    state.players.forEach((p,seat)=>{
      const fighter=document.querySelector(`#fighters .fighter:nth-child(${seat+1})`),track=fighter.querySelector('.health-track'),max=p.maxLife??40;
      const notes=[];
      for(const id of Object.values(p.equipment??{})){
        const item=saved.items[id];if(!item)continue;
        if(item.kind==='break'||item.kind==='burst'){
          const consumed=(p[item.kind==='break'?'usedBreak':'usedBurst']??[]).includes(item.at),inactive=p.life<item.at;
          const marker=document.createElement('span');marker.className=`rogue-bar-${item.kind}${consumed||inactive?' inactive':''}`;
          marker.style.left=`${(item.at-(item.width??0))/max*100}%`;if(item.width)marker.style.width=`${item.width/max*100}%`;
          marker.tabIndex=0;marker.title=`${item.name}: ${item.text} (${consumed?'consumed':inactive?'below current life range':'ready'})`;marker.setAttribute('aria-label',marker.title);track.append(marker);
        }
      }
      if(p.shield)notes.push(`Barrier ${p.shield}`);
      if(p.incomingConfirm)notes.push(`Marked ${p.incomingConfirm} · connect next round to confirm`);
      if(p.recoverable)notes.push(`Recoverable ${p.recoverable}`);
      const note=document.createElement('p');note.className='muted rogue-equipment-status';note.textContent=notes.join(' · ');if(notes.length)fighter.querySelector('.fighter-statuses').append(note);
    });
    // Show equipped armor and run-only timing alongside the same shared rules popup.
    const rules=document.getElementById('rulesContent');
    if(!rules.querySelector('.rogue-rules')){
      for(const li of rules.querySelectorAll('li'))li.innerHTML=li.innerHTML.replace('40 life each.',config.adventure?'Every fight begins at full health; each fighter’s maximum is shown in their bar.':'Life carries between fights; each enemy’s maximum life is shown in its bar.').replace('Burned cards are permanently discarded.','Burned cards are discarded for this fight and restored before the next.');
      const section=document.createElement('section');section.className='rogue-rules';
      section.innerHTML=`<h3>${escape(config.title)}</h3><p>${config.adventure?'Every bout starts at full health with your equipped deck. Match burns, advantages, marks, and armor charges reset. Defeat returns you to the sanctuary; your unlocks and tasks persist.':'Life persists between fights. Match burns return; advantages, marks, and armor charges reset. Death ends the run.'}</p><p>Armor limits final damage after combat and retaliation. Break checkpoints stop one exchange at their life value. Burst segments require their whole width in one exchange. Barriers absorb first. Recovery cannot revive.</p><p>Confirmed damage marks the next round: connect through block to add the old mark before armor; missing expires it. New marks replace old marks.</p><h3>Your armor</h3>${Object.values(saved.run.equipment).filter(Boolean).map(id=>`<p><b>${escape(saved.items[id].name)}</b>: ${escape(saved.items[id].text)}</p>`).join('')}`;
      rules.prepend(section);
    }
  }
  function playReaction(seat,type,animate){
    if(seat!==1||animated)return false;
    const image=document.querySelector('.arena-fighter-opponent');if(!image)return true;
    image.getAnimations().filter(a=>!(a instanceof CSSAnimation)).forEach(a=>a.cancel());
    if(matchMedia('(prefers-reduced-motion: reduce)').matches)return true;
    const frames=type==='knockout'?[{transform:'none'},{transform:'translateY(10px) rotate(15deg)',opacity:.55}]:type==='block'?[{transform:'none'},{transform:'translateX(10px) scaleY(.95)',offset:.4},{transform:'none'}]:[{transform:'none'},{transform:'translateX(15px) rotate(6deg)',filter:'brightness(1.3)',offset:.3},{transform:'none'}];
    image.animate(frames,{duration:type==='knockout'?1000:650,easing:'ease-out',fill:type==='knockout'?'forwards':'none'});
    return true;
  }
  return {request,render,playReaction,returnUrl:id=>config.root+(id==='goHome'&&!config.adventure?'?overview=1':''),get opponentName(){return opponentName;}};
})();
