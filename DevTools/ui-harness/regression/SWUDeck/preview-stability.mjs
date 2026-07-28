// Regression: after a LONG hold (>2s, outliving the 900ms synthetic-mouse guard) and release,
// the preview must stay stably visible — not flicker in and out — and must not intercept
// pointer events (which is what lets it ping-pong with the card's hover handlers).
// (The JS flicker state-machine is verifiable here via synthetic events; real iOS is not.)
import { ENGINES, login, openBoard, mobileContextOpts, harness } from '../lib.mjs';

const GAME = process.env.GAME || '201009';
const HOLD = Number(process.env.HOLD || 2500);
const SUITE_ENGINES = { chromium: ENGINES.chromium, webkit: ENGINES.webkit };

await harness(async (check) => {
  for (const [name, engine] of Object.entries(SUITE_ENGINES)) {
    console.log(`\n=== ${name} (hold ${HOLD}ms) ===`);
    const browser = await engine.launch();
    const ctx = await browser.newContext(mobileContextOpts());
    const page = await ctx.newPage();
    await login(page);
    await openBoard(page, { game: GAME, mobile: true, hoverReady: true });

    // exercise the first three cards — the reported glitch was position-dependent
    for (const idx of [0, 1, 2]) {
      const pos = await page.evaluate((i) => {
        document.querySelectorAll('[data-lp]').forEach(e => e.removeAttribute('data-lp'));
        const a = [...document.querySelectorAll("a[onmouseover*='ShowCardDetail']")][i];
        if (!a) return null;
        const img = a.querySelector('img');
        img.setAttribute('data-lp', '1');
        const r = img.getBoundingClientRect();
        return { x: Math.round(r.x + r.width / 2), y: Math.round(r.y + r.height / 2) };
      }, idx);
      if (!pos) { check(`card ${idx}: present`, false); continue; }

      const pt = [{ x: pos.x, y: pos.y, identifier: 0 }];
      await page.dispatchEvent('[data-lp]', 'touchstart', { touches: pt, targetTouches: pt, changedTouches: pt });
      await page.waitForTimeout(HOLD);   // outlive suppressMouseCardDetailUntil (900ms)
      await page.dispatchEvent('[data-lp]', 'touchend', { touches: [], targetTouches: [], changedTouches: pt });

      // the synthetic mouse events a touch platform emits after the sequence
      await page.evaluate((p) => {
        const el = document.elementFromPoint(p.x, p.y);
        if (!el) return;
        ['mousemove', 'mouseover', 'mouseout', 'mouseover'].forEach(t =>
          el.dispatchEvent(new MouseEvent(t, { bubbles: true, clientX: p.x, clientY: p.y })));
      }, pos);

      // sample visibility repeatedly: a flicker shows up as mixed true/false
      const samples = await page.evaluate(async () => {
        const el = document.getElementById('cardDetail');
        const out = [];
        for (let i = 0; i < 12; i++) {
          out.push(getComputedStyle(el).display !== 'none');
          await new Promise(r => setTimeout(r, 100));
        }
        return { out, pe: getComputedStyle(el).pointerEvents };
      });
      const stable = samples.out.every(v => v === samples.out[0]);
      check(`card ${idx}: preview stable (no flicker)`, stable, samples.out.map(v => v ? '1' : '0').join(''));
      check(`card ${idx}: visible after long hold`, samples.out[samples.out.length - 1] === true);
      check(`card ${idx}: preview does not intercept pointer`, samples.pe === 'none', samples.pe);

      // reset for the next card
      await page.dispatchEvent('body', 'touchstart', { touches: pt, targetTouches: pt, changedTouches: pt });
      await page.waitForTimeout(400);
    }
    await browser.close();
  }
});
