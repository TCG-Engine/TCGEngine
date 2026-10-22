// "Export SWUPGN" on the end-game overlay — Chromium, Firefox, WebKit × desktop + phone.
// A real PvP game (two anonymous seats paired by the Open Bo1 queue) is conceded from seat 1's board; the button sits
// in the Save Replay row, downloads seat 1's own view, and the Replays tab lists it. An Arenabot game (not recorded)
// shows "Replay file unavailable".
// Usage: node swupgn-export-xbrowser.mjs [baseURL]   ENGINES=chromium,firefox,webkit   LAYOUTS=desktop,mobile
import { chromium, firefox, webkit, request as pwRequest } from 'playwright';
import fs from 'node:fs';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const MENU = BASE + 'SharedUI/Sites/SWUSim/MainMenu.php';
const SHOTS = new URL('./_shots/', import.meta.url);
const ALL = { chromium, firefox, webkit };
const ENGINES = Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n));
const LAYOUTS = (process.env.LAYOUTS || 'desktop,mobile').split(',');
const deckFile = (f) => fs.readFileSync(new URL('../../SWUSim/Tests/BotFixtures/ash-meta-2026-09/' + f, import.meta.url), 'utf8')
  .split('\n').filter((l) => !l.startsWith('#')).join('\n').trim();
const DECK = deckFile('darth-vader_jtl_yellow.txt');
const DECK_B = deckFile(process.env.DECK_B || 'darth-vader_jtl_yellow.txt');
let allOk = true; const results = [];
const ok = (tag, name, cond, extra = '') => { if (!cond) allOk = false; results.push([tag, name, !!cond, extra]); };
setTimeout(() => { console.log('WATCHDOG: 600s'); report(); process.exit(9); }, 600000);
function report() { for (const [t, n, p, x] of results) console.log(`${p ? 'PASS' : 'FAIL'}  ${t.padEnd(18)} ${n}${x ? ' — ' + x : ''}`); }

const join = async (req, form) => (await req.post(BASE + 'APIs/Lobbies/JoinQueue.php', { form: { rootName: 'SWUSim', queueType: 'bo1', ...form } })).json();
// Two anonymous seats in one PvP game; returns seat 1's key (each request context is its own browser).
async function pvpGame() {
  const a = await pwRequest.newContext(), b = await pwRequest.newContext();
  const h = await join(a, { format: 'open', deckLink: DECK });
  const g = await join(b, { format: 'open', deckLink: DECK_B });
  const seats = { [String(h.playerID)]: h.authKey, [String(g.playerID)]: g.authKey };
  await a.dispose(); await b.dispose();
  return { gameName: String(g.gameName || ''), key1: seats['1'] };
}
const board = (gameName, layout) => `${BASE}NextTurn.php?gameName=${gameName}&playerID=1&folderPath=SWUSim${layout === 'mobile' ? '&swuLayout=mobile' : ''}`;
async function concede(page) {
  await page.waitForFunction(() => typeof window.SubmitInput === 'function', null, { timeout: 30000 });
  await page.evaluate(() => window.SubmitInput('10006', ''));
  await page.waitForSelector('#game-over-overlay', { timeout: 30000 });
}

