import {chromium,firefox,webkit} from 'playwright';
import assert from 'node:assert/strict';
import {execFileSync} from 'node:child_process';
import {fileURLToPath} from 'node:url';
import {mkdir,readFile} from 'node:fs/promises';
import {tmpdir} from 'node:os';
import {join} from 'node:path';
const php=process.env.PHP_BINARY||'C:/xampp/php/php.exe';
const fixture=JSON.parse(execFileSync(php,[fileURLToPath(new URL('../DFGSim/iron-guard-tests.php',import.meta.url)),'--fixtures'],{encoding:'utf8'})).planning;
const shots=process.env.DFG_SCREENSHOTS||join(tmpdir(),'dfg-menu-tutorial');await mkdir(shots,{recursive:true});
let failures=0;
for(const [name,engine] of Object.entries({chromium,firefox,webkit})){
 if(process.env.DFG_BROWSERS&&!process.env.DFG_BROWSERS.split(',').includes(name))continue;
 let browser;
 try{
  browser=await engine.launch({headless:true});
  for(const viewport of [{width:1440,height:1000},{width:390,height:844}]){
   const page=await browser.newPage({viewport,reducedMotion:'reduce'}),errors=[],writes=[];
   page.on('pageerror',e=>errors.push(e.message));
   await page.route('**/*',async route=>{
    const relative=new URL(route.request().url()).pathname.replace(/^\/TCGEngine\//,'');
    if(!relative.startsWith('DFGSim/')&&!relative.startsWith('Core/'))return route.fulfill({status:404,body:''});
    const source=new URL(`../../${relative.endsWith('/')?relative+'index.php':relative}`,import.meta.url);
    try{const body=source.pathname.endsWith('.php')?execFileSync(php,[fileURLToPath(source)]):await readFile(source);return route.fulfill({body,contentType:source.pathname.endsWith('.php')?'text/html':source.pathname.endsWith('.js')?'text/javascript':source.pathname.endsWith('.css')?'text/css':'image/png'});}
    catch{return route.fulfill({status:404,body:''});}
   });
   let saved=null;
   await page.route('**/DFGSim/api.php',async route=>{
    const body=route.request().postDataJSON();if(body){writes.push(body);saved=fixture.state;}
    await route.fulfill({json:{...fixture,state:saved}});
   });
   const base='http://localhost/TCGEngine/DFGSim/';
   await page.goto(base);await page.locator('#homePlay:enabled').waitFor();
   assert.equal(await page.locator('#home').isVisible(),true);assert.equal(await page.locator('#gameView').isVisible(),false);
   assert.equal(await page.locator('#setup').isVisible(),false);assert.equal(await page.locator('#resumeMatch').isVisible(),false);
   await page.locator('#homeRules').click();await page.locator('#rulesPopup').waitFor();await page.keyboard.press('Escape');
   await page.locator('#homePlay').click();await page.locator('#setup').waitFor();await page.locator('#cancelSetup').click();assert.equal(await page.locator('#home').isVisible(),true);
   await page.screenshot({path:join(shots,`${name}-${viewport.width}-home.png`),fullPage:true});
   // Start through the actual form; navigation must not create another match.
   await page.locator('#homePlay').click();await page.locator('#setupForm [type=submit]').click();await page.locator('#gameView').waitFor();
   assert.equal(writes.length,1);assert.equal(writes[0].command,'new');assert.equal(writes[0].settings.difficulty,'tactical');
   await page.locator('#matchMenu summary').click();await page.locator('#goHome').click();await page.locator('#resumeMatch').click();assert.equal(writes.length,1);
   await page.reload();await page.locator('#resumeMatch').waitFor();assert.equal(await page.locator('#home').isVisible(),true);
   await page.locator('#home a[href="learn.php"]').click();await page.locator('#stepTitle').waitFor();
   assert.equal(await page.locator('#advance').isDisabled(),true);
   await page.locator('[data-choice="1"]').click();assert.equal(await page.locator('#advance').isDisabled(),true);
   await page.locator('[data-choice="2"]').click();assert.equal(await page.locator('#advance').isEnabled(),true);
   await page.screenshot({path:join(shots,`${name}-${viewport.width}-learn.png`),fullPage:true});
   const overflow=()=>page.evaluate(()=>document.documentElement.scrollWidth>innerWidth);
   assert.equal(await overflow(),false,'Tutorial fits viewport');
   const lessons=await page.evaluate(()=>practiceData.lessons);
   for(let l=0;l<lessons.length;l++){
    await page.locator(`[data-lesson="${l}"]`).click();
    for(let i=0;i<lessons[l].frames.length;i++){
     const frame=lessons[l].frames[i];assert.equal(await page.locator('#stepTitle').textContent(),frame.title);
     if(frame.choice)await page.locator(`[data-choice="${frame.choice.value}"]`).click();
     assert.equal(await overflow(),false,`Fits lesson ${l} step ${i}`);
     await page.locator('#advance').click();
    }
   }
   assert.equal(await page.locator('#stepTitle').textContent(),'You’re ready.');
   await page.locator('#restart').click();assert.equal(await page.locator('#stepTitle').textContent(),'An empty hand is a trap');
   await page.locator('#previous').click();assert.equal(await page.locator('#stepTitle').textContent(),'Objectives create future options');
   await page.locator('.lesson-nav a[href="index.php"]').click();await page.locator('#resumeMatch').click();
   assert.equal(writes.length,1,'Tutorial and resume never modify the live match');
   assert.equal(await page.locator('#roundNumber').textContent(),String(fixture.state.round));
   await page.locator('#matchMenu summary').click();await page.locator('#goHome').click();assert.equal(await overflow(),false,'Menu fits viewport');
   assert.deepEqual(errors,[]);console.log(`${name} ${viewport.width}: menu and all 14 tutorial steps passed`);await page.close();
  }
 }catch(error){failures++;console.error(`${name}: ${error.stack}`);}finally{await browser?.close();}
}
console.log(`Screenshots: ${shots}`);if(failures)process.exitCode=1;
