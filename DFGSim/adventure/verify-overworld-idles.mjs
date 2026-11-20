import assert from 'node:assert/strict';
import {execFileSync} from 'node:child_process';
import {createRequire} from 'node:module';
const require=createRequire(new URL('../../DevTools/ui-harness/package.json',import.meta.url));
const {chromium,webkit}=require('playwright');
const base=process.env.ADVENTURE_URL||'http://localhost/TCGEngine/DFGSim/adventure/';
const fixtures=JSON.parse(execFileSync('php',['-r',`
require 'DFGSim/adventure/World.php';$all=[];
foreach([false,true] as $defeated){$j=ADNewJourney();$j['position']=['x'=>12,'y'=>8];if($defeated)$j['defeated']=array_keys(ADChampions());$view=ADObserve($j);
$all[$defeated?'defeated':'ready']=['ok'=>true,'token'=>'fixture-only','journey'=>$view,'cards'=>array_values(array_map('ADPublicCard',DFGCatalog())),'colors'=>DFG_STYLES,'items'=>ADItems(),'enemies'=>ADPublicEnemies(),'baselineCards'=>ADBaselineCards(),'animations'=>ADAnimations(),'map'=>ADMap('courtyard')];}echo json_encode($all,JSON_THROW_ON_ERROR);`],{encoding:'utf8'}));
for(const [name,engine]of Object.entries({chromium,webkit})){
 const browser=await engine.launch({headless:true});
 try{for(const [state,fixture]of Object.entries(fixtures)){
  const context=await browser.newContext({viewport:{width:1440,height:1000},reducedMotion:'reduce'}),page=await context.newPage(),errors=[];
  page.on('pageerror',e=>errors.push(e.message));await page.route('**/adventure/api.php',route=>{assert.equal(route.request().method(),'GET');return route.fulfill({json:fixture});});
  await page.goto(base);await page.locator('#loading[hidden]').waitFor({state:'attached'});await page.locator('#overview').click();
  const loaded=()=>page.evaluate(()=>performance.getEntriesByType('resource').filter(e=>e.name.includes('/overworld-idles/')).map(e=>e.name));
  assert.equal((await loaded()).length,0,'Reduced motion keeps static fighters without loading atlases');
  await page.emulateMedia({reducedMotion:'no-preference'});
  if(state==='defeated'){
    await page.evaluate(()=>new Promise(r=>requestAnimationFrame(()=>requestAnimationFrame(r))));assert.equal((await loaded()).length,0,'Defeated champions retain their kneeling art');
  }else{
    await page.waitForFunction(()=>performance.getEntriesByType('resource').filter(e=>e.name.includes('-idle-atlas-v1.webp')).length===4);
    if(process.env.ADVENTURE_SCREENSHOTS&&name==='chromium')await page.screenshot({path:process.env.ADVENTURE_SCREENSHOTS+'/adventure-enemy-idles.png'});
    await page.evaluate(()=>{
      const canvas=document.createElement('canvas');canvas.width=640;canvas.height=240;
      window.__idleTest={canvas,ctx:canvas.getContext('2d'),engine:DFGOverworldIdles.create(),ids:['iddin','imdugud','huwawa','bull-of-heaven']};
    });
    await page.waitForFunction(()=>{const t=window.__idleTest;t.time=performance.now();t.ctx.clearRect(0,0,640,240);return t.ids.map((id,i)=>t.engine.draw(t.ctx,id,80+160*i,210,160,{time:t.time,enabled:true,active:true,visible:true})).every(Boolean);});
    const result=await page.evaluate(()=>{
      const t=window.__idleTest,read=()=>t.ctx.getImageData(0,0,640,240).data;
      const render=active=>{t.time+=16;t.ctx.clearRect(0,0,640,240);t.ids.forEach((id,i)=>t.engine.draw(t.ctx,id,80+160*i,210,160,{time:t.time,enabled:true,active,visible:true}));};
      const start=read().slice();for(let i=0;i<45;i++)render(true);const moving=read().slice();let changes=[0,0,0,0];
      for(let y=0;y<240;y++)for(let x=0;x<640;x++){const p=(y*640+x)*4;if(moving[p]!==start[p]||moving[p+1]!==start[p+1]||moving[p+2]!==start[p+2]||moving[p+3]!==start[p+3])changes[Math.floor(x/160)]++;}
      for(let i=0;i<45;i++)render(false);const paused=read();let pauseChanges=0;for(let i=0;i<paused.length;i++)if(paused[i]!==moving[i])pauseChanges++;
      return {changes,pauseChanges};
    });
    assert.ok(result.changes.every(n=>n>20),'Each fighter visibly changes frames');assert.equal(result.pauseChanges,0,'Menus/fights freeze the animation clock');
    await page.emulateMedia({reducedMotion:'reduce'});
  }
  assert.deepEqual(errors,[]);console.log(`PASS ${name} ${state}: all four idle loops / paused frames / reduced motion / kneeling preservation`);await context.close();
 }}finally{await browser.close();}
}
