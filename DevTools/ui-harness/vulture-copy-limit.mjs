// LIVE check of SWUDeck's main-deck copy gate, through the real click path.
//
// A PHP test can prove SWUDeckMaxCopies()'s arithmetic, but not that the browse-pane click reaches
// it with the deck's format in hand — the gate reads `LoadAssetData(1, $gameName)['format']`, which
// only exists in a real request. This clicks Swarming Vulture Droid (JTL_256, "a deck can have up
// to 15 copies of this card") in the Cards pane five times and asserts the Main deck count rises by
// five. Before the fix it stopped at three: the 15-copy exception was keyed on the card's
// pre-migration FFG UUID, which no longer exists.
//
// ⚠ MUTATES the deck (the editor autosaves). Back up SWUDeck/Games/<id>/Gamestate.txt first and
// restore it after — this harness does not clean up after itself.
//
// Usage: node vulture-copy-limit.mjs [gameName] [engine]
import { chromium, firefox, webkit } from 'playwright';

const BASE = 'http://localhost:3100/TCGEngine';
const GAME = process.argv[2] || '100431';
const ENGINE = process.argv[3] || 'chromium';
const ADDS = 5;

setTimeout(() => { console.log('TIMEOUT'); process.exit(9); }, 180000).unref();

const driver = { chromium, firefox, webkit }[ENGINE];
const browser = await driver.launch();
const page = await browser.newPage({ viewport: { width: 1600, height: 1000 } });
const fails = [];
const count = async () => parseInt((await page.textContent('#swuMainDeckTitle')).replace(/\D/g, ''), 10);

try {
  await page.goto(`${BASE}/SharedUI/LoginPage.php`);
  await page.fill('input[name="userID"]', 'Drixx');
  await page.fill('input[name="password"]', 'pass');
  await Promise.all([page.waitForNavigation(), page.click('button[type="submit"]')]);

  await page.goto(`${BASE}/NextTurn.php?gameName=${GAME}&playerID=1&folderPath=SWUDeck&swuLayout=desktop`,
                  { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.waitForFunction(() => document.getElementById('swuMainDeckTitle') && window.myCardsData);

  // Cards tab, then filter the pane down to the one card.
  const tabs = await page.evaluate(() =>
    [...document.querySelectorAll('#myCardPaneSlot [onclick^="PaneTabClick"]')].map(e => e.textContent.trim()));
  const cardsTab = tabs.findIndex(t => /^Cards$/i.test(t));
  if (cardsTab < 0) throw new Error(`no Cards tab among ${JSON.stringify(tabs)}`);
  await page.click(`#myCardPaneSlot [onclick*='PaneTabClick("my", "CardPane", "${cardsTab}")']`);
  await page.fill('#myCardPaneFilterText', 'Swarming Vulture');
  await page.waitForTimeout(1500);

  const mzid = await page.evaluate(() => {
    const i = String(window.myCardsData || '').split('<|>').findIndex(e => e.startsWith('JTL_256 '));
    return i < 0 ? null : 'myCards-' + i;
  });
  if (!mzid) throw new Error('JTL_256 not found in the Cards pane');
  if (!(await page.isVisible('#' + mzid))) throw new Error(`${mzid} is not visible after filtering`);

  const start = await count();
  console.log(`${ENGINE.padEnd(9)} deck ${GAME}: starting at ${start} cards, adding ${ADDS}x JTL_256 via ${mzid}`);

  for (let n = 1; n <= ADDS; n++) {
    const before = await count();
    await page.click('#' + mzid);
    try {
      await page.waitForFunction(
        (b) => parseInt((document.getElementById('swuMainDeckTitle').textContent || '').replace(/\D/g, ''), 10) === b + 1,
        before, { timeout: 15000 });
    } catch {
      fails.push(`copy #${n} was REFUSED — deck stayed at ${await count()}`);
      break;
    }
    console.log(`  copy #${n}: Main deck (${await count()})`);
  }

  const end = await count();
  if (end !== start + ADDS) fails.push(`want ${start + ADDS} cards, got ${end}`);
} catch (e) {
  fails.push('ERROR ' + String(e).split('\n')[0]);
}
await browser.close();
console.log(fails.length ? `${ENGINE} FAIL: ${fails.join('; ')}` : `${ENGINE} PASS: all ${ADDS} copies accepted`);
process.exit(fails.length ? 1 : 0);
