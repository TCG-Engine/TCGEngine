// Cross-browser check for the Arenabot REMATCH button (spec 2026-09-23-swusim-bot-data-loop-design.md §3).
//
// What it protects:
//  • an Arenabot game's end-game overlay offers "Rematch" — the button only exists because
//    SWUShowEndGameMenu's non-match branch now BUILDS buttons when EndGameInfo returns rematch inputs
//    (it used to pass null, which made SWUBuildEndGameButtons' `!mid` arm unreachable dead code);
//  • it does NOT offer the old "Quick Rematch", which cannot work without a match record — mode 10013
//    needs SWUReadMatchRef(), and a local-mode game sets isGoldfish so JoinQueue never creates one;
//  • clicking it lands in a DIFFERENT game that is still Arenabot with the same decks and Play Style.
//
// The game is created through the real JoinQueue API rather than driven through the menu: this check is
// about the end-game overlay, and botpractice-menu-xbrowser.mjs already covers the menu.
//
// Usage: node botdata-rematch-xbrowser.mjs [baseURL]   ENGINES=chromium,firefox,webkit (default: all)
//        MOBILE=1 runs the phone layout (GameLayoutMobile.php, forced with swuLayout=mobile) at a 390x844 viewport.
import { chromium, firefox, webkit } from 'playwright';
import fs from 'node:fs';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const readFixture = (rel) => fs.readFileSync(new URL('../../SWUSim/Tests/BotFixtures/' + rel, import.meta.url), 'utf8');
const DECK_A = readFixture('premier_deck_a.txt');
const DECK_B = readFixture('premier_deck_b.txt');

const ALL = { chromium, firefox, webkit };
const MOBILE = process.env.MOBILE === '1';
const ENGINES = Object.fromEntries(Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n)));
let allOk = true;
const results = [];
const ok = (engine, name, cond, extra = '') => { if (!cond) allOk = false; results.push([engine, name, !!cond, extra]); };

async function createArenabotGame(request, style) {
  const r = await request.post(BASE + 'APIs/Lobbies/JoinQueue.php', {
    form: { rootName: 'SWUSim', format: 'botpractice', queueType: 'bo1',
            botStyle: style, cardPool: 'open', deckLink: DECK_A, deckLink2: DECK_B },
  });
  return await r.json();
}

for (const [engine, driver] of Object.entries(ENGINES)) {
  const browser = await driver.launch();
  const ctx = await browser.newContext(MOBILE ? { viewport: { width: 390, height: 844 } } : {});
  try {
    const g1 = await createArenabotGame(ctx.request, 'hyperaggro');
    ok(engine, 'created an Arenabot game', !!g1.success && !!g1.gameName, JSON.stringify(g1.message || ''));
    if (!g1.gameName) { await browser.close(); continue; }

    await ctx.addCookies([{ name: 'lastAuthKey', value: g1.authKey, url: BASE }]);
    const page = await ctx.newPage();
    await page.goto(`${BASE}NextTurn.php?gameName=${g1.gameName}&playerID=1&folderPath=SWUSim${MOBILE ? '&swuLayout=mobile' : ''}`, { waitUntil: 'domcontentloaded' });
    await page.waitForFunction(() => typeof window.SubmitInput === 'function', null, { timeout: 30000 });

    // Concede (EngineActionRunner input 10006) to reach the end-game overlay deterministically.
    await page.evaluate(() => window.SubmitInput('10006', ''));
    await page.waitForSelector('#game-over-overlay', { timeout: 30000 }).catch(() => {});
    const haveOverlay = await page.$('#game-over-overlay');
    ok(engine, 'the end-game overlay appears', !!haveOverlay);
    if (!haveOverlay) { await browser.close(); continue; }

    const labels = await page.$$eval('#game-over-overlay button, #game-over-overlay a',
      els => els.map(e => (e.textContent || '').trim()).filter(Boolean));
    ok(engine, 'offers Rematch', labels.some(l => l === 'Rematch'), JSON.stringify(labels));
    ok(engine, 'does NOT offer the broken Quick Rematch', !labels.some(l => /Quick Rematch/i.test(l)), JSON.stringify(labels));

    if (labels.some(l => l === 'Rematch')) {
      // Assert what the BUTTON SENT, not what a later page says: the new game's auth key travels in a
      // cookie, not in the DOM, so reading #authKey after navigating proves nothing.
      let posted = null, postCount = 0;
      page.on('request', (req) => {
        if (req.method() === 'POST' && req.url().includes('APIs/Lobbies/JoinQueue.php')) { posted = req.postData() || ''; postCount++; }
      });
      // Owner report 2026-10-08 (prod games 2372 + 2373, 4s apart): creating the rematch takes the server
      // seconds and the button gave no sign of life, so a second click created a SECOND game. Click it twice
      // on the same element — the second click lands while the first request is still in flight.
      const btn = await page.$('#game-over-overlay button:text-is("Rematch")');
      const nav = page.waitForNavigation({ timeout: 30000 }).catch(() => {});
      await btn.click();
      const afterFirst = await btn.evaluate(b => ({ disabled: b.disabled, label: (b.textContent || '').trim() })).catch(() => null);
      await btn.click({ force: true, timeout: 2000 }).catch(() => {});
      await nav;
      ok(engine, 'the first click locks the Rematch button', !!afterFirst && afterFirst.disabled, JSON.stringify(afterFirst));
      ok(engine, 'the locked button says it is working', !!afterFirst && /starting/i.test(afterFirst.label), JSON.stringify(afterFirst));
      ok(engine, 'a double click creates exactly ONE game', postCount === 1, `JoinQueue POSTs: ${postCount}`);
      await page.waitForFunction(() => document.getElementById('gameName') !== null, null, { timeout: 30000 }).catch(() => {});
      const g2 = await page.evaluate(() => {
        const el = document.getElementById('gameName');
        return el ? el.value : '';
      });
      ok(engine, 'lands in a NEW game', !!g2 && String(g2) !== String(g1.gameName), `${g1.gameName} -> ${g2}`);
      ok(engine, 'the rematch POST reached JoinQueue', posted !== null);
      if (posted !== null) {
        const body = decodeURIComponent(posted.replace(/\+/g, ' '));
        ok(engine, 'it re-posts format botpractice', /(^|[&\n])format=botpractice|name="format"[\s\S]{0,40}botpractice/.test(body));
        ok(engine, 'it carries the same Play Style', body.includes('hyperaggro'), body.slice(0, 120));
        ok(engine, 'it carries BOTH deck links',
           body.includes(DECK_A.split('\n').find(l => l.startsWith('1 SOR_009')) || 'SOR_009')
           && body.includes('SOR_010'));
      }
    }
  } catch (e) {
    ok(engine, 'harness ran without throwing', false, String(e).slice(0, 200));
  }
  await browser.close();
}

for (const [e, n, pass, x] of results) console.log(`${pass ? 'PASS' : 'FAIL'} [${e}] ${n}${x ? '  — ' + x : ''}`);
console.log(allOk ? '\nALL PASS' : `\n${results.filter(r => !r[2]).length} FAILED`);
process.exit(allOk ? 0 : 1);
