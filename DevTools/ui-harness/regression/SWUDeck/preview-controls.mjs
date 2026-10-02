// Regression: the persistent TOUCH preview carries an explicit close (X); a LEADER shows BOTH faces.
//
// SWU leaders are double-sided and the deployed Leader Unit side ships as "<CardID>_back" in the shared
// art corpus. It used to be reachable on a phone through a flip button; since 2026-10-01 (owner) a leader
// previews both faces side by side / stacked (Core/jsInclude.js ShowLeaderFacesDetail), so this suite pins:
// a leader shows the pair with NO flip, and an ordinary card still gets no flip either.
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
  // Condition-based: wait for the preview to actually appear, not a fixed sleep. A pane re-render
  // (a tab switch is a server round-trip) can delay it past any constant we would pick.
  await page.waitForFunction(
    () => getComputedStyle(document.getElementById('cardDetail')).display !== 'none',
    null, { timeout: 10000 }).catch(() => {});
  await page.waitForTimeout(600);   // let the async opposite-face probe resolve
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
      // Owner 2026-10-01: a leader previews BOTH faces at once (Core/jsInclude.js ShowLeaderFacesDetail), so
      // the flip — this suite's original subject for leaders — is gone: there is no hidden face left to flip to.
      const faces = await page.evaluate(() => [...document.querySelectorAll('#cardDetail [data-card-detail-pair] img')]
        .map(i => (i.getAttribute('src') || '').split('/').pop()));
      check(`${name}: a leader shows BOTH faces`, faces.length === 2 && /_back\.webp/i.test(faces[1]) && !/_back\.webp/i.test(faces[0]),
        JSON.stringify(faces));
      check(`${name}: NO flip button on a two-face leader preview`, await page.locator(FLIP).count() === 0);
      check(`${name}: close is tappable (pointer-events)`,
        await page.locator(CLOSE).evaluate(el => getComputedStyle(el).pointerEvents) === 'auto');

      // Disarm: the suppressor was armed 5s into the future above, and it would otherwise swallow
      // the tab click below — a stray failure that looks nothing like its cause.
      await page.evaluate(() => { window.suppressNextCardDetailClickUntil = 0; });

      // ── Close button dismisses ──────────────────────────────────────────────────────────────
      await page.locator(CLOSE).tap();
      await page.waitForTimeout(400);
      check(`${name}: X closes the preview`, !(await previewOpen(page)));

      // ── An ordinary card: preview must OPEN, and must NOT offer a flip ──────────────────────
      // The "preview open" check here is load-bearing beyond the flip. Cards-tab tiles are /concat/
      // art while the preview loads a different /WebpImages/ file, so on a cold cache the image
      // lands AFTER touchend — which used to let EndCardDetailLongPress kill the preview outright
      // (long-press appeared to do nothing). The Leaders pane cannot catch that: it already renders
      // WebpImages, so the preview art is always cached.
      await page.evaluate(() => {
        const t = [...document.querySelectorAll('.panelTab')].find(x => x.textContent.trim() === 'Cards');
        if (t) t.click();
      });
      await page.waitForFunction(() => !!document.querySelector('#my_CardPane_content #myCards'), null, { timeout: 15000 });
      // The library settles asynchronously after a rebuild (lazy tiles grow the pane for ~1s); a
      // long-press started mid-rebuild can target a tile that is about to be replaced.
      await page.waitForFunction(() => {
        const c = document.querySelector('#my_CardPane_content');
        return c && c.scrollHeight - c.clientHeight > 100;
      }, null, { timeout: 15000 }).catch(() => {});
      await page.waitForTimeout(1200);
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
