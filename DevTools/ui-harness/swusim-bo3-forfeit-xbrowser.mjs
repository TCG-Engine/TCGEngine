// SWUSim Bo3 — "Forfeit Best of 3" BETWEEN games, Chromium + Firefox + WebKit, desktop (1280x800) and phone (390x844).
// Live test 2026-11-03 (High Ground): seat 2 won game 1, then pressed Forfeit Best of 3 on the end screen. The match ended
// on the server, but nothing on screen changed; "Go to Next Game" led to a sideboard screen that answered
// "Not in sideboarding." with no way out, and the opponent's sideboard screen kept waiting for game 2.
//
// The match comes from SWUSim/DevTools/tests/draw_endscreen_fixture.php?queue=bo3&scenario=p2won: a real public Bo3 whose
// game 1 the engine ended as a P2 win, recorded by the match layer (the series is sideboarding).
//   · both seats on the end screen: seat 2 forfeits → seat 2's menu redraws at once as the finished match (no
//     "Go to Next Game", lost, "… forfeited the match"); seat 1's redraws on its poll (won — though it lost game 1);
//   · seat 1 already waiting on the sideboard screen: seat 2 forfeits → seat 1 is taken back to the board's end screen.
//
// Usage: node swusim-bo3-forfeit-xbrowser.mjs [BASE] [SHOTS_DIR] [engines]
import { chromium, firefox, webkit } from 'playwright';

const BASE  = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const SHOTS = process.argv[3] || '';
const ONLY  = (process.argv[4] || 'chromium,firefox,webkit').split(',');
const ENGINES = Object.fromEntries(Object.entries({ chromium, firefox, webkit }).filter(([n]) => ONLY.includes(n)));
const SIZES = [{ name: 'desktop', w: 1280, h: 800, layout: '' }, { name: 'phone', w: 390, h: 844, layout: '&swuLayout=mobile' }];

let failures = 0;
const bad = (where, msg) => { failures++; console.log(`  FAIL ${where}: ${msg}`); };
const ok  = (where, msg) => console.log(`  ok   ${where}: ${msg}`);

async function fixture(request) {
  const r = await request.get(`${BASE}SWUSim/DevTools/tests/draw_endscreen_fixture.php?queue=bo3&scenario=p2won`);
  const j = await r.json();
  if (!j.gameName || j.match !== 'sideboarding') throw new Error('fixture failed: ' + JSON.stringify(j));
  return j;
}
async function context(browser, size, fx, seat) {
  const ctx = await browser.newContext({ viewport: { width: size.w, height: size.h } });
  await ctx.addCookies([{ name: 'lastAuthKey', value: fx.keys[seat], domain: new URL(BASE).hostname, path: '/' }]);
  return ctx;
}
async function board(ctx, size, fx, seat) {
  const page = await ctx.newPage();
  // Generous waits: a loaded dev box can take 30s+ to serve a board.
  await page.goto(`${BASE}NextTurn.php?gameName=${fx.gameName}&playerID=${seat}&folderPath=SWUSim${size.layout}`, { waitUntil: 'domcontentloaded', timeout: 90000 });
  await page.waitForSelector('#game-over-overlay #game-over-title', { timeout: 90000 });
  return page;
}
const menu = (page) => page.evaluate(() => {
  const o = document.getElementById('game-over-overlay');
  if (!o) return null;
  return { buttons: [...o.querySelectorAll('button')].map((b) => b.textContent.trim()).filter(Boolean),
           won: o.classList.contains('won'), lost: o.classList.contains('lost'),
           subtitle: (document.getElementById('game-over-subtitle') || {}).textContent || '',
           overflow: document.documentElement.scrollWidth - window.innerWidth };
});
// The finished match's menu: no next game, the result stated.
const finished = (m, won) => !!m && !m.buttons.includes('Go to Next Game') && !m.buttons.includes('Forfeit Best of 3')
  && (won ? m.won : m.lost) && /forfeited the match/.test(m.subtitle);
async function waitFinished(page, won, timeout) {
  const t0 = Date.now();
  let m = null;
  while (Date.now() - t0 < timeout) {
    m = await menu(page).catch(() => null);
    if (finished(m, won)) return { m, ms: Date.now() - t0 };
    await page.waitForTimeout(500);
  }
  return { m, ms: -1 };
}
// The redrawn overlay fades in (headless Firefox runs that at a few frames a second): it must end fully visible.
const shown = (page) => page.waitForFunction(() => { const o = document.getElementById('game-over-overlay');
  return !!o && getComputedStyle(o).opacity === '1' && getComputedStyle(o).visibility !== 'hidden'; }, null, { timeout: 15000 }).then(() => true, () => false);
