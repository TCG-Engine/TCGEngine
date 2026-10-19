'use strict';
const practiceData=JSON.parse(document.getElementById('practiceData').textContent);
const practiceCards=new Map(practiceData.cards.map(c=>[c.id,c]));
const escapeText=value=>String(value).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
let lesson=0,step=0,selection=null;
function practiceRender(focus=false){
  const current=practiceData.lessons[lesson],frame=current.frames[step],s=frame.state,choice=frame.choice;
  document.getElementById('lessonTabs').innerHTML=practiceData.lessons.map((l,i)=>`<button data-lesson="${i}" ${i===lesson?'aria-current="step"':''}>${i+1}. ${escapeText(l.name)}</button>`).join('');
  const main=seat=>{const plan=s.commitments[seat],c=plan&&!plan.hidden?practiceCards.get(plan.main):null,stats=s.stats?.[seat];return `<article class="practice-card ${c?.color.toLowerCase()??''}"><small>${seat===0?'YOUR MAIN':'OPPONENT MAIN'}</small><h3>${c?escapeText(c.name):'Face down'}</h3>${c?`<strong>${stats?.attack??c.attack} attack / ${stats?.block??c.block} block</strong><p>${escapeText(c.ability||'No main ability.')}</p>${plan.bonuses.length?`<p>Bonus burn: ${plan.bonuses.map(id=>escapeText(practiceCards.get(id).name)).join(', ')}</p>`:''}`:'<p>Both players commit before revealing.</p>'}</article>`;};
  let options=[];
  if(choice?.kind==='card') options=s.players[0].available.filter(id=>!s.players[0].discarded.includes(id)&&(s.players[0].cardHands?.[id]??practiceCards.get(id).color)===s.players[0].color&&!s.commitments[0]?.bonuses?.includes(id)&&s.commitments[0]?.main!==id).map(id=>({value:id,label:practiceCards.get(id).name,color:practiceCards.get(id).color,detail:`${practiceCards.get(id).attack} ATK / ${practiceCards.get(id).block} BLK`}));
  if(choice?.kind==='color') options=s.switchOptions.map(color=>({value:color,label:color,color,detail:`${s.players[0].available.filter(id=>(s.players[0].cardHands?.[id]??practiceCards.get(id).color)===color).length} cards`}));
  if(choice?.kind==='token') options=s.players[0].advantages.map(color=>({value:color,label:`${color} advantage`,color,detail:'Spend with Forearm Check'}));
  document.getElementById('practice').innerHTML=`<div class="lesson-body"><section class="lesson-copy" aria-labelledby="stepTitle"><span class="lesson-progress">LESSON ${lesson+1} / 4 · STEP ${step+1} / ${current.frames.length}</span><h2 id="stepTitle" tabindex="-1">${escapeText(frame.title)}</h2><p>${escapeText(frame.text)}</p><p class="lesson-task">${escapeText(frame.task)}</p><p id="choiceFeedback" role="status"></p><button id="advance" class="primary" ${choice?'disabled':''}>${escapeText(frame.button)}</button></section><section class="practice-board" aria-label="Practice arena"><div class="practice-vitals">${s.players.map((p,i)=>`<div>${i===0?'You':'Practice opponent'} · ${p.life} life<small>${p.color} hand · ${p.discarded.length} burned</small><small>Advantage: ${p.advantages.join(', ')||'none'}${p.nextAttack?` · +${p.nextAttack} next attack`:''}</small></div>`).join('')}</div><div class="practice-mains">${main(0)}${main(1)}</div>${options.length?`<p class="practice-note">${choice.kind==='card'?'Choose the card named in the prompt. This practice opponent follows a fixed script.':'Choose the highlighted lesson’s destination or resource.'}</p><div class="practice-hand">${options.map(o=>`<button class="${o.color.toLowerCase()}" data-choice="${o.value}" aria-pressed="false">${escapeText(o.label)}<small>${escapeText(o.detail)}</small></button>`).join('')}</div>`:''}<p class="practice-note">Reaction burns: ${s.reactions.map(r=>`${r.seat===0?'You':'Opponent'} — ${escapeText(practiceCards.get(r.card).name)}`).join(', ')||'none'}</p>${['switching','finished'].includes(s.phase)?`<div class="lesson-outcome">${s.phase==='finished'?'Defeat · entered an empty hand':`Damage resolved: you dealt ${s.stats[0].damage}, opponent dealt ${s.stats[1].damage}.`}</div>`:''}</section></div>`;
  document.getElementById('previous').disabled=lesson===0&&step===0;
  if(focus)document.getElementById('stepTitle').focus();
}
document.addEventListener('click',event=>{
  const button=event.target.closest('button');if(!button||button.disabled)return;
  if(button.dataset.lesson!==undefined){lesson=Number(button.dataset.lesson);step=0;selection=null;practiceRender(true);}
  if(button.dataset.choice!==undefined){
    const expected=String(practiceData.lessons[lesson].frames[step].choice.value);
    selection=button.dataset.choice;
    document.querySelectorAll('[data-choice]').forEach(b=>b.setAttribute('aria-pressed',String(b===button)));
    const correct=selection===expected;document.getElementById('advance').disabled=!correct;
    document.getElementById('choiceFeedback').textContent=correct?'Selected. Continue when ready.':'For this guided exercise, follow the named choice in the prompt.';
  }
  if(button.id==='advance'){
    if(step<practiceData.lessons[lesson].frames.length-1)step++;
    else if(lesson<practiceData.lessons.length-1){lesson++;step=0;}
    else{document.getElementById('practice').innerHTML='<section class="lesson-copy"><h2 id="stepTitle" tabindex="-1">You’re ready.</h2><p>Read the opposing hand. Keep an eye on your routes. Burn when the exchange is worth the card.</p><a class="menu-link" href="index.php?play">Play your first match →</a><p><a href="index.php">Return to main menu</a></p></section>';document.getElementById('stepTitle').focus();return;}
    selection=null;practiceRender(true);
  }
  if(button.id==='previous'){if(step>0)step--;else{lesson--;step=practiceData.lessons[lesson].frames.length-1;}selection=null;practiceRender(true);}
  if(button.id==='restart'){step=0;selection=null;practiceRender(true);}
});
practiceRender();
