// SWUSim card overlays — Sentinel opacity + overlay brightness, Chromium + Firefox + WebKit (2026-10-23).
//
// Player feedback: "the Sentinel overlay is too transparent" and "overlays in general are not bright enough". So:
//   · the Sentinel wall (tech-wall.webp) is drawn at opacity SENTINEL_OPACITY, on the board (schema Overlay rule) AND on the
//     Twin Suns opponent preview tiles (.swu-mb-ov-sentinel, their own copy);
//   · every IMAGE overlay (the Sentinel wall, the Hidden smoke) carries filter brightness(BRIGHTEN), on both surfaces;
//   · the EXHAUSTED dimming layer is NOT brightened (negative control — it exists to darken).
// Boards: SWUSim/Tests/Visual/Overlays_SentinelAndHidden_1v1.md (desktop + mobile layout) and …_TwinSuns.md (preview tiles),
// built through the Test Schema Editor's own endpoints (mod login, claudebot1), opened as P1 with authKey=testschema.
//
// Usage: node swusim-overlay-brightness-xbrowser.mjs [BASE] [SHOTS_DIR]   (exits non-zero on failure)
import { chromium, firefox, webkit } from 'playwright';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const SHOTS = process.argv[3] || '/tmp';
const here = dirname(fileURLToPath(import.meta.url));
const schema = (name) => readFileSync(resolve(here, `../../SWUSim/Tests/Visual/${name}`), 'utf8');
const SENTINEL_OPACITY = 0.88;
const BRIGHTEN = 'brightness(1.2)';

let allOk = true;
const ok = (name, cond, extra = '') => { if (!cond) allOk = false; console.log(`${cond ? '  ok  ' : '  FAIL'} ${name} ${extra}`); };

