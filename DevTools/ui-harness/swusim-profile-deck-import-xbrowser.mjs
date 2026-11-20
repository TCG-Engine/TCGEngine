// Profile → Saved Decks → "Import decks", signed in as the local "ninin" account, in Chromium, Firefox and WebKit:
//  - one link box to start; "+ Add another" adds boxes up to 10 and then disables; ✕ removes one (never the last);
//  - a pasted multi-line list fills one box per link;
//  - Import & Save with two good links, a duplicate, a non-link, a dead link and a blank box: the good ones are saved
//    (once), join the dropdown in place, and their boxes go; the bad ones stay with their reason; the summary counts;
//  - after a reload the saved decks are still in the dropdown (they are on the account);
//  - at desktop and phone width the panel causes no horizontal scroll (screenshots in _shots/).
//
//   node DevTools/ui-harness/swusim-profile-deck-import-xbrowser.mjs     ENGINES=chromium,firefox,webkit (default: all three)
//
// ⚠ Saves two real swudb decks (network) onto ninin's account and removes them again — only decks ninin did not
//   already have; a deck that was there before is never deleted.
import { chromium, firefox, webkit } from 'playwright';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';

const BASE = process.env.BASE_URL || 'http://localhost:3400/TCGEngine/';
const SITE = BASE + 'SharedUI/Sites/SWUSim/';
const CONTAINER = 'otmtcge-swusim-web-server-1';
const ALL = { chromium, firefox, webkit };
const ENGINES = Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n));
const GOOD = ['https://swudb.com/deck/aICaKTGaQd', 'https://swudb.com/deck/LImIrpIS'];
const NOT_A_LINK = 'my favourite deck';
const DEAD = 'https://swudb.com/deck/zzNoSuchDeckzz';

let fails = 0, checks = 0;
const check = (engine, name, cond, extra = '') => { checks++; if (!cond) { fails++; console.log(`FAIL ${engine.padEnd(9)} ${name}${extra ? ' — ' + extra : ''}`); } else console.log(`PASS ${engine.padEnd(9)} ${name}`); };
const php = (code) => execFileSync('docker', ['exec', '-w', '/var/www/html/TCGEngine', CONTAINER, 'php', '-d', 'xdebug.mode=off', '-r',
  'require "Database/ConnectionManager.php"; require "Database/functions.inc.php"; require "SWUSim/Custom/DeckImport.php"; ' + code], { encoding: 'utf8' }).trim();
const UID = Number(php('$r = GetLocalMySQLConnection()->query("SELECT usersId FROM users WHERE usersUid = \'ninin\'")->fetch_row(); echo $r[0] ?? 0;'));
if (!UID) { console.log('no local "ninin" account'); process.exit(2); }
const IDS = JSON.parse(php(`echo json_encode(array_map('SWUComputeDeckIdentity', ${JSON.stringify(GOOD)}));`));
const saved = () => JSON.parse(php(`echo json_encode(array_column(LoadSavedDecks(${UID}), 'decklink'));`));
const preexisting = new Set(saved().filter((d) => IDS.includes(d)));
const cleanup = () => IDS.filter((d) => !preexisting.has(d)).forEach((d) => php(`DeleteSavedDeck(${UID}, ${JSON.stringify(d)});`));

async function login(page) {
  await page.goto(SITE + 'LoginPage.php');
  await page.fill('input[name="userID"]', 'ninin'); await page.fill('input[name="password"]', 'pass');
  await Promise.all([page.waitForNavigation().catch(() => {}), page.click('button[type="submit"]')]);
}
const state = (page) => page.evaluate(() => {
  const box = document.querySelector('.dl-import');
  if (!box) return null;
  const rows = [...box.querySelectorAll('.dl-import-row')].map((r) => ({
    value: r.querySelector('.dl-import-input').value, status: r.querySelector('.dl-import-status').textContent,
    state: r.getAttribute('data-state'), removeShown: !r.querySelector('.dl-import-remove').hidden
      && r.querySelector('.dl-import-remove').getBoundingClientRect().width > 0,
  }));
  const add = box.querySelector('.dl-import-add');
  const opts = [...document.querySelectorAll('.savedDecks .dl-select option')].map((o) => o.getAttribute('data-id')).filter(Boolean);
  const r = box.getBoundingClientRect();
  return { rows, addDisabled: add.disabled, addText: add.textContent.trim(), summary: box.querySelector('.dl-import-summary').textContent,
    opts, visible: r.width > 0 && r.height > 0,
    hScroll: document.documentElement.scrollWidth > document.documentElement.clientWidth + 1,
    overflow: [...box.querySelectorAll('*')].some((el) => { const b = el.getBoundingClientRect(); return b.width > 0 && (b.right > r.right + 1 || b.left < r.left - 1); }) };
});
const paste = (page, index, text) => page.evaluate(([i, t]) => {
  const inp = document.querySelectorAll('.dl-import-input')[i];
  const dt = new DataTransfer(); dt.setData('text/plain', t);
  inp.focus(); inp.dispatchEvent(new ClipboardEvent('paste', { clipboardData: dt, bubbles: true, cancelable: true }));
}, [index, text]);

