// Clicking the deck's top-card EYE badge must open the preview at once and keep it open until the next click.
//
// THE BUG (game 1459263, 2026-10-03). With HMW_205 Intelligence Agency on your base ("you may look at the top
// card of your deck at any time") the deck shows an eye badge. Hovering it previewed the top card, but its
// onclick called the SAME hover path (ShowTopCardPeek → ShowCardDetailByCardID): a 400ms dwell, then a
// preview that closes on mouseout. So after a hover had already opened the preview, a click visibly did
// nothing, and a click on its own only "worked" if the pointer sat still for the dwell. The owner's ruling:
// a click opens an INSTANT, STICKY preview — it stays up when the pointer leaves, and the next click
// anywhere (or the badge again) closes it, like the discard-pile and base-tab panels.
//
// Seeds a minimal 2-seat board through the Test Schema Editor's own TestSchemaSetup.php and drives the real
// badge. Run:  node DevTools/ui-harness/swusim-topcard-peek-click-xbrowser.mjs [chromium firefox webkit]
// Exit 0 = pass, 1 = regression, 2 = environment not ready.
import { chromium, firefox, webkit } from 'playwright';

const BASE = 'http://localhost:3400/TCGEngine/';
const TOP = 'SOR_092';   // the deck's top card — what the preview must show
const SCHEMA = `## GIVEN
CommonSetup: rrk/bbw
WithP1BaseUpgrade: HMW_205
WithP1Deck: [${TOP} SOR_058 SOR_217]
WithP1Hand: [SOR_078 SOR_233]
WithP1GroundArena: [SOR_032:1:0]
## WHEN
## EXPECT
`;

const engines = process.argv.slice(2).length ? process.argv.slice(2) : ['chromium', 'firefox', 'webkit'];
let fails = 0, checks = 0;
const ok = (name, cond, extra) => {
  checks++;
  if (!cond) { fails++; console.log(`FAIL ${name}${extra === undefined ? '' : '  ' + JSON.stringify(extra)}`); }
};

async function seedGame() {
  const fd = new FormData();
  fd.append('schema', SCHEMA);
  let data;
  try {
    const res = await fetch(BASE + 'SWUSim/TestSchemaSetup.php', { method: 'POST', body: fd });
    data = await res.json();
  } catch (e) { console.log('ENVIRONMENT NOT READY: TestSchemaSetup.php failed — ' + e.message); process.exit(2); }
  if (!data || !data.gameName) { console.log('ENVIRONMENT NOT READY: no gameName — ' + JSON.stringify(data)); process.exit(2); }
  return data.gameName;
}

// What the preview is showing right now: 'none', or the art filename.
const preview = page => page.evaluate(() => {
  const d = document.getElementById('cardDetail');
  if (!d || getComputedStyle(d).display === 'none') return 'none';
  const img = d.querySelector('img');
  return img ? img.src.split('/').pop() : 'open-no-img';
});

for (const engine of engines) {
  const game = await seedGame();
  const browser = await ({ chromium, firefox, webkit })[engine].launch();
  for (const width of [1728, 1280]) {
    const tag = `${engine}@${width}`;
    const page = await (await browser.newContext({ viewport: { width, height: 950 } })).newPage();
    page.on('pageerror', e => { fails++; console.log(`FAIL ${tag} pageerror ${e.message}`); });
    await page.goto(`${BASE}NextTurn.php?folderPath=SWUSim&gameName=${game}&playerID=1&authKey=testschema`, { waitUntil: 'load' });
    try {
      await page.waitForSelector('.topcard-peek-badge', { timeout: 15000 });
    } catch { console.log(`ENVIRONMENT NOT READY: ${tag} no .topcard-peek-badge rendered`); process.exit(2); }
    await page.waitForTimeout(600);
    const [bx, by] = await page.evaluate(() => {
      const r = document.querySelector('.topcard-peek-badge').getBoundingClientRect();
      return [r.x + r.width / 2, r.y + r.height / 2];
    });
    // A neutral spot that is not a card, so moving there fires no hover preview of its own.
    const away = [8, 8];
    const reset = async () => {
      await page.mouse.move(...away);
      await page.evaluate(() => HideCardDetail(true));
      await page.waitForTimeout(250);
    };

    // 1. CONTROL — plain hover is unchanged: opens after the dwell, closes on mouseout.
    await reset();
    await page.mouse.move(bx, by);
    await page.waitForTimeout(1300);
    ok(`${tag} hover opens the top card`, (await preview(page)).startsWith(TOP), await preview(page));
    await page.mouse.move(...away);
    await page.waitForTimeout(300);
    ok(`${tag} hover preview closes on mouseout (control)`, (await preview(page)) === 'none', await preview(page));

    // 2. A click opens it AT ONCE — well inside the 400ms hover dwell.
    await reset();
    await page.mouse.move(bx, by);
    await page.mouse.down(); await page.mouse.up();
    await page.waitForTimeout(250);
    ok(`${tag} click opens the preview immediately`, (await preview(page)).startsWith(TOP), await preview(page));

    // 3. It is STICKY: leaving the badge does not close it.
    await page.mouse.move(...away);
    await page.waitForTimeout(1200);
    ok(`${tag} clicked preview survives mouseout`, (await preview(page)).startsWith(TOP), await preview(page));

    // 4. Hovering another card while pinned must not swap the preview (it would then never close).
    const unit = await page.evaluate(() => {
      const a = [...document.querySelectorAll("a[onmouseover*='ShowCardDetail']")]
        .find(el => el.getBoundingClientRect().width > 20 && !el.closest('.topcard-peek-badge'));
      if (!a) return null;
      const r = a.getBoundingClientRect(); return [r.x + r.width / 2, r.y + r.height / 2];
    });
    if (unit) {
      await page.mouse.move(...unit);
      await page.waitForTimeout(1300);
      ok(`${tag} hovering another card keeps the pinned top card`, (await preview(page)).startsWith(TOP), await preview(page));
      await page.mouse.move(...away);
      await page.waitForTimeout(200);
    } else ok(`${tag} found another card to hover`, false);

    // 5. The next click anywhere closes it.
    await page.mouse.click(...away);
    await page.waitForTimeout(300);
    ok(`${tag} a click elsewhere closes the pinned preview`, (await preview(page)) === 'none', await preview(page));

    // 6. Clicking the badge a second time toggles it closed.
    await reset();
    await page.mouse.move(bx, by);
    await page.mouse.down(); await page.mouse.up();
    await page.waitForTimeout(250);
    await page.mouse.down(); await page.mouse.up();
    await page.waitForTimeout(300);
    ok(`${tag} a second badge click closes it`, (await preview(page)) === 'none', await preview(page));

    // 7. And after closing, plain hover works again (no stuck pin state).
    await reset();
    await page.mouse.move(bx, by);
    await page.waitForTimeout(1300);
    ok(`${tag} hover works again after unpinning`, (await preview(page)).startsWith(TOP), await preview(page));
    await page.mouse.move(...away);
    await page.waitForTimeout(300);
    ok(`${tag} ...and closes on mouseout again`, (await preview(page)) === 'none', await preview(page));

    await page.close();
  }
  await browser.close();
}
console.log(`${checks - fails}/${checks} checks passed`);
process.exit(fails ? 1 : 0);