for (const [engine, driver] of ENGINES) {
  const browser = await driver.launch();
  for (const layout of LAYOUTS) {
    const tag = `${engine}/${layout}`;
    const ctx = await browser.newContext({ acceptDownloads: true, viewport: layout === 'mobile' ? { width: 390, height: 844 } : { width: 1600, height: 900 } });
    const page = await ctx.newPage();
    const errors = []; page.on('pageerror', (e) => errors.push(String(e)));
    try {
      const g = await pvpGame();
      ok(tag, 'a PvP game was created', g.gameName !== '' && !!g.key1, JSON.stringify(g));
      await ctx.addCookies([{ name: 'lastAuthKey', value: g.key1, url: BASE }]);
      await page.goto(board(g.gameName, layout), { waitUntil: 'domcontentloaded' });
      await concede(page);
      const btn = await page.waitForSelector('#swupgn-export-btn', { state: 'visible', timeout: 15000 }).catch(() => null);
      ok(tag, 'the Export SWUPGN button is on the end-game overlay', btn !== null);
      if (!btn) continue;
      ok(tag, 'it sits in the Save Replay row', await page.evaluate(() => {
        const b = document.getElementById('swupgn-export-btn'), s = document.getElementById('match-replay-game-over-save-btn');
        return !!s && b.parentElement === s.parentElement && b.parentElement.classList.contains('match-replay-stats-actions');
      }));
      await btn.scrollIntoViewIfNeeded();
      const cover = await page.evaluate(() => {
        const b = document.getElementById('swupgn-export-btn'), r = b.getBoundingClientRect();
        const top = document.elementFromPoint(r.left + r.width / 2, r.top + r.height / 2);
        return { hit: !!top && (top === b || b.contains(top)), r: [r.left, r.top, r.width, r.height].map(Math.round), vw: innerWidth };
      });
      ok(tag, 'the button is not covered and fits the viewport', cover.hit && cover.r[0] >= 0 && cover.r[0] + cover.r[2] <= cover.vw + 1, JSON.stringify(cover));
      await page.waitForTimeout(1500);   // let the overlay's fade-in finish before the screenshot
      fs.mkdirSync(SHOTS, { recursive: true });
      await page.screenshot({ path: new URL(`swupgn-export-${engine}-${layout}.png`, SHOTS).pathname });
      const [dl] = await Promise.all([page.waitForEvent('download', { timeout: 20000 }), btn.click()]);
      const text = fs.readFileSync(await dl.path(), 'utf8');
      ok(tag, 'the download is seat 1\'s own view', text.includes('[Perspective "P1"]') && text.includes('[Seed "unseeded"]'), text.slice(0, 120));
      ok(tag, 'the download is named petranaki_…swupgn', /^petranaki_[a-z0-9-]+_vs_[a-z0-9-]+_\d{4}-\d\d-\d\d\.swupgn$/.test(dl.suggestedFilename()), dl.suggestedFilename());
      await page.waitForFunction(() => document.getElementById('swupgn-export-btn').textContent === 'Export SWUPGN', null, { timeout: 10000 }).catch(() => {});
      await page.goto(MENU);
      await page.click('#ga-info-tab-replays');
      await page.waitForFunction(() => [...document.querySelectorAll('#swupgnReplayList li')].some((li) => li.querySelector('[data-swupgn-play]')), null, { timeout: 10000 }).catch(() => {});
      const rows = await page.$$eval('#swupgnReplayList li', (lis) => lis.map((li) => li.textContent));
      ok(tag, 'the Replays tab lists the exported file', rows.some((t) => t.includes('Play SWUPGN') && t.includes(' vs ')), JSON.stringify(rows));

      // An Arenabot game is not recorded: the button says so.
      const bot = await join(ctx.request, { format: 'botpractice', botStyle: 'normal', cardPool: 'open', deckLink: DECK, deckLink2: DECK_B });
      await ctx.addCookies([{ name: 'lastAuthKey', value: bot.authKey, url: BASE }]);
      await page.goto(board(bot.gameName, layout), { waitUntil: 'domcontentloaded' });
      await concede(page);
      const b2 = await page.waitForSelector('#swupgn-export-btn', { state: 'visible', timeout: 15000 }).catch(() => null);
      if (b2) await b2.click();
      await page.waitForFunction(() => { const b = document.getElementById('swupgn-export-btn'); return b && b.textContent === 'Replay file unavailable'; }, null, { timeout: 10000 }).catch(() => {});
      ok(tag, 'an unrecorded game: "Replay file unavailable", disabled', await page.evaluate(() => { const b = document.getElementById('swupgn-export-btn'); return !!b && b.textContent === 'Replay file unavailable' && b.disabled; }));
      ok(tag, 'no page errors', errors.length === 0, errors.join(' | '));
    } catch (e) {
      ok(tag, 'harness ran', false, String(e).slice(0, 300));
    } finally {
      await ctx.close();
    }
  }
  await browser.close();
}
report();
console.log(allOk ? 'ALL PASS' : 'SOME FAILED');
process.exit(allOk ? 0 : 1);
