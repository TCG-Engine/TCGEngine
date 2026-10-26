// The end screen of a game with no match behind it (Goldfish, Hotseat, a harness game) has ONE button — the core's
// "Return to Menu" (#game-over-menu-btn) — next to the save-replay row (Save Replay, Export SWUPGN). The core styles
// that button to float in the panel's top-right corner; in SWUSim's grid it sat in the "buttons" cell, which then
// collapsed to 0px, and the button landed on top of the save-replay row (owner screenshot 2026-10-25).
//
// Checked: a Goldfish game, conceded, at desktop (2000x1144) and phone (390x844, the mobile layout) in Chromium, Firefox
// and WebKit — the Return to Menu button and the save-replay row do not overlap, both sit inside the panel, and the row's
// caption is not covered.
//
// Usage: node swusim-endscreen-lone-menu-btn-xbrowser.mjs [baseURL]   ENGINES=chromium,firefox,webkit (default: all)
import { chromium, firefox, webkit } from 'playwright';
import fs from 'node:fs';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const DECK = fs.readFileSync(new URL('../../SWUSim/Tests/BotFixtures/ash-meta-2026-09/admiral-piett_jtl_blue.txt', import.meta.url), 'utf8');
const ALL = { chromium, firefox, webkit };
const ENGINES = Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n));
let fails = 0, checks = 0;
const ok = (engine, name, cond, extra = '') => { checks++; if (!cond) fails++; console.log(`${cond ? 'PASS' : 'FAIL'}  ${engine.padEnd(9)} ${name}${!cond && extra ? ' — ' + extra : ''}`); };
const overlap = (a, b) => a && b && a.x < b.x + b.w && b.x < a.x + a.w && a.y < b.y + b.h && b.y < a.y + a.h;
const inside = (a, o) => a && o && a.x >= o.x - 1 && a.y >= o.y - 1 && a.x + a.w <= o.x + o.w + 1 && a.y + a.h <= o.y + o.h + 1;

fs.mkdirSync(new URL('./_shots/', import.meta.url), { recursive: true });
for (const [engine, launcher] of ENGINES) {
  const browser = await launcher.launch();
  try {
    for (const [label, vp, mobile] of [['desktop', { width: 2000, height: 1144 }, false], ['phone', { width: 390, height: 844 }, true]]) {
      const ctx = await browser.newContext({ viewport: vp });
      const g = await (await ctx.request.post(BASE + 'APIs/Lobbies/JoinQueue.php', { form: { rootName: 'SWUSim', format: 'goldfish', queueType: 'bo1', deckLink: DECK } })).json();
      if (!g.gameName) { ok(engine, `${label}: created a Goldfish game`, false, JSON.stringify(g).slice(0, 200)); await ctx.close(); continue; }
      await ctx.addCookies([{ name: 'lastAuthKey', value: g.authKey, url: BASE }]);
      const page = await ctx.newPage();
      await page.goto(`${BASE}NextTurn.php?gameName=${g.gameName}&playerID=1&folderPath=SWUSim${mobile ? '&swuLayout=mobile' : ''}`, { waitUntil: 'domcontentloaded' });
      await page.waitForFunction(() => typeof window.SubmitInput === 'function', null, { timeout: 30000 });
      await page.evaluate(() => window.SubmitInput('10006', ''));   // concede: straight to the end screen
      await page.waitForSelector('#game-over-overlay.active #game-over-menu-btn', { timeout: 30000 }).catch(() => {});
      await page.waitForSelector('#game-over-overlay .match-replay-stats-actions', { timeout: 15000 }).catch(() => {});
      await page.waitForTimeout(2000);   // the button's slide-up animation
      const m = await page.evaluate(() => {
        const box = (e) => { if (!e) return null; const r = e.getBoundingClientRect(); return { x: r.x, y: r.y, w: r.width, h: r.height }; };
        const row = document.querySelector('#game-over-overlay .match-replay-stats-actions');
        const cap = row && [...row.querySelectorAll('*')].find((e) => /Save this/i.test(e.textContent) && !e.querySelector('button'));
        return { overlay: box(document.querySelector('#game-over-overlay')), btn: box(document.querySelector('#game-over-menu-btn')),
          row: box(row), cap: box(cap), rowButtons: row ? [...row.querySelectorAll('button')].map((b) => box(b)) : [] };
      });
      ok(engine, `${label}: the lone Return to Menu button and the save-replay row are both there`, !!m.btn && !!m.row, JSON.stringify(m));
      ok(engine, `${label}: Return to Menu does not cover the save-replay row`, !!m.btn && !!m.row && !overlap(m.btn, m.row), JSON.stringify({ btn: m.btn, row: m.row }));
      ok(engine, `${label}: nor its caption or buttons`, !!m.btn && !overlap(m.btn, m.cap) && !m.rowButtons.some((b) => overlap(m.btn, b)), JSON.stringify(m));
      ok(engine, `${label}: both sit inside the panel`, inside(m.btn, m.overlay) && inside(m.row, m.overlay), JSON.stringify(m));
      await page.locator('#game-over-overlay').screenshot({ path: new URL(`./_shots/endscreen-lone-btn-${label}-${engine}.png`, import.meta.url).pathname }).catch(() => {});
      await ctx.close();
    }
  } catch (e) {
    ok(engine, 'harness ran', false, String(e).slice(0, 300));
  } finally {
    await browser.close();
  }
}
console.log(fails === 0 ? `ALL PASS (${checks})` : `SOME FAILED (${fails} of ${checks})`);
process.exit(fails === 0 ? 0 : 1);
