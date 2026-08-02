// Verify the live "Sideboard (N)" count on both SWUDeck layouts, in all three engines.
//
// The count reads window.mySideboardData (the zone data string), and the observer refreshes it
// when #mySideboardSlot re-renders. Production order (NextTurnRender.php) is: set mySideboardData,
// then replace mySideboardSlot.innerHTML. We reproduce exactly that order in-page, so this drives
// the real observer -> counter path without depending on a specific deck loading (which needs
// per-deck auth) or mutating any stored deck.
import { chromium, firefox, webkit } from 'playwright';

const BASE = 'http://localhost:3100/TCGEngine';
// mySideboardData format: entries joined by '<|>', each "cardID ...". '-' entries are placeholders.
const ID = '0002611789', ID2 = '1234567890', ID3 = '5555555555';
const cases = [
  { label: 'empty',            data: '',                              expect: 0 },
  { label: 'three distinct',   data: [ID, ID2, ID3].join('<|>'),      expect: 3 },
  { label: 'duplicates x2+1',  data: [ID, ID, ID2].join('<|>'),       expect: 3 },  // dupes counted, not collapsed
  { label: 'placeholder skip', data: ['-', ID, '-'].join('<|>'),      expect: 1 },  // '-' filtered out
  { label: 'full 10',          data: Array(10).fill(ID).join('<|>'),  expect: 10 },
  { label: 'back to one',      data: ID,                              expect: 1 },  // shrink updates live
];

const LAYOUTS = [
  { name: 'desktop', query: 'desktop', titleId: 'swuSideboardTitle' },
  { name: 'mobile',  query: 'mobile',  titleId: 'swuMobileSideboardTitle' },
];

for (const [ename, engine] of [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]]) {
  const browser = await engine.launch();
  try {
    const page = await browser.newPage();
    await page.goto(`${BASE}/SharedUI/LoginPage.php`);
    await page.fill('input[name="userID"]', 'Drixx');
    await page.fill('input[name="password"]', 'pass');
    await Promise.all([page.waitForNavigation(), page.click('button[type="submit"]')]);

    for (const layout of LAYOUTS) {
      await page.goto(`${BASE}/NextTurn.php?gameName=201007&playerID=1&folderPath=SWUDeck&swuLayout=${layout.query}`);
      // The observer is wired in the layout's init (DOMContentLoaded), independent of deck data.
      await page.waitForFunction(
        (id) => document.getElementById(id) && document.getElementById('mySideboardSlot'),
        layout.titleId,
        { timeout: 15000 }
      );

      const results = [];
      for (const c of cases) {
        const actual = await page.evaluate(async ({ data, titleId }) => {
          // Reproduce NextTurnRender order: data first, then slot re-render.
          window.mySideboardData = data;
          document.getElementById('mySideboardSlot').innerHTML = '<div id="mySideboardWrapper"></div>';
          await new Promise((r) => requestAnimationFrame(() => requestAnimationFrame(r)));
          return (document.getElementById(titleId) || {}).textContent;
        }, { data: c.data, titleId: layout.titleId });
        const want = `Sideboard (${c.expect})`;
        results.push({ case: c.label, want, actual, ok: actual === want });
      }

      const failed = results.filter((r) => !r.ok);
      const status = failed.length ? 'FAIL' : 'PASS';
      console.log(`${ename.padEnd(9)} ${layout.name.padEnd(8)} ${status}` +
        (failed.length ? '  ' + JSON.stringify(failed) : `  (${results.length} cases)`));
    }
  } catch (e) {
    console.log(`${ename.padEnd(9)} ERROR ${String(e).split('\n')[0]}`);
  } finally {
    await browser.close();
  }
}
