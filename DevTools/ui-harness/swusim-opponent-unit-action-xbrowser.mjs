// An OPPONENT's any-player unit Action (TS26_15 C-3P0: "Only opponents may use this ability") must be usable from every
// surface it is drawn on, in Chromium, Firefox and WebKit.
// Visual specs: SWUSim/Tests/Visual/TwinSuns_OpponentUnitAction_C3P0.md (4 seats) · OpponentUnitAction_C3P0_TwoSeats.md
// Discord 2026-10-22: "the TS26 C-3PO unit is not letting them use its ability" — the offer list had him, but the click was
// wired to MY arenas only, the glow looked for a p{n} data-mzid the board never draws, the Home tiles had no path, and
// CustomInput dropped the id anyway (schema: Tests/Cases/ts26/C3p0_DieJediDogs.md LiveInput_*).
//
// Scenarios (each on a fresh game — the Ability exhausts him):
//   ts-home     desktop Home view: seat 3's TILE glows → Ability-only menu → Ability → the damage target prompt opens
//   ts-zoom     desktop, zoomed in on seat 3: the board unit glows → same
//   ts-zoom-m   phone layout, zoomed in on seat 3 (phone rows draw no unit thumbnails, so Zoom is the phone's path)
//   2p / 2p-m   two seats, desktop and phone: P2's C-3PO on "their" board
// Usage: node swusim-opponent-unit-action-xbrowser.mjs [baseURL]   ENGINES=chromium,firefox,webkit (default all)
import { chromium, firefox, webkit, devices } from 'playwright';
import fs from 'node:fs';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const ALL = { chromium, firefox, webkit };
const ENGINES = Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n));
const schema = (f) => fs.readFileSync(new URL('../../SWUSim/Tests/Visual/' + f, import.meta.url), 'utf8');
const TS = schema('TwinSuns_OpponentUnitAction_C3P0.md');
const TWO = schema('OpponentUnitAction_C3P0_TwoSeats.md');

let allOk = true;
const results = [];
const ok = (label, name, cond, extra = '') => { if (!cond) allOk = false; results.push([label, name, !!cond, extra]); };
const report = () => { for (const [e, n, pass, extra] of results) console.log(`${pass ? 'PASS' : 'FAIL'}  ${e.padEnd(22)} ${n}${extra ? '  — ' + extra : ''}`); };
setTimeout(() => { console.log('WATCHDOG: timed out after 900s'); report(); process.exit(9); }, 900000).unref();

