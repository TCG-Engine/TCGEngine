// Cross-browser check for the main menu's News & tips carousel (Welcome tab, added 2026-11-08).
//
// Slides come from SharedUI/Sites/SWUSim/News.php, then the keyboard-shortcuts tip. Asserted in every engine, at a
// desktop and a phone width:
//   · exactly one slide is visible (the others are inert + visibility:hidden), and paging swaps WHICH one
//   · the panel's height does not change between slides (every slide shares one grid cell)
//   · arrows, dots and ←/→ all page, wrapping at both ends; the counter and aria-current follow
//   · the arrows are 44x44 and the dots 28x44 with no legacy button fill/scale (components.css alias)
//   · the shortcuts slide still lists every _hotkeyList row
//   · no horizontal page scroll at 420px
//
// Usage:
//   node swusim-news-carousel-xbrowser.mjs [baseUrl] [screenshotDir]
//
// ⚠ WEBKIT: see swusim-guest-notice-xbrowser.mjs — the pinned Playwright 1.61.1 launches it directly.
import { chromium, firefox, webkit } from 'playwright';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const SHOTS = process.argv[3] || '';
const MENU = BASE + 'SharedUI/Sites/SWUSim/MainMenu.php';
const ENGINES = { chromium, firefox, webkit };
const SIZES = [{ name: 'desktop', width: 1400, height: 900 }, { name: 'phone', width: 420, height: 900 }];

let allOk = true;
const ok = (tag, name, cond, extra = '') => {
  if (!cond) allOk = false;
  console.log(`${cond ? 'PASS' : 'FAIL'}  ${tag}  ${name}${extra ? '  — ' + extra : ''}`);
};
// Headless Firefox runs layout/rAF at ~4Hz, so give the 220ms fade plenty of room.
const settle = (page) => page.waitForTimeout(600);

const state = (page) => page.evaluate(() => {
  const root = document.getElementById('swu-news');
  const slides = [...root.querySelectorAll('.news__slide')];
  const panel = document.getElementById('ga-info-panel-welcome');
  return {
    n: slides.length,
    visible: slides.map((s, i) => getComputedStyle(s).visibility === 'visible' && !s.inert ? i : -1).filter(i => i >= 0),
    count: document.getElementById('swu-news-count').textContent.trim(),
    current: [...root.querySelectorAll('.news__dot')].findIndex(d => d.getAttribute('aria-current') === 'true'),
    panelH: Math.round(panel.getBoundingClientRect().height),
  };
});

