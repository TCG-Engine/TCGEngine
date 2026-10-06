// SAVE DECK SAVES IN PLACE (owner, 2026-10-04): "today, when someone hits Save Deck, it closes the modal. It'd be nice if it
// saves it in place without having to click back and select it."
// A signed-in save used to end in location.reload(): the modal closed and the new deck was not chosen. Now the saved-deck
// pickers are rebuilt in place (DECK_PICKERS_REBUILD, from the save response), the new deck is SELECTED in the modal that
// saved it — so it is the deck that gets played — and the "Saved …" line is shown. The page does not reload.
// Three scenarios, in Chromium, Firefox and WebKit:
//   account-first  — claudebot1 with NO saved decks: the pickers start as the empty state (no <select> at all);
//   account-more   — claudebot1 with one deck already saved: the pickers start server-rendered AND enhanced (the listbox),
//                    so the rebuild must drop the old popups, keep other pickers' choices and the bot picker's "none" row;
//   guest          — the localStorage path, which now also selects the new deck in the modal that saved it;
//   guest-more     — a guest with a deck already in this browser: without the explicit choice, the picker would keep it.
// claudebot1's test decks are deleted before and after.
// Usage: node swusim-save-deck-inplace-xbrowser.mjs     ENGINES=chromium,firefox,webkit (default all)
import { chromium, firefox, webkit } from 'playwright';

const BASE = process.env.SWU_BASE || 'http://localhost:3400/TCGEngine';
const MENU_URL = BASE + '/SharedUI/MainMenu.php';
const LINK = 'https://swudb.com/deck/eeFFtweXI';        // the deck saved through the UI (CLAUDE.md premier list)
const OTHER = 'https://swudb.com/deck/HeEAAQjVtrhee';   // the deck already saved in 'account-more'
const ALL = { chromium, firefox, webkit };
const ENGINES = Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n));
const SHOTS = process.env.SHOTS_DIR || '/tmp';

let fails = 0, checks = 0;
const ok = (name, cond, detail) => { checks++; console.log(`${cond ? 'PASS' : 'FAIL'}  ${name}${detail ? '  — ' + detail : ''}`); if (!cond) fails++; };

async function post(path, params, cookie = '') {
  const r = await fetch(BASE + '/' + path, { method: 'POST', body: new URLSearchParams(params), headers: cookie ? { Cookie: cookie } : {}, redirect: 'manual' });
  const text = await r.text();
  let json = null; try { json = JSON.parse(text); } catch (e) {}
  return { json, cookie: (r.headers.get('set-cookie') || '').split(';')[0] };
}
const cookie = (await post('AccountFiles/AttemptPasswordLogin.php', { submit: '1', userID: 'claudebot1', password: 'pass' })).cookie;
if (!cookie) { console.log('FAIL  could not log in as claudebot1'); process.exit(1); }
const apiSave = async (link) => (await post('SWUSim/SavedDecks.php', { action: 'save', deckInput: link }, cookie)).json;
// ⚠ NON-DESTRUCTIVE: claudebot1 is a shared fixture account (swusim-menu2-pickers.mjs needs it to own a saved deck). Note which
// of the two test decks it owned BEFORE this run, and put those back at the end — the deletes below are only for the test.
const menuHtml = await (await fetch(MENU_URL, { headers: { Cookie: cookie } })).text();
const owned = [LINK, OTHER].filter(l => menuHtml.includes('data-deck-input="' + l + '"'));
const ids = {};
for (const l of [LINK, OTHER]) {
  const r = await apiSave(l);
  if (!r || !r.success) { console.log('FAIL  this deck link does not save: ' + l + ' ' + JSON.stringify(r)); process.exit(1); }
  ids[l] = r.decklink;
}
const forget = async (l) => post('SWUSim/SavedDecks.php', { action: 'delete', decklink: ids[l] }, cookie);
const forgetAll = async () => { await forget(LINK); await forget(OTHER); };

