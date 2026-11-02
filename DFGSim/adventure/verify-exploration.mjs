import assert from 'node:assert/strict';
import {createRequire} from 'node:module';
import {mkdir} from 'node:fs/promises';
const require=createRequire(new URL('../../DevTools/ui-harness/package.json',import.meta.url));
const {chromium,webkit}=require('playwright');
const base=process.env.ADVENTURE_URL||'http://localhost/TCGEngine/DFGSim/adventure/';
const shots=process.env.ADVENTURE_SCREENSHOTS;if(shots)await mkdir(shots,{recursive:true});
for(const [name,engine] of Object.entries({chromium,webkit})){
 const browser=await engine.launch({headless:true});
 try{for(const viewport of [{width:1440,height:1000},{width:390,height:844}]){
  const context=await browser.newContext({viewport,reducedMotion:'reduce'}),page=await context.newPage(),errors=[];let data;
  page.on('pageerror',e=>errors.push(e.message));
  const read=async()=>{const response=await context.request.get(new URL('api.php',base).href);assert.equal(response.status(),200);return data=await response.json();};
  const act=async action=>{const response=await context.request.post(new URL('api.php',base).href,{data:{command:'action',token:data.token,runId:data.journey.id,revision:data.journey.revision,action}});assert.equal(response.status(),200,await response.text());return data=await response.json();};
  const ready=async()=>{await page.locator('#loading[hidden]').waitFor({state:'attached'});assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false);};
  const refresh=async()=>{await page.goto(base);await ready();await read();};
  function pathTo(id){const e=data.journey.entities[id],s=data.journey.position,q=[[s.x,s.y]],parents=new Map([[q[0].join(','),null]]);
   const blocked=(x,y)=>x<0||y<0||x>=data.map.width||y>=data.map.height||data.map.blocked.some(([a,b,w,h])=>x>=a&&x<a+w&&y>=b&&y<b+h)||Object.values(data.journey.entities).some(n=>['npc','champion'].includes(n.kind)&&n.x===x&&n.y===y);
   for(let i=0;i<q.length;i++){const p=q[i];if(Math.abs(p[0]-e.x)+Math.abs(p[1]-e.y)<=1){const out=[];let at=p;while(parents.get(at.join(','))!==null){out.push(at);at=parents.get(at.join(','));}return out.reverse();}for(const [dx,dy] of [[0,1],[1,0],[0,-1],[-1,0]]){const n=[p[0]+dx,p[1]+dy],key=n.join(',');if(!parents.has(key)&&!blocked(...n)){parents.set(key,p);q.push(n);}}}throw Error('Unreachable '+id);
  }
  const talk=async id=>{const path=pathTo(id);if(path.length)await act({type:'move',path});await act({type:'interact',id});};
  const ui=async selector=>{await Promise.all([page.waitForResponse(r=>r.url().endsWith('/api.php')&&r.request().method()==='POST'&&r.status()===200),page.locator(selector).click()]);await page.waitForFunction(()=>document.querySelector('#saveStatus').textContent==='Journey saved');await read();};
  await refresh();assert.equal(data.journey.copper,12);await talk('mailbox');await talk('quay-path');await refresh();await ui('[data-dialog-action="door"]');await page.locator('#loading[hidden]').waitFor({state:'attached'});assert.equal(data.journey.zone,'riverside');
  await page.locator('#overview').click();
  if(shots)await page.screenshot({path:`${shots}/${name}-${viewport.width}-riverside.png`});
  await talk('outfitter');await refresh();await page.locator('[data-dialog-action="shop"]').click();assert.equal(await page.locator('[data-buy]').count(),7);assert.equal(await page.locator('[data-buy]:disabled').count(),7,'Pouch cannot afford initial stock');
  for(const id of ['clay-bank','kiln','cargo-west','cargo-east','loading-post','herb-west','herb-east','water-jar'])assert.equal(data.journey.entities[id],undefined,'Inactive work has no interaction: '+id);
  await talk('potter');await refresh();await ui('[data-dialog-action="job"]');assert.equal(data.journey.jobs.potter.active,true);assert.ok(data.journey.entities['clay-bank']);assert.equal(data.journey.entities.kiln,undefined,'Only the next step is interactable');assert.equal(await page.locator('#objectiveTitle').textContent(),'A bowl before a boast');
  for(const id of ['clay-bank','kiln','potter'])await talk(id);assert.equal(data.journey.copper,22);await refresh();await ui('[data-dialog-action="learn"]');assert.ok(data.journey.collection.includes(1100));assert.equal(data.journey.deck.length,36);
  await talk('outfitter');await refresh();await page.locator('[data-dialog-action="shop"]').click();await ui('[data-buy="reed-boots"]');assert.equal(data.journey.copper,6);assert.ok(data.journey.inventory.includes('reed-boots'));assert.equal(await page.locator('[data-buy="reed-boots"]').textContent(),'Owned');
  if(shots)await page.screenshot({path:`${shots}/${name}-${viewport.width}-shop.png`});
  await page.locator('[data-shop-equipment]').click();await Promise.all([page.waitForResponse(r=>r.url().endsWith('/api.php')&&r.request().method()==='POST'&&r.status()===200),page.locator('[data-slot="Legs"]').selectOption('reed-boots')]);await read();assert.equal(data.journey.equipment.Legs,'reed-boots');
  for(const [owner,steps] of [['dockmaster',['cargo-west','cargo-east','loading-post']],['river-teacher',['herb-west','herb-east','water-jar']]]){
   await talk(owner);await act({type:'job',id:owner});for(const id of [...steps,owner])await talk(id);
   if(owner==='river-teacher'){for(const id of ['reflection-one','reflection-two','reflection-three'])await talk(id);await talk(owner);}
   await act({type:'learn',id:owner});
  }
  assert.equal(data.journey.collection.length,39);assert.equal(data.journey.copper,26);
  await refresh();await page.locator('#closePanel').click();await page.waitForFunction(()=>!document.querySelector('#panel').open);await read();
  await page.locator('[data-panel="deck"]').click();await page.locator('[data-color="Blue"]').click();assert.equal(await page.locator('[data-card="1102"]').count(),1,'Learned move shown in library');
  await page.locator('#closePanel').click();await page.waitForFunction(()=>document.querySelector('#saveStatus').textContent==='Journey saved');await read();
  await page.locator('[data-panel="journal"]').click();assert.ok((await page.locator('#panelContent').innerText()).includes('Riverside errands & lessons'));
  // A courtyard objective from the new quarter points at the real exit.
  await page.locator('[data-target="scribe"]').click();assert.ok((await page.locator('#toast').innerText()).includes('Path to the First Courtyard'));
  await read();await talk('river-exit');await act({type:'door',id:'river-exit'});await refresh();assert.equal(data.journey.zone,'courtyard');assert.equal(data.journey.copper,26);assert.equal(data.journey.collection.length,39);
  const cookies=(await context.cookies()).filter(c=>c.name==='DFGChallenger');await context.close();const resumed=await browser.newContext();await resumed.addCookies(cookies);const persisted=await (await resumed.request.get(new URL('api.php',base).href)).json();assert.equal(persisted.journey.copper,26);assert.ok(persisted.journey.collection.includes(1102));await resumed.close();
  assert.deepEqual(errors,[]);console.log(`PASS ${name} ${viewport.width}: travel, jobs, shop, equipment, three lessons, journal routing, persistence`);
 }}finally{await browser.close();}
}
