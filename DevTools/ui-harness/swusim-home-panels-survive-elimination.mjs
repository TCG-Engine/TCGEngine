// Twin Suns: a multi-seat game that narrows to TWO LIVE SEATS must KEEP its Home panels.
//
// Owner ruling 2026-09-25: "remove the experience in Twin Suns when a 3P game goes down to a 2P game and
// it zooms in to the 2 remaining players. people actually like the Home panels." The 4P -> 3P shift is the
// baseline — eliminated seats simply stop being tiled, and the multi-seat chrome stays.
//
// Before this, swuBuildViews() ended with `if (seats.length <= 2) return [];`, so the moment a 3-seat game
// lost a player the client threw away its view list: no Home view, no preview tiles, and the board
// re-framed itself as a plain 1v1. This drives a REAL narrowed game and watches the views survive.
//
// ⚠ A genuine TWO-PLAYER game must still return no views — that is the control below. Without it this
// harness would pass against a build that simply never collapses, which would put Home chrome on every
// 1v1 in the product.
//
// Usage:
//   node swusim-home-panels-survive-elimination.mjs [<3seatSrcGameId>] [<2playerSrcGameId>]
// SKIPs cleanly when a source game is not the shape it needs.
import { chromium, firefox, webkit } from 'playwright';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';

const BASE = 'http://localhost:3400/TCGEngine/';
const SRC3 = process.argv[2] || '846502';     // seatOrder=123
const SRC2 = process.argv[3] || '';           // optional 2-player control source
const WORK3 = '9846521';
const WORK2 = '9846522';
const CONTAINER = 'otmtcge-swusim-web-server-1';

let fails = 0, checks = 0;
const ok = (engine, name, cond, extra) => {
  checks++;
  if (!cond) fails++;
  console.log(`${cond ? 'ok  ' : 'BAD '} [${engine}] ${name}${cond || extra === undefined ? '' : '  ' + JSON.stringify(extra)}`);
};
const dex = (...a) => execFileSync('docker', ['exec', '-w', '/var/www/html/TCGEngine', CONTAINER, ...a], { encoding: 'utf8' });
const seats = (g) => {
  const out = dex('php', '-d', 'xdebug.mode=off', 'SWUSim/DevTools/set-live-seats.php', g).trim().split('\n').pop();
  const m = out.match(/seatOrder=(\d+) liveSeats=(\d+)/);
  return m ? { order: m[1], live: m[2] } : null;
};

// ── fixtures ────────────────────────────────────────────────────────────────────────────────────────
const src3 = seats(SRC3);
if (!src3 || src3.order.length < 3) {
  console.log(`SKIP: source ${SRC3} is not a 3+ seat game (${JSON.stringify(src3)})`);
  process.exit(0);
}
dex('sh', '-c', `rm -rf SWUSim/Games/${WORK3} && cp -r SWUSim/Games/${SRC3} SWUSim/Games/${WORK3}`);
// Seat 1 eliminated: three seated, two still alive — the exact case the owner asked about.
dex('php', '-d', 'xdebug.mode=off', 'SWUSim/DevTools/set-live-seats.php', WORK3, '23');

let haveControl = false;
if (SRC2) {
  const src2 = seats(SRC2);
  if (src2 && src2.order.length === 2) {
    dex('sh', '-c', `rm -rf SWUSim/Games/${WORK2} && cp -r SWUSim/Games/${SRC2} SWUSim/Games/${WORK2}`);
    haveControl = true;
  } else {
    console.log(`note: control source ${SRC2} is not a 2-player game (${JSON.stringify(src2)}) — control skipped`);
  }
}

// ⚠ THE DISCRIMINATING FIXTURE. In a game that ended by elimination the dead seat's units are already
// gone from the payload, so "the defeated tile shows no units" passes whether or not the renderer hides
// them — a mutation that removed the hiding still scored 45/45 against WORK3 alone. This builds a 4-seat
// board where P3/P4 EACH STILL HOLD TWO UNITS and are then marked dead, so only a renderer that actually
// drops them can pass.
// ⚠ APCu: the web process caches a game AS CREATED, so a CLI edit to LiveSeats is invisible to it. Clone
// to a FRESH id after the edit — a new id has no cache entry, so the page reads the file.
const SCHEMA4 = readFileSync(new URL('../../SWUSim/Tests/Visual/HomePanels_4P_TwoOpponentsAt1HP.md', import.meta.url), 'utf8');
let WORK4 = '';
try {
  const res = await fetch(BASE + 'SWUSim/TestSchemaSetup.php', { method: 'POST', body: new URLSearchParams({ schema: SCHEMA4 }) });
  const jj = await res.json();
  if (jj.gameName) {
    WORK4 = String(Number(jj.gameName) + 700000);
    dex('sh', '-c', `rm -rf SWUSim/Games/${WORK4} && cp -r SWUSim/Games/${jj.gameName} SWUSim/Games/${WORK4} && `
                  + `php -d xdebug.mode=off SWUSim/DevTools/set-live-seats.php ${WORK4} 12`);
  } else {
    console.log('note: TestSchemaSetup declined (' + JSON.stringify(jj).slice(0, 120) + ') — 4P case skipped');
  }
} catch (e) { console.log('note: 4P fixture unavailable (' + e.message + ') — case skipped'); }

