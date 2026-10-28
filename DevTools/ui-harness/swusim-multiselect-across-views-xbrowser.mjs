// An inline multi-select must keep its picks while the player pages between Twin Suns matchup views, in Chromium,
// Firefox and WebKit, on the phone layout and on desktop.
// Visual spec: SWUSim/Tests/Visual/TwinSuns_MultiSelectAcrossMatchupViews.md
// Reported 2026-10-27 (mobile, LAW_002 Tobias Beckett's deploy, "any time I need to target multiple units"): "when I change
// pages to look at other opponents it deselects what I selected". A board pick was stored as the RENDERED id
// (`theirGroundArena-0`), which names a different seat on every matchup view: paging dropped it (that seat had no unit
// there) or moved it onto the next opponent's unit, and Confirm then submitted that unit.
//
// Flow (JTL_140 IG-2000, "deal 1 damage to each of up to 3 units", one unit per opponent): zoom into P1 vs P2, pick P2's
// unit on the board, page to P3 (the pick survives), pick P3's, page to P4 (P4's unit is NOT pre-picked), pick it, page
// back to P2 (still picked), Confirm → exactly P2/P3/P4's units take 1 damage.
// Usage: node swusim-multiselect-across-views-xbrowser.mjs [baseURL]   ENGINES=chromium,firefox,webkit  LAYOUTS=mobile,desktop
import { chromium, firefox, webkit, devices } from 'playwright';
import fs from 'node:fs';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const ALL = { chromium, firefox, webkit };
const ENGINES = Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n));
const LAYOUTS = (process.env.LAYOUTS || 'mobile,desktop').split(',');
const SCHEMA = fs.readFileSync(new URL('../../SWUSim/Tests/Visual/TwinSuns_MultiSelectAcrossMatchupViews.md', import.meta.url), 'utf8');
const SHOTS = process.env.SHOTS || '';

let allOk = true;
const results = [];
const ok = (label, name, cond, extra = '') => { if (!cond) allOk = false; results.push([label, name, !!cond, extra]); };
const report = () => { for (const [e, n, pass, extra] of results) console.log(`${pass ? 'PASS' : 'FAIL'}  ${e.padEnd(18)} ${n}${extra ? '  — ' + extra : ''}`); };
setTimeout(() => { console.log('WATCHDOG: timed out after 900s'); report(); process.exit(9); }, 900000).unref();

async function makeGame() {
  const res = await fetch(BASE + 'SWUSim/TestSchemaSetup.php', { method: 'POST', body: new URLSearchParams({ schema: SCHEMA }) });
  const j = await res.json();
  if (!j.gameName) throw new Error('TestSchemaSetup failed: ' + JSON.stringify(j));
  return String(j.gameName);
}
const url = (gn, layout) => `${BASE}NextTurn.php?folderPath=SWUSim&gameName=${gn}&playerID=1&authKey=testschema&swuLayout=${layout}`;
function contextOptions(engine, layout) {
  if (layout === 'desktop') return { viewport: { width: 1700, height: 1050 } };
  const phone = devices['iPhone 13'];
  if (engine === 'firefox') return { viewport: phone.viewport, userAgent: phone.userAgent };   // no isMobile in Firefox
  return { ...phone };
}
async function pressEl(page, engine, layout, sel) {
  const el = page.locator(sel).first();
  await el.scrollIntoViewIfNeeded().catch(() => {});
  const box = await el.boundingBox();
  if (!box) return false;
  const x = box.x + box.width / 2, y = box.y + box.height / 2;
  if (layout === 'mobile' && engine !== 'firefox') await page.touchscreen.tap(x, y); else await page.mouse.click(x, y);
  return true;
}
const counterNow = (page) => page.evaluate(() => { const m = /(\d+) selected \/ (\d+) max/.exec(document.body.innerText); return m ? Number(m[1]) : -1; });
// Polled toward the expected value for the same ~4 Hz reason; returns what it last read, so a wrong count still fails.
const counter = async (page, want) => { for (let i = 0; i < 10 && want !== undefined; i++) { const v = await counterNow(page); if (v === want) return v; await page.waitForTimeout(200); } return counterNow(page); };
const pickedNow = (page, sel) => page.evaluate((s) => { const el = document.querySelector(s); return el ? el.classList.contains('selected-inline') : null; }, sel);
// Polled, not read once: headless Firefox paints at ~4 Hz, so a class set on tap can land a frame later.
// A NEGATIVE check (not picked) is read after the same settle, so it cannot pass merely by being early.
const isPicked = async (page, sel) => { for (let i = 0; i < 10; i++) { const v = await pickedNow(page, sel); if (v === true) return true; await page.waitForTimeout(200); } return pickedNow(page, sel); };
// Go to P1 vs <seat>: through the real ▶ arrow when it is drawn, else swuSetView (what the arrow calls).
async function goToOpp(page, engine, layout, seat) {
  const idx = await page.evaluate((s) => (window.swuViews || []).findIndex((v) => v.mode === 'matchup' && Number(v.oppSeat) === s), seat);
  for (let i = 0; i < 6; i++) {
    const cur = await page.evaluate(() => (window.swuView && window.swuView.index));
    if (cur === idx) break;
    const arrowShown = await page.evaluate(() => { const b = document.getElementById('swuPairNext'); return !!(b && b.getClientRects().length && getComputedStyle(b).visibility !== 'hidden'); });
    const onMatchup = await page.evaluate(() => !!(window.swuView && window.swuView.mode === 'matchup'));
    if (arrowShown && onMatchup) await pressEl(page, engine, layout, '#swuPairNext');
    else await page.evaluate((j) => window.swuSetView(j), idx);
    await page.waitForTimeout(600);
  }
  await page.waitForTimeout(400);
  return page.evaluate(() => (window.swuView && Number(window.swuView.oppSeat)));
}
function damageLog(gn) {
  const f = new URL(`../../SWUSim/Games/${gn}/Gamestate.txt`, import.meta.url);
  try { return fs.readFileSync(f, 'utf8'); } catch (e) { return ''; }
}

