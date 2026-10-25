import assert from 'node:assert/strict';
import {readFile,writeFile} from 'node:fs/promises';
import vm from 'node:vm';
import {createHash} from 'node:crypto';
const root=new URL('./',import.meta.url),context={window:{}};
for(const file of ['study-data.js','study-refinement.js','live-catalog.js','study-coverage.js','study-model.js'])vm.runInNewContext(await readFile(new URL(file,root),'utf8'),context,{filename:file});
const d=context.window.DFGFamilyStudy,evaluate=context.window.DFGStudyEvaluate;
const all=d.families.flatMap(f=>f.variants.map(v=>({f,v})));
assert.equal(d.families.length,65);assert.equal(d.entryCount,257);assert.equal(new Set(all.map(({v})=>v.id)).size,d.entryCount);
assert.equal(d.families.reduce((n,f)=>n+f.before.length,0),240);
for(const f of d.families){
  assert.ok(f.review.critique&&f.review.changes&&f.review.test,`${f.id} has a complete critique`);
  assert.ok(f.variants.filter(v=>!v.liveCardId).length>=3&&f.variants.filter(v=>!v.liveCardId).length<=6);
  for(const v of f.variants){
    assert.ok(Number.isInteger(v.attack)&&v.attack>=0&&Number.isInteger(v.block)&&v.block>=0);
    assert.ok(v.liveCardId?v.tags.length===0:v.tags.length>0);assert.ok((v.routes??f.routes).every(c=>d.colors.includes(c)));
    if(v.referenceOf){
      const source=all.find(({v:s})=>s.id===v.referenceOf);assert.ok(source);
      for(const key of ['name','attack','block','text','cost'])assert.equal(v[key],source.v[key],`${v.id}: shared ${key}`);
      for(const key of ['bonus','reaction','routes'])assert.equal(JSON.stringify(v[key]??f[key]),JSON.stringify(source.v[key]??source.f[key]));
      assert.equal(JSON.stringify(v.tags),JSON.stringify(source.v.tags));
    }
    assert.ok(!v.text.includes('gain +0'),'No zero-value main effects');
  }
}
// Coverage is checked against the repository source, not just the exported snapshot.
const liveText=await readFile(new URL('../../../DFGSim/data/cards.json',root),'utf8');
const sourceCards=JSON.parse(liveText).cards;
const runtime=await readFile(new URL('../../../DFGSim/Custom/GameLogic.php',root),'utf8');
assert.equal(d.liveSource.sha256,createHash('sha256').update(liveText).digest('hex'),'Refresh and review the stale live snapshot');
assert.equal(d.liveSource.rulesVersion,Number(runtime.match(/const DFG_RULES_VERSION = (\d+);/)[1]));
assert.equal(JSON.stringify(context.window.DFGStudyLiveCatalog.cards),JSON.stringify(sourceCards));
const anchors=all.filter(({v})=>v.liveCardId);
assert.equal(anchors.length,36);assert.equal(new Set(anchors.map(({v})=>v.liveCardId)).size,36);
assert.equal(d.coverage.length,sourceCards.length);
const facetCounts={main:0,objective:0,spend:0,bonus:0,reaction:0,routes:0};
for(const c of sourceCards){
  const entry=d.coverage.find(e=>e.cardId===c.id),anchor=anchors.find(({v})=>v.liveCardId===c.id);
  assert.ok(entry&&anchor,`${c.name} has a mapping and exact baseline`);assert.equal(anchor.f.id,entry.primary);
  for(const key of ['name','color','attack','block','bonus','reaction'])assert.equal(anchor.v[key],c[key],`${c.name}: original ${key}`);
  assert.equal(anchor.v.text,c.ability);assert.equal(JSON.stringify(anchor.v.routes),JSON.stringify(c.switches));
  for(const kind of ['main','objective','spend']){
    const clauses=c.ability.split('\n').filter(Boolean).filter(t=>(t.startsWith('Objective:')?'objective':t.startsWith('Spend ')?'spend':'main')===kind);
    assert.equal(JSON.stringify(entry.facets[kind].map(f=>f.text)),JSON.stringify(clauses),`${c.name}: every ${kind} clause`);
  }
  assert.equal(entry.facets.bonus[0].text,c.bonus||'Unavailable');assert.equal(entry.facets.reaction[0].text,c.reaction||'Unavailable');
  assert.equal(entry.facets.routes[0].text,c.switches.join(' / '));
  for(const [kind,facets] of Object.entries(entry.facets))for(const facet of facets){
    facetCounts[kind]++;assert.ok(facet.families.length>0,`${c.name}: unassigned ${kind}`);
    for(const id of facet.families)assert.ok(d.families.find(f=>f.id===id)?.sourceCards.some(s=>s.cardId===c.id));
    if(facet.text==='Unavailable')assert.ok(facet.families.includes('F58'));
  }
}
for(const f of d.families.slice(40))assert.ok(f.sourceCards?.length,`${f.id}: current mechanic provenance`);
const base={life:24,enemyAttack:4,enemyBlock:2,stamina:4,ownBonus:false,enemyBonus:false};
const run=(id,i,input={})=>evaluate(d.families.find(f=>f.id===id).variants[i],{...base,...input});
assert.deepEqual([0,2,4,6].map(a=>[0,1,2].map(i=>run('F01',i,{enemyAttack:a}).life-24)),[[4,3,2],[2,3,2],[0,1,2],[-2,-1,0]]);
assert.deepEqual([0,2,4].map(b=>[0,1,2].map(i=>run('F07',i,{enemyBlock:b}).dealt)),[[6,5,4],[4,5,4],[2,3,4]]);
assert.deepEqual([2,4,6].map(b=>[0,1,2].map(i=>run('F12',i,{enemyBlock:b}).dealt)),[[2,0,0],[0,2,0],[0,0,2]]);
assert.deepEqual([0,1,2].map(i=>run('F14',i).block),[1,3,5]);
assert.deepEqual([0,1,2].map(i=>run('F14',i,{enemyBonus:true}).block),[7,6,5]);
assert.deepEqual([0,1,2].map(i=>run('F15',i).attack),[7,6,5]);
assert.deepEqual([0,1,2].map(i=>run('F15',i,{ownBonus:true}).attack),[2,4,5]);
assert.equal(run('F16',2,{stamina:3}).available,false);
assert.equal(run('F16',2,{stamina:4}).remainingStamina,0);
assert.equal(run('F01',0,{life:40,enemyAttack:0}).gained,0);
assert.equal(run('F01',0,{life:2,enemyAttack:5}).life,1);
assert.equal(run('F02',0,{life:2,enemyAttack:8,enemyBlock:0}).gained,0);
assert.equal(run('F02',0,{life:2,enemyAttack:8,enemyBlock:0}).dealt,5);
assert.equal(run('F13',1,{enemyAttack:3}).attack,5);
assert.equal(run('F13',1,{enemyAttack:4}).attack,2);
assert.equal(run('F20',2,{stamina:5}).attack,7);
// The route-cut trigger windows must differ; attack - required damage is 0, 1, 2.
assert.deepEqual(Array.from(d.families[37].variants.filter(v=>!v.liveCardId),v=>v.attack-Number(v.text.match(/deal (\d+)\+/)[1])),[0,1,2]);
assert.ok(d.families[23].routes.includes('Purple'),'Exhaustion has a genuine stay option');
assert.equal(d.families[39].variants.every(v=>v.routes.length===2),true);

