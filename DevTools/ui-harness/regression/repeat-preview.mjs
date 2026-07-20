// Regression: a SECOND long-press preview must survive the synthetic mouseout that iOS fires
// when the finger moves between cards. Every card carries inline onmouseout='HideCardDetail()'
// (UILibraries:297) — an unforced call that must not kill a persistent touch preview.
import { chromium, webkit } from 'playwright';

const BASE = 'http://localhost:3100/TCGEngine';
const GAME = process.env.GAME || '201009';
const ENGINES = { chromium, webkit };
let failures = 0;
const check = (label, pass, detail) => {
  if (!pass) failures++;
  console.log(`   ${pass ? 'PASS' : 'FAIL'}  ${label}${detail ? ` — ${detail}` : ''}`);
};

const isVisible = () => {
  const el = document.getElementById('cardDetail');
  return !!el && getComputedStyle(el).display !== 'none';
};

for (const [name, engine] of Object.entries(ENGINES)) {
  console.log(`\n=== ${name} ===`);
  const browser = await engine.launch();
  const ctx = await browser.newContext({
    viewport: { width: 390, height: 844 }, deviceScaleFactor: 2, hasTouch: true, isMobile: true,
  });
  const page = await ctx.newPage();
  await page.goto(`${BASE}/SharedUI/LoginPage.php`);
  await page.fill('input[name="userID"]', 'Drixx');
  await page.fill('input[name="password"]', 'pass');
  await Promise.all([page.waitForNavigation(), page.click('button[type="submit"]')]);
  await page.goto(`${BASE}/NextTurn.php?gameName=${GAME}&playerID=1&folderPath=SWUDeck&swuLayout=mobile`);
  await page.waitForTimeout(3500);

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
console.log(`\n${failures === 0 ? 'ALL CHECKS PASSED' : failures + ' CHECK(S) FAILED'}`);
process.exit(failures === 0 ? 0 : 1);
