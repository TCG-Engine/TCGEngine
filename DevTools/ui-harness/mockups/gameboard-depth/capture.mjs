/* capture.mjs — screenshot the Depth Table board (and the eight-card hand strip) per engine.
 * A UI change is not done here until Chromium, Firefox AND WebKit have been seen.
 *   node capture.mjs [OUT_DIR] [chromium,firefox,webkit]
 */
import { chromium, firefox, webkit } from 'playwright';
import { mkdirSync } from 'node:fs';
import path from 'node:path';

const OUT = process.argv[2] || '/tmp/gbdepth';
const ENGINES = (process.argv[3] || 'chromium,firefox,webkit').split(',');
const URL = 'http://localhost:3400/TCGEngine/DevTools/ui-harness/mockups/gameboard-depth/index.html';
const LAUNCHERS = { chromium, firefox, webkit };
// Two boards now: the default cosmetics, and the same board under a background + two playmats.
const TARGETS = [['board', 0], ['cosmetics', 1]];

mkdirSync(OUT, { recursive: true });

for (const name of ENGINES) {
  const browser = await LAUNCHERS[name].launch();
  const page = await browser.newPage({ viewport: { width: 1700, height: 1200 }, deviceScaleFactor: 1 });
  await page.goto(URL, { waitUntil: 'networkidle' });
  await page.evaluate(() => document.fonts.ready);
  await page.evaluate(async () => {
    document.querySelectorAll('img[loading="lazy"]').forEach((i) => i.setAttribute('loading', 'eager'));
    await Promise.all([...document.images].filter((i) => !i.complete)
      .map((i) => new Promise((r) => { i.onload = i.onerror = r; })));
  });
  await page.waitForTimeout(400);

  for (const [label, idx] of TARGETS) {
    const el = page.locator('.board').nth(idx);
    await el.scrollIntoViewIfNeeded();
    await page.waitForTimeout(150);
    const file = path.join(OUT, `${name}-${label}.png`);
    await el.screenshot({ path: file });
    const box = await el.boundingBox();
    console.log(`${name} ${label}  ${Math.round(box.width)}x${Math.round(box.height)}  ${file}`);
  }

  const bad = await page.evaluate(() =>
    [...document.querySelectorAll('.board *')]
      .filter((el) => el.scrollHeight - el.clientHeight > 2 && getComputedStyle(el).overflowY === 'visible')
      .slice(0, 8)
      .map((el) => `${el.className || el.tagName} clips ${el.scrollHeight - el.clientHeight}px`));
  console.log(bad.length ? `${name} OVERFLOW:\n  ` + bad.join('\n  ') : `${name} overflow: none`);
  await browser.close();
}
console.log('done ->', OUT);
