// Stage 1 verification: card-touch.css is linked and computes on card images, and the
// contextmenu guard cancels on cards but not elsewhere.
// NOTE: this does NOT verify the iOS callout suppression itself — Playwright's WebKit does
// not implement the native callout. Device sign-off is still required.
import { chromium, firefox, webkit } from 'playwright';

const BASE = 'http://localhost:3100/TCGEngine';
const GAME = process.env.GAME || '201009';
const ENGINES = { chromium, firefox, webkit };
let failures = 0;

const check = (label, pass, detail) => {
  if (!pass) failures++;
  console.log(`   ${pass ? 'PASS' : 'FAIL'}  ${label}${detail ? ` — ${detail}` : ''}`);
};

for (const [name, engine] of Object.entries(ENGINES)) {
  const browser = await engine.launch();
  const ctx = await browser.newContext({ viewport: { width: 390, height: 844 }, deviceScaleFactor: 2 });
  const page = await ctx.newPage();
  await page.goto(`${BASE}/SharedUI/LoginPage.php`);
  await page.fill('input[name="userID"]', 'Drixx');
  await page.fill('input[name="password"]', 'pass');
  await Promise.all([page.waitForNavigation(), page.click('button[type="submit"]')]);

  const resp = [];
  page.on('response', r => { if (r.url().includes('card-touch.css')) resp.push(`${r.status()} ${r.url()}`); });

  await page.goto(`${BASE}/NextTurn.php?gameName=${GAME}&playerID=1&folderPath=SWUDeck&swuLayout=mobile`);
  await page.waitForTimeout(3500);
  console.log(`\n=== ${name} ===`);

  // 1. stylesheet linked + served 200 + cache-busted
  const link = await page.evaluate(() =>
    [...document.querySelectorAll('link[rel=stylesheet]')].map(l => l.href).find(h => h.includes('card-touch.css')) || null);
  check('card-touch.css linked', !!link, link ? link.replace(BASE, '') : 'not found');
  check('served 200', resp.some(r => r.startsWith('200')), resp.join(', ') || 'no response seen');
  check('cache-busted (?v=)', !!link && /\?v=\d+/.test(link));

  // 2. computed styles on a REAL card img (assert we actually found one)
  const styles = await page.evaluate(() => {
    const img = [...document.querySelectorAll("img[alt='Card']")].find(i => i.getBoundingClientRect().width > 20);
    if (!img) return null;
    const cs = getComputedStyle(img);
    return {
      src: img.src.split('/').slice(-2).join('/'),
      callout: cs.webkitTouchCallout || cs.getPropertyValue('-webkit-touch-callout') || '(unsupported)',
      userSelect: cs.userSelect || cs.webkitUserSelect,
    };
  });
  check('found a card img[alt=Card]', !!styles, styles ? styles.src : 'NONE — selector may be wrong');
  if (styles) {
    // user-select comes from the SAME rule block as -webkit-touch-callout, so this computing to
    // 'none' proves the block matches real card images in this engine.
    check('rule block applies to card img (user-select: none)', styles.userSelect === 'none', styles.userSelect);
  }

  // -webkit-touch-callout is a mobile-only property: desktop Chromium/WebKit drop it at parse
  // time and never expose it to getComputedStyle, and Firefox does not implement it at all.
  // Its EFFECT is unverifiable here by construction — assert only that the served file declares
  // it. Real suppression requires sign-off on a physical iOS device.
  const cssText = await page.evaluate(async (href) => (await fetch(href)).text(), link);
  check('served CSS declares -webkit-touch-callout: none', /-webkit-touch-callout:\s*none/.test(cssText));

  // 3. contextmenu guard: cancelled on a card, NOT cancelled on plain background
  const ctxMenu = await page.evaluate(() => {
    const fire = (el) => {
      const ev = new MouseEvent('contextmenu', { bubbles: true, cancelable: true });
      el.dispatchEvent(ev);
      return ev.defaultPrevented;
    };
    const img = [...document.querySelectorAll("img[alt='Card']")].find(i => i.getBoundingClientRect().width > 20);
    return { onCard: img ? fire(img) : null, onBody: fire(document.body) };
  });
  check('contextmenu prevented on card', ctxMenu.onCard === true, String(ctxMenu.onCard));
  check('contextmenu NOT prevented on body', ctxMenu.onBody === false, String(ctxMenu.onBody));

  await browser.close();
}

console.log(`\n${failures === 0 ? 'ALL CHECKS PASSED' : failures + ' CHECK(S) FAILED'}`);
process.exit(failures === 0 ? 0 : 1);
