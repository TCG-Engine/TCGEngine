import { chromium, firefox, webkit } from 'playwright';
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { readFile, mkdir } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import { join } from 'node:path';
import { tmpdir } from 'node:os';

const root = fileURLToPath(new URL('../../', import.meta.url));
const php = process.env.PHP_BINARY || 'C:/xampp/php/php.exe';
const fixture = JSON.parse(execFileSync(php, ['-r', `require 'DFGSim/Custom/GameLogic.php'; $s=DFGNewGame('Red','Purple',42); $s['matchId']='liquid-test'; echo json_encode(['ok'=>true,'token'=>'test','state'=>DFGObserve($s,0),'cards'=>array_values(array_map('DFGCardPublic',DFGCatalog())),'colors'=>DFG_STYLES]);`], { cwd: root, encoding: 'utf8' }));
const shots = process.env.DFG_SCREENSHOTS || join(tmpdir(), 'dfg-liquid-shots');
await mkdir(shots, { recursive: true });
let completed = 0, unavailable = 0;
for (const [name, engine] of Object.entries({ chromium, firefox, webkit })) {
  let browser;
  try { browser = await engine.launch({ headless: true }); }
  catch (error) { unavailable++; console.log(`${name}: unavailable: ${error.message.split('\n')[0]}`); continue; }
  try {
    for (const viewport of [{ width: 1366, height: 900 }, { width: 390, height: 844 }]) {
      const page = await browser.newPage({ viewport, reducedMotion: 'no-preference' });
      const errors = [];
      page.on('pageerror', error => errors.push(error.message));
      await page.route('**/*', async route => {
        const relative = new URL(route.request().url()).pathname.replace(/^\/TCGEngine\//, '');
        if (relative === 'DFGSim/api.php') return route.fulfill({ json: fixture });
        if (!relative.startsWith('DFGSim/') && !relative.startsWith('Core/')) return route.fulfill({ status: 404, body: '' });
        const source = join(root, relative.endsWith('/') ? relative + 'index.php' : relative);
        const body = source.endsWith('.php') ? execFileSync(php, [source]) : await readFile(source);
        const contentType = source.endsWith('.php') ? 'text/html' : source.endsWith('.js') ? 'text/javascript' : source.endsWith('.css') ? 'text/css' : 'image/png';
        return route.fulfill({ body, contentType });
      });
      await page.goto('http://localhost/TCGEngine/DFGSim/');
      await page.locator('#resumeMatch').click();
      assert.equal(await page.locator('#fighters .orb-liquid').count(), 12);
      assert.equal(await page.locator('.orb-liquid').first().evaluate(el => getComputedStyle(el).animationName), 'none', 'full hands have no liquid motion');
      assert.equal(await page.locator('.orb-slosh').first().evaluate(el => getComputedStyle(el).display), 'none', 'full hands show no exposed surface');
      assert.notEqual(await page.locator('.orb-fill').first().evaluate(el => getComputedStyle(el).backgroundColor), 'rgba(0, 0, 0, 0)', 'full hands fill the entire orb');
      await page.evaluate(() => {
        for (const player of state.players) {
          let remaining = 3;
          player.available = player.available.filter(id => card(id).color !== 'Red' || remaining-- > 0);
        }
        render();
      });
      assert.equal(await page.locator('.orb-liquid').first().evaluate(el => getComputedStyle(el).animationName), 'orb-liquid-idle');
      assert.equal(await page.locator('.orb-wave').first().evaluate(el => getComputedStyle(el).animationName), 'none', 'idle liquid rocks rather than sending waves across the orb');
      const idleTilt = await page.locator('.orb-liquid').first().evaluate(el => {
        return el.getAnimations()[0].effect.getKeyframes().map(frame => {
          const angle = frame.transform.match(/rotate\(([^d]+)/)[1];
          return Math.abs(parseFloat(angle));
        });
      });
      assert.ok(Math.max(...idleTilt) <= 1, 'idle surface only wavers by a fraction of a degree');
      const hit = amount => page.evaluate(amount => {
        const previous = structuredClone(state), next = structuredClone(state);
        next.players[0].life -= amount;
        animateMeterChanges(previous, next);
        const animation = document.querySelector('#yourOrbs .orb-slosh').getAnimations()[0];
        return {
          duration: animation.effect.getTiming().duration,
          peak: Math.max(...animation.effect.getKeyframes().map(frame => Math.abs(parseFloat(frame.transform.match(/rotate\(([^d]+)/)[1])))),
          own: [...document.querySelectorAll('#yourOrbs .orb-slosh')].filter(el => el.getAnimations().length).length,
          opponent: [...document.querySelectorAll('#opponentOrbs .orb-slosh')].filter(el => el.getAnimations().length).length,
        };
      }, amount);
      const small = await hit(1), large = await hit(12);
      assert.equal(large.own, 1, 'only partially drained hands animate after damage');
      assert.equal(large.opponent, 0, 'only the damaged fighter sloshes');
      assert.ok(large.peak > small.peak * 3, 'larger damage gives a stronger impulse');
      assert.ok(large.duration > small.duration, 'larger damage settles longer');
      await page.evaluate(() => {
        // An inventory change, browse, or bot update must not cut the impulse short.
        state.players[0].available = state.players[0].available.filter(id => card(id).color !== 'Red' || id === state.players[0].available[0]);
        render();
      });
      assert.ok(await page.locator('#yourOrbs .orb-slosh').first().evaluate(el => el.getAnimations().length > 0));
      await page.locator('#your-orb-Blue').click();
      assert.ok(await page.locator('#yourOrbs .orb-slosh').first().evaluate(el => el.getAnimations().length > 0), 'browsing preserves the settling motion');
      await page.screenshot({ path: join(shots, `${name}-${viewport.width}.png`), fullPage: true });
      await page.waitForFunction(() => [...document.querySelectorAll('#yourOrbs .orb-slosh')].every(el => !el.getAnimations().length));
      await page.evaluate(() => animateMeterChanges(state, state));
      assert.equal(await page.locator('#yourOrbs .orb-slosh').first().evaluate(el => el.getAnimations().length), 0, 'unchanged state does not replay damage');
      await hit(6);
      await page.emulateMedia({ reducedMotion: 'reduce' });
      await page.waitForFunction(() => !document.querySelector('#yourOrbs .orb-slosh').getAnimations().length);
      assert.equal(await page.locator('.orb-liquid').first().evaluate(el => getComputedStyle(el).animationName), 'none');
      assert.equal(await page.locator('.orb-wave').first().evaluate(el => getComputedStyle(el).animationName), 'none');
      await page.evaluate(() => {
        const next = structuredClone(state); next.players[1].life -= 5;
        animateMeterChanges(state, next);
        document.getElementById('controls').innerHTML = [0, 1, 3, 6, 7].map(n => `<button class="hand-orb blue">${orbMeter(n)}</button>`).join('');
      });
      assert.equal(await page.locator('#opponentOrbs .orb-slosh').first().evaluate(el => el.getAnimations().length), 0);
      assert.equal(await page.locator('#controls .orb-fill').first().evaluate(el => getComputedStyle(el).visibility), 'hidden', 'empty hands contain no liquid');
      assert.deepEqual(await page.locator('#controls .orb-count').allTextContents(), ['0', '1', '3', '6', '7']);
      assert.equal(await page.locator('#controls .orb-extra').textContent(), '+1');
      assert.equal(await page.locator('#controls .orb-fill[data-full=true]').count(), 2, 'six and seven cards both appear full');
      assert.deepEqual(await page.locator('#controls .orb-fill[data-full=true]').evaluateAll(fills => fills.map(el => ({height: el.style.height, surface: getComputedStyle(el.querySelector('.orb-slosh')).display}))), [{height:'100%',surface:'none'},{height:'100%',surface:'none'}]);
      assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false);
      assert.deepEqual(errors, []);
      await page.close();
      completed++;
      console.log(`${name} ${viewport.width}: passed`);
    }
  } finally { await browser.close(); }
}
assert.ok(completed > 0, 'at least one browser must run');
console.log(`Liquid orbs: ${completed} browser/viewport checks passed; ${unavailable} browser engines unavailable. Screenshots: ${shots}`);
