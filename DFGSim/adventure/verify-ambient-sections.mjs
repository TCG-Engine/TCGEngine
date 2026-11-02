import assert from 'node:assert/strict';
import {execFileSync} from 'node:child_process';
import {createRequire} from 'node:module';
import {mkdir} from 'node:fs/promises';
const require=createRequire(new URL('../../DevTools/ui-harness/package.json',import.meta.url));
const {chromium,webkit}=require('playwright');
const base=process.env.ADVENTURE_URL||'http://localhost/TCGEngine/DFGSim/adventure/';
// Read-only client fixtures; never introduce a state-changing production endpoint.
const fixtures=JSON.parse(execFileSync('php',['-r',`
require 'DFGSim/adventure/World.php';$all=[];
foreach(['courtyard','sanctuary','riverside'] as $zone){
 $j=ADNewJourney();$j['zone']=$zone;$j['position']=['x'=>12,'y'=>8];$view=ADObserve($j);
 $all[$zone]=['ok'=>true,'token'=>'fixture-only','journey'=>$view,'cards'=>array_values(array_map('ADPublicCard',DFGCatalog())),'colors'=>DFG_STYLES,'items'=>ADItems(),'enemies'=>ADPublicEnemies(),'baselineCards'=>ADBaselineCards(),'animations'=>ADAnimations(),'map'=>ADMap($zone)];
}echo json_encode($all,JSON_THROW_ON_ERROR);`],{encoding:'utf8'}));
const shots=process.env.ADVENTURE_SCREENSHOTS;if(shots)await mkdir(shots,{recursive:true});
for(const [engineName,engine]of Object.entries({chromium,webkit})){
 if(process.env.ADVENTURE_AMBIENT_ENGINE&&process.env.ADVENTURE_AMBIENT_ENGINE!==engineName)continue;
 const browser=await engine.launch({headless:true});
 try{for(const [zone,fixture]of Object.entries(fixtures)){
  const context=await browser.newContext({viewport:{width:1440,height:1000},reducedMotion:'reduce'}),page=await context.newPage(),errors=[];
  page.on('pageerror',e=>errors.push(e.message));page.setDefaultTimeout(15000);
  await page.route('**/adventure/api.php',route=>{assert.equal(route.request().method(),'GET','Fixture must not mutate a save');return route.fulfill({json:fixture});});
  await page.goto(base);await page.locator('#loading[hidden]').waitFor({state:'attached'});await page.locator('#overview').click();
  await page.evaluate(()=>new Promise(r=>requestAnimationFrame(()=>requestAnimationFrame(r))));
  assert.equal(await page.locator('video[data-ambient-section]').count(),0,'Reduced motion does not load media');
  await page.locator('#worldCanvas').evaluate(c=>{window.__staticMap=c.getContext('2d').getImageData(0,0,c.width,c.height).data;});
  await page.emulateMedia({reducedMotion:'no-preference'});
  await page.waitForFunction(zone=>{const v=document.querySelector(`video[data-ambient-section="${zone}"]`);return v&&!v.paused&&v.readyState>=2;},zone);
  const video=page.locator(`video[data-ambient-section="${zone}"]`),rate={courtyard:.87,sanctuary:.73,riverside:1}[zone];
  assert.equal(await video.evaluate(v=>v.playbackRate),rate,'Independent playback rate survives media loading');
  const time=await video.evaluate(v=>v.currentTime);await page.waitForFunction(t=>document.querySelector('video[data-ambient-section]').currentTime!==t,time);
  // Playback can begin before the mask decodes or a compositor frame is ready.
  try{await page.waitForFunction(()=>{const c=document.getElementById('worldCanvas'),a=c.getContext('2d').getImageData(0,0,c.width,c.height).data,b=window.__staticMap;let n=0;for(let i=0;i<a.length;i+=4)if(a[i]!==b[i]||a[i+1]!==b[i+1]||a[i+2]!==b[i+2])if(++n>50)return true;return false;});}catch(error){
    console.log(await video.evaluate(v=>({time:v.currentTime,paused:v.paused,ready:v.readyState,width:v.videoWidth,height:v.videoHeight,error:v.error?.message,reduced:matchMedia('(prefers-reduced-motion: reduce)').matches})));console.log(errors);throw error;
  }
  if(zone==='courtyard'){
    await page.waitForFunction(()=>{const v=document.querySelector('video[data-ambient-section="cedar-grove"]');return v&&!v.paused&&v.readyState>=2;});
    assert.equal(await page.locator('video[data-ambient-section="cedar-grove"]').evaluate(v=>v.playbackRate),.91,'Grove and canal use distinct playback periods');
    assert.ok((await page.locator('video[data-ambient-section="cedar-grove"]').getAttribute('src')).includes('restrained-v2'),'Cedar uses original-art restrained motion, not the rejected raw retry');
    await page.waitForFunction(()=>{const v=document.querySelector('video[data-ambient-section="temple-court"]');return v&&!v.paused&&v.readyState>=2;});
    assert.equal(await page.locator('video[data-ambient-section="temple-court"]').evaluate(v=>v.playbackRate),.79,'Temple has its own playback period');
    await page.evaluate(()=>{DFGAdventureAmbient.sections['timing-test']={...DFGAdventureAmbient.sections.courtyard,zone:'courtyard',rate:.79,phase:.71};});
    await page.waitForFunction(()=>{const v=document.querySelector('video[data-ambient-section="timing-test"]');return v&&!v.paused&&v.readyState>=2;});
    assert.equal(await page.locator('video[data-ambient-section="timing-test"]').evaluate(v=>v.playbackRate),.79,'Multiple visible sections have independent rates');
  }
  await page.locator('[data-panel="journal"]').click();await page.waitForFunction(()=>[...document.querySelectorAll('video[data-ambient-section]')].every(v=>v.paused));await page.locator('#closePanel').click();await page.waitForFunction(()=>!document.querySelector('video[data-ambient-section]').paused);
  if(zone==='courtyard'){await page.evaluate(()=>{delete DFGAdventureAmbient.sections['timing-test'];});await page.waitForFunction(()=>document.querySelector('video[data-ambient-section="timing-test"]').paused);}
  if(shots&&engineName==='chromium')await page.screenshot({path:`${shots}/adventure-live-${zone}-section.png`});
  await page.emulateMedia({reducedMotion:'reduce'});await page.waitForFunction(()=>document.querySelector('video[data-ambient-section]').paused);
  await page.setViewportSize({width:390,height:844});assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false);
  assert.deepEqual(errors,[]);console.log(`PASS ${engineName} ${zone}: live composite, rate ${rate}, menus, reduced motion, mobile layout`);await context.close();
 }}finally{await browser.close();}
}