fs.mkdirSync(new URL('./_shots/', import.meta.url), { recursive: true });
for (const [name, launcher] of ENGINES) {
  let browser;
  cleanup();
  try {
    browser = await launcher.launch();
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
    const page = await ctx.newPage();
    await login(page);
    await page.goto(SITE + 'Profile.php');
    let s = await state(page);
    check(name, 'the Import decks block is shown', !!s && s.visible, JSON.stringify(s));
    if (!s) continue;
    check(name, 'it starts with ONE box, and no ✕ on it', s.rows.length === 1 && !s.rows[0].removeShown, JSON.stringify(s.rows));
    check(name, 'the plus button reads "+ Add another"', s.addText === '+ Add another', s.addText);

    for (let i = 0; i < 12; i++) await page.click('.dl-import-add', { force: true }).catch(() => {});
    s = await state(page);
    check(name, '"Add another" stops at 10 boxes', s.rows.length === 10, String(s.rows.length));
    check(name, '…and is disabled there', s.addDisabled);
    check(name, 'every box has a ✕ once there are several', s.rows.every((r) => r.removeShown));
    await page.locator('.dl-import-remove').nth(3).click();
    s = await state(page);
    check(name, '✕ removes a box and re-enables "Add another"', s.rows.length === 9 && !s.addDisabled, String(s.rows.length));
    const focused = await page.evaluate(() => document.activeElement && document.activeElement.classList.contains('dl-import-input'));
    check(name, 'focus lands on a neighbouring box', focused);

    // ⚠ Firefox hands a SCRIPTED paste event an empty clipboard (only a real, trusted paste carries the data), and a
    // real paste would go through the machine's own clipboard — so there this check is reported SKIPPED, not passed.
    const scriptedPasteHasData = await page.evaluate(() => {
      let got = ''; const dt = new DataTransfer(); dt.setData('text/plain', 'x');
      const t = document.createElement('input'); t.addEventListener('paste', (e) => { got = e.clipboardData.getData('text'); });
      t.dispatchEvent(new ClipboardEvent('paste', { clipboardData: dt })); return got === 'x';
    });
    if (scriptedPasteHasData) {
      await page.goto(SITE + 'Profile.php');
      await paste(page, 0, GOOD.join('\n') + '\n' + DEAD + '\r\n');
      s = await state(page);
      check(name, 'a pasted list fills one box per link', s.rows.length === 3 && s.rows.map((r) => r.value).join('|') === [...GOOD, DEAD].join('|'),
        JSON.stringify(s.rows.map((r) => r.value)));
      await page.goto(SITE + 'Profile.php');
      await paste(page, 0, Array.from({ length: 12 }, (_, i) => `https://swudb.com/deck/x${i}`).join('\n'));
      s = await state(page);
      check(name, 'pasting 12 links fills the 10 boxes and says 2 were left out',
        s.rows.length === 10 && s.rows[9].value === 'https://swudb.com/deck/x9' && s.summary.includes('2 links were left out'), s.summary);
    } else console.log(`SKIP ${name.padEnd(9)} a pasted list fills one box per link — this engine empties a scripted paste`);

    // two good links, the first again, a non-link, a dead link, and a blank box
    await page.goto(SITE + 'Profile.php');
    for (let i = 0; i < 5; i++) await page.click('.dl-import-add');
    const values = [GOOD[0], NOT_A_LINK, GOOD[1], GOOD[0], DEAD, ''];
    for (let i = 0; i < values.length; i++) await page.locator('.dl-import-input').nth(i).fill(values[i]);
    await page.click('.dl-import-save');
    await page.waitForFunction(() => /^(Saved|Nothing)/.test(document.querySelector('.dl-import-summary').textContent), null, { timeout: 120000 })
      .catch(() => {});
    s = await state(page);
    check(name, 'the summary counts 2 of 4 different links saved', s.summary.startsWith('Saved 2 of 4:'), s.summary);
    check(name, 'only the two bad links are left', s.rows.length === 2 && s.rows[0].value === NOT_A_LINK && s.rows[1].value === DEAD,
      JSON.stringify(s.rows.map((r) => r.value)));
    check(name, 'the non-link says why', s.rows[0] && s.rows[0].state === 'error' && s.rows[0].status === '✗ Not a deck link', s.rows[0] && s.rows[0].status);
    check(name, 'the dead link says why', s.rows[1] && s.rows[1].state === 'error' && /^✗ \S/.test(s.rows[1].status), s.rows[1] && s.rows[1].status);
    check(name, 'both decks joined the dropdown in place, once each', IDS.every((d) => s.opts.filter((o) => o === d).length === 1),
      JSON.stringify(s.opts));
    const db = saved();
    check(name, 'both are on the account', IDS.every((d) => db.includes(d)), JSON.stringify(db));
    check(name, 'the button is usable again', await page.locator('.dl-import-save').isEnabled());
    await page.locator('.savedDecks').screenshot({ path: new URL(`./_shots/profile-deck-import-results-${name}.png`, import.meta.url).pathname });

    for (const [label, vp] of [['desktop', { width: 1280, height: 900 }], ['phone', { width: 390, height: 844 }]]) {
      await page.setViewportSize(vp);
      await page.goto(SITE + 'Profile.php');
      await page.click('.dl-import-add'); await page.click('.dl-import-add');
      await page.locator('.dl-import-input').nth(0).fill(GOOD[0]);
      await page.locator('.dl-import-input').nth(1).fill(NOT_A_LINK);
      s = await state(page);
      check(name, `${label}: after a reload both decks are still listed`, IDS.every((d) => s.opts.includes(d)), JSON.stringify(s.opts));
      check(name, `${label}: no horizontal scroll`, !s.hScroll);
      check(name, `${label}: nothing spills out of the block`, !s.overflow);
      await page.locator('.savedDecks').screenshot({ path: new URL(`./_shots/profile-deck-import-${label}-${name}.png`, import.meta.url).pathname });
    }
  } catch (e) {
    fails++; console.log(`FAIL ${name.padEnd(9)} threw: ${e.message.split('\n')[0]}`);
  } finally {
    if (browser) await browser.close();
    cleanup();
  }
}
console.log(`\n${checks - fails}/${checks} checks passed${fails ? ` — ${fails} FAILED` : ''}`);
process.exit(fails ? 1 : 0);