for (const [engine, launcher] of ENGINES) {
  for (const layout of LAYOUTS) {
    const label = `${engine}/${layout}`;
    let browser;
    try {
      browser = await launcher.launch();
      const ctx = await browser.newContext(contextOptions(engine, layout));
      const gn = await makeGame();
      await ctx.addCookies([{ name: 'lastAuthKey', value: 'testschema', url: BASE }]);
      const page = await ctx.newPage();
      await page.goto(url(gn, layout), { waitUntil: 'domcontentloaded' });
      await page.waitForFunction(() => (window.swuViews || []).length >= 3 && typeof window.SubmitInput === 'function', null, { timeout: 60000 }).catch((e) => { throw new Error('page never became ready: ' + JSON.stringify(e.message).slice(0, 60)); });
      await page.waitForTimeout(1500);
      await page.evaluate(() => SubmitInput(10002, '&cardID=myHand-0!FSM!'));
      await page.waitForFunction(() => !!document.getElementById('inline-multi-confirm'), null, { timeout: 30000 }).catch(async () => { throw new Error('the IG-2000 prompt never appeared; hand=' + await page.evaluate(() => String(window.myHandData || '').slice(0, 40)) + ' views=' + await page.evaluate(() => (window.swuViews || []).length)); });
      ok(label, 'IG-2000 raises the inline "up to 3" pick', (await counter(page, 0)) === 0);

      const P2 = '[data-mzid="theirGroundArena-0"]', P3 = '[data-mzid="theirSpaceArena-0"]', P4 = '[data-mzid="theirGroundArena-0"]';
      ok(label, 'zoomed into P1 vs P2', (await goToOpp(page, engine, layout, 2)) === 2);
      await pressEl(page, engine, layout, P2);
      await page.waitForTimeout(400);
      ok(label, "picked P2's unit on the board", (await counter(page, 1)) === 1 && (await isPicked(page, P2)) === true, `counter ${await counter(page)}`);

      ok(label, 'paged to P1 vs P3', (await goToOpp(page, engine, layout, 3)) === 3);
      ok(label, "after paging to P3 the P2 pick is still counted", (await counter(page, 1)) === 1, `counter ${await counter(page)}`);
      await pressEl(page, engine, layout, P3);
      await page.waitForTimeout(400);
      ok(label, "picked P3's unit", (await counter(page, 2)) === 2, `counter ${await counter(page)}`);

      ok(label, 'paged to P1 vs P4', (await goToOpp(page, engine, layout, 4)) === 4);
      ok(label, "P4's unit is NOT shown as picked (P2's pick did not move onto it)", (await isPicked(page, P4)) === false);
      ok(label, 'after paging to P4 both picks are still counted', (await counter(page, 2)) === 2, `counter ${await counter(page)}`);
      await pressEl(page, engine, layout, P4);
      await page.waitForTimeout(400);
      ok(label, "picked P4's unit", (await counter(page, 3)) === 3, `counter ${await counter(page)}`);

      ok(label, 'paged back to P1 vs P2', (await goToOpp(page, engine, layout, 2)) === 2);
      ok(label, "P2's unit is still shown as picked", (await isPicked(page, P2)) === true && (await counter(page, 3)) === 3, `counter ${await counter(page)}`);
      if (SHOTS) await page.screenshot({ path: `${SHOTS}/multiview-${engine}-${layout}.png` });

      await pressEl(page, engine, layout, '#inline-multi-confirm');
      await page.waitForFunction(() => !document.getElementById('inline-multi-confirm'), null, { timeout: 20000 }).catch(() => {});
      await page.waitForTimeout(1500);
      const gs = damageLog(gn);
      const hit = (who, card) => new RegExp(`dealt 1 damage to ${who}'s \\[\\[${card}\\|`).test(gs);
      ok(label, 'Confirm damages exactly P2, P3 and P4 (not my own unit)',
        hit('P2', 'SOR_046') && hit('P3', 'SOR_050') && hit('P4', 'SOR_108') && !hit('P1', 'SOR_095'),
        JSON.stringify({ p2: hit('P2', 'SOR_046'), p3: hit('P3', 'SOR_050'), p4: hit('P4', 'SOR_108'), p1: hit('P1', 'SOR_095') }));
    } catch (e) {
      ok(label, 'ran without throwing', false, String(e.message || e).slice(0, 200));
    } finally {
      if (browser) await browser.close();
    }
  }
}
report();
console.log(allOk ? 'PASS: multiselect-across-views' : 'FAIL: multiselect-across-views');
process.exit(allOk ? 0 : 1);
