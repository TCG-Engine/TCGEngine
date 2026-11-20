'use strict';
// Derive sounds from observed public transitions, never from the bot's hidden plan.
window.DFGCombatSounds=(()=>{
  const resolvedPhases=new Set(['stripping','relocating','routing','directing','switching','finished']);
  const publicPlans=s=>s.commitments?.every(p=>p&&!p.hidden);
  function events(before,after,action=null){
    if(!before||!after||before.matchId!==after.matchId||before.revision===after.revision)return [];
    const out=[],add=(id,seat=null,delay=0)=>out.push({id,seat,delay});
    const revealed=publicPlans(after)&&!publicPlans(before);
    if(action?.type==='commit')add('commit');
    if(revealed){add('reveal',null,.08);for(const seat of [0,1]){const p=after.commitments[seat];if(p.bonuses?.length)add('burn-bonus',seat,.14);if(p.advantages?.length)add('advantage-spend',seat,.2);}}
    // Reactions are already public. Multiple polling/render passes must not repeat them.
    if(publicPlans(after)&&(after.reactions?.length??0)>(before.reactions?.length??0))for(const r of after.reactions.slice(before.reactions?.length??0))add('burn-reaction',r.seat);
    const resolved=before.phase==='reactions'&&resolvedPhases.has(after.phase);
    const finished=after.phase==='finished'&&before.phase!=='finished';
    if(resolved){for(const seat of [0,1]){
      const a=before.players[seat],b=after.players[seat],stats=after.stats?.[1-seat]??{};
      const damage=Math.max(0,stats.damage??a.life-b.life),ko=b.life<=0;
      if(ko||damage>0||stats.attack>0||stats.preArmorDamage>0)add(ko?'knockout':damage===0?'block':damage<=2?'hit-light':damage<=4?'hit-medium':'hit-heavy',seat);
      if((a.shield??0)>(b.shield??0))add(b.shield===0?'barrier-break':'barrier-absorb',seat,.04);
      if((b.usedBreak?.length??0)>(a.usedBreak?.length??0))add('break-stop',seat,.04);
      if((b.usedBurst?.length??0)>(a.usedBurst?.length??0))add('burst-break',seat,.04);
      if(stats.confirmed>0)add('confirm-cash',seat,.07);
      if((b.incomingConfirm??0)>0)add('confirm-mark',seat,.3);
      if((b.advantages??[]).some(c=>!(a.advantages??[]).includes(c)))add('advantage-gain',seat,.35);
    }}
    // Concession has an outcome cue, but no fictional strike or knockout impact.
    if(finished)add(after.winner===0?'victory':'defeat',null,resolved?.65:0);
    else if(after.round>before.round&&after.phase==='planning')add('round-ready');
    else if(after.phase==='reactions'&&after.reactionTurn===0&&(before.phase!=='reactions'||before.reactionTurn!==0))add('reaction-ready',null,.3);
    return out;
  }
  function transition(before,after,action){for(const event of events(before,after,action))window.DFGSoundtrack?.effect(event.id,event);}
  function selection(moves){if(!moves.length)return;window.DFGSoundtrack?.effect(moves.some(m=>String(m.destination).startsWith('hand-'))?'card-return':'card-swipe');}
  return {events,transition,selection};
})();
