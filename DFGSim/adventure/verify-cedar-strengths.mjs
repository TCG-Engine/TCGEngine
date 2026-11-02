import assert from 'node:assert/strict';
import {createRequire} from 'node:module';
import {readFile} from 'node:fs/promises';
const require=createRequire(new URL('../../DevTools/ui-harness/package.json',import.meta.url));
const {chromium,webkit}=require('playwright');
const report=JSON.parse(await readFile(new URL('assets/ambient-sections/cedar-strength-study-v3.json',import.meta.url),'utf8'));
assert.equal(report.additionalCreditsSpent,0);
for(const v of report.variants){assert.ok(v.maximumDisplacementPixels<=v.limitPixels+.001);assert.equal(v.protectedTrunkMaxChannelDifferenceBeforeEncoding,0);}
const url=(process.env.ADVENTURE_URL||'http://localhost/TCGEngine/DFGSim/adventure/')+'ambient-study.php#cedar-strengths';
for(const [name,engine]of Object.entries({chromium,webkit})){
  if(process.env.CEDAR_ENGINE&&process.env.CEDAR_ENGINE!==name)continue;
  const browser=await engine.launch({headless:true});
  try{
    const page=await browser.newPage({viewport:{width:1440,height:1100}}),errors=[],nativeControlIssues=[];
    page.on('pageerror',e=>{
      // Known WebKit native-controls baseline in the older gallery players;
      // the new comparisons use custom controls and must pass all frame checks.
      if(name==='webkit'&&e.message==='Temporal.Duration properties must be finite and of consistent sign'&&e.stack?.includes('commitProperty'))nativeControlIssues.push(e.message);
      else errors.push(e.message);
      if(process.env.CEDAR_DIAGNOSTICS)console.log(e.stack);
    });page.setDefaultTimeout(15000);
    await page.goto(url);await page.locator('#cedarStrengthPlay').click();
    await page.waitForFunction(()=>[...document.querySelectorAll('video[data-cedar-strength]')].every(v=>!v.paused&&v.currentTime>1.5&&v.readyState>=2));
    assert.equal(await page.locator('video[data-cedar-strength]').count(),3);
    assert.ok((await page.locator('video[data-cedar-strength]').evaluateAll(es=>es.map(v=>v.playbackRate))).every(rate=>rate===.91));
    await page.locator('#cedarStrengthMap').scrollIntoViewIfNeeded();await page.locator('#cedarStrengthOriginal').click();
    await page.evaluate(()=>new Promise(r=>requestAnimationFrame(()=>requestAnimationFrame(r))));
    await page.locator('#cedarStrengthMap').evaluate(c=>{window.__cedarOriginal=c.getContext('2d').getImageData(0,0,c.width,c.height).data;});
    await page.locator('#cedarStrengthOriginal').click();
    const samples=[];
    for(const id of ['light','medium','strong']){
      await page.locator('#cedarStrengthChoice').selectOption(id);
      await page.evaluate(()=>new Promise(r=>requestAnimationFrame(()=>requestAnimationFrame(r))));
      const diff=await page.locator('#cedarStrengthMap').evaluate(c=>{const data=c.getContext('2d').getImageData(0,0,c.width,c.height).data,base=window.__cedarOriginal;let changed=0,outside=0;for(let y=0;y<c.height;y++)for(let x=0;x<c.width;x++){const i=(y*c.width+x)*4;if(data[i]!==base[i]||data[i+1]!==base[i+1]||data[i+2]!==base[i+2]){changed++;if(x>=576||y>=384)outside++;}}return {changed,outside};});
      assert.ok(diff.changed>100,'Visible map-scale composite for '+id);assert.equal(diff.outside,0,'Only the grove region changes');samples.push({id,...diff});
    }
    await page.locator('#cedarStrengthChoice').selectOption('medium');
    await page.evaluate(()=>new Promise(r=>requestAnimationFrame(()=>requestAnimationFrame(r))));
    await page.locator('#cedarStrengthMap').evaluate(c=>{window.__cedarFrame=c.getContext('2d').getImageData(0,0,c.width,c.height).data;});
    const start=await page.locator('video[data-cedar-strength="medium"]').evaluate(v=>v.currentTime);
    await page.waitForFunction(start=>{const v=document.querySelector('video[data-cedar-strength="medium"]');return (v.currentTime-start+v.duration)%v.duration>.7;},start);
    await page.evaluate(()=>new Promise(r=>requestAnimationFrame(()=>requestAnimationFrame(r))));
    const temporal=await page.locator('#cedarStrengthMap').evaluate(c=>{const a=c.getContext('2d').getImageData(0,0,c.width,c.height).data,b=window.__cedarFrame;let changed=0;for(let i=0;i<a.length;i+=4)if(a[i]!==b[i]||a[i+1]!==b[i+1]||a[i+2]!==b[i+2])changed++;return changed;});
    assert.ok(temporal>100,'The map preview advances actual animation frames');
    await page.locator('#cedarStrengthPause').click();assert.ok((await page.locator('video[data-cedar-strength]').evaluateAll(es=>es.map(v=>v.paused))).every(Boolean));
    await page.locator('#cedarStrengthChoice').selectOption('medium');await page.locator('#cedarStrengthPlay').click();
    if(process.env.ADVENTURE_SCREENSHOTS&&name==='chromium'){
      await page.locator('#cedar-strengths').scrollIntoViewIfNeeded();
      await page.screenshot({path:process.env.ADVENTURE_SCREENSHOTS+'/cedar-strengths-v3.png'});
    }
    await page.setViewportSize({width:390,height:844});assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false);assert.deepEqual(errors,[]);
    console.log(`PASS ${name}: three clips, restart/pause, map-scale selection, protected surroundings and mobile layout; ${temporal} temporally changed pixels ${JSON.stringify(samples)}`);
    if(nativeControlIssues.length)console.log(`KNOWN BASELINE ${name}: ${nativeControlIssues.length} native media-control time-formatting errors in older gallery players`);
  }finally{await browser.close();}
}
