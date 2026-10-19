import {chromium, firefox, webkit} from 'playwright';
import assert from 'node:assert/strict';
import {spawn} from 'node:child_process';
import {fileURLToPath} from 'node:url';
const port = Number(process.env.DFG_TEST_PORT || 18779);
const root = fileURLToPath(new URL('../../', import.meta.url));
const server = spawn(process.env.PHP_BINARY || 'C:/xampp/php/php.exe', ['-S', `127.0.0.1:${port}`, '-t', root], {stdio:'ignore'});
const base = `http://127.0.0.1:${port}/DFGSim/`;
let failures = 0;
try {
  for(let i=0;i<50;i++) {
    try { if((await fetch(base)).ok) break; } catch {}
    await new Promise(resolve=>setTimeout(resolve,100));
  }
  for (const [name, engine] of Object.entries({chromium,firefox,webkit})) {
    if(process.env.DFG_BROWSERS && !process.env.DFG_BROWSERS.split(',').includes(name)) continue;
    let browser;
    try {
      browser = await engine.launch({headless:true});
      for (const viewport of [{width:1440,height:1000},{width:390,height:844}]) {
        const page = await browser.newPage({viewport,reducedMotion:'reduce'});
        const errors=[];
        page.on('pageerror',e=>errors.push(e.message));
        await page.goto(base);
        await page.locator('#homePlay:enabled').waitFor();
        assert.deepEqual(await page.locator('#home a, #home button').allTextContents(), ['Learn to play', 'Play against the bot']);
        assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false,'Menu fits viewport');
        if(process.env.DFG_SCREENSHOTS) await page.screenshot({path:`${process.env.DFG_SCREENSHOTS}/${name}-${viewport.width}-menu.png`});
        // The full art set should warm while the player is still on the menu.
        await page.waitForFunction(() => new Set(performance.getEntriesByType('resource')
          .filter(entry=>entry.name.includes('/assets/cards/individual/') && entry.initiatorType==='img')
          .map(entry=>entry.name)).size===36, null, {timeout:20000});
        assert.equal(await page.locator('#home').isVisible(),true);
        const warmed = await page.evaluate(() => performance.getEntriesByType('resource')
          .filter(entry=>entry.name.includes('/assets/cards/individual/') && entry.initiatorType==='img')
          .map(entry=>new URL(entry.name).pathname.split('/').pop()));
        assert.equal(warmed.length,36,'Each art URL warms only once');
        assert.ok(warmed.slice(0,6).every(file=>/^00[1-6]-/.test(file)),'Red opening hand warms first');
        assert.ok(warmed.includes('020-v5.webp'),'Preloader uses current art revisions');
        await page.locator('#homePlay').click();
        await page.locator('#setupForm [type=submit]').click();
        await page.locator('#gameView').waitFor();
        const saved = await page.evaluate(async()=> (await (await fetch('api.php')).json()).state);
        await page.locator('#matchMenu summary').click();
        await page.locator('#goHome').click();
        await page.locator('#homePlay').click();
        assert.equal(await page.locator('#gameView').isVisible(),true,'Play resumes an active match');
        assert.equal(await page.locator('#setup').isVisible(),false,'Resuming skips new match setup');
        assert.deepEqual(await page.evaluate(async()=> (await (await fetch('api.php')).json()).state),saved,'Resuming preserves the match');
        await page.goto(base+'learn.php');
        await page.locator('#tutorialGuide:visible').waitFor();
        await page.locator('#hand-2').waitFor();
        assert.equal(await page.locator('#stepTitle').textContent(),'Tired of losing to a bad hand?');
        assert.match(await page.locator('#tutorialText').textContent(),/There’s no randomness/);
        assert.equal(await page.locator('#tutorialGuide details').count(),0,'No dropdown explanations or navigation');
        assert.equal(await page.locator('#tutorialSpotlight').isVisible(),false,'Introduction starts before a card choice');
        assert.equal(await page.locator('#tutorialGuide').evaluate(el=>getComputedStyle(el).position),'fixed');
        if(process.env.DFG_SCREENSHOTS) await page.screenshot({path:`${process.env.DFG_SCREENSHOTS}/${name}-${viewport.width}-intro.png`});
        assert.equal(await page.locator('.practice-board').count(),0);
        const rejected = await page.evaluate(async()=> {
          const data=await (await fetch('api.php?tutorial=1')).json();
          return (await fetch('api.php?tutorial=1', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({token:data.token,command:'action',matchId:data.state.matchId,revision:data.state.revision,action:{type:'commit',main:1}})})).json();
        });
        assert.equal(rejected.ok,false);
        assert.match(rejected.error,/Continue/);
        let steps=0;
        const total=await page.evaluate(async()=> (await (await fetch('api.php?tutorial=1')).json()).tutorial.totalSteps);
        assert.equal(total,25);
        while(steps<total) {
          const guide=await page.evaluate(async()=> (await (await fetch('api.php?tutorial=1')).json()).tutorial);
          assert.equal(guide.complete,false);
          const title=await page.locator('#stepTitle').textContent();
          await page.waitForFunction(() => {
            const el=document.getElementById('tutorialGuide'), r=el.getBoundingClientRect();
            return r.top>=0 && r.bottom<=innerHeight;
          }, null, {timeout:10000});
          assert.equal(await page.locator('#tutorialText').isVisible(),true,'Full explanation is visible');
          if(steps===5 && process.env.DFG_SCREENSHOTS) await page.screenshot({path:`${process.env.DFG_SCREENSHOTS}/${name}-${viewport.width}-card.png`});
          const action=guide.action;
          if(!action) await page.locator('#tutorialContinue').click();
          else if(action.type==='commit') {
            await page.locator(`#hand-${action.main}`).click();
            for(const id of action.bonuses||[]) await page.locator(`#hand-${id}`).click();
            if(action.advantages?.length) {
              await page.locator('#advantagePicker summary').click();
              for(const color of action.advantages) await page.locator(`[data-advantage="${color}"]`).click();
            }
            await page.locator('#commit').click();
          } else if(action.type==='react') await page.locator(`#hand-${action.card}`).click();
          else if(action.type==='pass') await page.locator('#pass').click();
          else if(action.type==='switch') await page.locator(`[data-switch="${action.color}"]`).click();
          await page.waitForFunction(old=>document.getElementById('stepTitle').textContent!==old,title);
          assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false,'Fits viewport');
          steps++;
          if(steps===1) {
            const resumedTitle=await page.evaluate(async()=> (await (await fetch('api.php?tutorial=1')).json()).tutorial.title);
            await page.reload();
            await page.locator('#tutorialGuide:visible').waitFor();
            await page.waitForFunction(title=>document.getElementById('stepTitle').textContent===title,resumedTitle);
          }
        }
        assert.equal(await page.locator('#stepTitle').textContent(),'You’re ready.');
        await page.locator('#tutorialRestart').click();
        await page.waitForFunction(()=>document.getElementById('stepTitle').textContent==='Keep an eye on your hands');
        await page.locator('#tutorialLessons').click();
        await page.locator('[data-lesson="0"]').click();
        await page.waitForFunction(()=>document.getElementById('stepTitle').textContent==='Tired of losing to a bad hand?');
        const after=await page.evaluate(async()=> (await (await fetch('api.php')).json()).state);
        assert.deepEqual(after,saved,'Tutorial preserves regular match exactly');
        assert.deepEqual(errors,[]);
        console.log(`${name} ${viewport.width}: 25 steps, introduction, visible explanations, normal controls, reload, replay, isolation passed`);
        await page.close();
      }
    } catch(error) { failures++; console.error(`${name}: ${error.stack}`); }
    finally { await browser?.close(); }
  }
} finally { server.kill(); }
if(failures) process.exitCode=1;
