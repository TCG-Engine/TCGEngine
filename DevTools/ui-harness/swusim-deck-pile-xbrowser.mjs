// Cross-browser check for the deck piles (player report after a Twin Suns game, 2026-09-21):
//   "can't see own deck size/count; and the stacked image starts overlapping the discard on the right".
// Visual case: SWUSim/Tests/Visual/DeckPile_CountAndNoOverlap.md
//
// Asserts, on REAL games (a guest Goldfish game, and a 3-seat Twin Suns room started from the menu):
//   the own deck pile shows a count badge equal to the zone data's count ("CardBack <n> -");
//   the deck's stacked layers end before the discard pile starts (desktop);
//   the hand never runs under the pile row (desktop); no horizontal page overflow.
// Usage: node swusim-deck-pile-xbrowser.mjs [baseURL]   ENGINES=chromium,firefox,webkit   SKIP_TWINSUNS=1
import { chromium, firefox, webkit } from 'playwright';
import fs from 'node:fs';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const MENU = BASE + 'SharedUI/Sites/SWUSim/MainMenu.php';
const OUT = process.env.OUT || '/tmp';
const PREMIER = fs.readFileSync(new URL('../../SWUSim/Tests/BotFixtures/premier_deck_a.txt', import.meta.url), 'utf8');
const TWIN = fs.readFileSync(new URL('../../SWUSim/Tests/BotFixtures/twinsuns_deck_a.txt', import.meta.url), 'utf8');
const ALL = { chromium, firefox, webkit };
const ENGINES = Object.fromEntries(Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n)));
let allOk = true;
const results = [];
const ok = (engine, name, cond, extra = '') => { if (!cond) allOk = false; results.push([engine, name, !!cond, extra]); };

const probe = (page) => page.evaluate(() => {
  const data = String(window.myDeckData || '').trim();
  const want = data ? parseInt(data.split(' ')[1], 10) : 0;
  const slot = document.getElementById('myDeckSlot');
  const badge = slot && slot.parentElement ? slot.parentElement.querySelector(':scope > .swu-pile-count') : null;
  const visible = !!badge && !badge.hidden && badge.getBoundingClientRect().width > 0 && getComputedStyle(badge).display !== 'none';
  const stack = document.querySelector('#myPileRow #myDeckSlot .tcg-single-zone-stack');
  const piles = [...document.querySelectorAll('#myPileRow > .swu-pile')].map(e => e.getBoundingClientRect());
  // The hand PANEL's edge, not its card images: the cards scroll inside the panel's wrapper (overflow-x: auto), so an
  // off-screen card reports an unclipped rect far past the panel even though nothing is drawn there.
  const handPanel = document.getElementById('myHandSlot');
  const mobile = !!document.getElementById('swuMobileRoot') && getComputedStyle(document.getElementById('swuMobileRoot')).display !== 'none';
  return {
    want, badge: badge ? badge.textContent : null, visible,
    desktop: !mobile && !!document.getElementById('myPileRow') && piles.length === 2 && piles[0].width > 0,
    stackRight: stack ? stack.getBoundingClientRect().right : null, discardLeft: piles[1] ? piles[1].left : null,
    handRight: handPanel ? handPanel.getBoundingClientRect().right : 0, rowLeft: piles[0] ? piles[0].left : null,
    overflow: document.documentElement.scrollWidth - document.documentElement.clientWidth,
  };
});

function checkBoard(engine, tag, s) {
  ok(engine, `${tag}: the deck pile shows its count`, s.visible && s.want > 0 && s.badge === String(s.want), `badge=${s.badge} data=${s.want} visible=${s.visible}`);
  if (s.desktop) {
    ok(engine, `${tag}: the deck's stacked layers stop before the discard`, s.stackRight !== null && s.stackRight <= s.discardLeft + 0.5, `${s.stackRight} vs ${s.discardLeft}`);
    ok(engine, `${tag}: the hand stays clear of the pile row`, s.handRight <= s.rowLeft + 0.5, `${s.handRight} vs ${s.rowLeft}`);
  }
  ok(engine, `${tag}: no horizontal overflow`, s.overflow <= 0, String(s.overflow));
}

