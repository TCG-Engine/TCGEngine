// "Last deck used" — the menu auto-fills the Deck Link box with the deck you last STARTED A
// GAME with, and leaves it blank if that deck is no longer available (owner, 2026-09-25).
//
// Three decisions this gate pins down, because each rules out an obvious-looking alternative:
//   * USED means a game actually started. The recording seam is inside submitQueueJoin, AFTER
//     validation succeeds — so this drives the REAL submission path with only the lobby call
//     stubbed, rather than calling REMEMBER_DECK directly and proving nothing about where it
//     is wired. Stubbing JoinQueue also keeps the run from leaving stray lobbies behind.
//   * It prefills only modals that CAN SEAT the deck (matched on leader count), so a one-leader
//     Premier deck must fill PvP / Arenabot / 1P and must NOT fill Twin Suns.
//   * It is remembered in BOTH places — localStorage always, the account too when signed in,
//     with the account copy server-rendered into the next page load.
import { chromium } from 'playwright';

const BASE = process.env.SWU_BASE || 'http://localhost:3400/TCGEngine';
const URL = process.env.MENU_URL || BASE + '/SharedUI/MainMenu.php';
const KEY = 'tcgengine:lastDeck:SWUSim';
const PREMIER = 'https://swudb.com/deck/eeFFtweXI';      // one leader
const TWINSUNS = 'https://swudb.com/deck/kWzBQPfCopFMV'; // two leaders

let fails = 0, checks = 0;
const ok = (name, cond, detail) => {
  checks++;
  if (!cond) { fails++; console.log(`FAIL ${name}${detail ? ' :: ' + detail : ''}`); }
};

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
const errs = [];
page.on('pageerror', e => errs.push(e.message));

let joined = false, lastDeckPosts = [];
await page.route('**/APIs/Lobbies/JoinQueue.php', r => {
  joined = true;
  r.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ queueId: 'stub', playerId: 1 }) });
});
page.on('request', r => {
  if (r.url().includes('SavedDecks.php') && (r.postData() || '').includes('action=lastdeck')) {
    lastDeckPosts.push(decodeURIComponent(r.postData()));
  }
});

const openOnly = async (id) => {
  await page.evaluate((id) => {
    document.querySelectorAll('dialog[open]').forEach(d => d.close());
    document.getElementById(id).showModal();
  }, id);
  await page.waitForTimeout(1400);   // detection / verification round trip
};

// ── nothing remembered → the box stays blank ─────────────────────────────────
await page.goto(URL, { waitUntil: 'networkidle' });
await page.evaluate(() => { try { localStorage.clear(); } catch (e) {} });
await page.goto(URL, { waitUntil: 'networkidle' });
await openOnly('setup-pvp');
ok('with nothing remembered the box is blank',
   (await page.evaluate(() => document.querySelector('#setup-pvp input[data-detect]').value)) === '');

// ── a guest's deck is remembered in THIS BROWSER, and only there ─────────────
await page.evaluate(() => document.querySelectorAll('dialog[open]').forEach(d => d.close()));
await page.evaluate(() => document.getElementById('setup-pvp').showModal());
await page.fill('#pvp-link', PREMIER);
await page.click('#setup-pvp [data-act=join]');
await page.waitForTimeout(4000);
ok('the real submission path ran', joined);
let stored = await page.evaluate((k) => localStorage.getItem(k), KEY);
ok('a guest\'s deck is remembered in this browser', !!stored && stored.includes(PREMIER), String(stored));
ok('a guest writes nothing to an account', lastDeckPosts.length === 0, `${lastDeckPosts.length} POSTs`);
ok('what is remembered carries the leader count', /"leaders":1/.test(stored || ''), String(stored));

// ── it prefills only the modals that can SEAT it ─────────────────────────────
await page.goto(URL, { waitUntil: 'networkidle' });
for (const id of ['setup-pvp', 'setup-arenabot', 'setup-solo']) {
  await openOnly(id);
  const r = await page.evaluate((id) => {
    const d = document.getElementById(id), m = d.querySelector('[data-pickmsg="own"]');
    return { v: d.querySelector('input[data-detect]').value, msg: (m && !m.hidden) ? m.textContent : '' };
  }, id);
  ok(`${id} is prefilled with the last deck`, r.v === PREMIER, `"${r.v}"`);
  ok(`${id} says why it is filled`, /played last/.test(r.msg), r.msg.trim().slice(0, 60));
}
await openOnly('setup-twin-suns');
ok('Twin Suns is NOT prefilled with a one-leader deck',
   (await page.evaluate(() => document.querySelector('#setup-twin-suns input[data-detect]').value)) === '',
   'a two-leader modal must not take a one-leader deck');

