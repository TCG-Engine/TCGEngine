// SWUStats / Saved deck-source toggle in the main menu's setup modals
// (docs/superpowers/specs/2026-10-10-petranaki-swustats-link-design.md §2), Chromium + Firefox + WebKit, desktop + phone.
// Signed in as claudebot3 with a FIXTURE swustats_links row; SWUSim/SWUStatsDecks.php is MOCKED per check with
// page.route, so no SWUStats is involved. Deck inputs are real local SWUStats deck links (dev decks 104 / 107).
// Usage: node swusim-deck-source-xbrowser.mjs [baseURL]   ENGINES=chromium,firefox,webkit (default all)   SHOTS_DIR=...
import { chromium, firefox, webkit } from 'playwright';
import { execSync } from 'node:child_process';
import fs from 'node:fs';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const ALL = { chromium, firefox, webkit };
const ENGINES = Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n));
const SHOTS = process.env.SHOTS_DIR || '/tmp/swusim-deck-source';
fs.mkdirSync(SHOTS, { recursive: true });
const PICKERS = ['pvp-saved', 'ts-saved', 'ab-saved', 'ab-bot-saved', 'sp-saved', 'sp-saved-2'];
const LINK = (id) => `http://localhost:3100/TCGEngine/NextTurn.php?gameName=${id}&folderPath=SWUDeck`;
const DECKS = [
  { key: 'ss104', name: 'Fixture SWUStats One', leaders: ['ASH_009'], base: 'ASH_025', count: 0, input: LINK(104), subtitle: 'Fixture Leader One · Fixture Base One' },
  { key: 'ss107', name: 'Fixture SWUStats Two', leaders: ['SEC_010'], base: 'JTL_021', count: 0, input: LINK(107), subtitle: 'Fixture Leader Two · Fixture Base Two' },
];
const SAVED_LINK = 'https://swudb.com/deck/UIFIXTURESAVED';

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

// The mock is installed on the CONTEXT before signing in: login redirects to the menu, and a real
// SWUStatsDecks.php call with the fixture's fake tokens would (correctly) get invalid_grant and clear the link.
async function signedIn(browser, user, viewport) {
  const ctx = await browser.newContext({ viewport });
  await mockDecks(ctx);
  const page = await ctx.newPage();
  await page.goto(BASE + 'SharedUI/LoginPage.php', { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="userID"]', user);
  await page.fill('input[name="password"]', 'pass');
  await Promise.all([page.waitForNavigation({ waitUntil: 'load' }).catch(() => {}), page.click('button[type="submit"]')]);
  return { ctx, page };
}
let mockBody = { status: 'ok', decks: DECKS, swustatsUrl: 'http://127.0.0.1:3100/TCGEngine/SharedUI/MainMenu.php' };
async function mockDecks(target) {
  await target.route('**/SWUSim/SWUStatsDecks.php*', (route) =>
    route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(mockBody) }));
}
const openMenu = async (page) => { await page.goto(BASE + 'SharedUI/MainMenu.php', { waitUntil: 'load' }); await page.waitForTimeout(800); };