async function login(page) {
  await page.goto(BASE + 'SharedUI/LoginPage.php', { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="userID"]', 'claudebot1');
  await page.fill('input[name="password"]', 'pass');
  await Promise.all([page.waitForNavigation({ waitUntil: 'load' }).catch(() => {}), page.click('button[type="submit"]')]);
}
async function buildGame(page, file) {
  const setup = await (await page.request.post(BASE + 'SWUSim/TestSchemaSetup.php', { multipart: { schema: schema(file) } })).json();
  if (setup.error) throw new Error('setup: ' + setup.error);
  for (const step of setup.whenSteps) {
    const r = await (await page.request.post(BASE + 'SWUSim/TestSchemaStep.php', { multipart: { gameName: String(setup.gameName), step: step.raw } })).json();
    if (r.error) throw new Error(`step "${step.raw}": ${r.error}`);
  }
  return setup.gameName;
}

// The schema renderer's image layers: an inline-styled div whose background-image names the overlay art.
function measureBoard() {
  const zones = ['#myGroundArena', '#mySpaceArena', '#theirGroundArena', '#theirSpaceArena'];
  const layers = zones.flatMap(z => [...document.querySelectorAll(z + ' div')]).filter(d => /Overlays\//.test(d.style.backgroundImage || ''));
  const pick = (re) => layers.filter(d => re.test(d.style.backgroundImage)).map(d => {
    const cs = getComputedStyle(d); return { opacity: parseFloat(cs.opacity), filter: cs.filter };
  });
  const exhausted = [...document.querySelectorAll('.exhausted-status-overlay-layer')].map(d => getComputedStyle(d).filter);
  return { sentinel: pick(/tech-wall/), hidden: pick(/smoke-overlay/), exhausted };
}
function measurePreviews() {
  const m = (sel) => [...document.querySelectorAll(sel)].map(d => { const cs = getComputedStyle(d); return { opacity: parseFloat(cs.opacity), filter: cs.filter }; });
  return { sentinel: m('.swu-mb-ov-sentinel'), hidden: m('.swu-mb-ov-hidden') };
}
const allAre = (arr, f) => arr.length > 0 && arr.every(f);

for (const [engineName, engine] of Object.entries({ chromium, firefox, webkit })) {
  const browser = await engine.launch();
  try {
    for (const layout of ['desktop', 'mobile']) {
      const tag = `${engineName}/${layout}/1v1`;
      const ctx = await browser.newContext(layout === 'desktop' ? { viewport: { width: 1600, height: 900 } } : { viewport: { width: 390, height: 844 } });
      const page = await ctx.newPage();
      await login(page);
      const game = await buildGame(page, 'Overlays_SentinelAndHidden_1v1.md');
      await page.goto(BASE + `NextTurn.php?folderPath=SWUSim&gameName=${game}&playerID=1&authKey=testschema&viewerPerspective=1&opponentID=2`
        + (layout === 'mobile' ? '&swuLayout=mobile' : ''), { waitUntil: 'load' });
      // Our board (not a stale cached game on a reused number) and its overlays drawn — the first render can precede them.
      const ready = await page.waitForFunction(() => /played\s+Teebo/.test(document.body.innerText)
        && ['#myGroundArena', '#mySpaceArena', '#theirGroundArena'].flatMap(z => [...document.querySelectorAll(z + ' div')])
          .filter(d => /tech-wall/.test(d.style.backgroundImage || '')).length >= 4, null, { timeout: 15000 }).then(() => true).catch(() => false);
      const ours = await page.evaluate(() => /played\s+Teebo/.test(document.body.innerText));
      ok(`${tag}: board is ours (game ${game})`, ours);
      ok(`${tag}: overlays rendered within 15s`, ready);
      if (!ready) await page.screenshot({ path: `${SHOTS}/overlays-FAILED-${engineName}-${layout}-${game}.png` });
      const m = await page.evaluate(measureBoard);
      ok(`${tag}: 4 Sentinel walls drawn (both sides, both arenas)`, m.sentinel.length === 4, `(${m.sentinel.length})`);
      ok(`${tag}: Sentinel opacity ${SENTINEL_OPACITY}`, allAre(m.sentinel, s => Math.abs(s.opacity - SENTINEL_OPACITY) < 0.005), JSON.stringify(m.sentinel.map(s => s.opacity)));
      ok(`${tag}: Sentinel brightened`, allAre(m.sentinel, s => s.filter === BRIGHTEN), JSON.stringify([...new Set(m.sentinel.map(s => s.filter))]));
      ok(`${tag}: Hidden smoke drawn and brightened`, allAre(m.hidden, s => s.filter === BRIGHTEN), JSON.stringify(m.hidden));
      ok(`${tag}: exhausted dimming NOT brightened`, allAre(m.exhausted, f => f === 'none'), JSON.stringify(m.exhausted));
      await page.screenshot({ path: `${SHOTS}/overlays-${engineName}-${layout}.png` });
      await ctx.close();
    }
    const ctx = await browser.newContext({ viewport: { width: 1600, height: 900 } });
    const page = await ctx.newPage();
    await login(page);
    const game = await buildGame(page, 'Overlays_SentinelAndHidden_TwinSuns.md');
    await page.goto(BASE + `NextTurn.php?folderPath=SWUSim&gameName=${game}&playerID=1&authKey=testschema`, { waitUntil: 'load' });
    const tag = `${engineName}/desktop/twinsuns-previews`;
    const ready = await page.waitForFunction(() => document.querySelectorAll('.swu-mb-ov-sentinel').length >= 3, null, { timeout: 15000 }).then(() => true).catch(() => false);
    ok(`${tag}: preview tiles rendered`, ready);
    const pv = await page.evaluate(measurePreviews);
    ok(`${tag}: 3 Sentinel walls on the preview tiles`, pv.sentinel.length === 3, `(${pv.sentinel.length})`);
    ok(`${tag}: preview Sentinel opacity ${SENTINEL_OPACITY} (= the board's)`, allAre(pv.sentinel, s => Math.abs(s.opacity - SENTINEL_OPACITY) < 0.005), JSON.stringify(pv.sentinel.map(s => s.opacity)));
    ok(`${tag}: preview Sentinel brightened`, allAre(pv.sentinel, s => s.filter === BRIGHTEN), JSON.stringify([...new Set(pv.sentinel.map(s => s.filter))]));
    ok(`${tag}: preview Hidden smoke drawn and brightened`, allAre(pv.hidden, s => s.filter === BRIGHTEN), JSON.stringify(pv.hidden));
    await page.screenshot({ path: `${SHOTS}/overlays-${engineName}-twinsuns.png` });
    await ctx.close();
  } catch (e) {
    ok(`${engineName}: harness ran`, false, e.message);
  } finally {
    await browser.close();
  }
}
console.log(allOk ? 'PASS' : 'FAIL');
process.exit(allOk ? 0 : 1);