for (const [engine, driver] of Object.entries(ENGINES)) {
  let browser;
  try {
    browser = await driver.launch();
    // ── 2-player: a guest Goldfish game, desktop and phone ──
    for (const [w, h] of [[1728, 1000], [1280, 800], [390, 844]]) {
      const page = await (await browser.newContext({ viewport: { width: w, height: h } })).newPage();
      await page.goto(MENU, { waitUntil: 'load' });
      await page.selectOption('#swu-gametype-select', 'solo'); await page.selectOption('#swu-second-select', 'goldfish');
      await page.evaluate(() => switchDeckTab('text')); await page.fill('#deck-text', PREMIER);
      await Promise.all([page.waitForURL(/NextTurn/, { timeout: 30000 }), page.click('#start-solo-btn')]);
      await page.waitForFunction(() => String(window.myDeckData || '').trim() !== '', null, { timeout: 15000 }).catch(() => {});
      await page.waitForTimeout(800);
      checkBoard(engine, `2P ${w}px`, await probe(page));
      await page.screenshot({ path: `${OUT}/deck-pile-${engine}-2p-${w}.png` });
      await page.context().close();
    }
    // ── Twin Suns: a 3-seat room started from the menu (the reported case: a 74-card deck) ──
    if (!process.env.SKIP_TWINSUNS) {
      const host = await (await browser.newContext({ viewport: { width: 1728, height: 1000 } })).newPage();
      await host.goto(MENU, { waitUntil: 'load' });
      await host.selectOption('#swu-gametype-select', 'twinsuns'); await host.selectOption('#swu-second-select', 'ffa');
      await host.evaluate(() => switchDeckTab('text')); await host.fill('#deck-text', TWIN);
      await Promise.all([host.waitForURL(/WaitingRoom/, { timeout: 30000 }), host.click('#create-private-game-btn')]);
      await host.waitForFunction(() => /[0-9a-f]{16,}/i.test((document.getElementById('wr-invite') || {}).textContent || ''), null, { timeout: 15000 });
      const invite = await host.evaluate(() => document.getElementById('wr-invite').textContent.match(/[0-9a-f]{16,}/i)[0]);
      for (let j = 0; j < 2; j++) {
        const g = await (await browser.newContext()).newPage();
        await g.goto(MENU + '?privateInvite=' + invite, { waitUntil: 'load' }); await g.waitForTimeout(900);
        await g.evaluate(() => switchDeckTab('text')); await g.fill('#deck-text', TWIN);
        await Promise.all([g.waitForURL(/WaitingRoom/, { timeout: 30000 }).catch(() => {}), g.click('#join-private-invite-btn')]);
      }
      await host.waitForFunction(() => { const s = document.getElementById('wr-start'); return s && !s.disabled; }, null, { timeout: 20000 });
      await Promise.all([host.waitForURL(/NextTurn/, { timeout: 30000 }), host.click('#wr-start')]);
      await host.waitForFunction(() => String(window.myDeckData || '').trim() !== '', null, { timeout: 15000 }).catch(() => {});
      await host.waitForTimeout(1500);
      const s = await probe(host);
      checkBoard(engine, 'Twin Suns 1728px', s);
      ok(engine, 'Twin Suns: it is the big deck the report was about (70+ cards)', s.want >= 70, String(s.want));
      await host.screenshot({ path: `${OUT}/deck-pile-${engine}-twinsuns.png` });
    }
  } catch (e) {
    ok(engine, 'harness ran', false, String(e && e.message ? e.message : e));
  } finally {
    if (browser) await browser.close().catch(() => {});
  }
}
for (const [engine, name, pass, extra] of results) console.log(`${pass ? 'ok  ' : 'BAD '} [${engine}] ${name}${pass || !extra ? '' : '  ' + extra}`);
console.log(allOk ? '\nALL PASS' : '\nFAILURES');
process.exit(allOk ? 0 : 1);
