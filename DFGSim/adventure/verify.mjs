import assert from 'node:assert/strict';
import {createRequire} from 'node:module';
import {mkdir} from 'node:fs/promises';
const require=createRequire(new URL('../../DevTools/ui-harness/package.json',import.meta.url));
const {chromium,webkit,firefox}=require('playwright');
const base=process.env.ADVENTURE_URL||'http://localhost/TCGEngine/DFGSim/adventure/';
const shots=process.env.ADVENTURE_SCREENSHOTS;if(shots)await mkdir(shots,{recursive:true});
const skipped=[];let cases=0;
for(const [name,engine] of Object.entries({chromium,webkit,firefox})){
 let browser;try{browser=await engine.launch({headless:true});}catch(e){skipped.push(name+': '+e.message.split('\n')[0]);continue;}
 try{for(const viewport of [{width:1440,height:1000},{width:390,height:844}]){
  const context=await browser.newContext({viewport,reducedMotion:'reduce'}),page=await context.newPage(),errors=[];
  page.on('pageerror',e=>errors.push(e.message));
  let data;
  const read=async()=>{const r=await context.request.get(new URL('api.php',base).href);assert.equal(r.status(),200);return data=await r.json();};
  const act=async action=>{const r=await context.request.post(new URL('api.php',base).href,{data:{command:'action',token:data.token,runId:data.journey.id,revision:data.journey.revision,action}});assert.equal(r.status(),200,(await r.text()).slice(0,350));return data=await r.json();};
  const fit=async()=>assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false,'No horizontal overflow');
  const saved=()=>page.waitForFunction(()=>document.querySelector('#saveStatus')?.textContent==='Journey saved');
  const ready=async()=>{await page.locator('#loading[hidden]').waitFor({state:'attached'});await fit();};
  await page.goto(base);await ready();await read();assert.equal(data.journey.deck.length,36);
  if(shots)await page.screenshot({path:`${shots}/${name}-${viewport.width}-arrival.png`});
  // Real click-to-walk: enter overview and click the actual mailbox on the canvas.
  await page.locator('#followQuest').click();
  const point=await page.locator('canvas').evaluate((c,e)=>{const r=c.getBoundingClientRect(),s=Math.min(r.width/1536,r.height/1024);return{x:r.x+r.width/2+(e.x*64+32-768)*s,y:r.y+r.height/2+(e.y*64+32-512)*s};},data.journey.entities.mailbox);
  await page.mouse.click(point.x,point.y);await page.locator('#panel[open]').waitFor();await saved();await read();assert.equal(data.journey.flags.invited,true);
  assert.ok((await page.locator('.dialogue-text').textContent()).includes('good breakfast'));
  if(shots)await page.screenshot({path:`${shots}/${name}-${viewport.width}-invitation.png`});
  await page.locator('#closePanel').click();await saved();
  // Keyboard and touch movement both use the same collision-checked path contract.
  const initial=(await read()).journey.position;
  await page.keyboard.down('ArrowRight');await page.waitForFunction(()=>document.querySelector('#saveStatus')?.textContent==='Saving…');await page.keyboard.up('ArrowRight');await saved();
  const moved=(await read()).journey.position;assert.notDeepEqual(moved,initial,'Keyboard moves player');
  await page.waitForTimeout(180);
  if(viewport.width<700){const south=page.getByRole('button',{name:'Walk south',exact:true});await south.hover();await page.mouse.down();await page.waitForFunction(()=>document.querySelector('#saveStatus')?.textContent==='Saving…');await page.mouse.up();await saved();}
  await page.locator('[data-panel="deck"]').click();await page.locator('.card-library').waitFor();await fit();
  assert.equal(await page.locator('.library-entry').count(),6);assert.equal(await page.locator('.library-entry input:checked').count(),6);assert.equal(await page.locator('.baseline-origin').count(),2);
  await page.locator('.library-entry input').first().uncheck();await page.locator('#saveDeck').click();await page.locator('#panel:not([open])').waitFor({state:'attached'});await saved();assert.equal((await read()).journey.deck.length,35);
  await page.locator('[data-panel="deck"]').click();await page.locator('.library-entry input[data-card="1"]').check();await page.locator('#saveDeck').click();await page.locator('#panel:not([open])').waitFor({state:'attached'});await saved();
  await page.locator('[data-panel="equipment"]').click();await page.locator('[data-slot="Chest"]').selectOption('');await saved();assert.equal((await read()).journey.equipment.Chest,null);await page.locator('#closePanel').click();
  const blocked=(await context.request.post(new URL('api.php',base).href,{data:{command:'action',token:data.token,runId:data.journey.id,revision:data.journey.revision,action:{type:'move',path:[[20,8]]}}}));assert.equal(blocked.status(),400);
  const envelope={command:'action',token:data.token,runId:data.journey.id,revision:data.journey.revision,action:{type:'close'}};
  assert.equal((await context.request.post(new URL('api.php',base).href,{data:{...envelope,token:'bad'}})).status(),403);
  assert.equal((await context.request.post(new URL('api.php',base).href,{data:{...envelope,revision:-1}})).status(),409);
  function pathTo(id){const j=data.journey,e=j.entities[id],start=[j.position.x,j.position.y],q=[start],parents=new Map([[start.join(','),null]]),blocked=(x,y)=>x<0||y<0||x>=24||y>=16||data.map.blocked.some(([a,b,w,h])=>x>=a&&x<a+w&&y>=b&&y<b+h)||Object.values(j.entities).some(n=>['npc','champion'].includes(n.kind)&&n.x===x&&n.y===y);for(let n=0;n<q.length;n++){const p=q[n];if(Math.abs(p[0]-e.x)+Math.abs(p[1]-e.y)<=1){const out=[];let at=p;while(parents.get(at.join(','))!==null){out.push(at);at=parents.get(at.join(','));}return out.reverse();}for(const [dx,dy] of [[0,1],[1,0],[0,-1],[-1,0]]){const next=[p[0]+dx,p[1]+dy],k=next.join(',');if(!parents.has(k)&&!blocked(...next)){parents.set(k,p);q.push(next);}}}throw Error('Unreachable '+id);}
  async function talk(id){await read();const path=pathTo(id);if(path.length)await act({type:'move',path});await act({type:'interact',id});}
  for(const id of ['scribe','gardener','stopper','reed','sluice','gardener','attendant','bull-of-heaven'])await talk(id);
  assert.equal(data.journey.flags.canalRestored,true);assert.equal(data.journey.flags.gardenWitness,true);assert.equal(data.journey.canChallenge.includes('bull-of-heaven'),false);
  const before=data.journey.revision;const locked=await context.request.post(new URL('api.php',base).href,{data:{command:'action',token:data.token,runId:data.journey.id,revision:before,action:{type:'challenge',id:'bull-of-heaven',color:'Red'}}});assert.equal(locked.status(),400);assert.equal((await read()).journey.revision,before);
  await page.reload();await ready();await page.locator('#panel[open]').waitFor();assert.equal(await page.locator('[data-dialog-action="challenge"]').count(),0,'Locked champion refuses fight');await fit();
  if(shots)await page.screenshot({path:`${shots}/${name}-${viewport.width}-bull-gate.png`});
  await page.locator('#closePanel').click();await saved();await read();
  // File persistence survives a new PHP session while retaining the private save cookie.
  const saveCookie=(await context.cookies()).find(c=>c.name==='DFGChallenger');const fresh=await browser.newContext();await fresh.addCookies([saveCookie]);const durable=await (await fresh.request.get(new URL('api.php',base).href)).json();assert.equal(durable.journey.id,data.journey.id);assert.equal(durable.journey.flags.gardenWitness,true);await fresh.close();
  await talk('iddin');await page.reload();await ready();await page.locator('[data-dialog-action="challenge"]').click();const arena=page.frameLocator('#adventureBattle');await arena.locator('#gameView:not([hidden])').waitFor();
  await read();assert.equal(data.journey.phase,'fight');assert.equal(data.journey.battle.players[0].life,40);assert.equal(data.journey.battle.players[0].deck.length,36);assert.equal(data.journey.battle.commitments[1].hidden,true);assert.equal(data.journey.battle.initialSeed,undefined);
  assert.equal(await arena.locator('.arena-fighter-opponent').getAttribute('data-creature'),'iddin');
  assert.equal(await arena.locator('.arena-fighter-opponent.rogue-generated').count(),0,'Human retains original fighter framing');
  await arena.locator('#hand-1').click();await arena.locator('#commit').click();await page.frames().find(f=>f.url().includes('embedded=1')).waitForFunction(()=>typeof state!=='undefined'&&state.phase!=='planning');await fit();
  if(shots)await page.screenshot({path:`${shots}/${name}-${viewport.width}-shared-fight.png`});
  await read();await act({type:'battle',move:{type:'resign'}});assert.equal(data.journey.phase,'result');assert.equal(data.journey.flags.gardenWitness,true);assert.equal(data.journey.zone,'sanctuary');assert.deepEqual(data.journey.position,{x:11,y:12});
  await page.goto(base);await ready();await page.getByRole('button',{name:'Return to the courtyard →',exact:true}).click();await saved();await page.locator('#panel:not([open])').waitFor({state:'attached'});assert.equal((await read()).journey.phase,'world');
  assert.equal(data.journey.zone,'sanctuary');if(shots)await page.screenshot({path:`${shots}/${name}-${viewport.width}-sanctuary.png`});
  await talk('keeper');assert.ok(data.journey.dialog.text.includes('getting up again'));
  await talk('exit');await page.reload();await ready();await page.getByRole('button',{name:'Step into the courtyard',exact:true}).click();await page.locator('#panel:not([open])').waitFor({state:'attached'});await ready();await saved();assert.equal((await read()).journey.zone,'courtyard','Usable sanctuary exit');
  await talk('sanctuary');await page.reload();await ready();await page.getByRole('button',{name:'Enter the sanctuary',exact:true}).click();await page.locator('#panel:not([open])').waitFor({state:'attached'});await ready();await saved();assert.equal((await read()).journey.zone,'sanctuary','Usable sanctuary entrance');
  await talk('exit');await act({type:'door',id:'exit'});
  // Read-only completed-world fixture checks kneeling art and the ten-choice/six-slot editor.
  const fixture=structuredClone(data);fixture.journey.defeated=Object.keys(fixture.enemies);fixture.journey.flags.regionComplete=true;fixture.journey.collection=fixture.cards.filter(c=>fixture.journey.collection.includes(c.id)||c.champion&&!c.enemy).map(c=>c.id);
  await page.route('**/adventure/api.php',route=>route.fulfill({json:fixture}));await page.reload();await ready();await page.locator('#overview').click();
  await page.locator('[data-panel="deck"]').click();assert.equal(await page.locator('.library-entry').count(),10);assert.equal(await page.locator('.library-entry input:not(:checked):disabled').count(),4);await page.locator('[data-card="1"]').uncheck();await page.locator('[data-card="1000"]').check();assert.equal(await page.locator('.library-entry input:checked').count(),6);assert.equal(await page.locator('.library-entry input:not(:checked):disabled').count(),4,'Seventh card cannot be equipped');await fit();
  if(shots)await page.screenshot({path:`${shots}/${name}-${viewport.width}-six-card-limit.png`});await page.locator('#closePanel').click();await saved();
  if(shots)await page.screenshot({path:`${shots}/${name}-${viewport.width}-champions-kneeling.png`});assert.deepEqual(errors,[]);
  const gallery=await context.newPage();gallery.on('pageerror',e=>errors.push(e.message));await gallery.goto(new URL('technique-art.php',base).href);await gallery.locator('.library-entry').last().waitFor();assert.equal(await gallery.locator('.library-entry').count(),24);assert.equal(await gallery.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false,'Gallery has no horizontal overflow');
  const artURLs=await gallery.locator('.technique-art').evaluateAll(nodes=>nodes.map(e=>getComputedStyle(e,':before').backgroundImage));assert.equal(new Set(artURLs).size,24,'Every champion move has distinct artwork');assert.ok(artURLs.every(s=>s.includes('/adventure/assets/techniques/')));
  const paths=await gallery.locator('.full-art-link').evaluateAll(nodes=>nodes.map(e=>e.href));for(const path of paths){const asset=await context.request.get(path);assert.equal(asset.status(),200);assert.ok(asset.headers()['content-type'].includes('image/webp'));}
  if(shots){await gallery.locator('#imdugud').scrollIntoViewIfNeeded();await gallery.screenshot({path:`${shots}/${name}-${viewport.width}-technique-gallery.png`});}assert.deepEqual(errors,[]);
  await context.close();cases++;console.log(`PASS ${name} ${viewport.width}: adventure flow, shared fight, 24 unique champion illustrations and responsive gallery`);
 }}finally{await browser.close();}
}
console.log(`PASS ${cases} adventure browser cases`);if(skipped.length)console.log('Unavailable: '+skipped.join('; '));
