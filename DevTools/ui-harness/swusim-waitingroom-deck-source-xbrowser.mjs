// SWUStats / Saved toggle in the Waiting Room library and the Fill-Seat-with-Bot dialog
// (docs/superpowers/specs/2026-10-10-petranaki-swustats-link-design.md §2), Chromium + Firefox + WebKit, desktop + phone.
// Host = claudebot3 with a FIXTURE swustats_links row; SWUSim/SWUStatsDecks.php is mocked; AddBot.php is intercepted
// so the check reads WHICH deck the dialog would send without seating anything.
// Usage: node swusim-waitingroom-deck-source-xbrowser.mjs [baseURL]   ENGINES=...   SHOTS_DIR=...
import { chromium, firefox, webkit } from 'playwright';
import { execSync } from 'node:child_process';
import fs from 'node:fs';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const ALL = { chromium, firefox, webkit };
const ENGINES = Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n));
const SHOTS = process.env.SHOTS_DIR || '/tmp/swusim-wr-deck-source';
fs.mkdirSync(SHOTS, { recursive: true });
const LINK = (id) => `http://localhost:3100/TCGEngine/NextTurn.php?gameName=${id}&folderPath=SWUDeck`;
const DECKS = [
  { key: 'ss104', name: 'Fixture SWUStats One', leaders: ['ASH_009'], base: 'ASH_025', count: 0, input: LINK(104), subtitle: 'Fixture Leader One · Fixture Base One' },
  { key: 'ss107', name: 'Fixture SWUStats Two', leaders: ['SEC_010'], base: 'JTL_021', count: 0, input: LINK(107), subtitle: 'Fixture Leader Two · Fixture Base Two' },
];
const SAVED_LINK = 'https://swudb.com/deck/UIFIXTURESAVED';
const DECK = fs.readFileSync(new URL('../../SWUSim/DevTools/tests/fixtures/twinsuns_deck.json', import.meta.url), 'utf8').trim();

const sql = (q) => execSync(`docker exec -i otmtcge-swusim-mysql-server-1 sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" swusim -N'`,
  { input: q, stdio: ['pipe', 'pipe', 'ignore'] }).toString();
const fixtureUp = () => sql(`
  REPLACE INTO swustats_links (usersId, swustatsUserId, swustatsUsername, accessToken, refreshToken, accessExpires, linkedAt)
    VALUES (8, 5, 'Drixx', 'ui-fixture-access', 'ui-fixture-refresh', UNIX_TIMESTAMP() + 86400, UNIX_TIMESTAMP());
  REPLACE INTO favoritedeck (decklink, usersId, name, hero, baseId, format)
    VALUES ('${SAVED_LINK}', 8, 'UI Fixture Saved', 'SOR_005', 'SOR_027', 'premier');`);
const fixtureDown = () => sql(`
  DELETE FROM swustats_links WHERE usersId = 8 AND accessToken = 'ui-fixture-access';
  DELETE FROM favoritedeck WHERE usersId = 8 AND decklink = '${SAVED_LINK}';`);

let allOk = true;
const results = [];
const ok = (e, n, cond, extra = '') => { if (!cond) allOk = false; results.push([e, n, !!cond, extra]); };
const report = () => { for (const [e, n, pass, extra] of results) console.log(`${pass ? 'PASS' : 'FAIL'}  ${e.padEnd(16)} ${n}${extra ? '  — ' + extra : ''}`); };
setTimeout(() => { console.log('WATCHDOG: timed out'); report(); try { fixtureDown(); } catch (e) {} process.exit(9); }, 900000).unref();

