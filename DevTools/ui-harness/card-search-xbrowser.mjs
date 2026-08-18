// Cross-engine check for the SWUStats/SWUDeck main-menu Card Search applet.
//
// What it protects, none of which any PHP test can see:
//  • the ~1MB filter bundle is fetched LAZILY — never on page load, and not for a plain name query
//  • the caret lands in the search box when the popup opens (it is focused after an animation, so a
//    naive focus() call inside a display:none panel silently does nothing)
//  • the deckbuilder filter syntax actually reaches this box (f=/format=, aspect, numeric, type)
//  • a bad format name yields zero results rather than silently matching everything
//
// Usage: node card-search-xbrowser.mjs [mainMenuUrl] [outPrefix]
import { chromium, firefox, webkit } from 'playwright';

const url = process.argv[2] || 'http://localhost:3100/TCGEngine/SharedUI/MainMenu.php';
const outPrefix = process.argv[3] || '/tmp/card-search';
const ENGINES = { chromium, firefox, webkit };
let allOk = true;

for (const [name, driver] of Object.entries(ENGINES)) {
  const browser = await driver.launch();
  const page = await browser.newPage({ viewport: { width: 1400, height: 900 } });
  const checks = [];
  const ok = (k, v, extra) => checks.push([k, !!v, extra]);
  const bundleHits = [];
  const jsErrors = [];
  page.on('request', r => { if (/GeneratedCardDictionaries_.*\.js/.test(r.url())) bundleHits.push(r.url()); });
  page.on('pageerror', e => jsErrors.push(e.message));

  const count = async () => parseInt((await page.textContent('#cbCount')).replace(/\D/g, ''), 10);

  try {
    await page.goto(url, { waitUntil: 'networkidle', timeout: 60000 });
    ok('bundle not fetched on page load', bundleHits.length === 0, bundleHits.length);

    await page.click('#cardSearchInput');
    await page.waitForTimeout(400);
    ok('popup visible', await page.isVisible('#cbSearch'));
    ok('search box focused',
       await page.evaluate(() => document.activeElement?.id === 'cbSearch'),
       await page.evaluate(() => document.activeElement?.id));
    ok('placeholder is "Search Cards..."',
       (await page.getAttribute('#cbSearch', 'placeholder')) === 'Search Cards...');

    const all = await count();
    ok('grid starts populated', all > 2000, all);

    await page.fill('#cbSearch', 'vader');
    await page.waitForTimeout(300);
    const byName = await count();
    ok('name search works', byName > 0 && byName < all, byName);
    ok('name search does NOT fetch the bundle', bundleHits.length === 0, bundleHits.length);

    await page.fill('#cbSearch', 'f=premier a=space');
    await page.waitForFunction(() => window.cardFormatBits !== undefined, null, { timeout: 30000 });
    await page.waitForTimeout(500);
    const spacePremier = await count();
    ok('syntax query fetched the bundle once', bundleHits.length === 1, bundleHits.length);

    await page.fill('#cbSearch', 'f=premier');
    await page.waitForTimeout(400);
    const premier = await count();
    ok('f=premier is a strict subset', premier > 0 && premier < all, premier);
    ok('a=space narrows f=premier', spacePremier > 0 && spacePremier < premier, spacePremier);

    await page.fill('#cbSearch', 'f=padawan');
    await page.waitForTimeout(400);
    const padawan = await count();
    ok('padawan narrower than premier', padawan > 0 && padawan < premier, padawan);

    await page.fill('#cbSearch', 'f=nonsense');
    await page.waitForTimeout(400);
    ok('unknown format yields zero', (await count()) === 0);
    ok('empty state shown', await page.isVisible('#cbEmpty'));

    await page.fill('#cbSearch', 'c:rr');
    await page.waitForTimeout(400);
    const rr = await count();
    ok('aspect syntax c:rr', rr > 0 && rr < all, rr);

    await page.fill('#cbSearch', 'cost>=8 is=unit');
    await page.waitForTimeout(400);
    const big = await count();
    ok('numeric + type syntax', big > 0 && big < all, big);

    ok('no page errors', jsErrors.length === 0, jsErrors.slice(0, 2).join(' | '));
    await page.screenshot({ path: `${outPrefix}-${name}.png` });
  } catch (e) {
    ok('ran to completion', false, String(e).split('\n')[0]);
  }
  await browser.close();

  console.log(`\n=== ${name} ===`);
  for (const [k, v, extra] of checks) {
    console.log(`  ${v ? 'ok:  ' : 'FAIL:'} ${k}${extra !== undefined ? `  [${extra}]` : ''}`);
    if (!v) allOk = false;
  }
}
console.log('\n' + (allOk ? 'CARD SEARCH UI OK' : 'CARD SEARCH UI HAS FAILURES'));
process.exit(allOk ? 0 : 1);
