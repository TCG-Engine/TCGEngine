import { chromium, firefox, webkit } from 'playwright';
import assert from 'node:assert/strict';
import { mkdir } from 'node:fs/promises';

const url = process.env.DFG_URL || 'http://localhost/TCGEngine/DFGSim/';
const output = process.env.DFG_SCREENSHOTS;
if (output) await mkdir(output, { recursive: true });
let completed = 0, failures = 0;
for (const [name, engine] of Object.entries({ chromium, firefox, webkit })) {
  let browser;
  try { browser = await engine.launch({ headless: true }); }
  catch (error) { console.log(`${name}: unavailable: ${error.message.split('\n')[0]}`); continue; }
  try {
    for (const viewport of [{width:1366,height:768}, {width:1024,height:600}, {width:390,height:844}, {width:320,height:700}]) {
      const page = await browser.newPage({viewport, reducedMotion:'reduce'});
      const errors = [];
      page.on('pageerror', error => errors.push(error.message));
      await page.goto(`${url}?play`);
      await page.locator('#setup[open]').waitFor();
      await page.locator('[name=seed]').fill('42');
      await page.locator('#setupForm [type=submit]').click();
      await page.locator('#setup[open]').waitFor({state:'detached'});
      for (const [winner, title] of [[0,'You win!'], [1,'Bot wins'], ['draw','Draw']]) {
        // Render each authoritative outcome without relying on a particular bot strategy.
        await page.evaluate(winner => { state.phase='finished'; state.winner=winner; render(); }, winner);
        assert.equal(await page.locator('#phaseTitle').innerText(), title);
        assert.equal(await page.locator('#phaseHint').isVisible(), false);
        const fits = await page.locator('#matchControls').evaluate(panel => {
          const bounds = panel.getBoundingClientRect();
          return panel.scrollHeight <= panel.clientHeight + 1 && panel.scrollWidth <= panel.clientWidth + 1 &&
            [...panel.querySelectorAll('button')].every(button => {
              const rect = button.getBoundingClientRect();
              return rect.top >= bounds.top && rect.bottom <= bounds.bottom && rect.left >= bounds.left && rect.right <= bounds.right;
            });
        });
        assert.ok(fits, `${name} ${viewport.width}: result and actions fit without scrolling`);
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false);
        await page.locator('#reviewFight').click();
        await page.locator('#fightLog[open]').waitFor();
        await page.keyboard.press('Escape');
        await page.waitForFunction(() => document.activeElement?.id === 'reviewFight');
        if (output && winner === 1 && viewport.width !== 320) await page.screenshot({path:`${output}/dfg-game-over-${name}-${viewport.width}.png`,fullPage:true});
        await page.locator('#playAgain').click();
        await page.locator('#setup[open]').waitFor();
        await page.locator('#cancelSetup').click();
      }
      await page.evaluate(() => {state.phase='planning'; state.winner=null; render();});
      assert.equal(await page.locator('#phaseTag').isVisible(), false);
      assert.equal(await page.locator('#commit').count(), 1);
      assert.deepEqual(errors, []);
      console.log(`${name} ${viewport.width}: victory, defeat, draw, log, replay and reset passed`);
      completed++;
      await page.close();
    }
  } catch (error) {failures++; console.error(`${name}: ${error.stack}`);}
  finally {await browser.close();}
}
process.exitCode = failures || !completed ? 1 : 0;
