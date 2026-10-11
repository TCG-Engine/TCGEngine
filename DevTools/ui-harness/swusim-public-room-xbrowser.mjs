// Cross-browser check for the PUBLIC Twin Suns room route (owner, 2026-09-20).
//
// What changed and why this exists: the Twin Suns family now takes the public queue, and its queue is
// a public ROOM rather than Constructed's quick match. Nothing visual was written for it — the page is
// the existing SharedUI/Render/WaitingRoom.php — but the ROUTE to it is new, and the route runs
// client code that behaves differently per engine:
//
//   MainMenu.php's join handler stores the seat authKey in localStorage and then navigates to
//   WaitingRoom.php. If localStorage throws or is partitioned (WebKit private mode, Firefox's
//   stricter storage rules), the handoff is lost and you land on your OWN room showing "Join"
//   instead of seated — the exact failure the private-room harness was written to catch.
//
// So this drives the REAL menu against REAL endpoints in chromium + firefox + webkit, and asserts the
// public Twin Suns join lands on the waiting room WITH the seat held.
//
// What it deliberately does NOT cover: the quick-match path (unchanged), and starting the game (that
// needs four live seats and team picks — the HTTP test covers the server side of both).
//
// Usage:
//   node swusim-public-room-xbrowser.mjs
//   node swusim-public-room-xbrowser.mjs http://localhost:3400/TCGEngine/
//
// ⚠ WEBKIT: on Playwright >= 1.62 newPage() hangs (frozen webkit_mac14_special build vs a newer
// driver). This harness launches webkit directly because the pinned local install is 1.61.1. If you
// upgrade past 1.62, route webkit through a playwright@1.52.0 side-install via PW152, exactly as
// waiting-room-xbrowser.mjs documents. Do NOT re-diagnose it as "WebKit is broken here".
import { chromium, firefox, webkit } from 'playwright';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const CRED = { user: 'claudebot1', pass: 'pass' };

// A Twin Suns-legal list as FREE TEXT — 2 leaders + 80 singleton cards (CR §12.2). Deliberately not a
// swudb URL: the harness must not depend on an external host, or a network blip reads as a product
// failure. Mirrors SWUSim/Tests/BotFixtures/twinsuns_deck_a.txt.
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';
const HERE = dirname(fileURLToPath(import.meta.url));
const DECK = readFileSync(join(HERE, '../../SWUSim/Tests/BotFixtures/twinsuns_deck_a.txt'), 'utf8')
  .split('\n').filter((l) => !l.startsWith('#')).join('\n').trim();

const ENGINES = { chromium, firefox, webkit };

let allOk = true;
const results = [];
const ok = (engine, name, cond, extra) => {
  if (!cond) allOk = false;
  results.push([engine, name, !!cond, extra]);
};

async function login(page, cred) {
  await page.goto(BASE + 'SharedUI/LoginPage.php', { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="userID"]', cred.user);
  await page.fill('input[name="password"]', cred.pass);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'load' }).catch(() => {}),
    page.click('button[type="submit"]'),
  ]);
}

const seatState = (page) => page.evaluate(() => {
  const root = document.querySelector('#wr-root');
  return {
    state: root && root.getAttribute('data-state'),
    seats: document.querySelectorAll('.wr-seat').length,
    mine: document.querySelectorAll('.wr-seat-mine').length,
    start: !!document.querySelector('#wr-start'),
    leave: !!document.querySelector('#wr-leave'),
  };
});

for (const [engine, driver] of Object.entries(ENGINES)) {
  let browser;
  try {
    browser = await driver.launch();
    const ctx = await browser.newContext({ viewport: { width: 1400, height: 1000 } });
    const page = await ctx.newPage();
    await login(page, CRED);

    await page.goto(BASE + 'SharedUI/MainMenu.php', { waitUntil: 'load' });
    await page.waitForTimeout(600);

    // The menu's dropdowns write a hidden #swu-format-select, which selectOption cannot target.
    const reachable = await page.evaluate((f) => swuSelectFormat(f, '', false), 'twinsuns');
    ok(engine, 'the menu offers a path to twinsuns', reachable);

    // Join Queue must be OFFERED for Twin Suns now — it is hidden for a pool without publicQueue,
    // so this is the menu half of the feature.
    const joinVisible = await page.isVisible('#join-queue-btn').catch(() => false);
    ok(engine, 'Join Queue is offered for Twin Suns', joinVisible);

    await page.click('#tab-text');
    await page.fill('#deck-text', DECK);
    await Promise.all([
      page.waitForURL(/WaitingRoom\.php/, { timeout: 20000 }),
      page.click('#join-queue-btn'),
    ]);
    ok(engine, 'a public Twin Suns join lands on the waiting room', /WaitingRoom\.php/.test(page.url()));

    await page.waitForTimeout(1800);   // let the first poll land
    const s = await seatState(page);
    // THE POINT OF THIS HARNESS: seated, not just present. mine===0 is the localStorage handoff bug.
    ok(engine, 'the joiner HOLDS a seat (authKey handoff survived)', s.mine === 1, JSON.stringify(s));
    ok(engine, 'the room draws 4 seats, not 2', s.seats === 4, JSON.stringify(s));
    ok(engine, 'the creator sees a Start control (they are the host)', s.start === true, JSON.stringify(s));

    // Always release the lobby — a stray public room would pair with the next run (10 min TTL).
    await page.click('#wr-leave').catch(() => {});
    await page.waitForTimeout(600);
    await ctx.close();
  } catch (e) {
    ok(engine, 'harness ran', false, String(e && e.message ? e.message : e));
  } finally {
    if (browser) await browser.close().catch(() => {});
  }
}

for (const [engine, name, pass, extra] of results) {
  console.log(`${pass ? 'ok  ' : 'BAD '} [${engine}] ${name}${pass || !extra ? '' : '  ' + extra}`);
}
console.log(allOk ? '\nALL PASS' : '\nFAILURES');
process.exit(allOk ? 0 : 1);
