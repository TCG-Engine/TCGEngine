// Verify live "Main deck (N)" and "Sideboard (N)" counts on both SWUDeck layouts, all 3 engines.
// Also confirm the desktop standalone "Deck Count" pane (#myDeckSlot) is hidden.
//
// Desktop counts come from window.<zone>Data via an observer on #<zone>Slot (same pattern for
// both zones). Mobile sideboard uses the same; mobile main deck reads the hidden Count(MainDeck)
// widget (#myDeckSlot text) via observeDeckCount. We reproduce each real trigger in-page.
import { chromium, firefox, webkit } from 'playwright';

const BASE = 'http://localhost:3100/TCGEngine';
const ID = '0002611789';
const data = (n) => Array(n).fill(ID).join('<|>');

// Cross-count pairs catch a zone observer accidentally reading the other zone's data.
const pairs = [ { main: 0, side: 0 }, { main: 50, side: 10 }, { main: 3, side: 7 } ];

async function setDesktop(page, main, side) {
  return page.evaluate(({ main, side }) => {
    window.myMainDeckData = main; window.mySideboardData = side;
    document.getElementById('myMainDeckSlot').innerHTML = '<div id="myMainDeckWrapper"></div>';
    document.getElementById('mySideboardSlot').innerHTML = '<div id="mySideboardWrapper"></div>';
    // #myDeckSlot stays visible (it holds the Hand Draw button); the redundant count TEXT is
    // suppressed via font-size:0 on the slot while .widget-button keeps its own size.
    return new Promise((r) => requestAnimationFrame(() => requestAnimationFrame(() =>
      r({
        main: (document.getElementById('swuMainDeckTitle') || {}).textContent,
        side: (document.getElementById('swuSideboardTitle') || {}).textContent,
        deckPaneCountFontPx: getComputedStyle(document.getElementById('myDeckSlot')).fontSize,
      })
    )));
  }, { main, side });
}

async function setMobile(page, main, side) {
  return page.evaluate(({ main, side }) => {
    // Mobile main deck reads the Count(MainDeck) widget text; sideboard reads the data string.
    document.getElementById('myDeckSlot').innerHTML = 'Deck Count: ' + main;
    window.mySideboardData = side;
    document.getElementById('mySideboardSlot').innerHTML = '<div id="mySideboardWrapper"></div>';
    return new Promise((r) => requestAnimationFrame(() => requestAnimationFrame(() =>
      r({
        main: (document.getElementById('swuMobileDeckCount') || {}).textContent,
        side: (document.getElementById('swuMobileSideboardTitle') || {}).textContent,
      })
    )));
  }, { main, side });
}

for (const [ename, engine] of [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]]) {
  const browser = await engine.launch();
  try {
    const page = await browser.newPage();
    await page.goto(`${BASE}/SharedUI/LoginPage.php`);
    await page.fill('input[name="userID"]', 'Drixx');
    await page.fill('input[name="password"]', 'pass');
    await Promise.all([page.waitForNavigation(), page.click('button[type="submit"]')]);

    // ---- Desktop ----
    await page.goto(`${BASE}/NextTurn.php?gameName=201007&playerID=1&folderPath=SWUDeck&swuLayout=desktop`);
    await page.waitForFunction(() => document.getElementById('swuMainDeckTitle') && document.getElementById('myDeckSlot'));
    let dFails = [];
    for (const p of pairs) {
      const r = await setDesktop(page, data(p.main), data(p.side));
      if (r.main !== `Main deck (${p.main})`) dFails.push(`main want (${p.main}) got "${r.main}"`);
      if (r.side !== `Sideboard (${p.side})`) dFails.push(`side want (${p.side}) got "${r.side}"`);
      if (r.deckPaneCountFontPx !== '0px') dFails.push(`count text not suppressed: ${r.deckPaneCountFontPx}`);
    }
    console.log(`${ename.padEnd(9)} desktop  ${dFails.length ? 'FAIL ' + JSON.stringify(dFails) : 'PASS (' + pairs.length + ' pairs, count text suppressed)'}`);

    // ---- Mobile ----
    await page.goto(`${BASE}/NextTurn.php?gameName=201007&playerID=1&folderPath=SWUDeck&swuLayout=mobile`);
    await page.waitForFunction(() => document.getElementById('swuMobileDeckCount') && document.getElementById('myDeckSlot'));
    let mFails = [];
    for (const p of pairs) {
      const r = await setMobile(page, p.main, data(p.side));
      if (r.main !== `(${p.main})`) mFails.push(`main want (${p.main}) got "${r.main}"`);
      if (r.side !== `Sideboard (${p.side})`) mFails.push(`side want (${p.side}) got "${r.side}"`);
    }
    console.log(`${ename.padEnd(9)} mobile   ${mFails.length ? 'FAIL ' + JSON.stringify(mFails) : 'PASS (' + pairs.length + ' pairs)'}`);
  } catch (e) {
    console.log(`${ename.padEnd(9)} ERROR ${String(e).split('\n')[0]}`);
  } finally {
    await browser.close();
  }
}