async function makeGame(text) {
  const res = await fetch(BASE + 'SWUSim/TestSchemaSetup.php', { method: 'POST', body: new URLSearchParams({ schema: text }) });
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
async function press(page, engine, layout, x, y) {
  if (layout === 'mobile' && engine !== 'firefox') await page.touchscreen.tap(x, y);
  else await page.mouse.click(x, y);
}

// The menu, its buttons, and whether each button is the topmost element at its centre (i.e. really clickable).
const menuState = (page) => page.evaluate(() => {
  const menu = document.getElementById('swuUnitActionMenu');
  if (!menu) return null;
  return [...menu.querySelectorAll('button')].map((b) => {
    const r = b.getBoundingClientRect();
    const top = document.elementFromPoint(r.x + r.width / 2, r.y + r.height / 2);
    return { text: b.textContent.trim(), rect: [r.x, r.y, r.width, r.height], topmost: !!top && (top === b || b.contains(top)) };
  });
});
// The glowing element: its box, its cyan glow, and whether a click at its centre lands on it.
const glowState = (page, sel) => page.evaluate((s) => {
  const el = document.querySelector(s);
  if (!el) return null;
  const r = el.getBoundingClientRect();
  const top = document.elementFromPoint(r.x + r.width / 2, r.y + r.height / 2);
  return { rect: [r.x, r.y, r.width, r.height], shadow: getComputedStyle(el).boxShadow,
           topmost: !!top && (top === el || el.contains(top)), actMz: el.getAttribute('data-swu-act-mz') };
}, sel);

const SCENARIOS = [
  { id: 'ts-home',   schema: TS,  layout: 'desktop', zoomSeat: 0, sel: '#swuHomeStrips .swu-mb-card[data-mz="p3GroundArena-0"]', actMz: 'p3GroundArena-0', prompt: true },
  { id: 'ts-zoom',   schema: TS,  layout: 'desktop', zoomSeat: 3, sel: '[data-mzid="theirGroundArena-0"]', actMz: 'p3GroundArena-0', prompt: true },
  { id: 'ts-zoom-m', schema: TS,  layout: 'mobile',  zoomSeat: 3, sel: '[data-mzid="theirGroundArena-0"]', actMz: 'p3GroundArena-0', prompt: true },
  { id: '2p',        schema: TWO, layout: 'desktop', zoomSeat: 0, sel: '[data-mzid="theirGroundArena-0"]', actMz: 'theirGroundArena-0' },
  { id: '2p-m',      schema: TWO, layout: 'mobile',  zoomSeat: 0, sel: '[data-mzid="theirGroundArena-0"]', actMz: 'theirGroundArena-0' },
];

for (const [engine, launcher] of ENGINES) {
  for (const sc of SCENARIOS) {
    const label = `${engine}/${sc.id}`;
    let browser;
    try {
      browser = await launcher.launch();
      const gn = await makeGame(sc.schema);
      const page = await (await browser.newContext(contextOptions(engine, sc.layout))).newPage();
      const errors = [];
      page.on('pageerror', (e) => errors.push(String(e)));
      const sent = [];
      page.on('request', (r) => { if (r.url().includes('ProcessInput.php')) sent.push(decodeURIComponent(r.url())); });
      await page.goto(url(gn, sc.layout), { waitUntil: 'domcontentloaded' });
      if (sc.zoomSeat) {
        await page.waitForFunction(() => (window.swuViews || []).length > 1, null, { timeout: 60000 });
        await page.evaluate((seat) => {
          const vs = window.swuViews; for (let i = 0; i < vs.length; i++) if (vs[i].mode === 'matchup' && vs[i].oppSeat === seat) return window.swuSetView(i);
        }, sc.zoomSeat);
      }
      const glowSel = sc.sel + '.unit-action';
      await page.waitForSelector(glowSel, { timeout: 60000 });
      await page.waitForTimeout(2500);   // let the board settle (click handlers are wired after the first glow pass)
      const g = await glowState(page, glowSel);
      ok(label, 'C-3PO glows as "you may use this Action"', !!g && /95, 208, 255/.test(g.shadow), g ? g.shadow.slice(0, 60) : 'no element');
      ok(label, 'the glowing element carries the SERVER id', !!g && g.actMz === sc.actMz, g ? String(g.actMz) : '');
      ok(label, 'a click at its centre lands on it', !!g && g.topmost, g ? JSON.stringify(g.rect) : '');
      if (!g) continue;
      const [x, y, w, h] = g.rect;
      await press(page, engine, sc.layout, x + w / 2, y + h / 2);
      await page.waitForTimeout(800);
      const m = await menuState(page);
      ok(label, 'the click opens the unit-action menu', m !== null);
      if (!m) continue;
      ok(label, 'the menu offers ONLY Ability (not mine to attack with)', m.map((b) => b.text).join('|') === 'Ability', m.map((b) => b.text).join('|'));
      const ab = m.find((b) => b.text === 'Ability');
      ok(label, 'the Ability button is not covered', !!ab && ab.topmost, ab ? JSON.stringify(ab.rect) : '');
      await page.screenshot({ path: `/tmp/opponent-unit-action-${engine}-${sc.id}.png` });
      if (!ab) continue;
      await press(page, engine, sc.layout, ab.rect[0] + ab.rect[2] / 2, ab.rect[1] + ab.rect[3] / 2);
      await page.waitForTimeout(1500);
      ok(label, 'Ability sends "<server id>!CustomInput!Activate"',
        sent.some((u) => u.includes('mode=10001') && u.includes(`cardID=${sc.actMz}!CustomInput!Activate`)), sent.join(' | ').slice(0, 200));
      // The SERVER accepted it: the game log records the Action (dev serves game files; prod 404s Games/).
      const gs = await (await fetch(`${BASE}SWUSim/Games/${gn}/Gamestate.txt`)).text();
      ok(label, "the server ran C-3PO's Action (game log)", gs.includes("used [[TS26_15|C-3P0]]'s Action"));
      // With two legal targets (the 4-seat board) the damage target prompt opens; the 2-seat board has ONE other ground
      // unit, so the engine resolves the 2 damage onto it with no prompt.
      if (sc.prompt) {
        const prompted = await page.waitForFunction(() => !!(window.SelectionMode && window.SelectionMode.active), null, { timeout: 20000 })
          .then(() => true).catch(() => false);
        ok(label, 'the damage target prompt opens', prompted);
      }
      await page.screenshot({ path: `/tmp/opponent-unit-action-${engine}-${sc.id}-prompt.png` });
      ok(label, 'no page errors', errors.length === 0, errors.join(' | ').slice(0, 200));
    } catch (e) {
      ok(label, 'scenario ran', false, String(e).slice(0, 240));
    } finally {
      if (browser) await browser.close();
    }
  }
}
report();
console.log(allOk ? '\nALL PASS' : '\nFAILURES ABOVE');
process.exit(allOk ? 0 : 1);
