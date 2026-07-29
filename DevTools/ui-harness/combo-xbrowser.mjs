// Cross-engine check for the Tournament Stats "Leader/Base Combo Meta Share" chart.
// The chart lives in the inactive #meta-share tab, so it must be activated before it has
// any layout — a plain snap.mjs run screenshots a 0x0 element and times out.
//
// Usage: node combo-xbrowser.mjs <tournamentPageUrl> <outPrefix>
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
    await page.click('.tab[data-tab="meta-share"]');
    await page.waitForSelector('#combo-meta-chart .meta-bar', { state: 'visible', timeout: 15000 });
    await page.waitForTimeout(1200);

    const data = await page.evaluate(() => {
      const chart = document.getElementById('combo-meta-chart');
      const bars = [...chart.querySelectorAll('.meta-bar')];
      return {
        chartBox: (({ width, height }) => ({ w: Math.round(width), h: Math.round(height) }))(
          chart.getBoundingClientRect()),
        bars: bars.map(b => {
          const r = b.getBoundingClientRect();
          const imgs = [...b.querySelectorAll('img')];
          return {
            value: (b.querySelector('.bar-value') || {}).textContent?.trim() || null,
            leader: imgs[0]?.getAttribute('alt') || null,
            base: imgs[1]?.getAttribute('alt') || null,
            // naturalWidth 0 => the image failed to load in this engine
            imgsLoaded: imgs.map(i => i.naturalWidth > 0),
            w: Math.round(r.width),
            h: Math.round(r.height),
          };
        }),
      };
    });

    await page.locator('#combo-meta-chart').screenshot({ path: `${outPrefix}-${name}.png` });
    results.push({ engine: name, ok: true, jsErrors, ...data });
  } catch (err) {
    results.push({ engine: name, ok: false, error: err.message, jsErrors });
  }
  await browser.close();
}

// ---- report ----
for (const r of results) {
  console.log(`\n=== ${r.engine} ===`);
  if (!r.ok) { console.log('  ERROR:', r.error); continue; }
  console.log(`  chart ${r.chartBox.w}x${r.chartBox.h}, ${r.bars.length} bars, jsErrors=${r.jsErrors.length}`);
  for (const b of r.bars) {
    console.log(`   ${(b.value || '').padEnd(12)} ${b.leader} / ${b.base}  [${b.w}x${b.h}] imgs=${b.imgsLoaded.join(',')}`);
  }
}

// ---- cross-engine consistency ----
const ok = results.filter(r => r.ok);
const failures = [];
if (ok.length !== 3) failures.push(`only ${ok.length}/3 engines rendered`);
if (ok.length > 1) {
  const sig = r => JSON.stringify(r.bars.map(b => [b.value, b.leader, b.base]));
  const ref = sig(ok[0]);
  for (const r of ok.slice(1)) {
    if (sig(r) !== ref) failures.push(`${r.engine} bar labels/values differ from ${ok[0].engine}`);
  }
}
for (const r of ok) {
  if (r.bars.length === 0) failures.push(`${r.engine}: no bars`);
  if (r.jsErrors.length) failures.push(`${r.engine}: JS errors ${JSON.stringify(r.jsErrors)}`);
  if (!r.bars.some(b => /—/.test(b.base || ''))) failures.push(`${r.engine}: no lumped bucket label rendered`);
}

console.log('\n' + (failures.length ? 'FAIL' : 'PASS — all three engines agree'));
failures.forEach(f => console.log('  -', f));
process.exit(failures.length ? 1 : 0);
