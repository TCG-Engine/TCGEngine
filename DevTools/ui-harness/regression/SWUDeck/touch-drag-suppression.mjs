// Regression: on touch devices a long-press must NOT start an HTML5 drag (which paints the
// yellow dashed .droppable targets from NextTurn.php:48). Desktop mouse drag must still work.
// NOTE: the dragstart HANDLER is verifiable here; the real iOS drag-initiation-from-long-press is
// not (Playwright's WebKit lacks the native gesture layer) — see regression/README.md.
import { ENGINES, login, openBoard, mobileContextOpts, desktopContextOpts, harness } from '../lib.mjs';

const GAME = process.env.GAME || '201009';
const SUITE_ENGINES = { chromium: ENGINES.chromium, webkit: ENGINES.webkit };

async function open(browser, mobile) {
  const ctx = await browser.newContext(mobile ? mobileContextOpts() : desktopContextOpts());
  const page = await ctx.newPage();
  await login(page);
  await openBoard(page, { game: GAME, mobile });   // condition-based wait + fail-loud
  return { ctx, page };
}

// Fire a cancelable dragstart on a card and report whether it was prevented.
const tryDrag = () => {
  const a = [...document.querySelectorAll("a[onmouseover*='ShowCardDetail']")]
    .find(el => el.getBoundingClientRect().width > 20);
  if (!a) return { found: false };
  const img = a.querySelector('img') || a;
  const ev = new Event('dragstart', { bubbles: true, cancelable: true });
  img.dispatchEvent(ev);
  return {
    found: true,
    prevented: ev.defaultPrevented,
    coarse: matchMedia('(hover: none) and (pointer: coarse)').matches,
    droppableCount: document.querySelectorAll('.droppable').length,
  };
};

await harness(async (check) => {
  for (const [name, engine] of Object.entries(SUITE_ENGINES)) {
    console.log(`\n=== ${name} ===`);
    const browser = await engine.launch();

    const m = await open(browser, true);
    const mob = await m.page.evaluate(tryDrag);
    check('mobile: found a card', mob.found);
    check('mobile: media query reports coarse pointer', mob.coarse === true, String(mob.coarse));
    check('mobile: dragstart PREVENTED', mob.prevented === true, String(mob.prevented));
    check('mobile: no .droppable targets painted', mob.droppableCount === 0, String(mob.droppableCount));
    await m.ctx.close();

    const d = await open(browser, false);
    const desk = await d.page.evaluate(tryDrag);
    check('desktop: found a card', desk.found);
    check('desktop: NOT coarse pointer', desk.coarse === false, String(desk.coarse));
    check('desktop: dragstart still allowed', desk.prevented === false, String(desk.prevented));
    await d.ctx.close();

    await browser.close();
  }
});
