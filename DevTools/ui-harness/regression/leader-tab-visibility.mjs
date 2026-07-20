// Regression: a single-leader (premier) deck must never show Leader1/Leader2 browse tabs —
// those are Twin Suns only. The mobile layout re-renders the pane on a tab switch and moves the
// tabs OUT of #myCardPane, so a query scoped to that container silently stops hiding them.
import { chromium, webkit } from 'playwright';

const BASE = 'http://localhost:3100/TCGEngine';
const PREMIER = process.env.PREMIER || '100431';
const TWINSUNS = process.env.TWINSUNS || '201009';
const ENGINES = { chromium, webkit };
let failures = 0;
const check = (label, pass, detail) => {
  if (!pass) failures++;
  console.log(`   ${pass ? 'PASS' : 'FAIL'}  ${label}${detail ? ` — ${detail}` : ''}`);
};

// Visible tab labels, document-wide (the mobile layout keeps a second hidden copy).
const visibleTabs = () => [...document.querySelectorAll('.panelTab')]
  .filter(t => t.getBoundingClientRect().width > 0)
  .map(t => t.textContent.trim());

for (const [name, engine] of Object.entries(ENGINES)) {
  console.log(`\n=== ${name} ===`);
  const browser = await engine.launch();
  const ctx = await browser.newContext({
    viewport: { width: 390, height: 844 }, deviceScaleFactor: 2, hasTouch: true, isMobile: true,
  });
  const page = await ctx.newPage();
  await page.goto(`${BASE}/SharedUI/LoginPage.php`);
  await page.fill('input[name="userID"]', 'Drixx');
  await page.fill('input[name="password"]', 'pass');
  await Promise.all([page.waitForNavigation(), page.click('button[type="submit"]')]);

  for (const [deck, format] of [[PREMIER, 'premier'], [TWINSUNS, 'twinsuns']]) {
    await page.goto(`${BASE}/NextTurn.php?gameName=${deck}&playerID=1&folderPath=SWUDeck&swuLayout=mobile`);
    await page.waitForTimeout(3500);
    check(`${format}: format global correct`,
      (await page.evaluate(() => window.SWU_DECK_FORMAT)) === format);

    const onLoad = await page.evaluate(visibleTabs);
    const expectTwin = format === 'twinsuns';
    check(`${format}: on load tabs correct`,
      expectTwin
        ? onLoad.includes('Leader1') && onLoad.includes('Leader2') && !onLoad.includes('Leaders')
        : onLoad.includes('Leaders') && !onLoad.includes('Leader1') && !onLoad.includes('Leader2'),
      onLoad.join(' | '));

    // switch panes — this is what breaks it
    for (const target of ['Cards', 'Bases', 'Cards']) {
      await page.evaluate((t) => {
        const el = [...document.querySelectorAll('.panelTab')]
          .find(e => e.textContent.trim() === t && e.getBoundingClientRect().width > 0);
        if (el) el.click();
      }, target);
      await page.waitForTimeout(1200);
    }

    const after = await page.evaluate(visibleTabs);
    check(`${format}: tabs still correct after pane switches`,
      expectTwin
        ? after.includes('Leader1') && after.includes('Leader2') && !after.includes('Leaders')
        : after.includes('Leaders') && !after.includes('Leader1') && !after.includes('Leader2'),
      after.join(' | '));
  }
  await browser.close();
}
console.log(`\n${failures === 0 ? 'ALL CHECKS PASSED' : failures + ' CHECK(S) FAILED'}`);
process.exit(failures === 0 ? 0 : 1);
