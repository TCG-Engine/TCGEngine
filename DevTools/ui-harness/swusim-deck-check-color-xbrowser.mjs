// The main menu's deck-check line is GREEN on success and RED on an error, in Chromium, Firefox and WebKit.
// Regression (owner report 2026-09-22): "the deck check shows a checkmark but the text is red on success". The menu
// revamp gave #queue-inline-error a fixed swu-note--error class whose `color: … !important` beat the inline green
// showQueueInlineInfo() set, so "✓ Leader / Base — 50 cards" rendered red. The colour is now a state class.
// Visual spec: SWUSim/Tests/Visual/Menu_DeckCheckColors.md
// Usage: node swusim-deck-check-color-xbrowser.mjs [baseURL]   ENGINES=chromium,firefox,webkit (default all)
import { chromium, firefox, webkit } from 'playwright';
import os from 'node:os';
import path from 'node:path';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const ALL = { chromium, firefox, webkit };
const ENGINES = Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n));
const SHOTS = process.env.SHOTS_DIR || os.tmpdir();
const GREEN = 'rgb(168, 200, 160)', RED = 'rgb(255, 123, 114)';

let allOk = true;
const results = [];
const ok = (engine, name, cond, extra = '') => { if (!cond) allOk = false; results.push([engine, name, !!cond, extra]); };
setTimeout(() => { console.log('WATCHDOG: timed out'); process.exit(9); }, 300000).unref();

const colourAfter = (page, fn, msg) => page.evaluate(([fn, msg]) => {
  window[fn](msg);
  const el = document.getElementById('queue-inline-error');
  return { colour: getComputedStyle(el).color, shown: getComputedStyle(el).display !== 'none', text: el.textContent };
}, [fn, msg]);

for (const [engine, launcher] of ENGINES) {
  let browser;
  try {
    browser = await launcher.launch();
    const page = await (await browser.newContext({ viewport: { width: 1440, height: 950 } })).newPage();
    await page.goto(BASE + 'SharedUI/Sites/SWUSim/MainMenu.php', { waitUntil: 'domcontentloaded' });
    await page.waitForFunction(() => typeof showQueueInlineInfo === 'function' && document.getElementById('queue-inline-error'), null, { timeout: 60000 });

    const ok1 = await colourAfter(page, 'showQueueInlineInfo', '✓ Darth Vader / Energy Conversion Lab — 50 cards');
    ok(engine, 'success line is green', ok1.shown && ok1.colour === GREEN, JSON.stringify(ok1));
    await page.locator('#queue-inline-error').screenshot({ path: path.join(SHOTS, `deck-check-${engine}-ok.png`) }).catch(() => {});
    const err = await colourAfter(page, 'showQueueInlineError', 'Premier format error:\n• Too few cards');
    ok(engine, 'error line is red', err.shown && err.colour === RED, JSON.stringify(err));
    await page.locator('#queue-inline-error').screenshot({ path: path.join(SHOTS, `deck-check-${engine}-error.png`) }).catch(() => {});
    // A retry after an error must go back to green — the classes are swapped, not accumulated.
    const ok2 = await colourAfter(page, 'showQueueInlineInfo', 'Validating deck…');
    ok(engine, 'success after an error is green again', ok2.colour === GREEN, JSON.stringify(ok2));
    const err2 = await colourAfter(page, 'showQueueInlineError', 'Could not reach deck validator.');
    ok(engine, 'error after a success is red again', err2.colour === RED, JSON.stringify(err2));
  } catch (e) {
    ok(engine, 'harness ran', false, String(e && e.stack || e));
  } finally {
    if (browser) await browser.close();
  }
}
for (const [e, n, pass, extra] of results) console.log(`${pass ? 'PASS' : 'FAIL'}  ${e.padEnd(8)} ${n}${extra ? '  — ' + extra : ''}`);
console.log(allOk ? '\nALL PASS' : '\nFAILURES');
process.exit(allOk ? 0 : 1);
