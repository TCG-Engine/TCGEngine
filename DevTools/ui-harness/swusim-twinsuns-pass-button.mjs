// Twin Suns: the Pass BUTTON must follow CR §12.6.1.a — "players may only pass if there are no
// counters available to take". Owner QOL request 2026-09-20: hide it, don't just disable it.
//
// Before this, the button was merely DISABLED, and only when blast/plan were free — it ignored the
// INITIATIVE counter entirely. At three seats the last free counter is very often the initiative, so
// Pass sat there clickable exactly where the rules forbid it, and the click handler silently swallowed
// the click, which reads as a broken button.
//
// ⚠ WHAT THIS CHECKS, AND WHY IT USES A REAL GAME. The visibility decision is client-side but its INPUT
// is server-side (myActionsData.passAvailable), so a DOM-only assertion would prove nothing about the
// rule. This drives the counter state through the real endpoints on a clone of a live 3-seat game and
// watches the button follow.
//
// Usage:
//   node swusim-twinsuns-pass-button.mjs <srcGameId> [<scratchGameId>]
// SKIPs cleanly unless the source is a 3-seat game with all seats live.
import { chromium, firefox, webkit } from 'playwright';
import { execFileSync } from 'node:child_process';

const BASE = 'http://localhost:3400/TCGEngine/';
const SRC = process.argv[2] || '846502';
const WORK = process.argv[3] || '9846510';
const CONTAINER = 'otmtcge-swusim-web-server-1';

let fails = 0;
const ok = (engine, name, cond, extra) => {
  if (!cond) fails++;
  console.log(`${cond ? 'ok  ' : 'BAD '} [${engine}] ${name}${cond || extra === undefined ? '' : '  ' + JSON.stringify(extra)}`);
};
const dex = (...a) => execFileSync('docker', ['exec', '-w', '/var/www/html/TCGEngine', CONTAINER, ...a], { encoding: 'utf8' });

// Force the three counters into a known state directly in the gamestate — far cheaper and far more
// controllable than playing a game into each configuration, and it is exactly the state the predicate
// reads. Written through the engine's own accessors + WriteGamestate so the file stays well-formed.
function setCounters(init, blast, plan, turn = 1, taken = '') {
  dex('php', '-d', 'xdebug.mode=off', 'SWUSim/DevTools/set-twinsuns-counters.php', WORK, init, blast, plan,
      String(turn), taken);
}

dex('sh', '-c', `rm -rf SWUSim/Games/${WORK} && cp -r SWUSim/Games/${SRC} SWUSim/Games/${WORK}`);

const ENGINES = { chromium, firefox, webkit };
for (const [engine, driver] of Object.entries(ENGINES)) {
  let browser;
  try {
    browser = await driver.launch();
    const ctx = await browser.newContext({ viewport: { width: 1600, height: 1000 } });
    const page = await ctx.newPage();

    const read = async (seat) => {
      await page.goto(`${BASE}NextTurn.php?gameName=${WORK}&playerID=${seat}&folderPath=SWUSim`, { waitUntil: 'load' });
      await page.waitForTimeout(3500);
      return page.evaluate(() => {
        const b = document.getElementById('swuPassBtn');
        const ad = window.myActionsData || {};
        return {
          seats: String(window.SeatOrderData || ''),
          passAvailable: ad.passAvailable,
          present: !!b,
          hidden: b ? b.hidden : null,
          visible: b ? (b.offsetParent !== null) : null,
          mustTakeCounter: typeof window.swuMustTakeCounter === 'function' ? window.swuMustTakeCounter() : null,
        };
      });
    };

    // ── A counter is still free → NO Pass button ──────────────────────────────────────────────
    // Only the INITIATIVE is left, which is precisely the case the old blast/plan-only test missed.
    setCounters('P1_UNCLAIMED', 'P2', 'P3');
    let s = await read(1);
    if (s.seats !== '123') {
      console.log(`SKIP: ${SRC} is not a 3-seat game`, JSON.stringify(s));
      dex('sh', '-c', `rm -rf SWUSim/Games/${WORK}`);
      await browser.close();
      process.exit(0);
    }
    ok(engine, 'only the initiative is free: server says pass is NOT available', s.passAvailable === false, s);
    ok(engine, 'only the initiative is free: the Pass button is HIDDEN', s.hidden === true && s.visible === false, s);
    ok(engine, 'only the initiative is free: the Space-key guard also blocks', s.mustTakeCounter === true, s);

    // ── Blast free → still no Pass ────────────────────────────────────────────────────────────
    // Initiative is claimed by ANOTHER seat, so seat 1 is not a counter-taker and its pass is a CHOICE.
    setCounters('P2_CLAIMED', 'AVAILABLE', 'P3');
    s = await read(1);
    ok(engine, 'blast free: server says pass is NOT available', s.passAvailable === false, s);
    ok(engine, 'blast free: the Pass button is HIDDEN', s.hidden === true, s);

    // ── The FORCED pass is never hidden ───────────────────────────────────────────────────────
    // ⚠ At THREE seats "every counter is taken" necessarily means THIS seat took one (three counters,
    // three players, one each) — so the only way a seat can pass here is the forced pass, and hiding
    // that would deadlock the phase. The four-seat "last player may pass by choice" case cannot be
    // built at three seats at all; it lives in SWUSim/DevTools/tests/twinsuns_pass_rule_test.php.
    setCounters('P2_CLAIMED', 'P3', 'P1', 1, '1');
    s = await read(1);
    ok(engine, 'took a counter: server says pass IS available (forced)', s.passAvailable === true, s);
    ok(engine, 'took a counter: the Pass button is SHOWN', s.hidden === false, s);
    ok(engine, 'took a counter: the Space-key guard allows it', s.mustTakeCounter === false, s);

    await page.screenshot({ path: `/tmp/swusim-pass-button-${engine}.png` });
    await ctx.close();
  } catch (e) {
    ok(engine, 'harness ran', false, String(e && e.message ? e.message : e));
  } finally {
    if (browser) await browser.close().catch(() => {});
  }
}
dex('sh', '-c', `rm -rf SWUSim/Games/${WORK}`);
console.log(fails === 0 ? '\nALL PASS' : `\n${fails} FAILED`);
process.exit(fails === 0 ? 0 : 1);