// Finite, deliberately limited Pareto screen. This is not a game-theoretic dominance proof.
const samples=[];
for(let enemyAttack=0;enemyAttack<=10;enemyAttack++)for(let enemyBlock=0;enemyBlock<=8;enemyBlock++)
  for(const life of [3,24,40])for(let stamina=0;stamina<=6;stamina++)for(const ownBonus of [false,true])for(const enemyBonus of [false,true])
    samples.push({enemyAttack,enemyBlock,life,stamina,ownBonus,enemyBonus});
const comparable=all.filter(({v})=>v.model&&!v.referenceOf);
const contextKey=({f,v})=>JSON.stringify([f.color,v.bonus??f.bonus,v.reaction??f.reaction,[...(v.routes??f.routes)].sort(),[...v.tags].sort()]);
const vector=r=>[r.life,r.dealt,-r.taken,r.remainingStamina,Number(!r.ko)];
let pairs=0;const flagged=[];
for(let i=0;i<comparable.length;i++)for(let j=i+1;j<comparable.length;j++) {
  const a=comparable[i],b=comparable[j];if(contextKey(a)!==contextKey(b))continue;pairs++;
  let aBetter=false,bBetter=false;
  for(const sample of samples){
    const x=evaluate(a.v,sample),y=evaluate(b.v,sample);
    if(x.available&&!y.available)aBetter=true;
    else if(!x.available&&y.available)bBetter=true;
    else if(x.available&&y.available){
      const xv=vector(x),yv=vector(y);
      if(xv.some((v,k)=>v>yv[k]))aBetter=true;
      if(yv.some((v,k)=>v>xv[k]))bBetter=true;
    }
    if(aBetter&&bBetter)break;
  }
  if(!aBetter||!bBetter)flagged.push({a:a.v.id,b:b.v.id,result:aBetter?'A numerically dominates sampled outcomes':bBetter?'B numerically dominates sampled outcomes':'Equivalent sampled outcomes'});
}
console.log(JSON.stringify({revision:d.revision,families:d.families.length,entries:d.entryCount,liveBaselines:anchors.length,proposalEntries:d.proposalCount,coverageFields:facetCounts,references:all.filter(({v})=>v.referenceOf).length,critiques:d.families.length,contexts:samples.length,comparablePairs:pairs,flagged},null,2));
if(process.argv.includes('--report')) {
  const links=facets=>facets.length?facets.map(x=>x.families.join(', ')).join('; '):'None';
  const report=[`# DFG current-card coverage — revision 3`,``,`2026-10-23. The refined 40-family study is merged with 25 additional families. ${d.families.length} families contain ${d.entryCount} entries: ${d.proposalCount} proposal/reference placements and ${anchors.length} exact current-card baselines. The game is unchanged.`,``,`## Traceability`,``,`All 36 current cards have one exact baseline. Every original main clause, objective, spend, bonus, reaction, and printed-route set maps to one or more families. Unavailable burn modes are mapped explicitly, while absent objectives and spends are identified as absent. This is coverage of mechanics and exact card definitions, not a guarantee that every possible combination has a separate variation.`,``,`Source: local DFGSim/data/cards.json; rules v${d.liveSource.rulesVersion}; SHA-256 ${d.liveSource.sha256}. The audit checks the snapshot, baseline fields, and all clause text against that source.`,``,`Field counts: ${Object.entries(facetCounts).map(([k,n])=>`${k}: ${n}`).join('; ')}.`,``,`## Current cards`,``,`| Card | Exact baseline | Main | Objective | Spend | Bonus | Reaction | Routes |`,`|---|---|---|---|---|---|---|---|`,...d.coverage.map(c=>`| ${c.name} (#${c.cardId}) | [${c.primary}](index.html#${c.primary}) | ${links(c.facets.main)} | ${links(c.facets.objective)} | ${links(c.facets.spend)} | ${links(c.facets.bonus)} | ${links(c.facets.reaction)} | ${links(c.facets.routes)} |`),``,`## Added families`,``,...d.families.slice(40).flatMap(f=>[`### ${f.id}: ${f.title}`,``,`**Gap:** ${f.review.critique}`,``,`**Family design:** ${f.review.changes}`,``,`**Still to test:** ${f.review.test}`,``,`Current examples: ${f.sourceCards.map(c=>c.name).join(', ')}.`,``]),`## Checks and limits`,``,`The screen retains ${samples.length} possible contexts and ${pairs} comparable modeled pairs. It exits when both directions have witnesses; it is not a full set of matrices. Exact current baselines are shown but deliberately excluded from the draft-only explorer and numerical screen because their additional effects are not fully modeled. New control, replacement, status, and objective families have semantic coverage and explicit tests to perform, not simulated proof of balance.`,``,`The historical [revision-2 critique](REFINEMENT.md) remains intact. Refresh the snapshot with sync-live-catalog.mjs after source changes and review mapping semantics; copying new text does not itself establish semantic coverage.`,``].join('\n');
  await writeFile(new URL('COVERAGE.md',root),report);
}
assert.deepEqual(flagged,[],'Resolve numeric flags or document exclusions explicitly before claiming a clean screen');
