// Regression: the persistent TOUCH preview carries an explicit close (X), and double-sided cards
// carry a flip to the opposite face.
//
// Why the flip exists: SWU leaders are double-sided and the deployed Leader Unit side ships as
// "<CardID>_back" in the shared art corpus. On a phone there was no way to see it at all.
// Which cards get the button is decided by an ASSET PROBE (Core/jsInclude.js), not a leader list,
// so this suite pins BOTH outcomes — a leader offers the flip, an ordinary card must not.
//
// The two mechanics most likely to silently break:
//   * #cardDetail is pointer-events:none on touch, so a control that forgets pointer-events:auto
//     renders fine and is completely untappable. Asserted via the computed value AND a real tap.
//   * BeginCardDetailLongPress is a document-level CAPTURE touchstart handler that dismisses the
//     preview on any tap; without its [data-card-detail-control] exemption the flip button would
//     dismiss instead of flipping. Asserted by tapping flip and requiring the preview to SURVIVE.
import { ENGINES, login, openBoard, mobileContextOpts, desktopContextOpts, harness } from '../lib.mjs';

const GAME = process.env.GAME || '100431';
const only = (process.env.ENGINES || 'chromium,firefox').split(',').map(s => s.trim()).filter(Boolean);
const SUITE_ENGINES = Object.fromEntries(only.map(n => [n, ENGINES[n]]));
const CLOSE = '#cardDetail [data-card-detail-control="close"]';
const FLIP = '#cardDetail [data-card-detail-control="flip"]';

// Long-press the nth on-screen card in the browse pane, mirroring touch-preview.mjs.
async function longPress(page, index = 0) {
  const pos = await page.evaluate((i) => {
    document.querySelectorAll('[data-lp]').forEach(e => e.removeAttribute('data-lp'));
    const a = [...document.querySelectorAll("#my_CardPane_content a[onmouseover*='ShowCardDetail']")]
      .filter(el => { const r = el.getBoundingClientRect();
                      return r.width > 20 && r.top > 0 && r.top < window.innerHeight; })[i];
    if (!a) return null;
    a.querySelector('img').setAttribute('data-lp', '1');
    const r = a.getBoundingClientRect();
    return { x: Math.round(r.x + r.width / 2), y: Math.round(r.y + r.height / 2) };
  }, index);
  if (!pos) return null;
  const pt = [{ x: pos.x, y: pos.y, identifier: 0 }];
  await page.dispatchEvent('[data-lp]', 'touchstart', { touches: pt, targetTouches: pt, changedTouches: pt });
  await page.waitForTimeout(700);   // outlive CARD_DETAIL_LONG_PRESS_MS (430)
  await page.dispatchEvent('[data-lp]', 'touchend', { touches: [], targetTouches: [], changedTouches: pt });
  await page.waitForTimeout(600);   // let the async face probe resolve
  return pos;
}

const previewSrc = (page) => page.evaluate(() => {
  const i = document.querySelector('#cardDetail img');
  return i ? i.getAttribute('src') : null;
});
const previewOpen = (page) => page.evaluate(() =>
  getComputedStyle(document.getElementById('cardDetail')).display !== 'none');

