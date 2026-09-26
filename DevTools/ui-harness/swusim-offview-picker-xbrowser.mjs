// Twin Suns, bug #1086 (game 1310526): TS26_80 Reveal Intentions walks the table — "in player order,
// each player discards a card from the hand of the player to their right" — and the live game stalled
// after the caster's pick. No further discards, and no closing draw for anybody.
//
// WHAT IS ALREADY RULED OUT, so this harness does not re-litigate it:
//   • The ENGINE. The schema suite resolves the walk at 3 and 4 seats, and seat 2 genuinely holds a
//     decision whose pool is p3Hand-*.
//   • The TRANSPORT. _swuRevealSeats (GetNextTurn.php) reads p(\d+)Hand out of the pending decision's
//     Param, so seat 3's hand contents really are sent to seat 2.
//   • The PICKER ITSELF. On a FRESH PAGE LOAD seat 2 gets a working "CHOOSE CARD" modal listing all
//     three of seat 3's revealed cards. Verified by screenshot in all three engines.
//
// So the remaining difference between the harness and the live game is that a real player's page is
// ALREADY OPEN AND POLLING when the decision arrives. This drives exactly that: seat 2's page is opened
// BEFORE the decision exists, left open, and then the caster answers through the real endpoint from a
// second page. The question is whether seat 2's modal appears on the poll tick.
//
// ⚠ The fresh-load path is the CONTROL and it is what gives a failure here its meaning. Without it, a
// modal that never appeared for any reason — a broken fixture, a login wall, a page that never finished
// loading — would read as this specific bug.
//
// Usage:
//   node swusim-offview-picker-xbrowser.mjs
// Rebuilds its own fixture game via SWUSim/DevTools/make-offview-picker-fixture.php.
import { chromium, firefox, webkit } from 'playwright';
import { execFileSync } from 'node:child_process';

const BASE = 'http://localhost:3400/TCGEngine/';
let GID = 9931000;                      // fresh id per fixture build (see the caching note below)
const nextGame = () => String(++GID);
const CONTAINER = 'otmtcge-swusim-web-server-1';

let fails = 0, checks = 0;
const ok = (engine, name, cond, extra) => {
  checks++;
  if (!cond) fails++;
  console.log(`${cond ? 'ok  ' : 'BAD '} [${engine}] ${name}${cond || extra === undefined ? '' : '  ' + JSON.stringify(extra)}`);
};
const dex = (...a) => execFileSync('docker', ['exec', '-w', '/var/www/html/TCGEngine', '-e', 'XDEBUG_MODE=off', CONTAINER, ...a], { encoding: 'utf8' });
// ⚠ EVERY build gets its OWN game id. The web process caches a game as first created, so reusing an
// id serves the previous state: rebuilding 'before the answer' over an answered game left Firefox and
// WebKit showing a picker that could not possibly be open yet, which read exactly like a bug.
const build = (...flags) => {
  const g = nextGame();
  dex('php', 'SWUSim/DevTools/make-offview-picker-fixture.php', `--game=${g}`, ...flags);
  return g;
};

// The picker is a MODAL (.mzchoose-popup-*), not a board element. Two earlier versions of this probe
// measured the wrong thing and each produced a confident false result, so the selectors here are the
// ones read off the live DOM:
//   • probing the board for data-mzid="p3Hand-*" found nothing and "confirmed" a bug that a screenshot
//     then disproved — the modal was there the whole time;
//   • matching the modal by text with .pop() selected the TITLE div, whose textContent has no card
//     captions, so a working picker reported 0 offered cards.
const probe = () => {
  const vis = (el) => { const r = el.getBoundingClientRect(); return r.width > 0 && r.height > 0; };
  const panel = [...document.querySelectorAll('.mzchoose-popup-panel')].find(vis) || null;
  const title = panel ? panel.querySelector('.mzchoose-popup-title') : null;
  const cards = panel ? [...panel.querySelectorAll('.mzchoose-popup-card')].filter(vis) : [];
  return {
    seats: String(window.SeatOrderData || ''),
    modalVisible: !!panel,
    title: title ? title.textContent.trim() : null,
    offeredCards: cards.length,
    zoneLabels: cards.map(c => {
      const z = c.querySelector('.mzchoose-popup-zone-label');
      return z ? z.textContent.trim() : '?';
    }),
  };
};

const ENGINES = { chromium, firefox, webkit };
for (const [engine, driver] of Object.entries(ENGINES)) {
  let browser;
  try {
    browser = await driver.launch();
    const ctx = await browser.newContext({ viewport: { width: 1700, height: 1100 } });

    // ── CONTROL: fresh load into the already-answered state → the modal must be there.
    const gCtl = build('--answered');
    const fresh = await ctx.newPage();
    await fresh.goto(`${BASE}NextTurn.php?gameName=${gCtl}&playerID=2&folderPath=SWUSim`, { waitUntil: 'load' });
    await fresh.waitForTimeout(4000);
    const c = await fresh.evaluate(probe);
    if (c.seats !== '123') {
      console.log(`SKIP: fixture is not a 3-seat game`, JSON.stringify(c));
      await browser.close();
      process.exit(0);
    }
    ok(engine, 'control: fresh load shows seat 2 the picker', c.modalVisible && c.offeredCards === 3, c);
    await fresh.close();

    // ── THE REPORTED PATH: seat 2 is already watching when the decision arrives.
    const gLive = build();                     // a NEW game where the caster still owes its pick
    const p2 = await ctx.newPage();
    await p2.goto(`${BASE}NextTurn.php?gameName=${gLive}&playerID=2&folderPath=SWUSim`, { waitUntil: 'load' });
    await p2.waitForTimeout(4000);
    const before = await p2.evaluate(probe);
    ok(engine, 'seat 2 has no picker before the caster answers', !before.modalVisible, before);

    const p1 = await ctx.newPage();
    await p1.goto(`${BASE}NextTurn.php?gameName=${gLive}&playerID=1&folderPath=SWUSim`, { waitUntil: 'load' });
    await p1.waitForTimeout(4000);
    // The caster picks a real card by CLICKING its tile in their own modal — the live path, not a
    // harness call.
    const casterState = await p1.evaluate(probe);
    ok(engine, 'caster sees its own picker (its right neighbour is the in-view opponent)',
       casterState.modalVisible && casterState.offeredCards > 0, casterState);
    const tile = p1.locator('.mzchoose-popup-card').first();
    if (await tile.count() > 0) await tile.click({ timeout: 5000 }).catch(() => {});

    // Give seat 2's open page several poll ticks to notice.
    await p2.waitForTimeout(9000);
    const after = await p2.evaluate(probe);
    ok(engine, 'seat 2 ALREADY WATCHING gets the picker on the poll tick',
       after.modalVisible && after.offeredCards === 3, after);

    await p2.screenshot({ path: `/tmp/offview-picker-live-${engine}.png` });
    console.log(`    screenshot: /tmp/offview-picker-live-${engine}.png`);
    await browser.close();
  } catch (e) {
    fails++;
    console.log(`BAD [${engine}] threw: ${e.message}`);
    if (browser) await browser.close();
  }
}

console.log(`\n${checks - fails}/${checks} checks passed`);
process.exit(fails ? 1 : 0);
