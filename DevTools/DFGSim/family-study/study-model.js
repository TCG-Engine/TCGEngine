'use strict';
// Local exchange arithmetic only. This file is never used by the playable game.
window.DFGStudyEvaluate = (v, input) => {
  const m=v.model||{};
  if(v.cost>input.stamina)return {available:false};
  let attack=v.attack,block=v.block;
  const read=(m.attackThreshold!==undefined&&input.enemyAttack>=m.attackThreshold)||
    (m.blockThreshold!==undefined&&input.enemyBlock>=m.blockThreshold)||
    (m.exactAttack!==undefined&&input.enemyAttack===m.exactAttack);
  if(read){attack+=m.readAttack||0;block+=m.readBlock||0;}
  if(input.enemyBonus)block+=m.bonusBlock||0;
  if(!input.ownBonus)attack+=m.noBonusAttack||0;
  if(m.reserveThreshold!==undefined&&input.stamina>=m.reserveThreshold)attack+=m.reserveAttack;
  const dealt=Math.max(0,attack-Math.max(0,input.enemyBlock-(m.pierce||0)));
  const taken=Math.max(0,input.enemyAttack-block);
  const after=m.healTiming==='after';
  const eligible=(!m.healOnHit||dealt>0)&&(!m.cleanHeal||taken===0);
  const plannedHeal=eligible?(m.heal||0):0;
  let gained=0,life=input.life;
  if(!after){gained=Math.min(plannedHeal,40-life);life+=gained;}
  const ko=taken>=life;
  life=Math.max(0,life-taken);
  if(after&&!ko){gained=Math.min(plannedHeal,40-life);life+=gained;}
  return {available:true,attack,block,gained,taken,dealt,life,ko,clean:taken===0,remainingStamina:input.stamina-v.cost,score:life-input.life+dealt};
};
