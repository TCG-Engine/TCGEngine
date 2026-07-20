// Regression: on touch devices a long-press must NOT start an HTML5 drag (which paints the
// yellow dashed .droppable targets from NextTurn.php:48). Desktop mouse drag must still work.
import { chromium, webkit } from 'playwright';

const BASE = 'http://localhost:3100/TCGEngine';
const GAME = process.env.GAME || '201009';
const ENGINES = { chromium, webkit };
let failures = 0;
const check = (label, pass, detail) => {
  if (!pass) failures++;
  console.log(`   ${pass ? 'PASS' : 'FAIL'}  ${label}${detail ? ` — ${detail}` : ''}`);
};

async function open(browser, mobile) {
  const ctx = await browser.newContext(mobile
    ? { viewport: { width: 390, height: 844 }, deviceScaleFactor: 2, hasTouch: true, isMobile: true }
    : { viewport: { width: 1600, height: 950 } });
  const page = await ctx.newPage();
  await page.goto(`${BASE}/SharedUI/LoginPage.php`);
  await page.fill('input[name="userID"]', 'Drixx');
  await page.fill('input[name="password"]', 'pass');
  await Promise.all([page.waitForNavigation(), page.click('button[type="submit"]')]);
  await page.goto(`${BASE}/NextTurn.php?gameName=${GAME}&playerID=1&folderPath=SWUDeck${mobile ? '&swuLayout=mobile' : ''}`);
  await page.waitForTimeout(3500);
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

for (const [name, engine] of Object.entries(ENGINES)) {
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
console.log(`\n${failures === 0 ? 'ALL CHECKS PASSED' : failures + ' CHECK(S) FAILED'}`);
process.exit(failures === 0 ? 0 : 1);
