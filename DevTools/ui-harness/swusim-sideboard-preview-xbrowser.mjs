// SWUSim sideboard — the card preview waits (owner, 2026-11-04: a click, above all a tap on a phone, left the preview open
// over the other cards). Chromium + Firefox + WebKit:
//   desktop (mouse)  · hovering shows nothing at first, the preview after the delay; · a click hides it and moves the card;
//                    · leaving the card hides it;
//   phone (touch)    · a tap moves the card and never opens the preview; · holding a finger opens it, lifting hides it,
//                    and that press does not move the card.
// The match comes from SWUSim/DevTools/tests/draw_endscreen_fixture.php?queue=bo3&scenario=p2won (seat 1 sideboarding).
//
// Usage: node swusim-sideboard-preview-xbrowser.mjs [BASE] [engines]
import { chromium, firefox, webkit } from 'playwright';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const ONLY = (process.argv[3] || 'chromium,firefox,webkit').split(',');
const ENGINES = Object.fromEntries(Object.entries({ chromium, firefox, webkit }).filter(([n]) => ONLY.includes(n)));

let failures = 0;
const ok  = (where, msg) => console.log(`  ok   ${where}: ${msg}`);
const bad = (where, msg) => { failures++; console.log(`  FAIL ${where}: ${msg}`); };
const expect = (where, cond, msg, detail) => cond ? ok(where, msg) : bad(where, msg + ' — ' + JSON.stringify(detail));

const state = (page) => page.evaluate(() => {
  const p = document.getElementById('sbPreview');
  return { shown: !!p && p.style.display === 'block',
           deck: document.getElementById('deckCount').textContent, side: document.getElementById('sideCount').textContent };
});

for (const [eng, type] of Object.entries(ENGINES)) {
  const browser = await type.launch();
  try {
    const api = await browser.newContext();
    for (const touch of [false, true]) {
      const where = `${eng}/${touch ? 'phone' : 'desktop'}`;
      const fx = await (await api.request.get(`${BASE}SWUSim/DevTools/tests/draw_endscreen_fixture.php?queue=bo3&scenario=p2won`)).json();
      if (!fx.matchId || fx.match !== 'sideboarding') { bad(where, 'fixture failed: ' + JSON.stringify(fx)); continue; }
      const ctx = await browser.newContext(touch ? { viewport: { width: 390, height: 844 }, hasTouch: true, ...(eng === 'firefox' ? {} : { isMobile: true }) }
                                                 : { viewport: { width: 1280, height: 800 } });
      const page = await ctx.newPage();
      await page.goto(`${BASE}SWUSim/Sideboard.php?matchId=${fx.matchId}&playerID=1&authKey=${fx.keys[1]}`, { waitUntil: 'domcontentloaded', timeout: 90000 });
      await page.waitForSelector('#deckGrid .card', { timeout: 60000 });
      await page.waitForTimeout(500);
      const card = page.locator('#deckGrid .card').first();
      await card.scrollIntoViewIfNeeded();
      const before = await state(page);

      if (!touch) {
        const box = await card.boundingBox();
        await page.mouse.move(box.x + box.width / 2, box.y + box.height / 2, { steps: 3 });
        await page.waitForTimeout(150);
        let s = await state(page);
        expect(where, !s.shown, 'a fresh hover shows no preview yet', s);
        await page.waitForTimeout(700);
        s = await state(page);
        expect(where, s.shown, 'the preview opens after the delay', s);
        await page.mouse.down(); await page.mouse.up();
        await page.waitForTimeout(150);
        s = await state(page);
        expect(where, !s.shown, 'a click hides the preview', s);
        expect(where, s.deck !== before.deck && s.side !== before.side, 'and moves the card', { before, s });
        await page.waitForTimeout(700);   // still over the grid: the preview may come back, but leaving must hide it
        await page.mouse.move(5, 5, { steps: 3 });
        await page.waitForTimeout(150);
        s = await state(page);
        expect(where, !s.shown, 'leaving the card hides the preview', s);
      } else {
        await card.tap();
        await page.waitForTimeout(900);
        let s = await state(page);
        expect(where, !s.shown, 'a tap never opens the preview', s);
        expect(where, s.deck !== before.deck, 'a tap moves the card', { before, s });
        // A held finger: real touch pointer events in order (Playwright has no long-press), then the click that follows.
        const held = page.locator('#deckGrid .card').first();
        const mid = await state(page);
        await held.evaluate((el) => {
          const r = el.getBoundingClientRect(), o = { bubbles: true, pointerType: 'touch', isPrimary: true, clientX: r.x + 20, clientY: r.y + 20 };
          el.dispatchEvent(new PointerEvent('pointerdown', o));
          window.__sbHeld = o;
        });
        await page.waitForTimeout(250);
        s = await state(page);
        expect(where, !s.shown, 'a short hold shows nothing yet', s);
        await page.waitForTimeout(500);
        s = await state(page);
        expect(where, s.shown, 'holding the card opens the preview', s);
        const r = await page.evaluate(() => { const b = document.getElementById('sbPreview').getBoundingClientRect(); return { l: Math.round(b.left), r: Math.round(b.right), vw: innerWidth }; });
        expect(where, r.l >= 0 && r.r <= r.vw, 'the preview fits on the phone screen', r);
        await held.evaluate((el) => { el.dispatchEvent(new PointerEvent('pointerup', window.__sbHeld)); el.click(); });
        await page.waitForTimeout(150);
        s = await state(page);
        expect(where, !s.shown, 'lifting the finger hides it', s);
        expect(where, s.deck === mid.deck && s.side === mid.side, 'and the held card did not move', { mid, s });
        await card.tap();
        await page.waitForTimeout(200);
        s = await state(page);
        expect(where, s.deck !== mid.deck, 'the next tap moves a card again', { mid, s });
      }
      await ctx.close();
    }
  } catch (e) { bad(eng, String(e && e.stack || e)); }
  await browser.close();
}
console.log(failures ? `FAIL (${failures})` : 'PASS');
process.exit(failures ? 1 : 0);
