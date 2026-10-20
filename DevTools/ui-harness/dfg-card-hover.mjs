import {chromium, firefox, webkit} from 'playwright';
import assert from 'node:assert/strict';
import {spawn} from 'node:child_process';
import {fileURLToPath} from 'node:url';
const port = Number(process.env.DFG_TEST_PORT || 18780);
const root = fileURLToPath(new URL('../../', import.meta.url));
const base = `http://127.0.0.1:${port}/DFGSim/`;
const server = spawn(process.env.PHP_BINARY || 'C:/xampp/php/php.exe', ['-S', `127.0.0.1:${port}`, '-t', root], {stdio:'ignore'});
let failures = 0;
try {
  for(let i=0;i<50;i++) {
    try { if((await fetch(base)).ok) break; } catch {}
    await new Promise(resolve=>setTimeout(resolve,100));
  }
  for(const [name,engine] of Object.entries({chromium,firefox,webkit})) {
    if(process.env.DFG_BROWSERS && !process.env.DFG_BROWSERS.split(',').includes(name)) continue;
    let browser;
    try {
      browser=await engine.launch({headless:true});
      for(const viewport of [{width:1920,height:1000},{width:1366,height:768},{width:390,height:844}]) {
        const page=await browser.newPage({viewport,reducedMotion:'reduce',hasTouch:viewport.width<700});
        const errors=[];
        page.on('pageerror',error=>errors.push(error.message));
        await page.goto(base);
        await page.locator('#homePlay:enabled').click();
        await page.locator('#setupForm [type=submit]').click();
        await page.locator('#hand-1').waitFor();
        if(viewport.width>700) {
          await page.locator('#hand-1').hover();
          await page.locator('#cardPreview:visible').waitFor();
          assert.equal(await page.locator('#previewContent .main-ability').innerText(),await page.evaluate(()=>card(1).ability));
          assert.equal(await page.locator('#previewContent .technique-art, #previewContent .card-heading, #previewContent .card-top').count(),0,'Hover adds rules without repeating art, name or stats');
          await page.evaluate(()=>{window.originalHoverCard=document.querySelector('#previewContent .technique-card');});
          await page.locator('#hand-1 .tile-name').hover();
          await page.locator('#hand-1 .tile-stats').hover();
          assert.equal(await page.evaluate(()=>window.originalHoverCard===document.querySelector('#previewContent .technique-card')),true,'Moving inside a tile preserves preview DOM');
          // All six cards switch on pointer entry, without a dwell timer.
          for(let id=1;id<=6;id++) {
            await page.locator(`#hand-${id}`).hover();
            assert.equal(await page.evaluate(()=>previewId),id);
            if(process.env.DFG_SCREENSHOTS && id===3) await page.screenshot({path:`${process.env.DFG_SCREENSHOTS}/${name}-${viewport.width}-hover.png`});
            assert.equal(await page.locator('#cardPreview').evaluate(el=>getComputedStyle(el).pointerEvents),'none','Hover preview cannot intercept card input');
            const bounds=await page.locator('#cardPreview').boundingBox();
            assert.ok(bounds.x>=0 && bounds.y>=0 && bounds.x+bounds.width<=viewport.width && bounds.y+bounds.height<=viewport.height,'Preview stays in viewport');
            if(viewport.width>700) {
              const tile=await page.locator(`#hand-${id}`).boundingBox();
              assert.ok(bounds.y+bounds.height<=tile.y,'Hand preview appears above row');
            }
          }
          await page.mouse.move(10,10);
          await page.locator('#cardPreview').waitFor({state:'hidden'});
          await page.locator('#hand-2').focus();
          await page.locator('#cardPreview:visible').waitFor();
          assert.equal(await page.locator('#previewContent .main-ability').innerText(),await page.evaluate(()=>card(2).ability));
          await page.keyboard.press('Escape');
          await page.locator('#cardPreview').waitFor({state:'hidden'});
          // Playing a card must still select it instead of pinning the hover.
          await page.locator('#hand-2').click();
          assert.equal(await page.locator('#hand-2').getAttribute('aria-pressed'),'true');
        } else {
          await page.evaluate(()=>closePreview());
          await page.locator('#hand-1').dispatchEvent('pointerover',{pointerType:'touch'});
          assert.equal(await page.locator('#cardPreview').isVisible(),false,'Touch has no synthetic hover');
        }
        // Clicking an inactive card can focus its preview but never pins it.
        await page.locator('[data-browse="Blue"]').click();
        await page.locator('#hand-25').click();
        await page.locator('#cardPreview:visible').waitFor();
        assert.equal(await page.locator('#cardPreview').evaluate(el=>getComputedStyle(el).pointerEvents),'none');
        assert.equal(await page.locator('#cardPreview.pinned, .preview-help, #closePreview').count(),0);
        await page.locator('#hand-26').focus();
        assert.equal(await page.evaluate(()=>previewId),26,'Focus can replace a clicked preview');
        await page.locator('[data-browse="Blue"]').focus();
        await page.locator('#cardPreview').waitFor({state:'hidden'});
        // Opponent inventory uses the same transient hover/focus behavior.
        await page.locator('[data-opponent="Purple"]').click();
        const opponent=page.locator('[data-opponent-card="31"]');
        if(viewport.width>700) {
          await opponent.hover();
          await page.locator('#cardDetail').waitFor({state:'visible'});
          await page.evaluate(()=>{window.originalOpponentCard=document.querySelector('#cardDetail .technique-card');});
          await opponent.locator('.tile-name').hover();
          await opponent.locator('.tile-stats').hover();
          assert.equal(await page.evaluate(()=>window.originalOpponentCard===document.querySelector('#cardDetail .technique-card')),true,'Opponent preview survives child crossings');
        }
        await opponent.click();
        await page.locator('#cardDetail').waitFor({state:'visible'});
        assert.equal(await page.locator('#cardDetail .technique-art, #cardDetail .card-heading, #cardDetail .card-top').count(),0,'Opponent expansion is also text only');
        assert.equal(await page.locator('[data-close-shared]').count(),0);
        assert.equal(await page.evaluate(()=>cardDetailPinned),false);
        await page.locator('#opponentHandPopup').locator('button').first().focus();
        await page.mouse.move(10,10);
        await page.locator('#cardDetail').waitFor({state:'hidden'});
        assert.deepEqual(errors,[]);
        console.log(`${name} ${viewport.width}: stable hover DOM, immediate switching, placement, focus, selection, touch and unpinned inspection passed`);
        await page.close();
      }
    } catch(error) {failures++;console.error(`${name}: ${error.stack}`);}
    finally {await browser?.close();}
  }
} finally {server.kill();}
if(failures)process.exitCode=1;
