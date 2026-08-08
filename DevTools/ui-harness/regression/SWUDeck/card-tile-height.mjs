// Regression: the shared Card() renderer must emit a card tile's height as a VALID CSS length.
//
// Core/UILibraries*.js built the tile's inline style as `height:<n>; width:<n>px` — the height had
// NO UNIT, so the declaration was invalid and every browser dropped it, while width (correctly
// suffixed) applied. Tiles are loading='lazy', so wherever that inline height is the only height,
// a tile contributed ~0 until its image arrived: layout shift on every board, in every app.
//
// SCOPE — read this before "fixing" a failure here:
//   * DESKTOP is where the inline height is load-bearing, so that is where the effect is asserted.
//   * MOBILE deliberately overrides it (`width:100% !important; height:auto !important` in
//     GameLayoutMobile.php) to make tiles responsive to the grid cell. So mobile tiles still
//     collapse before their image loads BY DESIGN, and that is NOT what this suite guards.
//     The mobile library's scroll position is protected separately, by the settle loop in
//     bindMobileLibraryScroll() — see library-scroll-persistence.mjs.
// Blocking images isolates the CSS contract from image loading. Read-only: never mutates a deck.
import { ENGINES, login, openBoard, desktopContextOpts, harness } from '../lib.mjs';

const GAME = process.env.GAME || '100431';
const only = (process.env.ENGINES || 'chromium,firefox').split(',').map(s => s.trim()).filter(Boolean);
const SUITE_ENGINES = Object.fromEntries(only.map(n => [n, ENGINES[n]]));

await harness(async (check) => {
  for (const [name, engine] of Object.entries(SUITE_ENGINES)) {
    if (!engine) { check(`${name}: engine available`, false, 'unknown engine name'); continue; }
    console.log(`\n=== ${name} (desktop, images blocked) ===`);
    const browser = await engine.launch();
    try {
      const ctx = await browser.newContext(desktopContextOpts());
      // Never let a card image load, so only the CSS can give a tile its height.
      await ctx.route(/\/(WebpImages|concat|crops)\/.*\.(webp|png|jpg)(\?|$)/i, r => r.abort());
      const page = await ctx.newPage();
      await login(page);
      await openBoard(page, { game: GAME });

      // Measure on the CARDS pane. The board opens on Leaders, and desktop deliberately overrides
      // the Leaders/Leader1/Leader2/Bases tiles with `height:auto !important` (GameLayout.php) so
      // they render as narrow strips — measuring those tests the override, not the renderer.
      const onCards = await page.evaluate(() => {
        const tab = [...document.querySelectorAll('.panelTab')].find(t => t.textContent.trim() === 'Cards');
        if (!tab) return false;
        tab.click();
        return true;
      });
      check(`${name}: switched to the Cards tab`, onCards);
      await page.waitForFunction(() => !!document.querySelector('#my_CardPane_content #myCards'), null, { timeout: 15000 });
      await page.waitForTimeout(1500);

      const m = await page.evaluate(() => {
        const img = document.querySelector("#my_CardPane_content #myCards img[alt='Card']");
        if (!img) return null;
        const declared = /height:\s*([\d.]+)px/.exec(img.getAttribute('style') || '');
        return {
          styleAttr: img.getAttribute('style'),
          declared: declared ? Math.round(Number(declared[1])) : null,
          boxHeight: Math.round(img.getBoundingClientRect().height),
          overridden: getComputedStyle(img).height,
        };
      });

      if (!m) { check(`${name}: found a card tile`, false); continue; }

      // The actual guard on the renderer: a unit-less length is silently dropped by every engine.
      check(`${name}: tile height declaration carries a unit`, /height:\s*[\d.]+px/.test(m.styleAttr),
        m.styleAttr.replace(/\s+/g, ' ').slice(0, 90));

      // ...and proves it is EFFECTIVE, not merely present, where nothing overrides it.
      check(`${name}: tile reserves its declared height with no image`,
        m.declared !== null && Math.abs(m.boxHeight - m.declared) <= 2,
        `declared ${m.declared}px, laid out ${m.boxHeight}px (computed ${m.overridden})`);
    } finally {
      await browser.close();
    }
  }
});
