/* capture.mjs — screenshot each of the eight gameboard concepts, per engine.
 *
 * Per the CLAUDE.md engineering rule a UI change is not done until it has been seen in Chromium,
 * Firefox AND WebKit. Layout diverges between them; this is the check, not a formality.
 *
 *   node capture.mjs [OUT_DIR] [chromium,firefox,webkit]
 */
import { chromium, firefox, webkit } from 'playwright';
import { mkdirSync } from 'node:fs';
import path from 'node:path';

const OUT = process.argv[2] || '/tmp/gameboard8';
const ENGINES = (process.argv[3] || 'chromium,firefox,webkit').split(',');
const URL = 'http://localhost:3400/TCGEngine/DevTools/ui-harness/mockups/gameboard-8/index.html';
const LAUNCHERS = { chromium, firefox, webkit };

mkdirSync(OUT, { recursive: true });

for (const name of ENGINES) {
  const browser = await LAUNCHERS[name].launch();
  const page = await browser.newPage({ viewport: { width: 1700, height: 1200 }, deviceScaleFactor: 1 });
  await page.goto(URL, { waitUntil: 'networkidle' });
  // Settle web fonts and lazy card art before capturing: an element still loading reads as a
  // missing element and gets "fixed" into a regression.
  await page.evaluate(() => document.fonts.ready);
  await page.evaluate(async () => {
    document.querySelectorAll('img[loading="lazy"]').forEach((i) => i.setAttribute('loading', 'eager'));
    await Promise.all([...document.images].filter((i) => !i.complete)
      .map((i) => new Promise((r) => { i.onload = i.onerror = r; })));
  });
  await page.waitForTimeout(400);

  for (let n = 1; n <= 8; n++) {
    const el = page.locator(`#c${n} .board`);
    await el.scrollIntoViewIfNeeded();
    await page.waitForTimeout(150);
    const file = path.join(OUT, `${name}-c${n}.png`);
    await el.screenshot({ path: file });
    const box = await el.boundingBox();
    console.log(`${name} c${n}  ${Math.round(box.width)}x${Math.round(box.height)}  ${file}`);
  }

  // Overflow audit: any board whose content is taller/wider than its own frame is a broken layout,
  // and at 1600x1100 that is invisible in a thumbnail.
  const bad = await page.evaluate(() =>
    [...document.querySelectorAll('.board')].flatMap((b, i) =>
      [...b.querySelectorAll('*')]
        .filter((el) => el.scrollHeight - el.clientHeight > 2 && getComputedStyle(el).overflowY === 'visible')
        .slice(0, 4)
        .map((el) => `c${i + 1}: ${el.className || el.tagName} clips ${el.scrollHeight - el.clientHeight}px`)));
  if (bad.length) console.log(`${name} OVERFLOW:\n  ` + bad.join('\n  '));

  await browser.close();
}
console.log('done ->', OUT);
