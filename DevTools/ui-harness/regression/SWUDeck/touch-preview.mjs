// Stage 2 verification: touch preview is viewport-fitted, centered, persists past touchend,
// dismisses on the next tap without mutating the deck — and desktop hover is UNCHANGED.
// (Desktop hover + geometry are fully verifiable; the touch persistence path is simulated — see README.)
import { ENGINES, login, openBoard, mobileContextOpts, desktopContextOpts, harness } from '../lib.mjs';

const GAME = process.env.GAME || '201009';

const readDetail = () => {
  const el = document.getElementById('cardDetail');
  const scrim = document.getElementById('cardDetailScrim');
  const img = el && el.querySelector('img');
  const r = el ? el.getBoundingClientRect() : null;
  return {
    visible: !!el && getComputedStyle(el).display !== 'none',
    src: img ? img.src.split('/').slice(-2).join('/') : null,
    w: r ? Math.round(r.width) : 0, h: r ? Math.round(r.height) : 0,
    x: r ? Math.round(r.x) : 0, y: r ? Math.round(r.y) : 0,
    scrimVisible: !!scrim && getComputedStyle(scrim).display !== 'none',
    scrimPointerEvents: scrim ? getComputedStyle(scrim).pointerEvents : null,
    vw: innerWidth, vh: innerHeight,
  };
};

// Drive the real long-press path: dispatch touchstart on a card, wait past 430ms, then touchend.
const longPress = async (page) => {
  const found = await page.evaluate(() => {
    document.querySelectorAll('[data-lp]').forEach(e => e.removeAttribute('data-lp'));
    const a = [...document.querySelectorAll("a[onmouseover*='ShowCardDetail']")]
      .find(el => { const r = el.getBoundingClientRect(); return r.width > 20 && r.top > 60 && r.bottom < innerHeight; });
    if (!a) return null;
    const img = a.querySelector('img');
    if (!img) return null;
    img.setAttribute('data-lp', '1');
    const r = img.getBoundingClientRect();
    return { x: Math.round(r.x + r.width / 2), y: Math.round(r.y + r.height / 2) };
  });
  if (!found) return null;
  const pt = [{ x: found.x, y: found.y, identifier: 0 }];
  await page.dispatchEvent('[data-lp]', 'touchstart', { touches: pt, targetTouches: pt, changedTouches: pt });
  await page.waitForTimeout(800);
  return { found, pt };
};

// Condition-wait until the card-detail preview has actually rendered (the 850ms ShowCardDetail
// delay + the full-res WebpImages load). Replaces fixed sleeps that assumed the image was ready —
// the source of this suite's flakiness once navigation stopped over-waiting.
const waitForPreview = (page) => page.waitForFunction(() => {
  const el = document.getElementById('cardDetail');
  return !!el && getComputedStyle(el).display !== 'none' && el.getBoundingClientRect().width > 0;
}, { timeout: 6000 }).catch(() => {});

await harness(async (check) => {
  for (const [name, engine] of Object.entries(ENGINES)) {
    console.log(`\n=== ${name} ===`);
    const browser = await engine.launch();

    // ---------- touch ----------
    const tctx = await browser.newContext(mobileContextOpts({ isMobile: name !== 'firefox' }));
    const page = await tctx.newPage();
    await login(page);
    await openBoard(page, { game: GAME, mobile: true, hoverReady: true });

    const lp = await longPress(page);
    check('found a long-pressable card', !!lp, lp ? `@${lp.found.x},${lp.found.y}` : 'none');

    if (lp) {
      await waitForPreview(page);   // wait for the preview to actually render before measuring
      const held = await page.evaluate(readDetail);
      check('preview visible while held', held.visible);
      check('renders full card (WebpImages)', !!held.src && held.src.startsWith('WebpImages'), held.src);
      check('fits viewport width', held.w > 0 && held.w <= held.vw, `${held.w} <= ${held.vw}`);
      check('fits viewport height', held.h > 0 && held.h <= held.vh, `${held.h} <= ${held.vh}`);
      check('not clipped at left edge', held.x >= 0, `x=${held.x}`);
      check('horizontally centered', Math.abs((held.x + held.w / 2) - held.vw / 2) <= 2,
        `center=${Math.round(held.x + held.w / 2)} vs ${held.vw / 2}`);
      check('scrim shown', held.scrimVisible);
      check('scrim does not block taps', held.scrimPointerEvents === 'none', held.scrimPointerEvents);

      // persistence past touchend
      await page.dispatchEvent('[data-lp]', 'touchend', { touches: [], targetTouches: [], changedTouches: lp.pt });
      await page.waitForTimeout(400);
      const lifted = await page.evaluate(readDetail);
      check('PERSISTS after finger lifts', lifted.visible);
      check('scrim still shown after lift', lifted.scrimVisible);
      // capture the preview WHILE it is up — the post-dismiss shot proves nothing
      await page.screenshot({ path: `/tmp/s2-open-${name}.png` });

      // next tap dismisses, and must not mutate the deck
      const before = await page.evaluate(() => document.body.innerText.match(/(\d+)\s*CARDS/i)?.[1] || null);
      await page.dispatchEvent('body', 'touchstart', { touches: lp.pt, targetTouches: lp.pt, changedTouches: lp.pt });
      await page.waitForTimeout(400);
      const dismissed = await page.evaluate(readDetail);
      check('next tap dismisses preview', !dismissed.visible);
      check('scrim hidden on dismiss', !dismissed.scrimVisible);
      await page.waitForTimeout(900);
      const after = await page.evaluate(() => document.body.innerText.match(/(\d+)\s*CARDS/i)?.[1] || null);
      check('dismiss tap did not change deck count', before === after, `${before} -> ${after}`);
      await page.screenshot({ path: `/tmp/s2-${name}.png` });
    }
    await tctx.close();

    // ---------- desktop hover regression ----------
    const dctx = await browser.newContext(desktopContextOpts());
    const dpage = await dctx.newPage();
    await login(dpage);
    await openBoard(dpage, { game: GAME, mobile: false, hoverReady: true });
    const dispatched = await dpage.evaluate(() => {
      const a = [...document.querySelectorAll("a[onmouseover*='ShowCardDetail']")]
        .find(el => el.getBoundingClientRect().width > 20);
      if (!a) return false;
      const r = a.getBoundingClientRect();
      a.dispatchEvent(new MouseEvent('mouseover', { bubbles: true, clientX: r.x + 5, clientY: r.y + 5 }));
      return true;
    });
    await waitForPreview(dpage);   // condition-wait for the preview instead of a fixed 1200ms
    const hovered = !dispatched ? null : await dpage.evaluate(() => {
      const el = document.getElementById('cardDetail');
      const scrim = document.getElementById('cardDetailScrim');
      const br = el.getBoundingClientRect();
      return {
        w: Math.round(br.width), h: Math.round(br.height),
        scrimVisible: !!scrim && getComputedStyle(scrim).display !== 'none',
      };
    });
    check('desktop hover preview renders', !!hovered, hovered ? `${hovered.w}x${hovered.h}` : 'none');
    if (hovered) {
      check('desktop keeps 400px cap', Math.max(hovered.w, hovered.h) === 400, `${hovered.w}x${hovered.h}`);
      check('desktop shows NO scrim', !hovered.scrimVisible);
    }
    await dctx.close();
    await browser.close();
  }
});