for (const [engineName, engine] of Object.entries(ENGINES)) {
  const browser = await engine.launch();
  for (const size of SIZES) {
    const tag = `${engineName}/${size.name}`;
    const page = await browser.newPage({ viewport: { width: size.width, height: size.height } });
    await page.goto(MENU, { waitUntil: 'networkidle' });
    await page.locator('#swu-news').scrollIntoViewIfNeeded();
    await settle(page);

    let s = await state(page);
    ok(tag, 'at least two slides (news + shortcuts)', s.n >= 2, `n=${s.n}`);
    ok(tag, 'opens on slide 1, only slide 1 visible', s.visible.length === 1 && s.visible[0] === 0, JSON.stringify(s.visible));
    ok(tag, 'counter reads 1 / n', s.count === `1 / ${s.n}`, s.count);
    ok(tag, 'first dot is current', s.current === 0);
    const firstText = await page.locator('.news__slide').first().innerText();
    ok(tag, 'first slide is the IC27 announcement', /IC27/.test(firstText), firstText.replace(/\s+/g, ' ').slice(0, 80));
    // The news image: between the title and the body, loaded, a horizontal 2:1 rectangle filling the slide's width.
    const img = await page.evaluate(() => {
      const slide = document.querySelector('.news__slide');
      const im = slide.querySelector('.news__img');
      if (!im) return null;
      const b = im.getBoundingClientRect(), sb = slide.getBoundingClientRect();
      return { prev: im.previousElementSibling?.className, next: im.nextElementSibling?.className,
               loaded: im.complete && im.naturalWidth > 0, w: Math.round(b.width), h: Math.round(b.height), slideW: Math.round(sb.width) };
    });
    ok(tag, 'news image sits between title and body', img && img.prev === 'news__title' && img.next === 'news__body', JSON.stringify(img));
    ok(tag, 'news image loaded', img && img.loaded);
    ok(tag, 'news image is a horizontal 2:1 rectangle at full width', img && Math.abs(img.w - 2 * img.h) <= 1 && img.w === img.slideW, img && `${img.w}x${img.h} slide=${img.slideW}`);
    const h0 = s.panelH;
    if (SHOTS) await page.locator('#ga-info-panel-welcome').screenshot({ path: `${SHOTS}/news-${engineName}-${size.name}-1.png` });

    const geo = await page.evaluate(() => {
      const r = (el) => { const b = el.getBoundingClientRect(); return [Math.round(b.width), Math.round(b.height)]; };
      const dot = document.querySelector('.news__dot');
      const cs = getComputedStyle(dot);
      return { arrow: r(document.querySelector('.news__arrow')), dot: r(dot), dotBg: cs.backgroundColor, dotImg: cs.backgroundImage,
               rows: document.querySelectorAll('#hotkey-list .hotkey-row').length,
               scrollW: document.documentElement.scrollWidth, clientW: document.documentElement.clientWidth };
    });
    ok(tag, 'arrow is 44x44', geo.arrow[0] === 44 && geo.arrow[1] === 44, geo.arrow.join('x'));
    ok(tag, 'dot target is 28x44', geo.dot[0] === 28 && geo.dot[1] === 44, geo.dot.join('x'));
    ok(tag, 'dot has no legacy fill', /rgba\(0, 0, 0, 0\)|transparent/.test(geo.dotBg) && geo.dotImg === 'none', `${geo.dotBg} ${geo.dotImg}`);
    ok(tag, 'shortcuts slide lists 3 hotkeys', geo.rows === 3, `rows=${geo.rows}`);
    ok(tag, 'no horizontal page scroll', geo.scrollW <= geo.clientW, `${geo.scrollW} > ${geo.clientW}`);

    await page.locator('.news__dot').first().hover();
    const dotTransform = await page.evaluate(() => getComputedStyle(document.querySelector('.news__dot')).transform);
    ok(tag, 'dot hover does not scale', dotTransform === 'none', dotTransform);

    // Next arrow → slide 2
    await page.locator('.news__arrow[data-step="1"]').click();
    await settle(page);
    s = await state(page);
    ok(tag, 'Next shows slide 2 alone', s.visible.length === 1 && s.visible[0] === 1, JSON.stringify(s.visible));
    ok(tag, 'counter + dot follow', s.count === `2 / ${s.n}` && s.current === 1, `${s.count} dot=${s.current}`);
    ok(tag, 'panel height unchanged between slides', s.panelH === h0, `${h0} -> ${s.panelH}`);
    if (SHOTS) await page.locator('#ga-info-panel-welcome').screenshot({ path: `${SHOTS}/news-${engineName}-${size.name}-2.png` });

    // Previous from slide 1 wraps to the last slide
    await page.locator('.news__dot').first().click();
    await settle(page);
    await page.locator('.news__arrow[data-step="-1"]').click();
    await settle(page);
    s = await state(page);
    ok(tag, 'dot click + Previous wraps to the last slide', s.visible[0] === s.n - 1 && s.current === s.n - 1, JSON.stringify(s.visible));

    // ←/→ while focus is inside
    await page.locator('.news__arrow[data-step="1"]').focus();
    await page.keyboard.press('ArrowRight');
    await settle(page);
    s = await state(page);
    ok(tag, 'ArrowRight from the last slide wraps to slide 1', s.visible[0] === 0, JSON.stringify(s.visible));

    // Inactive slides are out of the tab order
    const inertOk = await page.evaluate(() => [...document.querySelectorAll('.news__slide')].every((sl, i) => (i === 0) !== sl.inert));
    ok(tag, 'only the active slide is non-inert', inertOk);

    await page.close();
  }
  await browser.close();
}

console.log(allOk ? '\nALL PASS' : '\nFAILURES');
process.exit(allOk ? 0 : 1);
