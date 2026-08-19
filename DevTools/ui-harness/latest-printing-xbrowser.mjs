// A deck holding a reprinted card must RENDER the newest standard printing while its stored identity
// stays canonical. Only a browser can see this: every server-side test asserts ids, and the whole point
// of the change is which art file the <img> actually resolves to.
//
// Usage: node latest-printing-xbrowser.mjs --game <deckID> --canonical <id> --display <id>
//                                          [--engines chromium,firefox,webkit] [--out /tmp/prefix]
//
// ⚠ Pass the values the <img src> actually carries. SWUDeck's card art is still UUID-NAMED (SET_NNN ->
// uuid via uuidLookupData / resolveCardImageID), so for the deckbuilder these are the two printings'
// UUIDs, not their SET_NNN ids. SWUSim's art is SET_NNN-named and takes the ids directly. Checking the
// wrong vocabulary makes both assertions vacuously "absent" and the run looks broken when it is not.
// Logs in as Drixx (see CLAUDE.md `## Creds`).
import { chromium, firefox, webkit } from 'playwright';

const ENGINES = { chromium, firefox, webkit };
const BASE = 'http://localhost:3100/TCGEngine';
const args = Object.fromEntries(process.argv.slice(2).join(' ').split('--').filter(Boolean)
  .map(s => s.trim().split(/\s+/)).map(([k, ...v]) => [k, v.join(' ') || true]));

const game      = args.game;
const canonical = args.canonical;   // stored identity — must NOT appear in the render
const display   = args.display;     // what the board must actually show
const outPrefix = args.out || '/tmp/latest-printing';
const engineNames = String(args.engines || 'chromium,firefox,webkit').split(',');
if (!game || !canonical || !display) {
  console.error('usage: node latest-printing-xbrowser.mjs --game <deckID> --canonical <id> --display <id>');
  process.exit(2);
}

let allOk = true;
for (const name of engineNames) {
  const browser = await ENGINES[name].launch();
  const page = await (await browser.newContext({ viewport: { width: 1600, height: 950 } })).newPage();
  const jsErrors = [];
  page.on('pageerror', e => jsErrors.push(e.message));
  const checks = [];
  try {
    await page.goto(`${BASE}/SharedUI/LoginPage.php`);
    await page.fill('input[name="userID"]', args.user || 'Drixx');
    await page.fill('input[name="password"]', args.pass || 'pass');
    await Promise.all([page.waitForNavigation(), page.click('button[type="submit"]')]);
    await page.goto(`${BASE}/NextTurn.php?gameName=${game}&playerID=1&folderPath=SWUDeck`);
    await page.waitForTimeout(2500);

    const srcs = await page.$$eval('img', els => els.map(e => e.getAttribute('src') || ''));
    const shownNew = srcs.filter(s => s.includes(display)).length;
    const shownOld = srcs.filter(s => s.includes(canonical)).length;
    checks.push([`renders the display printing ${display}`, shownNew > 0, shownNew]);
    checks.push([`never renders the stored printing ${canonical}`, shownOld === 0, shownOld]);
    // A wrong display id would 404 rather than fall back, so broken art is the loud failure mode.
    checks.push(['no broken card images', await page.$$eval('img',
      els => els.every(e => !e.complete || e.naturalWidth > 0)), '']);
    checks.push(['no page errors', jsErrors.length === 0, jsErrors.slice(0, 2).join(' | ')]);
    await page.screenshot({ path: `${outPrefix}-${name}.png` });
  } catch (e) {
    checks.push(['ran to completion', false, String(e).split('\n')[0]]);
  }
  await browser.close();
  console.log(`\n=== ${name} ===`);
  for (const [k, v, extra] of checks) {
    console.log(`  ${v ? 'ok:  ' : 'FAIL:'} ${k}${extra !== '' ? `  [${extra}]` : ''}`);
    if (!v) allOk = false;
  }
}
console.log('\n' + (allOk ? 'LATEST PRINTING UI OK' : 'LATEST PRINTING UI HAS FAILURES'));
process.exit(allOk ? 0 : 1);
