// Regression: a SECOND long-press preview must survive the synthetic mouseout that iOS fires
// when the finger moves between cards. Every card carries inline onmouseout='HideCardDetail()'
// (UILibraries:297) — an unforced call that must not kill a persistent touch preview.
// (JS event-handling logic is verifiable here; the real iOS gesture is not — see README.)
import { ENGINES, login, openBoard, mobileContextOpts, harness } from '../lib.mjs';

const GAME = process.env.GAME || '201009';
const SUITE_ENGINES = { chromium: ENGINES.chromium, webkit: ENGINES.webkit };

const isVisible = () => {
  const el = document.getElementById('cardDetail');
  return !!el && getComputedStyle(el).display !== 'none';
};

await harness(async (check) => {
  for (const [name, engine] of Object.entries(SUITE_ENGINES)) {
    console.log(`\n=== ${name} ===`);
    const browser = await engine.launch();
    const ctx = await browser.newContext(mobileContextOpts());
    const page = await ctx.newPage();
    await login(page);
    await openBoard(page, { game: GAME, mobile: true, hoverReady: true });

    // tag two distinct cards
    const cards = await page.evaluate(() => {
      const els = [...document.querySelectorAll("a[onmouseover*='ShowCardDetail']")]
        .filter(el => { const r = el.getBoundingClientRect(); return r.width > 20 && r.top > 60 && r.bottom < innerHeight; });
      if (els.length < 2) return null;
      els[0].querySelector('img')?.setAttribute('data-c1', '1');
      els[1].querySelector('img')?.setAttribute('data-c2', '1');
      const box = el => { const r = el.getBoundingClientRect(); return { x: Math.round(r.x + r.width / 2), y: Math.round(r.y + r.height / 2) }; };
      return { a: box(els[0]), b: box(els[1]) };
    });
    check('found two distinct cards', !!cards);
    if (!cards) { await browser.close(); continue; }

    const press = async (sel, p) => {
      const pt = [{ x: p.x, y: p.y, identifier: 0 }];
      await page.dispatchEvent(sel, 'touchstart', { touches: pt, targetTouches: pt, changedTouches: pt });
      await page.waitForTimeout(700);
      await page.dispatchEvent(sel, 'touchend', { touches: [], targetTouches: [], changedTouches: pt });
      await page.waitForTimeout(250);
    };

    // 1st long press
    await press('[data-c1]', cards.a);
    check('1st preview visible', await page.evaluate(isVisible));

    // dismiss with a tap
    const ptA = [{ x: cards.a.x, y: cards.a.y, identifier: 0 }];
    await page.dispatchEvent('body', 'touchstart', { touches: ptA, targetTouches: ptA, changedTouches: ptA });
    await page.waitForTimeout(300);
    check('dismissed', !(await page.evaluate(isVisible)));

    // 2nd long press on a DIFFERENT card, then the synthetic mouseout iOS fires on the first card
    await press('[data-c2]', cards.b);
    check('2nd preview visible immediately', await page.evaluate(isVisible));

    await page.evaluate(() => {
      const c1 = document.querySelector('[data-c1]');
      if (c1) c1.closest('a').dispatchEvent(new MouseEvent('mouseout', { bubbles: true }));
    });
    await page.waitForTimeout(300);
    check('2nd preview SURVIVES synthetic mouseout', await page.evaluate(isVisible));

    await browser.close();
  }
});