fixtureUp();
try {
  for (const [eng, launcher] of ENGINES) {
    const browser = await launcher.launch();
    for (const [vpName, viewport] of [['desktop', { width: 1440, height: 1000 }], ['phone', { width: 390, height: 844 }]]) {
      const tag = `${eng}/${vpName}`;
      const { ctx, page } = await signedIn(browser, 'claudebot3', viewport);
      mockBody = { status: 'ok', decks: DECKS, swustatsUrl: 'http://127.0.0.1:3100/TCGEngine/SharedUI/MainMenu.php' };
      await openMenu(page);
      await page.evaluate(() => { try { localStorage.removeItem('swusim:deckSourceTab'); } catch (e) {} });
      await openMenu(page);

      // 1 ─ every picker has a toggle, SWUStats chosen, saved panel hidden, list filled
      const state = await page.evaluate((ids) => ids.map((id) => {
        const ss = document.getElementById(id + '-src-ss'), sv = document.getElementById(id + '-src-saved');
        const root = ss && ss.closest('[data-decksrc]');
        const ssSel = document.getElementById(id + '-ss');
        return { id, has: !!root, ssOn: !!(ss && ss.checked), svOff: !!(sv && !sv.checked),
                 savedHidden: !!(root && root.querySelector('.decksrc__panel[data-src="saved"]').hidden),
                 opts: ssSel ? [...ssSel.options].map((o) => o.getAttribute('data-deck-input')).filter(Boolean) : [] };
      }), PICKERS);
      for (const s of state) {
        ok(tag, `${s.id}: toggle present, SWUStats chosen, Saved hidden`, s.has && s.ssOn && s.svOff && s.savedHidden, JSON.stringify(s));
        ok(tag, `${s.id}: SWUStats list filled from the endpoint`, s.opts.length === 2 && s.opts[1] === LINK(107), JSON.stringify(s.opts));
      }

      // 2 ─ picking a SWUStats deck plays it
      let r = await page.evaluate((want) => {
        const dlg = document.getElementById('setup-pvp'); dlg.showModal();
        const sel = document.getElementById('pvp-saved-ss');
        sel.value = 'ss107'; sel.dispatchEvent(new Event('change', { bubbles: true }));
        window.SYNC_ACTIVE_SETUP();
        return { got: document.getElementById('deck-link').value, want };
      }, LINK(107));
      ok(tag, 'a SWUStats pick becomes the played deck', r.got === r.want, r.got);
      const sub = await page.evaluate(() => document.querySelector('#setup-pvp .deckprev--ss107 .deck__sub')?.textContent || '');
      ok(tag, 'a SWUStats row shows card names, not ids', sub === 'Fixture Leader Two · Fixture Base Two', sub);
      await page.waitForTimeout(400);
      await page.screenshot({ path: `${SHOTS}/${eng}-${vpName}-pvp-swustats.png` });

      // 3 ─ switching to Saved plays the saved pick
      await page.click('label[for="pvp-saved-src-saved"]');
      r = await page.evaluate(() => {
        const dlg = document.getElementById('setup-pvp');
        const sel = document.getElementById('pvp-saved');
        const opt = [...sel.options].find((o) => o.getAttribute('data-deck-input') === 'https://swudb.com/deck/UIFIXTURESAVED');
        sel.value = opt.value; sel.dispatchEvent(new Event('change', { bubbles: true }));
        window.SYNC_ACTIVE_SETUP();
        return { got: document.getElementById('deck-link').value,
                 ssHidden: dlg.querySelector('.decksrc__panel[data-src="swustats"]').hidden,
                 stored: localStorage.getItem('swusim:deckSourceTab') };
      });
      ok(tag, 'Saved tab shows, SWUStats panel hidden', r.ssHidden === true);
      ok(tag, 'a Saved pick becomes the played deck', r.got === 'https://swudb.com/deck/UIFIXTURESAVED', r.got);
      ok(tag, 'the choice is stored', r.stored === 'saved', String(r.stored));
      await page.waitForTimeout(300);
      await page.screenshot({ path: `${SHOTS}/${eng}-${vpName}-pvp-saved.png` });

      // 4 ─ a hidden panel's select never decides the deck
      r = await page.evaluate(() => {
        const dlg = document.getElementById('setup-pvp');
        dlg.querySelector('input[data-detect]').value = '';
        document.getElementById('pvp-saved-ss').value = 'ss104';     // hidden panel
        window.SYNC_ACTIVE_SETUP();
        return document.getElementById('deck-link').value;
      });
      ok(tag, 'the hidden SWUStats select does not decide the deck', r === 'https://swudb.com/deck/UIFIXTURESAVED', r);

      // 5 ─ remembered across a reload, on every picker
      await openMenu(page);
      r = await page.evaluate((ids) => ids.every((id) => document.getElementById(id + '-src-saved').checked
        && !document.getElementById(id + '-src-saved').closest('[data-decksrc]').querySelector('.decksrc__panel[data-src="saved"]').hidden), PICKERS);
      ok(tag, 'Saved is remembered on reload, on every picker', r === true);
      await page.evaluate(() => localStorage.removeItem('swusim:deckSourceTab'));

      // 6 ─ saving a deck rebuilds the Saved pickers but leaves the SWUStats ones alone
      await openMenu(page);
      r = await page.evaluate(() => {
        const before = [...document.getElementById('pvp-saved-ss').options].map((o) => o.value).join(',');
        DECK_PICKERS_REBUILD([{ key: 'zz', name: 'Just Saved', input: 'https://swudb.com/deck/JUSTSAVED', leaders: [], base: '' }]);
        const after = document.getElementById('pvp-saved-ss');
        return { before, after: after ? [...after.options].map((o) => o.value).join(',') : 'GONE',
                 savedHasNew: !![...document.getElementById('pvp-saved').options].find((o) => o.value === 'zz') };
      });
      ok(tag, 'a save leaves the SWUStats list intact', r.after === r.before, JSON.stringify(r));
      ok(tag, 'a save still rebuilds the Saved list', r.savedHasNew === true);

      // 7 ─ panel states
      for (const [status, want] of [['ok', 'No hearted decks on SWUStats yet'], ['unavailable', 'Couldn’t reach SWUStats'], ['relink', 'Reconnect SWUStats']]) {
        mockBody = { status, decks: [], swustatsUrl: 'http://127.0.0.1:3100/TCGEngine/SharedUI/MainMenu.php' };
        await openMenu(page);
        const txt = await page.evaluate(() => document.querySelector('#setup-pvp .decksrc__panel[data-src="swustats"] .deckpick__empty')?.textContent || '');
        ok(tag, `state ${status} (no decks) → "${want}"`, txt.includes(want), txt);
      }
      // 7b ─ the REVERSE of check 4: a SWUStats panel with no list (here "no hearted decks") must not fall back to the
      //      hidden Saved select — neither when the deck is read, nor when the player switches to SWUStats.
      mockBody = { status: 'ok', decks: [], swustatsUrl: '' };
      await openMenu(page);
      r = await page.evaluate(() => {
        const dlg = document.getElementById('setup-pvp'); dlg.showModal();
        dlg.querySelector('input[data-detect]').value = '';
        window.SYNC_ACTIVE_SETUP();
        return document.getElementById('deck-link').value;
      });
      ok(tag, 'an empty SWUStats panel plays nothing (not the hidden saved deck)', r === '', r);
      await page.evaluate(() => { localStorage.setItem('swusim:deckSourceTab', 'saved'); });
      await openMenu(page);
      await page.evaluate(() => { const d = document.getElementById('setup-pvp'); d.showModal(); d.querySelector('input[data-detect]').value = ''; });
      await page.click('label[for="pvp-saved-src-ss"]');
      r = await page.evaluate(() => {
        const dlg = document.getElementById('setup-pvp');
        const m = dlg.querySelector('[data-pickmsg="own"]');
        return { link: dlg.querySelector('input[data-detect]').value, msg: m && !m.hidden ? m.textContent.trim() : '' };
      });
      ok(tag, 'switching to an empty SWUStats panel does not load the hidden saved deck', r.link === '' && r.msg === '', JSON.stringify(r));
      await page.evaluate(() => localStorage.removeItem('swusim:deckSourceTab'));

      // Retry from the error state loads the list
      mockBody = { status: 'unavailable', decks: [] };
      await openMenu(page);
      await page.evaluate(() => { document.getElementById('setup-pvp').showModal(); });
      await page.screenshot({ path: `${SHOTS}/${eng}-${vpName}-pvp-unavailable.png` });
      mockBody = { status: 'ok', decks: DECKS, swustatsUrl: '' };
      await page.click('#setup-pvp .decksrc__retry');
      await page.waitForTimeout(800);
      r = await page.evaluate(() => (document.getElementById('pvp-saved-ss') || { options: [] }).options.length);
      ok(tag, 'Retry loads the list', r === 2, String(r));
      mockBody = { status: 'relink', decks: [] };
      await openMenu(page);
      const relinkHref = await page.evaluate(() => document.querySelector('#setup-pvp .decksrc__panel[data-src="swustats"] a')?.getAttribute('href') || '');
      ok(tag, 'Reconnect points at the Profile', relinkHref.endsWith('SharedUI/Sites/SWUSim/Profile.php'), relinkHref);
      await ctx.close();
    }

    // 8 ─ unlinked and guests: no toggle anywhere
    {
      const { ctx, page } = await signedIn(browser, 'claudebot2', { width: 1440, height: 1000 });
      await openMenu(page);
      ok(eng, 'unlinked account: no toggle', (await page.locator('[data-decksrc]').count()) === 0);
      await ctx.close();
      const g = await browser.newContext(); const gp = await g.newPage();
      await gp.goto(BASE + 'SharedUI/MainMenu.php', { waitUntil: 'load' });
      ok(eng, 'guest: no toggle', (await gp.locator('[data-decksrc]').count()) === 0);
      await g.close();
    }
    await browser.close();
  }
} catch (e) {
  ok('harness', 'ran to completion', false, String(e).split('\n')[0]);
} finally {
  fixtureDown();
}
report();
console.log(allOk ? 'ALL PASS' : 'SOME FAILED');
process.exit(allOk ? 0 : 1);
