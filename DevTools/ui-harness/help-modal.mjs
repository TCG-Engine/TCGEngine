// The help modal is a fixed-position overlay with maxHeight/overflowY; adding ~15 lines of text
// can push it past the viewport. Layout differs per engine, so check all three.
import { chromium, firefox, webkit } from 'playwright';

const BASE = 'http://localhost:3100/TCGEngine';
for (const [name, engine] of [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]]) {
  const browser = await engine.launch();
  try {
    const page = await browser.newPage();
    await page.goto(`${BASE}/SharedUI/LoginPage.php`);
    await page.fill('input[name="userID"]', 'Drixx');
    await page.fill('input[name="password"]', 'pass');
    await Promise.all([page.waitForNavigation(), page.click('button[type="submit"]')]);
    await page.goto(`${BASE}/NextTurn.php?gameName=201007&playerID=1&folderPath=SWUDeck`);
    await page.waitForFunction(() => typeof ShowFilterBarHelp === 'function');
    await page.evaluate(() => ShowFilterBarHelp());
    await page.waitForTimeout(400);

    const r = await page.evaluate(() => {
      const overlay = [...document.querySelectorAll('div')]
        .find(d => d.style.position === 'fixed' && d.style.zIndex === '2000');
      if (!overlay) return { err: 'overlay not found' };
      const box = overlay.getBoundingClientRect();
      const text = overlay.innerText;
      return {
        hasAspectSection: text.includes('Aspect filtering'),
        hasDoublesHint:   text.includes('c:rr'),
        hasNegationNote:  text.includes('contains none of'),
        overflowsViewport: box.bottom > window.innerHeight + 1 || box.right > window.innerWidth + 1,
      };
    });
    const pass = !r.err && r.hasAspectSection && r.hasDoublesHint && r.hasNegationNote && !r.overflowsViewport;
    console.log(`${name.padEnd(9)} ${pass ? 'PASS' : 'FAIL'}  ${JSON.stringify(r)}`);
    await page.screenshot({ path: `/tmp/help-modal-${name}.png` });
  } catch (e) {
    console.log(`${name.padEnd(9)} ERROR ${String(e).split('\n')[0]}`);
  } finally {
    await browser.close();
  }
}