await harness(async (check) => {
  for (const [name, engine] of Object.entries(SUITE_ENGINES)) {
    if (!engine) { check(`${name}: engine available`, false, 'unknown engine name'); continue; }
    console.log(`\n=== ${name} ===`);
    const browser = await engine.launch();
    try {
      const page = await (await browser.newContext(mobileContextOpts())).newPage();
      await login(page);
      await openBoard(page, { game: GAME, mobile: true, hoverReady: true });
      await page.evaluate(() => window.SWUDeckMobileSetPane && window.SWUDeckMobileSetPane('search'));

      // ── A leader (the browse pane opens on the Leaders tab) ─────────────────────────────────
      const got = await longPress(page, 0);
      check(`${name}: long-pressed a leader`, !!got);
      check(`${name}: preview open`, await previewOpen(page));
      check(`${name}: close button present`, await page.locator(CLOSE).count() === 1);
      check(`${name}: flip button offered for a leader`, await page.locator(FLIP).count() === 1);

      if (await page.locator(FLIP).count() === 1) {
        check(`${name}: flip is tappable (pointer-events)`,
          await page.locator(FLIP).evaluate(el => getComputedStyle(el).pointerEvents) === 'auto');
        check(`${name}: flip labelled for the unit side`,
          (await page.locator(FLIP).textContent()).includes('Leader Unit'));

        const front = await previewSrc(page);

        // Arm the click suppressor deterministically first. It is normally armed for 700ms after a
        // long-press — exactly when the player reaches for this button — and a control that is not
        // exempt from it renders, highlights, and does nothing. Timing-independent by construction.
        // ONE gesture only: tap() does emit a click here, so tap-then-click would flip twice and
        // land back on the front, reading as "broken" when it works.
        await page.evaluate(() => { window.suppressNextCardDetailClickUntil = Date.now() + 5000; });
        await page.locator(FLIP).tap();
        await page.waitForTimeout(800);
        check(`${name}: preview survives the flip tap`, await previewOpen(page));
        const back = await previewSrc(page);
        check(`${name}: flipped to the Leader Unit side`, !!back && /_back\.webp/i.test(back),
          `${front} -> ${back}`);
        check(`${name}: label flips back`,
          (await page.locator(FLIP).textContent()).includes('See Leader side'));

        await page.locator(FLIP).tap();
        await page.waitForTimeout(800);
        check(`${name}: flips back to the leader side`, (await previewSrc(page)) === front);
      }

      // Disarm: the suppressor was armed 5s into the future above, and it would otherwise swallow
      // the tab click below — a stray failure that looks nothing like its cause.
      await page.evaluate(() => { window.suppressNextCardDetailClickUntil = 0; });

      // ── Close button dismisses ──────────────────────────────────────────────────────────────
      await page.locator(CLOSE).tap();
      await page.waitForTimeout(400);
      check(`${name}: X closes the preview`, !(await previewOpen(page)));

      // ── An ordinary card must NOT offer a flip ──────────────────────────────────────────────
      await page.evaluate(() => {
        const t = [...document.querySelectorAll('.panelTab')].find(x => x.textContent.trim() === 'Cards');
        if (t) t.click();
      });
      await page.waitForFunction(() => !!document.querySelector('#my_CardPane_content #myCards'), null, { timeout: 15000 });
      await page.waitForTimeout(800);
      await longPress(page, 0);
      check(`${name}: preview open on an ordinary card`, await previewOpen(page));
      check(`${name}: close button still present`, await page.locator(CLOSE).count() === 1);
      check(`${name}: NO flip button on a non-double-sided card`, await page.locator(FLIP).count() === 0);

      // ── Desktop hover preview must be untouched (controls are a touch affordance) ───────────
      const dpage = await (await browser.newContext(desktopContextOpts())).newPage();
      await login(dpage);
      await openBoard(dpage, { game: GAME, hoverReady: true });
      // Dispatch mouseover directly, as touch-preview.mjs does: a real Playwright hover is
      // intercepted by #swuDeckBoard on this layout.
      await dpage.evaluate(() => {
        const a = [...document.querySelectorAll("a[onmouseover*='ShowCardDetail']")]
          .find(el => el.getBoundingClientRect().width > 20);
        const r = a.getBoundingClientRect();
        a.dispatchEvent(new MouseEvent('mouseover', { bubbles: true, clientX: r.x + 5, clientY: r.y + 5 }));
      });
      await dpage.waitForFunction(() =>
        getComputedStyle(document.getElementById('cardDetail')).display !== 'none', null, { timeout: 10000 });
      check(`${name}: desktop hover preview still renders`, await previewOpen(dpage));
      check(`${name}: desktop hover shows NO controls`,
        await dpage.locator('#cardDetail [data-card-detail-control]').count() === 0);
    } finally {
      await browser.close();
    }
  }
});
