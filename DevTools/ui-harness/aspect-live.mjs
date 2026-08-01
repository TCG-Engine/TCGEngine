// Exercise the GENERATED ShouldFilter against the real card dictionary in a real browser.
// The node tests cover SWUAspectMatch in isolation; this proves the wiring — alias, routing,
// and inlining — actually works on live data.
import { chromium } from 'playwright';

const BASE = 'http://localhost:3100/TCGEngine';
const browser = await chromium.launch();
const page = await browser.newPage();
await page.goto(`${BASE}/SharedUI/LoginPage.php`);
await page.fill('input[name="userID"]', 'Drixx');
await page.fill('input[name="password"]', 'pass');
await Promise.all([page.waitForNavigation(), page.click('button[type="submit"]')]);
await page.goto(`${BASE}/NextTurn.php?gameName=201007&playerID=1&folderPath=SWUDeck`);
await page.waitForFunction(() => typeof ShouldFilter === 'function' && typeof SWUAspectMatch === 'function');

const out = await page.evaluate(() => {
  // ShouldFilter returns TRUE to HIDE, so "matches" is the negation.
  const matches = (id, q) => !ShouldFilter(id, q);
  const ids = Object.keys(window.aspectData || {});
  const sample = (q) => ids.filter((id) => matches(id, q)).length;
  const neutralCount = ids.filter((id) => (window.aspectData[id] || '') === '').length;
  return {
    haveDictionary: ids.length,
    neutralCardsInDict: neutralCount,
    'c:rr': sample('c:rr'),
    'c:r': sample('c:r'),
    'c:n': sample('c:n'),
    'c<=gbk': sample('c<=gbk'),
    'c=gbk': sample('c=gbk'),
    'aspect>=bk': sample('aspect>=bk'),
    'aspect!=bk': sample('aspect!=bk'),
    'aspect=bk': sample('aspect=bk'),
    'c:vig_fallback': sample('c:vig'),
    'cost=3': sample('cost=3'),
    'cost!=3': sample('cost!=3'),
    'type=unit': sample('type=unit'),
    'type!=unit': sample('type!=unit'),
    total: ids.length,
  };
});
console.log(JSON.stringify(out, null, 2));
await browser.close();
