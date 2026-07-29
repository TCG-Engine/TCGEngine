import { chromium } from 'playwright';

const url = process.argv[2];
const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1600, height: 1000 } });
const errs = [];
page.on('pageerror', e => errs.push(e.message));
await page.goto(url, { waitUntil: 'networkidle', timeout: 60000 });
await page.click('.tab[data-tab="matchup-matrix"]');
await page.waitForSelector('.archetype-tile', { state: 'visible', timeout: 15000 });

const tiles = await page.locator('.archetype-tile').count();
await page.locator('.archetype-tile').first().click();
await page.waitForSelector('.archetype-rows tbody tr', { state: 'visible', timeout: 15000 });

const detail = await page.evaluate(() => {
  const rows = [...document.querySelectorAll('.archetype-rows tbody tr')]
    .filter(r => !r.classList.contains('archetype-divider'));
  return {
    rowCount: rows.length,
    thinRows: rows.filter(r => r.classList.contains('thin')).length,
    thinWithPercent: rows.filter(r => r.classList.contains('thin') && r.textContent.includes('%')).length,
    matches: rows.map(r => parseInt(r.children[1].textContent, 10)),
    hasDivider: !!document.querySelector('.archetype-divider'),
    hasNotice: !!document.querySelector('.archetype-notice'),
  };
});
const sorted = detail.matches.every((m, i) => i === 0 || detail.matches[i - 1] >= m);

console.log(JSON.stringify({
  jsErrors: errs, tiles, rowCount: detail.rowCount, thinRows: detail.thinRows,
  thinWithPercent: detail.thinWithPercent, hasDivider: detail.hasDivider,
  hasNotice: detail.hasNotice, sortedDesc: sorted,
  topMatches: detail.matches.slice(0, 5),
}, null, 2));
const ok = errs.length === 0 && tiles > 0 && detail.thinWithPercent === 0 && sorted;
console.log(ok ? 'PASS' : 'FAIL');
await browser.close();
process.exit(ok ? 0 : 1);
