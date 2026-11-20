// Dualing Suns board controls — Chromium + Firefox + WebKit, desktop and phone.
// Visual case: SWUSim/Tests/Visual/DualingSuns_Board.md (P1 to act, P2 holds the initiative).
//
// Measures, on P1's view: Take Initiative and Plan are visible; Blast and Pass are not; the Take Initiative control's
// tooltip names the 1-damage toll; nothing overflows the page horizontally. Saves a screenshot per engine x layout to
// look at — geometry cannot see a screenshot.
// Usage: node swusim-dualingsuns-board-xbrowser.mjs [BASE]   ENGINES=chromium,firefox,webkit   OUT=/tmp
import { chromium, firefox, webkit } from 'playwright';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

const BASE   = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const OUT    = process.env.OUT || '/tmp';
const here   = dirname(fileURLToPath(import.meta.url));
const SCHEMA = readFileSync(resolve(here, '../../SWUSim/Tests/Visual/DualingSuns_Board.md'), 'utf8');
const ALL = { chromium, firefox, webkit };
const ENGINES = Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n));
const LAYOUTS = [['desktop', { width: 1440, height: 900 }, ''], ['phone', { width: 390, height: 844 }, '&swuLayout=mobile']];
const TOLL = 'Take the initiative — costs 1 damage to your base (your opponent holds it)';

let allOk = true;
const results = [];
const ok = (name, cond, extra = '') => { if (!cond) allOk = false; results.push([name, !!cond, extra]); };

async function buildGame(page) {
  const setup = await (await page.request.post(BASE + 'SWUSim/TestSchemaSetup.php', { multipart: { schema: SCHEMA } })).json();
  if (setup.error) throw new Error('setup: ' + setup.error);
  return setup.gameName;
}

// Visible = in the layout, not [hidden], not display:none, with a real box.
const probe = (page) => page.evaluate(() => {
  const vis = (el) => !!el && !el.hidden && getComputedStyle(el).display !== 'none' && el.getBoundingClientRect().width > 0;
  const take = [...document.querySelectorAll('.swu-take-init')].find(vis) || null;
  return {
    take: vis(take), plan: vis(document.getElementById('swuPlanBtn')),
    blast: vis(document.getElementById('swuBlastBtn')), pass: vis(document.getElementById('swuPassBtn')),
    title: take ? take.getAttribute('title') : null,
    toll: (window.myActionsData || {}).initiativeToll,
    overflow: document.documentElement.scrollWidth - document.documentElement.clientWidth,
  };
});

for (const [engineName, engine] of ENGINES) {
  const browser = await engine.launch();
  try {
    for (const [layout, viewport, q] of LAYOUTS) {
      const ctx = await browser.newContext({ viewport });
      const page = await ctx.newPage();
      const tag = `${engineName}/${layout}`;
      let gameName;
      try { gameName = await buildGame(page); }
      catch (e) { ok(`${tag}: board built`, false, e.message); await ctx.close(); continue; }
      await page.goto(BASE + `NextTurn.php?folderPath=SWUSim&gameName=${gameName}&playerID=1&authKey=testschema&viewerPerspective=1${q}`,
        { waitUntil: 'load' });
      // Firefox headless throttles layout; poll until the actions data has arrived rather than sleeping a fixed time.
      await page.waitForFunction(() => window.myActionsData && window.myActionsData.initiativeToll !== undefined, null, { timeout: 20000 }).catch(() => {});
      await page.waitForTimeout(1500);
      const s = await probe(page);
      ok(`${tag}: Take Initiative is showing`, s.take, JSON.stringify(s));
      ok(`${tag}: Plan is showing`, s.plan);
      ok(`${tag}: no Blast`, !s.blast);
      ok(`${tag}: no Pass while a counter is there`, !s.pass);
      ok(`${tag}: the take names the toll`, s.title === TOLL && s.toll === 1, `title=${s.title} toll=${s.toll}`);
      ok(`${tag}: no horizontal overflow`, s.overflow <= 0, `overflow=${s.overflow}`);
      await page.screenshot({ path: `${OUT}/dualingsuns-board-${engineName}-${layout}.png` });
      await ctx.close();
    }
  } finally { await browser.close(); }
}

for (const [n, c, x] of results) console.log(`${c ? 'PASS' : 'FAIL'}: ${n}${x && !c ? '  ' + x : ''}`);
console.log(allOk ? 'ALL PASS' : 'FAILURES');
process.exit(allOk ? 0 : 1);