// ── a deck that no longer resolves is forgotten, not shown ───────────────────
await page.evaluate((k) => {
  document.querySelectorAll('dialog[open]').forEach(d => d.close());
  localStorage.setItem(k, JSON.stringify(
    { input: 'https://swudb.com/deck/DEADDECK404', format: 'premier', leaders: 1, name: 'Gone' }));
}, KEY);
await page.goto(URL, { waitUntil: 'networkidle' });
await openOnly('setup-pvp');
await page.waitForTimeout(1500);
const gone = await page.evaluate((k) => ({
  box: document.querySelector('#setup-pvp input[data-detect]').value,
  stored: localStorage.getItem(k) }), KEY);
ok('an unavailable deck leaves the box blank', gone.box === '', `"${gone.box}"`);
ok('an unavailable deck is forgotten, so it stops coming back', gone.stored === null, String(gone.stored));

// ── signed in: BOTH copies, and the account one survives to the next load ────
await page.goto(BASE + '/SharedUI/Sites/SWUSim/LoginPage.php', { waitUntil: 'networkidle' });
await page.fill('input.username', 'claudebot1');
await page.fill('input.password', 'pass');
await page.click('button[type=submit], input[type=submit]').catch(() => page.keyboard.press('Enter'));
await page.waitForTimeout(1500);
await page.goto(URL, { waitUntil: 'networkidle' });
const signedIn = await page.evaluate(() => window.SWU_IS_GUEST === false);
ok('the test account is signed in', signedIn, 'everything below this depends on it');

if (signedIn) {
  // the bug this banner caused: it told signed-in players they were guests
  ok('the guest banner is gone when signed in',
     (await page.evaluate(() => !document.querySelector('.guest'))));

  await page.evaluate((k) => { try { localStorage.removeItem(k); } catch (e) {} }, KEY);
  joined = false; lastDeckPosts = [];
  await page.evaluate(() => document.getElementById('setup-twin-suns').showModal());
  await page.fill('#ts-link', TWINSUNS);
  await page.waitForTimeout(2500);
  await page.evaluate(() => document.querySelector('dialog.setup[open] [data-act=join]').click());
  await page.waitForTimeout(4000);

  ok('the signed-in submission path ran', joined);
  stored = await page.evaluate((k) => localStorage.getItem(k), KEY);
  ok('a member gets the browser copy too', !!stored && stored.includes(TWINSUNS), String(stored));
  ok('a member also writes it to the account', lastDeckPosts.length > 0 && lastDeckPosts[0].includes(TWINSUNS),
     lastDeckPosts[0] || 'no POST');
  ok('the account copy carries the two-leader count', /leaders=2/.test(lastDeckPosts[0] || ''), lastDeckPosts[0] || '');

  await page.goto(URL, { waitUntil: 'networkidle' });
  const rendered = await page.evaluate(() => window.SWU_LAST_DECK);
  ok('the account copy is server-rendered on the next load',
     !!rendered && rendered.deckInput === TWINSUNS, JSON.stringify(rendered));

  // the account copy must WIN over a stale browser copy
  await page.evaluate((k) => localStorage.setItem(k, JSON.stringify(
    { input: 'https://swudb.com/deck/eeFFtweXI', format: 'premier', leaders: 1, name: 'stale' })), KEY);
  await page.goto(URL, { waitUntil: 'networkidle' });
  await openOnly('setup-twin-suns');
  ok('the account copy beats a stale browser copy',
     (await page.evaluate(() => document.querySelector('#setup-twin-suns input[data-detect]').value)) === TWINSUNS,
     'a machine used as a guest first must not override the account');

  // leave the test account as we found it
  await page.evaluate((b) => fetch(b + '/SWUSim/SavedDecks.php', {
    method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'action=forgetlastdeck' }), BASE);
  await page.waitForTimeout(600);
}

ok('no page errors', errs.length === 0, errs[0]);
await browser.close();
console.log(fails ? `\n${fails}/${checks} checks failed` : `\nLAST DECK WIRED — ${checks} checks`);
process.exit(fails ? 1 : 0);