async function postForm(path, params, cookie = '') {
  const r = await fetch(BASE + path, { method: 'POST', body: new URLSearchParams(params), headers: cookie ? { Cookie: cookie } : {}, redirect: 'manual' });
  const text = await r.text();
  let json = null; try { json = JSON.parse(text); } catch (e) {}
  return { json, cookie: (r.headers.get('set-cookie') || '').split(';')[0] };
}
const login = async (user) => (await postForm('AccountFiles/AttemptPasswordLogin.php', { submit: '1', userID: user, password: 'pass' })).cookie;
async function privateRoom(cookie) {
  const host = (await postForm('APIs/Lobbies/JoinQueue.php',
    { rootName: 'SWUSim', createPrivate: '1', format: 'twinsuns', deckLink: DECK, preconstructedDeck: '', game_type: '' }, cookie)).json;
  if (!host || !host.success) throw new Error('room create failed: ' + JSON.stringify(host));
  return { lobbyID: host.lobbyID, key: host.authKey };
}
async function openRoom(browser, room, cookie, viewport) {
  const ctx = await browser.newContext({ viewport });
  const [name, value] = cookie.split('=');
  await ctx.addCookies([{ name, value, url: new URL(BASE).origin }]);
  const page = await ctx.newPage();
  await page.route('**/SWUSim/SWUStatsDecks.php*', (route) => route.fulfill({ status: 200, contentType: 'application/json',
    body: JSON.stringify({ status: 'ok', decks: DECKS, swustatsUrl: '' }) }));
  let addBotBody = null;
  await page.route('**/APIs/Lobbies/AddBot.php', (route) => { addBotBody = route.request().postData();
    route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ success: false, message: 'intercepted' }) }); });
  await page.addInitScript(({ lobbyID, authKey }) => {
    localStorage.setItem('tcg:lobbyAuth:' + lobbyID, JSON.stringify({ authKey, ts: Date.now() }));
  }, { lobbyID: room.lobbyID, authKey: room.key });
  await page.goto(`${BASE}SharedUI/WaitingRoom.php?lobby=${encodeURIComponent(room.lobbyID)}`, { waitUntil: 'domcontentloaded' });
  await page.evaluate(() => { try { localStorage.removeItem('swusim:deckSourceTab'); } catch (e) {} });
  await page.reload({ waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(2500);
  return { ctx, page, addBot: () => addBotBody };
}

fixtureUp();
try {
  const cookie = await login('claudebot3');
  // the PHP toggle for fixed args, to compare the JS twin against
  const phpToggle = execSync(`docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -r 'require "SWUSim/Custom/DeckSource.php"; echo SWUDeckSourceToggle("p", "<i></i>", "<b></b>", "lbl");'`).toString();
  for (const [eng, launcher] of ENGINES) {
    const browser = await launcher.launch();
    for (const [vpName, viewport] of [['desktop', { width: 1400, height: 950 }], ['phone', { width: 390, height: 844 }]]) {
      const tag = `${eng}/${vpName}`;
      const room = await privateRoom(cookie);
      const { ctx, page, addBot } = await openRoom(browser, room, cookie, viewport);

      ok(tag, 'JS toggleHtml is byte-identical to the PHP toggle',
        (await page.evaluate(() => window.SWUDeckSource && SWUDeckSource.toggleHtml ? SWUDeckSource.toggleHtml('p', '<i></i>', '<b></b>', 'lbl') : 'MISSING')) === phpToggle);

      let r = await page.evaluate(() => {
        const lib = document.querySelector('#wr-deck-library');
        const ss = lib && lib.querySelector('.dl-select[data-source="swustats"]');
        return { toggle: !!(lib && lib.querySelector('[data-decksrc]')), opts: ss ? ss.options.length : 0, disabled: ss ? ss.disabled : null };
      });
      ok(tag, 'library has the toggle', r.toggle);
      ok(tag, 'SWUStats library list filled', r.opts === 3 && r.disabled === false, JSON.stringify(r));

      // the fill-bot dialog: toggle present, SWUStats pick is what Add Bot sends
      await page.click('.wr-fill-bot');   // same opener swusim-fill-bot-sources-xbrowser.mjs uses
      await page.waitForSelector('.wr-botdlg');
      r = await page.evaluate(() => {
        const d = document.querySelector('.wr-botdlg');
        const ss = d.querySelector('#wr-botdlg-ss');
        return { toggle: !!d.querySelector('[data-decksrc]'), ssChecked: !!d.querySelector('#wr-botdlg-src-ss:checked'), ssOpts: ss ? ss.options.length : 0 };
      });
      ok(tag, 'fill-bot dialog has the toggle, SWUStats chosen', r.toggle && r.ssChecked && r.ssOpts === 3, JSON.stringify(r));
      if (r.toggle) {
        await page.selectOption('#wr-botdlg-ss', 'ss107');
        await page.waitForTimeout(300);
        await page.screenshot({ path: `${SHOTS}/${eng}-${vpName}-fillbot-swustats.png` });
        await page.click('.wr-botdlg-add');
        await page.waitForTimeout(500);
        ok(tag, 'Add Bot sends the SWUStats pick', new URLSearchParams(addBot() || '').get('botDeck') === LINK(107), String(addBot()));

        // switch to Saved inside the dialog → the saved pick is sent, the hidden SWUStats one is not
        await page.click('label[for="wr-botdlg-src-saved"]');
        await page.evaluate((link) => {
          const s = document.getElementById('wr-botdlg-saved');
          const o = [...s.options].find((x) => x.getAttribute('data-queue-input') === link || x.getAttribute('data-id') === link);
          s.value = o.value; s.dispatchEvent(new Event('change', { bubbles: true }));
        }, SAVED_LINK);
        await page.waitForTimeout(300);
        await page.screenshot({ path: `${SHOTS}/${eng}-${vpName}-fillbot-saved.png` });
        await page.click('.wr-botdlg-add');
        await page.waitForTimeout(500);
        ok(tag, 'Add Bot sends the Saved pick after switching', new URLSearchParams(addBot() || '').get('botDeck') === SAVED_LINK, String(addBot()));
        ok(tag, 'the switch is remembered', (await page.evaluate(() => localStorage.getItem('swusim:deckSourceTab'))) === 'saved');
      }
      await ctx.close();

      // the page library's chosenDeck(): SWUStats deck picked, then switched to an EMPTY Saved tab → nothing is sent
      // (the hidden SWUStats select must not decide the deck). Runs with the saved-deck fixture removed.
      if (vpName === 'desktop') {
        sql(`DELETE FROM favoritedeck WHERE usersId = 8 AND decklink = '${SAVED_LINK}';`);
        const room2 = await privateRoom(cookie);
        const o2 = await openRoom(browser, room2, cookie, viewport);
        let updates = 0;
        await o2.page.route('**/APIs/Lobbies/UpdateLobbyDeck.php', (route) => { updates++;
          route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ success: false, message: 'intercepted' }) }); });
        await o2.page.evaluate(() => { document.getElementById('wr-deck').style.display = ''; });
        await o2.page.selectOption('#wr-deck-library .dl-select[data-source="swustats"]', 'ss107');
        await o2.page.click('label[for="wr-lib-src-saved"]');
        await o2.page.evaluate(() => { const i = document.getElementById('wr-deck-input'); if (i) i.value = ''; });
        await o2.page.click('#wr-deck-btn');
        await o2.page.waitForTimeout(600);
        ok(tag, 'empty Saved tab: the hidden SWUStats pick is NOT sent', updates === 0, `${updates} UpdateLobbyDeck call(s)`);
        await o2.ctx.close();
        sql(`REPLACE INTO favoritedeck (decklink, usersId, name, hero, baseId, format)
               VALUES ('${SAVED_LINK}', 8, 'UI Fixture Saved', 'SOR_005', 'SOR_027', 'premier');`);
      }
    }
    await browser.close();
  }
} finally {
  fixtureDown();
}
report();
console.log(allOk ? 'ALL PASS' : 'SOME FAILED');
process.exit(allOk ? 0 : 1);
