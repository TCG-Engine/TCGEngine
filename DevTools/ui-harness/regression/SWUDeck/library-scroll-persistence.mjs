// Regression: the mobile library pane (left "Cards" browse list) must KEEP its scroll position
// when you tap a card to add it. Reported from a real phone: "every time I click on a card, it
// always goes back to the top of the list."
//
// Mechanism this pins down — clicking a card is a server round-trip, and the response rebuilds the
// whole pane (`myCardPaneSlot.innerHTML = ... PopulateZone(...)` in SWUDeck/NextTurnRender.php).
// The rebuilt tiles are <img> elements with no intrinsic height until they load, so for the first
// few hundred ms `scrollHeight ≈ clientHeight` and the restore in bindMobileLibraryScroll() clamps
// its target to `max(0, scrollHeight - clientHeight)` = 0 — i.e. it restores to the top and never
// retries once the images land and the pane grows back.
//
// WHY THE SLOW-IMAGE ROUTE IS LOAD-BEARING: with a warm cache the tiles lay out in the same frame
// and the bug does NOT reproduce (verified — Chromium warm-cache restores correctly). Delaying the
// card images reproduces the phone's cold-cache/cellular timing deterministically. Without this
// route the suite would pass while the bug was fully present.
//
// MUTATES THE DECK (a tap ADDS a card), so it runs against a scratch deck and restores the
// Gamestate file afterwards — never point GAME at a real deck.
import { readFileSync, writeFileSync, existsSync } from 'node:fs';
import { ENGINES, login, openBoard, mobileContextOpts, harness, EnvError } from '../lib.mjs';

const GAME = process.env.GAME || '900001';
const CONTENT = '#my_CardPane_content';
const TARGET = Number(process.env.TARGET || 600);
const IMAGE_DELAY = Number(process.env.IMAGE_DELAY || 400);
const REPO = new URL('../../../../', import.meta.url).pathname;
const STATE = `${REPO}SWUDeck/Games/${GAME}/Gamestate.txt`;

const only = (process.env.ENGINES || 'chromium,firefox').split(',').map(s => s.trim()).filter(Boolean);
const SUITE_ENGINES = Object.fromEntries(only.map(n => [n, ENGINES[n]]));

if (!existsSync(STATE)) {
  throw new EnvError(
    `scratch deck ${GAME} missing (${STATE}).\n` +
    `  Create one:  cp -r SWUDeck/Games/100431 SWUDeck/Games/${GAME}\n` +
    `  and insert an ownership row for it owned by the Drixx user (assetType=1, assetOwner=<Drixx usersId>).`);
}

await harness(async (check) => {
  for (const [name, engine] of Object.entries(SUITE_ENGINES)) {
    if (!engine) { check(`${name}: engine available`, false, 'unknown engine name'); continue; }
    console.log(`\n=== ${name} (target ${TARGET}px, image delay ${IMAGE_DELAY}ms) ===`);
    const snapshot = readFileSync(STATE);
    const browser = await engine.launch();
    try {
      const ctx = await browser.newContext(mobileContextOpts());
      // Cold-cache timing: tiles must be height-less while the pane is rebuilt.
      await ctx.route(/\.(webp|png|jpg)(\?|$)/i, async (route) => {
        await new Promise(r => setTimeout(r, IMAGE_DELAY));
        await route.continue();
      });
      const page = await ctx.newPage();
      await login(page);
      await openBoard(page, { game: GAME, mobile: true });
      await page.evaluate(() => window.SWUDeckMobileSetPane && window.SWUDeckMobileSetPane('search'));

      // The browse pane opens on the "Leaders" tab, whose click action sets the deck's LEADER.
      // The reported bug is about the "Cards" library, so switch tabs first — otherwise the tap
      // mutates the leader, the deck count never moves, and the scroll check is vacuous.
      const onCards = await page.evaluate(() => {
        const tab = [...document.querySelectorAll('#myCardPane .panelTab, .panelTab')]
          .find(t => t.textContent.trim() === 'Cards');
        if (!tab) return false;
        tab.click();
        return true;
      });
      check(`${name}: switched to the Cards tab`, onCards);
      await page.waitForFunction(() => !!document.querySelector('#my_CardPane_content #myCards'), null, { timeout: 15000 });

      // Wait for the library to be tall enough to scroll by TARGET, rather than sleeping.
      await page.waitForFunction(
        (a) => { const c = document.querySelector(a.sel); return c && c.scrollHeight - c.clientHeight > a.target; },
        { sel: CONTENT, target: TARGET }, { timeout: 20000 });

      await page.evaluate((a) => { document.querySelector(a.sel).scrollTop = a.target; }, { sel: CONTENT, target: TARGET });
      await page.waitForFunction((a) => document.querySelector(a.sel).scrollTop === a.target,
        { sel: CONTENT, target: TARGET }, { timeout: 5000 });

      const deckBefore = await page.evaluate(() => {
        const m = String(document.getElementById('myDeckSlot')?.textContent || '').match(/deck\s*count\s*:\s*(\d+)/i);
        return m ? Number(m[1]) : -1;
      });

      // Tap a card that is actually on screen at this scroll offset.
      const clicked = await page.evaluate((sel) => {
        const c = document.querySelector(sel);
        const els = [...c.querySelectorAll("a[onmouseover*='ShowCardDetail'], img[alt='Card']")]
          .filter(e => { const r = e.getBoundingClientRect();
                         return r.width > 20 && r.top > 0 && r.top < window.innerHeight; });
        const el = els[Math.floor(els.length / 2)];
        if (!el) return false;
        el.click();
        return true;
      }, CONTENT);
      check(`${name}: a card was on screen to tap`, clicked);

      // The pane rebuild + image loads must fully settle before we judge the scroll position:
      // wait until the pane has grown back past our target, so we are not reading a mid-rebuild value.
      await page.waitForFunction(
        (a) => { const c = document.querySelector(a.sel); return c && c.scrollHeight - c.clientHeight > a.target; },
        { sel: CONTENT, target: TARGET }, { timeout: 20000 }).catch(() => {});
      await page.waitForTimeout(1200);

      // Guard against a vacuous pass: if the tap did not actually add a card (validation refused,
      // deck full, selector drifted), the scroll assertion below would be meaningless.
      const deckAfter = await page.evaluate(() => {
        const m = String(document.getElementById('myDeckSlot')?.textContent || '').match(/deck\s*count\s*:\s*(\d+)/i);
        return m ? Number(m[1]) : -1;
      });
      check(`${name}: the tap actually added a card (deck ${deckBefore} -> ${deckAfter})`, deckAfter > deckBefore);

      const after = await page.evaluate((sel) => {
        const c = document.querySelector(sel);
        return { top: Math.round(c.scrollTop), max: Math.max(0, c.scrollHeight - c.clientHeight) };
      }, CONTENT);

      // Allow a small drift (the pane's own height can shift by a row), but nothing like a reset.
      const kept = Math.abs(after.top - TARGET) <= 80;
      check(`${name}: library kept its scroll position`, kept,
        `expected ~${TARGET}, got ${after.top} (max ${after.max})`);
    } finally {
      await browser.close();
      writeFileSync(STATE, snapshot);   // restore the scratch deck for the next run
    }
  }
});
