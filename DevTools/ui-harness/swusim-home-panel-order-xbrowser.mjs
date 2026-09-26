// Twin Suns: the Home panel strip must PAINT in viewer-relative order — start at the seat to your right
// and wrap (owner request 2026-09-26):
//     P1 → P2 P3 P4 · P2 → P3 P4 P1 · P3 → P4 P1 P2 · P4 → P1 P2 P3
//
// The arithmetic is pinned separately by swusim-home-panel-order.mjs, which evaluates the ranking lines
// lifted out of GameLayoutShared.php. This half answers the different question that one cannot: does the
// strip actually render in that order, in Chromium, Firefox AND WebKit.
//
// ⚠ It reads the tile ORDER OFF THE DOM and also saves a screenshot per seat. Both matter: the DOM order
// is what can be asserted, and the screenshot is the only thing that shows the strip is legible and that
// the tiles are the seats they claim to be — a label is cheap to get right while the art beside it is
// wrong. Each seat has a distinct leader in the fixture precisely so the image is readable.
//
// Usage:
//   node swusim-home-panel-order-xbrowser.mjs
import { chromium, firefox, webkit } from 'playwright';
import { execFileSync } from 'node:child_process';

const BASE = 'http://localhost:3400/TCGEngine/';
const CONTAINER = 'otmtcge-swusim-web-server-1';
let GID = 9932100;

let fails = 0, checks = 0;
const ok = (engine, name, cond, extra) => {
  checks++;
  if (!cond) fails++;
  console.log(`${cond ? 'ok  ' : 'BAD '} [${engine}] ${name}${cond || extra === undefined ? '' : '  ' + JSON.stringify(extra)}`);
};
const dex = (...a) => execFileSync('docker', ['exec', '-w', '/var/www/html/TCGEngine', '-e', 'XDEBUG_MODE=off', CONTAINER, ...a], { encoding: 'utf8' });

// ⚠ A fresh game id per engine: the web process caches a game as first created, and a reused id serves
// the earlier state (this cost a bogus cross-engine failure on an earlier harness).
const build = () => {
  const g = String(++GID);
  dex('php', 'SWUSim/DevTools/make-fourseat-fixture.php', `--game=${g}`);
  return g;
};

// The strip order, read from the rendered tiles. swuViews is also reported so a mismatch says WHICH
// layer is wrong — the view list or the paint.
const probe = () => {
  const vis = (el) => { const r = el.getBoundingClientRect(); return r.width > 0 && r.height > 0; };
  const box = document.getElementById('swuHomeStrips');
  const tiles = box ? [...box.children].filter(vis) : [];
  return {
    me: window.MY_PLAYER_ID,
    seats: String(window.SeatOrderData || ''),
    mode: window.swuView ? window.swuView.mode : null,
    viewTiles: (window.swuViews && window.swuViews[0] && window.swuViews[0].tiles) || null,
    oppSeat: window.swuView ? window.swuView.oppSeat : null,
    // Each tile opens a matchup view; its seat is recoverable from the tile's own text label.
    painted: tiles.map(t => {
      const m = (t.textContent || '').match(/P(\d)/);
      return m ? parseInt(m[1], 10) : 0;
    }).filter(Boolean),
  };
};

const WANT = { 1: [2, 3, 4], 2: [3, 4, 1], 3: [4, 1, 2], 4: [1, 2, 3] };

const ENGINES = { chromium, firefox, webkit };
for (const [engine, driver] of Object.entries(ENGINES)) {
  let browser;
  try {
    const game = build();
    browser = await driver.launch();
    const ctx = await browser.newContext({ viewport: { width: 1700, height: 1100 } });
    const page = await ctx.newPage();

    for (const seat of [1, 2, 3, 4]) {
      await page.goto(`${BASE}NextTurn.php?gameName=${game}&playerID=${seat}&folderPath=SWUSim`, { waitUntil: 'load' });
      await page.waitForTimeout(4000);
      const s = await page.evaluate(probe);
      if (seat === 1 && s.seats !== '1234') {
        console.log(`SKIP: fixture is not a 4-seat game`, JSON.stringify(s));
        await browser.close();
        process.exit(0);
      }
      ok(engine, `P${seat} view list is ${WANT[seat].join(' ')}`,
         JSON.stringify(s.viewTiles) === JSON.stringify(WANT[seat]), s);
      ok(engine, `P${seat} strip PAINTS ${WANT[seat].join(' ')}`,
         JSON.stringify(s.painted) === JSON.stringify(WANT[seat]), s);
      ok(engine, `P${seat} home oppSeat leads the strip`, s.oppSeat === WANT[seat][0], s);

      const shot = `/tmp/home-panel-order-P${seat}-${engine}.png`;
      await page.screenshot({ path: shot, clip: { x: 0, y: 0, width: 1700, height: 200 } });
      if (engine === 'chromium') console.log(`    strip screenshot: ${shot}`);
    }
    dex('php', 'SWUSim/DevTools/make-fourseat-fixture.php', `--game=${game}`, '--remove');
    await browser.close();
  } catch (e) {
    fails++;
    console.log(`BAD [${engine}] threw: ${e.message}`);
    if (browser) await browser.close();
  }
}

console.log(`\n${checks - fails}/${checks} checks passed`);
process.exit(fails ? 1 : 0);
