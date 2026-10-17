import { chromium, firefox, webkit } from 'playwright';
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { mkdir, readFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const fixtures = JSON.parse(execFileSync(process.env.PHP_BINARY || 'C:/xampp/php/php.exe', [fileURLToPath(new URL('../DFGSim/relocation-tests.php', import.meta.url)), '--fixtures'], {encoding:'utf8'}));
const guardFixtures = JSON.parse(execFileSync(process.env.PHP_BINARY || 'C:/xampp/php/php.exe', [fileURLToPath(new URL('../DFGSim/iron-guard-tests.php', import.meta.url)), '--fixtures'], {encoding:'utf8'}));
const url = process.env.DFG_URL || 'http://localhost/TCGEngine/DFGSim/';
const shots = process.env.DFG_SCREENSHOTS || join(tmpdir(), 'dfg-relocation-shots');
await mkdir(shots, {recursive:true});
let failures = 0;
for (const [name, engine] of Object.entries({chromium, firefox, webkit})) {
  if (process.env.DFG_BROWSERS && !process.env.DFG_BROWSERS.split(',').includes(name)) continue;
  let browser;
  try {
    browser = await engine.launch({headless:true});
    for (const viewport of [{width:1440,height:1000}, {width:390,height:844}]) {
      const page = await browser.newPage({viewport,reducedMotion:'reduce'});
      const errors = []; const actions = [];
      page.on('pageerror', e=>errors.push(e.message));
      // Serve the workspace directly so the checks need no Apache or network access.
      await page.route('**/*', async route=>{
        const pathname = new URL(route.request().url()).pathname;
        const relative = pathname.replace(/^\/TCGEngine\//, '');
        if (!relative.startsWith('DFGSim/') && !relative.startsWith('Core/')) return route.fulfill({status:404,body:''});
        const source = new URL(`../../${relative.endsWith('/')?relative+'index.php':relative}`, import.meta.url);
        try {
          const body = source.pathname.endsWith('.php') ? execFileSync(process.env.PHP_BINARY || 'C:/xampp/php/php.exe', [fileURLToPath(source)]) : await readFile(source);
          const contentType = source.pathname.endsWith('.php') ? 'text/html' : source.pathname.endsWith('.js') ? 'text/javascript' : source.pathname.endsWith('.css') ? 'text/css' : 'image/png';
          return route.fulfill({body,contentType});
        } catch { return route.fulfill({status:404,body:''}); }
      });
      await page.route('**/DFGSim/api.php', async route=>{
        const action = route.request().postDataJSON()?.action;
        if (action) actions.push(action);
        const payload = !action ? fixtures.choice : action.type === 'relocate' ? fixtures.moved : fixtures.humanDefeat;
        await route.fulfill({json:payload});
      });
      await page.goto(url);
      await page.locator('#resumeMatch').click();
      await page.locator('[data-relocate="Blue"]').waitFor();
      assert.equal(await page.locator('select[name="difficulty"]').inputValue(), 'tactical', 'default bot stays Tactical');
      assert.equal(await page.locator('select[name="difficulty"] option[value="pro"]').count(), 1, 'Pro is selectable');
      assert.equal(await page.locator('[data-relocate]').count(), 6, 'five alternate hands and keep');
      await page.locator('[data-relocate="Blue"]').click();
      await page.locator('#orb-Blue .orb-extra').waitFor();
      assert.deepEqual(actions[0], {type:'relocate',color:'Blue'});
      assert.equal(await page.locator('#orb-Blue .orb-count').textContent(), '7');
      assert.equal(await page.locator('#orb-Blue .orb-extra').textContent(), '+1');
      assert.equal(await page.locator('#orb-Blue .orb-fill').evaluate(el=>el.style.height), '100%');
      await page.locator('#orb-Blue').click();
      assert.equal(await page.locator('#opponentHandPopup [data-opponent-card="1"]').count(), 1, 'moved main appears in destination popup');
      assert.equal(await page.locator('#opponentHandPopup .remaining-cards button').count(), 7);
      await page.keyboard.press('Escape');
      await page.locator('#orb-Red').click();
      assert.equal(await page.locator('#opponentHandPopup [data-opponent-card="1"]').count(), 0, 'moved main leaves original popup');
      await page.keyboard.press('Escape');
      await page.evaluate(payload=>accept(payload), fixtures.ownMoved);
      assert.equal(await page.locator('#your-orb-Yellow .orb-count').textContent(), '7');
      assert.equal(await page.locator('#hand-1').count(), 1, 'own hand grid uses relocated inventory');
      await page.locator('#hand-1').click();
      assert.equal(await page.locator('#commit').isEnabled(), true, 'moved card can be staged as main');
      await page.locator('#clear').click();
      await page.screenshot({path:join(shots,`dfg-relocation-${name}-${viewport.width}.png`),fullPage:true});
      assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth), false, 'no horizontal overflow');
      await page.evaluate(payload=>accept(payload), fixtures.emptyHuman);
      assert.match(await page.locator('[data-switch="Blue"]').getAttribute('aria-label'), /EMPTY HAND/);
      await page.locator('[data-switch="Blue"]').click();
      await page.locator('#phaseTitle').filter({hasText:'Bot wins.'}).waitFor();
      assert.deepEqual(actions.at(-1), {type:'switch',color:'Blue'});
      await page.evaluate(payload=>accept(payload), guardFixtures.earned);
      assert.match(await page.locator('.fighter:first-child .fighter-statuses p').filter({hasText:'Pressure:'}).textContent(), /\+1 attack next round/);
      await page.evaluate(payload=>accept(payload), guardFixtures.planning);
      assert.match(await page.locator('.fighter:first-child .fighter-statuses p').filter({hasText:'Pressure:'}).textContent(), /\+1 attack this round/);
      await page.evaluate(payload=>accept(payload), guardFixtures.carried);
      assert.equal(await page.locator('#played-main-0 .tile-stats>span').nth(0).textContent(), 'ATK6');
      await page.evaluate(payload=>accept(payload), guardFixtures.expired);
      assert.equal(await page.locator('.fighter:first-child .fighter-statuses p').filter({hasText:'Pressure:'}).count(), 0, 'expired pressure status disappears');
      assert.deepEqual(errors, []);
      console.log(`PASS ${name} ${viewport.width}: relocation, inventories, overfill, empty-hand loss, Iron Guard pressure`);
      await page.close();
    }
  } catch (error) {
    ++failures;
    console.error(`FAIL ${name}: ${error.stack}`);
  } finally { await browser?.close(); }
}
process.exitCode = failures ? 1 : 0;
