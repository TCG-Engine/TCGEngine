// SWUSim drawn game — the end-game screen, Chromium + Firefox + WebKit, desktop (1280x800) and phone (390x844, the
// separate mobile layout). Spec: docs/superpowers/specs/2026-10-30-swusim-draws-design.md
//
// The match comes from SWUSim/DevTools/tests/draw_endscreen_fixture.php: a real public match whose game 1 the engine
// ended as a DRAW (SWUDeclareGameDraw) and the match layer recorded.
//   · seat 1, seat 2 and a spectator: the title reads "Draw", the overlay carries .draw, and the title colour is neither
//     the win nor the loss colour;
//   · Bo1 (a drawn match): the SWUStats banner says drawn games are not sent;
//   · Bo3 (game 1 drawn, the series goes on): the score line names the draw;
//   · no horizontal page overflow;
//   · a Bo3 that went Draw, P1, P2 (a DRAWN MATCH whose last game was decided): game 3's winner and loser both see the
//     match called a draw under their title (scenario=lastwon);
//   · a drawn game the match layer has not recorded yet: the board alone still shows "Draw" (scenario=unrecorded).
//
// Usage: node swusim-draw-endscreen-xbrowser.mjs [BASE] [SHOTS_DIR] [engines]
import { chromium, firefox, webkit } from 'playwright';

const BASE  = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const SHOTS = process.argv[3] || '';
const ONLY  = (process.argv[4] || 'chromium,firefox,webkit').split(',');
const ENGINES = Object.fromEntries(Object.entries({ chromium, firefox, webkit }).filter(([n]) => ONLY.includes(n)));
const SIZES = [{ name: 'desktop', w: 1280, h: 800, layout: '' }, { name: 'phone', w: 390, h: 844, layout: '&swuLayout=mobile' }];

let failures = 0;
const bad = (where, msg) => { failures++; console.log(`  FAIL ${where}: ${msg}`); };
const ok  = (where, msg) => console.log(`  ok   ${where}: ${msg}`);

async function fixture(request, queue, scenario = '') {
  const r = await request.get(`${BASE}SWUSim/DevTools/tests/draw_endscreen_fixture.php?queue=${queue}&scenario=${scenario}`);
  const j = await r.json();
  if (!j.gameName) throw new Error('fixture failed: ' + JSON.stringify(j));
  return j;
}
async function viewer(browser, size, fx, seat) {
  const ctx = await browser.newContext({ viewport: { width: size.w, height: size.h } });
  if (seat !== 'S') await ctx.addCookies([{ name: 'lastAuthKey', value: fx.keys[seat], domain: new URL(BASE).hostname, path: '/' }]);
  const page = await ctx.newPage();
  // Generous waits: a loaded dev box (load average 200+, other agents' sweeps) can take 30s+ to serve a board.
  await page.goto(`${BASE}NextTurn.php?gameName=${fx.gameName}&playerID=${seat}&folderPath=SWUSim${size.layout}`, { waitUntil: 'domcontentloaded', timeout: 90000 });
  await page.waitForSelector('#game-over-overlay #game-over-title', { timeout: 90000 });
  // The overlay fades in; headless Firefox runs that at a few frames a second.
  await page.waitForFunction(() => getComputedStyle(document.getElementById('game-over-overlay')).opacity === '1', null, { timeout: 15000 }).catch(() => {});
  return { ctx, page };
}
const titleInfo = (page) => page.evaluate(() => {
  const t = document.getElementById('game-over-title'), o = document.getElementById('game-over-overlay');
  return { text: t.textContent, draw: o.classList.contains('draw'), won: o.classList.contains('won'), lost: o.classList.contains('lost'),
           color: getComputedStyle(t).color, overflow: document.documentElement.scrollWidth - window.innerWidth,
           subtitle: (document.getElementById('game-over-subtitle') || {}).textContent || '',
           stats: (document.getElementById('game-over-stats') || {}).textContent || '' };
});

for (const [ename, engine] of Object.entries(ENGINES)) {
  const browser = await engine.launch();
  for (const size of SIZES) {
    for (const queue of ['bo1', 'bo3']) {
      const where = `${ename} ${size.name} ${queue}`;
      const api = await browser.newContext();
      try {
        const fx = await fixture(api.request, queue);
        for (const seat of ['1', '2', 'S']) {
          const { ctx, page } = await viewer(browser, size, fx, seat);
          const t = await titleInfo(page);
          const who = seat === 'S' ? 'spectator' : `seat ${seat}`;
          t.text === 'Draw' && t.draw && !t.won && !t.lost ? ok(where, `${who} sees "Draw"`) : bad(where, `${who}: ${JSON.stringify(t)}`);
          // gold (255,215,0) / SWU accent glow = won; red tones = lost. A draw must be neither.
          const [r, g, b] = (t.color.match(/\d+/g) || []).map(Number);
          !(r > 200 && g > 180 && b < 80) && !(r > 120 && g < 80 && b < 80) ? ok(where, `${who} title colour ${t.color}`) : bad(where, `${who} title colour ${t.color}`);
          t.overflow <= 0 ? ok(where, `${who} no horizontal overflow`) : bad(where, `${who} overflow ${t.overflow}px`);
          if (seat === '1' && queue === 'bo1') {
            await page.waitForFunction(() => /Drawn games are not sent to SWUStats/.test(document.getElementById('game-over-stats')?.textContent || ''), null, { timeout: 15000 })
              .then(() => ok(where, 'the SWUStats banner says drawn games are not sent'), () => bad(where, 'no drawn-games SWUStats banner'));
          }
          if (seat === '1' && queue === 'bo3') {
            /Match score: 0 – 0, 1 draw/.test(t.stats) ? ok(where, 'the Bo3 score line names the draw') : bad(where, 'score line: ' + (t.stats.match(/Match score[^)]*\)/) || ['-'])[0]);
          }
          if (SHOTS) await page.screenshot({ path: `${SHOTS}/draw-${ename}-${size.name}-${queue}-${seat}.png` });
          await ctx.close();
        }
      } catch (e) { bad(where, e.message.split('\n')[0]); }
      await api.close();
    }
    // A drawn MATCH whose last game was decided, and a draw the match layer has not recorded yet.
    const api = await browser.newContext();
    let where = `${ename} ${size.name} lastwon`;
    try {
      const fx = await fixture(api.request, 'bo3', 'lastwon');
      for (const seat of ['1', '2']) {
        const { ctx, page } = await viewer(browser, size, fx, seat);
        const t = await titleInfo(page);
        /match is a draw/i.test(t.subtitle) ? ok(where, `seat ${seat} (${t.text}) is told the match is a draw: "${t.subtitle}"`) : bad(where, `seat ${seat}: ${JSON.stringify(t)}`);
        if (SHOTS) await page.screenshot({ path: `${SHOTS}/draw-${ename}-${size.name}-lastwon-${seat}.png` });
        await ctx.close();
      }
    } catch (e) { bad(where, e.message.split('\n')[0]); }
    where = `${ename} ${size.name} unrecorded`;
    try {
      const fx = await fixture(api.request, 'bo1', 'unrecorded');
      const { ctx, page } = await viewer(browser, size, fx, '1');
      const t = await titleInfo(page);
      t.text === 'Draw' && t.draw ? ok(where, 'the board alone shows "Draw"') : bad(where, JSON.stringify(t));
      await ctx.close();
    } catch (e) { bad(where, e.message.split('\n')[0]); }
    await api.close();
  }
  await browser.close();
}
console.log(failures === 0 ? 'PASS' : `${failures} FAILED`);
process.exit(failures === 0 ? 0 : 1);
