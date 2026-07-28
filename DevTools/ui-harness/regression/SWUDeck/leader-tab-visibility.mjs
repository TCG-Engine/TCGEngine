// Regression: a single-leader (premier) deck must never show Leader1/Leader2 browse tabs —
// those are Twin Suns only. The mobile layout re-renders the pane on a tab switch and moves the
// tabs OUT of #myCardPane, so a query scoped to that container silently stops hiding them.
import { ENGINES, login, openBoard, mobileContextOpts, harness } from '../lib.mjs';

const PREMIER = process.env.PREMIER || '100431';
const TWINSUNS = process.env.TWINSUNS || '201009';
const SUITE_ENGINES = { chromium: ENGINES.chromium, webkit: ENGINES.webkit };

// Visible tab labels, document-wide (the mobile layout keeps a second hidden copy).
const visibleTabs = () => [...document.querySelectorAll('.panelTab')]
  .filter(t => t.getBoundingClientRect().width > 0)
  .map(t => t.textContent.trim());

await harness(async (check) => {
  for (const [name, engine] of Object.entries(SUITE_ENGINES)) {
    console.log(`\n=== ${name} ===`);
    const browser = await engine.launch();
    const ctx = await browser.newContext(mobileContextOpts());
    const page = await ctx.newPage();
    await login(page);

    for (const [deck, format] of [[PREMIER, 'premier'], [TWINSUNS, 'twinsuns']]) {
      await openBoard(page, { game: deck, mobile: true });   // condition-based wait + fail-loud
      check(`${format}: format global correct`,
        (await page.evaluate(() => window.SWU_DECK_FORMAT)) === format);

      const onLoad = await page.evaluate(visibleTabs);
      const expectTwin = format === 'twinsuns';
      check(`${format}: on load tabs correct`,
        expectTwin
          ? onLoad.includes('Leader1') && onLoad.includes('Leader2') && !onLoad.includes('Leaders')
          : onLoad.includes('Leaders') && !onLoad.includes('Leader1') && !onLoad.includes('Leader2'),
        onLoad.join(' | '));

      // switch panes — this is what breaks it. (A short fixed settle for the re-render is fine here:
      // it's a bounded local DOM swap, not the variable network/board load that openBoard now guards.)
      for (const target of ['Cards', 'Bases', 'Cards']) {
        await page.evaluate((t) => {
          const el = [...document.querySelectorAll('.panelTab')]
            .find(e => e.textContent.trim() === t && e.getBoundingClientRect().width > 0);
          if (el) el.click();
        }, target);
        await page.waitForTimeout(900);
      }

      const after = await page.evaluate(visibleTabs);
      check(`${format}: tabs still correct after pane switches`,
        expectTwin
          ? after.includes('Leader1') && after.includes('Leader2') && !after.includes('Leaders')
          : after.includes('Leaders') && !after.includes('Leader1') && !after.includes('Leader2'),
        after.join(' | '));
    }
    await browser.close();
  }
});