async function scenario(eng, launcher, kind) {
  await forgetAll();
  if (kind === 'account-more') await apiSave(OTHER);
  const browser = await launcher.launch();
  const ctx = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
  if (!kind.startsWith('guest')) {
    const [name, value] = cookie.split('=');
    await ctx.addCookies([{ name, value, url: new URL(BASE).origin }]);
  }
  if (kind === 'guest-more') {
    await ctx.addInitScript((other) => {
      if (!localStorage.getItem('tcgengine:savedDecks:SWUSim')) localStorage.setItem('tcgengine:savedDecks:SWUSim', JSON.stringify([
        { key: 'gseeded1', name: 'Seeded Deck', leaders: ['SOR_010'], base: 'SOR_022', subtitle: '', count: 50, input: other, format: 'premier' }]));
    }, OTHER);
  }
  const page = await ctx.newPage();
  const tag = `${eng} [${kind}]`;
  try {
    await page.goto(MENU_URL, { waitUntil: 'networkidle' });
    const before = await page.evaluate(() => {
      window.__sameDocument = true;             // gone if the page reloads
      const sel = document.querySelector('#setup-pvp select[data-slot="own"]');
      const bot = document.querySelector('#setup-arenabot select[data-slot="bot"]');
      document.getElementById('setup-pvp').showModal();
      return { options: sel ? sel.options.length : 0, botValue: bot ? bot.value : null };
    });
    await page.fill('#setup-pvp input[data-detect]', LINK);
    const done = page.waitForResponse(r => r.url().includes(kind.startsWith('guest') ? 'ValidateDeck.php' : 'SavedDecks.php'), { timeout: 30000 });
    await page.click('#setup-pvp button[data-act="save"]');
    const resp = await (await done).json().catch(() => null);
    await page.waitForTimeout(1500);
    const r = await page.evaluate(() => {
      const dlg = document.getElementById('setup-pvp');
      const sel = dlg.querySelector('select[data-slot="own"]');
      const opt = sel ? sel.options[sel.selectedIndex] : null;
      const prev = opt && dlg.querySelector('.deckprev--' + opt.value);
      const msg = dlg.querySelector('[data-pickmsg="own"] .pickmsg__t');
      const listIds = [...document.querySelectorAll('[id$="-list"]')].map(e => e.id);
      const ts = document.querySelector('#setup-twin-suns select[data-slot="own"]');
      const bot = document.querySelector('#setup-arenabot select[data-slot="bot"]');
      window.SYNC_ACTIVE_SETUP();
      return {
        same: window.__sameDocument === true, open: dlg.open,
        options: sel ? sel.options.length : 0, input: opt ? opt.getAttribute('data-deck-input') : '', optText: opt ? opt.textContent.trim() : '',
        shown: !!(prev && getComputedStyle(prev).display !== 'none' && prev.getBoundingClientRect().height > 0),
        msg: msg ? msg.textContent : '', link: dlg.querySelector('input[data-detect]').value,
        played: document.getElementById('deck-link').value,
        dupIds: listIds.filter((id, i) => listIds.indexOf(id) !== i),
        tsInputs: ts ? [...ts.options].map(o => o.getAttribute('data-deck-input')) : [],
        tsValueInput: ts && ts.selectedIndex >= 0 ? ts.options[ts.selectedIndex].getAttribute('data-deck-input') : '',
        botFirst: bot ? bot.options[0].value : null, botValue: bot ? bot.value : null,
      };
    });
    if (!kind.startsWith('guest')) ok(`${tag} the save succeeds and names its picker key`, !!(resp && resp.success && resp.key), JSON.stringify(resp && { success: resp.success, key: resp.key, error: resp.error }));
    ok(`${tag} the page does NOT reload`, r.same);
    ok(`${tag} the modal stays OPEN`, r.open);
    ok(`${tag} the saved-deck picker gains the deck`, r.options === Math.max(1, before.options + 1), `${before.options} -> ${r.options}`);
    ok(`${tag} the new deck is SELECTED in the modal that saved it`, r.input === LINK, `"${r.optText}" ${r.input}`);
    ok(`${tag} its preview row is the one shown`, r.shown);
    ok(`${tag} the player is told it was saved`, /^Saved\b/.test(r.msg), `"${r.msg}"`);
    ok(`${tag} the deck link stays in the box`, r.link === LINK, r.link);
    ok(`${tag} the saved deck is what gets played`, r.played === LINK, r.played);
    ok(`${tag} every other modal's picker has it too`, r.tsInputs.includes(LINK), JSON.stringify(r.tsInputs));
    ok(`${tag} no orphaned picker popups (duplicate ids)`, r.dupIds.length === 0, JSON.stringify(r.dupIds));
    if (kind === 'account-more') {
      ok(`${tag} another modal KEEPS the deck it had chosen`, r.tsValueInput === OTHER, r.tsValueInput);
      ok(`${tag} the bot picker keeps its "use a pre-con" row, still chosen`, r.botFirst === 'none' && r.botValue === before.botValue,
         `first=${r.botFirst} value ${before.botValue} -> ${r.botValue}`);
    }
    await page.screenshot({ path: `${SHOTS}/savedeck-inplace-${eng}-${kind}.png` });
  } catch (e) {
    ok(`${tag} ran without throwing`, false, String(e && e.message || e));
  } finally {
    await browser.close();
  }
}

for (const [eng, launcher] of ENGINES) {
  for (const kind of ['account-first', 'account-more', 'guest', 'guest-more']) await scenario(eng, launcher, kind);
}
await forgetAll();
for (const l of owned) await apiSave(l);   // restore the account's own decks (see NON-DESTRUCTIVE above)
console.log(`\n${checks - fails}/${checks} passed`);
console.log(fails === 0 ? 'ALL PASS' : 'SOME FAILED');
process.exit(fails === 0 ? 0 : 1);
