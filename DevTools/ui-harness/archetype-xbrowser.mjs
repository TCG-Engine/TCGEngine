// Cross-engine check for the Matchup Matrix archetype explorer.
// The tab is inactive by default, so it must be clicked before anything has layout.
//
// Usage: node archetype-xbrowser.mjs <tournamentPageUrl> <outPrefix>
import { chromium, firefox, webkit } from 'playwright';

const url = process.argv[2];
const outPrefix = process.argv[3];
const ENGINES = { chromium, firefox, webkit };
const results = [];

for (const [name, driver] of Object.entries(ENGINES)) {
  const browser = await driver.launch();
  const page = await browser.newPage({ viewport: { width: 1600, height: 1000 } });
  const jsErrors = [];
  page.on('pageerror', e => jsErrors.push(e.message));
  try {
    await page.goto(url, { waitUntil: 'networkidle', timeout: 60000 });
    await page.click('.tab[data-tab="matchup-matrix"]');
    await page.waitForSelector('.archetype-tile', { state: 'visible', timeout: 15000 });
    const tiles = await page.locator('.archetype-tile').count();
    await page.locator('#archetype-explorer').screenshot({ path: `${outPrefix}-gallery-${name}.png` });

    await page.locator('.archetype-tile').first().click();
    await page.waitForSelector('.archetype-rows tbody tr', { state: 'visible', timeout: 15000 });
    await page.locator('#archetype-explorer').screenshot({ path: `${outPrefix}-detail-${name}.png` });

    const d = await page.evaluate(() => {
      const rows = [...document.querySelectorAll('.archetype-rows tbody tr')]
        .filter(r => !r.classList.contains('archetype-divider'));
      const thin = rows.find(r => r.classList.contains('thin'));
      const strong = rows.find(r => !r.classList.contains('thin'));
      return {
        rowCount: rows.length,
        thinWithPercent: rows.filter(r => r.classList.contains('thin') && r.textContent.includes('%')).length,
        firstRows: rows.slice(0, 5).map(r => [...r.children].map(c => c.textContent.trim()).join(' | ')),
        thinOpacity: thin ? getComputedStyle(thin).opacity : null,
        strongOpacity: strong ? getComputedStyle(strong).opacity : null,
      };
    });

    // Back control must restore the gallery.
    await page.locator('.archetype-back').click();
    await page.waitForSelector('.archetype-tile', { state: 'visible', timeout: 10000 });
    const backOk = (await page.locator('.archetype-tile').count()) === tiles;

    results.push({ engine: name, ok: true, jsErrors, tiles, backOk, ...d });
  } catch (e) {
    results.push({ engine: name, ok: false, error: e.message, jsErrors });
  }
  await browser.close();
}

for (const r of results) {
  console.log(`\n=== ${r.engine} ===`);
  if (!r.ok) { console.log('  ERROR:', r.error); continue; }
  console.log(`  tiles=${r.tiles} rows=${r.rowCount} back=${r.backOk} thinOpacity=${r.thinOpacity} strongOpacity=${r.strongOpacity} jsErrors=${r.jsErrors.length}`);
  r.firstRows.forEach(x => console.log('   ', x));
}

const ok = results.filter(r => r.ok);
const failures = [];
if (ok.length !== 3) failures.push(`only ${ok.length}/3 engines rendered`);
for (const r of ok) {
  if (r.jsErrors.length) failures.push(`${r.engine}: JS errors ${JSON.stringify(r.jsErrors)}`);
  if (r.thinWithPercent) failures.push(`${r.engine}: ${r.thinWithPercent} thin rows printed a %`);
  if (!r.backOk) failures.push(`${r.engine}: back control did not restore the gallery`);
  if (r.thinOpacity !== null && r.strongOpacity !== null
      && parseFloat(r.thinOpacity) >= parseFloat(r.strongOpacity)) {
    failures.push(`${r.engine}: thin rows not de-emphasised (${r.thinOpacity} vs ${r.strongOpacity})`);
  }
}
if (ok.length > 1) {
  const sig = r => JSON.stringify([r.tiles, r.rowCount, r.firstRows]);
  const ref = sig(ok[0]);
  ok.slice(1).forEach(r => { if (sig(r) !== ref) failures.push(`${r.engine} differs from ${ok[0].engine}`); });
}
console.log('\n' + (failures.length ? 'FAIL' : 'PASS — all three engines agree'));
failures.forEach(f => console.log('  -', f));
process.exit(failures.length ? 1 : 0);