const read = (page) => page.evaluate(() => ({
  order: String(window.SeatOrderData || ''),
  live: String(window.LiveSeatsData || ''),
  modes: (window.swuViews || []).map(v => v.mode),
  labels: (window.swuViews || []).map(v => v.label),
  homeOpps: (window.swuViews || []).filter(v => v.mode === 'home').map(v => v.opps)[0] || null,
  homeTiles: (window.swuViews || []).filter(v => v.mode === 'home').map(v => v.tiles)[0] || null,
  deadMatchups: (window.swuViews || []).filter(v => v.mode === 'matchup' && v.defeated).map(v => v.oppSeat),
  strips: Array.from(document.querySelectorAll('#swuHomeStrips .swu-home-strip')).map(e => ({
    seat: e.getAttribute('data-seat'),
    dead: e.classList.contains('is-defeated'),
    units: e.querySelectorAll('.swu-mb-unit').length,
    zoom: !!e.querySelector('.swu-mb-zoom'),
  })),
  stripSeats: Array.from(document.querySelectorAll('#swuHomeStrips .swu-home-strip, #swuHomeStrips [data-seat]'))
                   .map(e => e.getAttribute('data-seat')).filter(Boolean),
}));

for (const [engine, driver] of Object.entries({ chromium, firefox, webkit })) {
  let browser;
  try {
    browser = await driver.launch();
    const page = await browser.newPage({ viewport: { width: 1700, height: 1100 } });

    // ── the case under test: 3 seated, 2 live, viewed as a SURVIVOR ────────────────────────────────
    await page.goto(`${BASE}NextTurn.php?folderPath=SWUSim&gameName=${WORK3}&playerID=2`, { waitUntil: 'load' });
    await page.waitForTimeout(2500);
    const r = await read(page);

    ok(engine, 'fixture: 3 seated, 2 live', r.order === '123' && r.live === '23', r);
    ok(engine, 'the view list SURVIVES the narrowing (was [] before 2026-09-25)', r.modes.length > 0, r.modes);
    ok(engine, 'a HOME view is present', r.modes.includes('home'), r.modes);
    // One matchup PER SEATED OPPONENT, dead included — a defeated seat keeps its Zoom In (owner choice
    // 2026-09-25), so a 3-seat game viewed by a survivor has Home + a matchup for P1 (dead) and P3 (live).
    // ⚠ ORDER IS VIEWER-RELATIVE since 2026-09-26: the list starts at the seat to the viewer's RIGHT and
    // wraps, so seat 2 reads P3 then P1 — not ascending P1, P3. The SET is unchanged; only the walk is.
    ok(engine, 'a matchup per SEATED opponent, dead included, starting to the viewer\'s right',
       JSON.stringify(r.labels) === JSON.stringify(['Home', 'vs P3', 'vs P1']), r.labels);
    ok(engine, 'opps stays LIVE-ONLY (it also supplies the home view oppSeat)',
       JSON.stringify(r.homeOpps) === JSON.stringify([3]), r.homeOpps);

    // ── the DEFEATED seat keeps its panel (owner ruling 2026-09-25) ────────────────────────────────
    // Viewer-relative order (2026-09-26): seat 2's walk is P3 then P1, not ascending.
    ok(engine, 'tiles cover EVERY seated opponent, dead included', JSON.stringify(r.homeTiles) === JSON.stringify([3, 1]), r.homeTiles);
    ok(engine, 'the ELIMINATED seat 1 IS still tiled', r.stripSeats.includes('1'), r.stripSeats);
    ok(engine, 'the live opponent IS tiled', r.stripSeats.includes('3'), r.stripSeats);
    const dead = r.strips.find(t => t.seat === '1') || {};
    const live = r.strips.find(t => t.seat === '3') || {};
    ok(engine, "the dead seat's tile is marked .is-defeated", dead.dead === true, dead);
    ok(engine, "the dead seat's UNITS are gone from the panel", dead.units === 0, dead);
    ok(engine, 'the LIVE seat is not marked defeated', live.dead === false, live);
    ok(engine, 'Zoom In is KEPT on the defeated tile (owner choice)', dead.zoom === true, dead);
    ok(engine, 'a DEFEATED matchup view exists for seat 1', JSON.stringify(r.deadMatchups) === JSON.stringify([1]), r.deadMatchups);

    // ── 4 seats, TWO dead, and the dead seats still hold units in the payload ─────────────────────
    if (WORK4) {
      await page.goto(`${BASE}NextTurn.php?folderPath=SWUSim&gameName=${WORK4}&playerID=1&authKey=testschema`, { waitUntil: 'load' });
      await page.waitForTimeout(2500);
      const f = await read(page);
      ok(engine, '4P fixture: 4 seated, 2 live', f.order === '1234' && f.live === '12', { order: f.order, live: f.live });
      ok(engine, '4P: all three opponents still tiled', JSON.stringify(f.homeTiles) === JSON.stringify([2, 3, 4]), f.homeTiles);
      const d3 = f.strips.find(t => t.seat === '3') || {}, d4 = f.strips.find(t => t.seat === '4') || {};
      const a2 = f.strips.find(t => t.seat === '2') || {};
      ok(engine, '4P: both dead seats marked defeated', d3.dead === true && d4.dead === true, [d3, d4]);
      // THE load-bearing one: these seats DO have two units each in the seat block.
      ok(engine, '4P: dead seats show NO units even though they hold two', d3.units === 0 && d4.units === 0, [d3, d4]);
      ok(engine, '4P: the LIVE opponent still shows its two units', a2.units === 2 && a2.dead === false, a2);

      // ── the ELIMINATED VIEWER gets the home panels too (owner ruling 2026-09-25) ────────────────
      // Before this they were short-circuited into a single "P1 vs P2" matchup of the survivors.
      await page.goto(`${BASE}NextTurn.php?folderPath=SWUSim&gameName=${WORK4}&playerID=3&authKey=testschema`, { waitUntil: 'load' });
      await page.waitForTimeout(2500);
      const e = await page.evaluate(() => ({
        mode: window.swuView && window.swuView.mode,
        viewSeat: window.swuView && window.swuView.viewSeat,
        eliminated: !!(window.swuViewerIsEliminated && window.swuViewerIsEliminated()),
        spectating: !!window.swuSpectating,
        selfGrey: document.body.classList.contains('swu-self-defeated'),
        badge: (document.getElementById('swuSpectateBadge') || {}).textContent || '',
        tiles: Array.from(document.querySelectorAll('#swuHomeStrips .swu-home-strip'))
                    .map(t => t.getAttribute('data-seat') + (t.classList.contains('is-defeated') ? ':DEAD' : '')),
      }));
      ok(engine, 'ELIMINATED VIEWER: gets the HOME view, not a survivors-matchup', e.mode === 'home', e);
      ok(engine, 'ELIMINATED VIEWER: views their OWN seat', e.viewSeat === 3, e);
      ok(engine, 'ELIMINATED VIEWER: still READ-ONLY (viewSeat is now their own, so it cannot key on that)',
         e.eliminated === true && e.spectating === true, e);
      ok(engine, 'ELIMINATED VIEWER: their own board is greyed', e.selfGrey === true, e);
      ok(engine, 'ELIMINATED VIEWER: the badge says why, not "viewing P1 vs P2"', /eliminated/i.test(e.badge), e.badge);
      // Viewer-relative order (2026-09-26): an eliminated viewer keeps a stable rotation because the
      // anchor is the SEATED order, not the live one — seat 3 reads P4, P1, P2.
      ok(engine, 'ELIMINATED VIEWER: sees the two live seats plus the other dead one, from their right',
         JSON.stringify(e.tiles) === JSON.stringify(['4:DEAD', '1', '2']), e.tiles);
    }

    // ── control: a genuine 1v1 must still have NO views ───────────────────────────────────────────
    if (haveControl) {
      await page.goto(`${BASE}NextTurn.php?folderPath=SWUSim&gameName=${WORK2}&playerID=1`, { waitUntil: 'load' });
      await page.waitForTimeout(2000);
      const c = await read(page);
      ok(engine, 'CONTROL: a real 2-player game still builds no views', c.modes.length === 0, c);
      ok(engine, 'CONTROL: and no home strip tiles', c.strips.length === 0, c.strips);
    }
    await browser.close();
  } catch (e) {
    fails++;
    console.log(`BAD [${engine}] threw: ${e && e.message}`);
    try { if (browser) await browser.close(); } catch {}
  }
}

dex('sh', '-c', `rm -rf SWUSim/Games/${WORK3} SWUSim/Games/${WORK2} SWUSim/Games/${WORK4}`);
console.log(`\n${checks - fails}/${checks} checks passed`);
process.exit(fails ? 1 : 0);