async function forfeit(page) {
  await page.locator('#game-over-overlay button', { hasText: 'Forfeit Best of 3' }).click();
  await page.locator('.sd-modal .sd-ok').click();
}

for (const [ename, engine] of Object.entries(ENGINES)) {
  const browser = await engine.launch();
  for (const size of SIZES) {
    const api = await browser.newContext();
    // ── Both seats on the end screen ──
    let where = `${ename} ${size.name} endscreen`;
    try {
      const fx = await fixture(api.request);
      const c1 = await context(browser, size, fx, '1'), c2 = await context(browser, size, fx, '2');
      const p1 = await board(c1, size, fx, '1'), p2 = await board(c2, size, fx, '2');
      await p2.waitForSelector('#game-over-overlay button:has-text("Forfeit Best of 3")', { timeout: 30000 });
      const before = await menu(p1);
      before && before.buttons.includes('Go to Next Game') ? ok(where, 'between games, seat 1 is offered the next game') : bad(where, 'seat 1 before: ' + JSON.stringify(before));
      await forfeit(p2);
      const r2 = await waitFinished(p2, false, 15000);
      r2.ms >= 0 ? ok(where, `seat 2's menu redraws as the forfeited match (${r2.ms}ms): ${r2.m.buttons.join(' | ')}`) : bad(where, 'seat 2 after forfeit: ' + JSON.stringify(r2.m));
      const r1 = await waitFinished(p1, true, 30000);
      r1.ms >= 0 ? ok(where, `seat 1 is shown the won match on its poll (${r1.ms}ms), "${r1.m.subtitle}"`) : bad(where, 'seat 1 after forfeit: ' + JSON.stringify(r1.m));
      for (const [s, r, p] of [['1', r1, p1], ['2', r2, p2]]) {
        if (r.m && r.m.overflow > 0) bad(where, `seat ${s} overflow ${r.m.overflow}px`);
        (await shown(p)) ? ok(where, `seat ${s}'s redrawn menu is fully visible`) : bad(where, `seat ${s}'s redrawn menu never became visible`);
      }
      if (SHOTS) { await p1.screenshot({ path: `${SHOTS}/forfeit-${ename}-${size.name}-1.png` }); await p2.screenshot({ path: `${SHOTS}/forfeit-${ename}-${size.name}-2.png` }); }
      await c1.close(); await c2.close();
    } catch (e) { bad(where, e.message.split('\n')[0]); }
    // ── Seat 1 already waiting on the sideboard screen ──
    where = `${ename} ${size.name} sideboard`;
    try {
      const fx = await fixture(api.request);
      const c1 = await context(browser, size, fx, '1'), c2 = await context(browser, size, fx, '2');
      const p1 = await c1.newPage();
      await p1.goto(`${BASE}SWUSim/Sideboard.php?matchId=${fx.matchId}&playerID=1&authKey=${fx.keys['1']}`, { waitUntil: 'domcontentloaded', timeout: 90000 });
      await p1.waitForSelector('#submit', { timeout: 60000 });
      const p2 = await board(c2, size, fx, '2');
      await p2.waitForSelector('#game-over-overlay button:has-text("Forfeit Best of 3")', { timeout: 30000 });
      await forfeit(p2);
      await p1.waitForURL(/NextTurn\.php\?gameName=/, { timeout: 30000 });
      await p1.waitForSelector('#game-over-overlay #game-over-title', { timeout: 90000 });
      const r1 = await waitFinished(p1, true, 20000);
      r1.ms >= 0 ? ok(where, `the waiting seat is taken back to the board's end screen: ${r1.m.buttons.join(' | ')}`) : bad(where, 'seat 1: ' + JSON.stringify(r1.m));
      (await shown(p1)) ? ok(where, 'and it is fully visible') : bad(where, 'the end screen never became visible');
      if (SHOTS) await p1.screenshot({ path: `${SHOTS}/forfeit-${ename}-${size.name}-sideboard-1.png` });
      await c1.close(); await c2.close();
    } catch (e) { bad(where, e.message.split('\n')[0]); }
    await api.close();
  }
  await browser.close();
}
console.log(failures === 0 ? 'PASS' : `${failures} FAILED`);
process.exit(failures === 0 ? 0 : 1);
