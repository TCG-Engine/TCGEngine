// End-to-end check for the shared WaitingRoom page (SharedUI/Render/WaitingRoom.php).
//
// Unlike the render tests, this drives the REAL endpoints against a REAL apcu lobby: it logs in,
// creates a private lobby from the menu, and reads what comes back. That distinction matters — every
// bug found by hand during this feature's build (Leave silently no-oping, Join throwing on a deleted
// element, aspect rings shipping grey, the idle flicker) was invisible to the render suite and only
// appeared once real requests were involved.
//
// What it protects:
//  • creating a private lobby REDIRECTS to the page and seats you (the authKey handoff through
//    localStorage — get that wrong and you land on your own room showing "Join")
//  • the roster renders your seat with its identity strip, art actually loading (not 404 placeholders)
//  • RELOAD keeps your seat — the whole reason this is a page and not a popup
//  • Leave releases the seat and the lobby ends (it used to match on a seat number and silently no-op)
//  • a second player can join by invite, both rosters agree, and the host can start
//
// Usage:
//   node waiting-room-xbrowser.mjs                       # chromium + firefox
//   PW152=<dir>/node_modules node waiting-room-xbrowser.mjs   # + webkit
//
// ⚠ WEBKIT: under Playwright >= 1.62 on macOS 14 newPage() hangs forever (frozen
// webkit_mac14_special build vs a newer driver). Run it through a playwright@1.52.0 side-install and
// pass that node_modules path as PW152 — see the memory note verifying-swudeck-ui-cross-browser.
// Do NOT re-diagnose it as "WebKit is broken here".
import { chromium, firefox } from 'playwright';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const CRED = { user: 'claudebot1', pass: 'pass' };
const CRED2 = { user: 'claudebot2', pass: 'pass' };

// A raw free-text deck, deliberately NOT a swudb URL: the harness must not depend on an external
// host being reachable, or a network blip reads as a product failure.
const DECK = [
  'Leader', 'LAW_004', 'Base', 'JTL_026', 'Deck',
  '3 JTL_100', '3 LOF_100', '3 SEC_100', '3 LAW_100', '3 ASH_100', '3 IBH_010',
  '3 JTL_101', '3 LOF_101', '3 SEC_101', '3 LAW_101', '3 ASH_101', '3 IBH_011',
  '3 JTL_102', '3 LOF_102', '3 SEC_102', '3 LAW_102', '1 JTL_103', '1 LOF_103',
].join('\n');

const ENGINES = { chromium, firefox };
if (process.env.PW152) {
  const m = await import(process.env.PW152 + '/playwright/index.js');
  ENGINES.webkit = (m.default ?? m).webkit;
}

let allOk = true;
const results = [];
const ok = (engine, name, cond, extra) => {
  if (!cond) allOk = false;
  results.push([engine, name, !!cond, extra]);
};

async function login(page, cred) {
  await page.goto(BASE + 'SharedUI/LoginPage.php', { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="userID"]', cred.user);
  await page.fill('input[name="password"]', cred.pass);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'load' }).catch(() => {}),
    page.click('button[type="submit"]'),
  ]);
}

// Create a private lobby from the menu and land on the waiting room. Returns the lobby id.
async function createLobby(page, format) {
  await page.goto(BASE + 'SharedUI/MainMenu.php', { waitUntil: 'load' });
  await page.waitForTimeout(600);
  await page.selectOption('#swu-format-select', format);
  await page.click('#tab-text');                       // free-text deck entry
  await page.fill('#deck-text', DECK);
  await Promise.all([
    page.waitForURL(/WaitingRoom\.php/, { timeout: 15000 }),
    page.click('#create-private-game-btn'),
  ]);
  await page.waitForTimeout(1600);                     // let the first poll land
  return new URL(page.url()).searchParams.get('lobby');
}

const seatState = (page) => page.evaluate(() => {
  const root = document.querySelector('#wr-root');
  const cards = Array.from(document.querySelectorAll('.wr-card'));
  return {
    state: root && root.getAttribute('data-state'),
    seats: document.querySelectorAll('.wr-seat').length,
    mine: document.querySelectorAll('.wr-seat-mine').length,
    cards: cards.length,
    artOk: cards.length > 0 && cards.every((i) => i.naturalWidth > 0),
    titles: cards.map((i) => i.title),
    count: (document.querySelector('#wr-count-n') || {}).textContent,
    invite: (document.querySelector('#wr-invite strong') || {}).textContent || '',
    ready: !!document.querySelector('#wr-ready'),
    start: !!document.querySelector('#wr-start'),
    leave: !!document.querySelector('#wr-leave'),
    pills: Array.from(document.querySelectorAll('.wr-pill')).map((p) => p.textContent),
  };
});

for (const [engine, driver] of Object.entries(ENGINES)) {
  const browser = await driver.launch();
  const ctx = await browser.newContext({ viewport: { width: 1400, height: 1000 } });
  const page = await ctx.newPage();
  const jsErrors = [];
  const httpErrors = [];
  page.on('pageerror', (e) => jsErrors.push(e.message));
  page.on('response', (r) => { if (r.status() >= 400) httpErrors.push(r.status() + ' ' + r.url()); });

  try {
    await login(page, CRED);
    const lobby = await createLobby(page, 'premier');
    ok(engine, 'creating a private lobby lands on the waiting room', !!lobby, page.url());

    const s = await seatState(page);
    ok(engine, 'the creator is SEATED, not asked to join', s.state === 'seated', s.state);
    ok(engine, 'both premier seats render', s.seats === 2, s.seats);
    ok(engine, 'your own seat is ringed', s.mine === 1, s.mine);
    ok(engine, 'the identity strip shows leader + base', s.cards === 2, s.cards);
    ok(engine, 'every card image really loaded', s.artOk, s.titles);
    ok(engine, 'the seat count reads 1/2', s.count === '1/2', s.count);
    ok(engine, 'an invite code is offered', s.invite.length > 8, s.invite);
    ok(engine, 'loading a deck auto-readied the seat', s.pills.includes('READY'), s.pills);
    ok(engine, 'the host sees Start', s.start === true);
    ok(engine, 'Leave is offered', s.leave === true);

    // ★ THE HEADLINE PROPERTY. The popup this replaced kept its state in a JS variable, so a refresh
    // lost the room entirely even though the lobby was alive server-side for another 900 seconds.
    await page.reload({ waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1800);
    const afterReload = await seatState(page);
    ok(engine, 'RELOAD keeps your seat', afterReload.state === 'seated', afterReload.state);
    ok(engine, 'reload keeps the identity strip', afterReload.cards === 2, afterReload.cards);

    // Leave must actually release the seat. It used to match on a seat NUMBER and ignore the authKey,
    // so it silently did nothing whenever the seat had shifted.
    await page.click('#wr-leave');
    await page.waitForURL(/MainMenu\.php/, { timeout: 10000 }).catch(() => {});
    await page.goto(BASE + 'SharedUI/WaitingRoom.php?lobby=' + encodeURIComponent(lobby),
                    { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(2600);
    const afterLeave = await seatState(page);
    ok(engine, 'Leave really released the seat (lobby ends)', afterLeave.state === 'gone', afterLeave.state);

    ok(engine, 'no JS errors', jsErrors.length === 0, jsErrors.slice(0, 3));
    ok(engine, 'no 4xx/5xx responses', httpErrors.length === 0, httpErrors.slice(0, 3));
  } catch (e) {
    ok(engine, 'harness ran to completion', false, String(e).split('\n')[0]);
  }
  await browser.close();
}

// ── The two-seat flow. One engine is enough: this is about the SERVER agreeing with two clients,
//    not about rendering, and the per-engine pass above already covers the drawing. ──────────────
{
  const engine = 'two-seat';
  const browser = await chromium.launch();
  const hostCtx = await browser.newContext({ viewport: { width: 1300, height: 950 } });
  const joinCtx = await browser.newContext({ viewport: { width: 1300, height: 950 } });
  const host = await hostCtx.newPage();
  const join = await joinCtx.newPage();
  const errs = [];
  host.on('pageerror', (e) => errs.push('host: ' + e.message));
  join.on('pageerror', (e) => errs.push('join: ' + e.message));

  try {
    await login(host, CRED);
    await login(join, CRED2);
    const lobby = await createLobby(host, 'premier');
    const invite = (await seatState(host)).invite;
    ok(engine, 'host created a lobby with an invite code', !!invite, invite);

    // The invitee opens the invite link directly and sees the room BEFORE committing a deck.
    await join.goto(BASE + 'SharedUI/WaitingRoom.php?invite=' + encodeURIComponent(invite),
                    { waitUntil: 'domcontentloaded' });
    await join.waitForTimeout(1800);
    const preJoin = await seatState(join);
    ok(engine, 'the invitee sees the room before joining', preJoin.state === 'notseated', preJoin.state);
    ok(engine, "the invitee can see the host's deck", preJoin.cards === 2, preJoin.cards);
    ok(engine, 'the invitee has no Ready button yet', preJoin.ready === false);

    // Joining with a deck seats AND readies them.
    await join.fill('#wr-deck-input', DECK);
    await join.click('#wr-deck-btn');
    await join.waitForTimeout(2600);
    const joined = await seatState(join);
    ok(engine, 'the invitee is now seated', joined.state === 'seated', joined.state);
    ok(engine, 'joining with a legal deck auto-readied them', joined.pills.includes('READY'), joined.pills);
    ok(engine, 'the invitee does NOT see Start (not the host)', joined.start === false);
    ok(engine, 'the seat count reads 2/2', joined.count === '2/2', joined.count);

    // The host's roster must show the newcomer without a refresh.
    await host.waitForTimeout(2200);
    const hostView = await seatState(host);
    ok(engine, "the host's roster picked up the joiner", hostView.count === '2/2', hostView.count);
    ok(engine, 'the host sees four identity cards (two seats)', hostView.cards === 4, hostView.cards);
    ok(engine, 'both seats show READY',
       hostView.pills.filter((p) => p === 'READY').length === 2, hostView.pills);

    // Start must be live for the host and carry BOTH players into the game.
    const startEnabled = await host.locator('#wr-start').isEnabled();
    ok(engine, 'Start is enabled once everyone is ready', startEnabled);
    await host.click('#wr-start');
    await Promise.all([
      host.waitForURL(/NextTurn\.php/, { timeout: 20000 }).catch(() => {}),
      join.waitForURL(/NextTurn\.php/, { timeout: 20000 }).catch(() => {}),
    ]);
    ok(engine, 'the host entered the game', /NextTurn\.php/.test(host.url()), host.url());
    ok(engine, 'the joiner was carried into the SAME game', /NextTurn\.php/.test(join.url()), join.url());
    const hg = new URL(host.url()).searchParams.get('gameName');
    const jg = new URL(join.url()).searchParams.get('gameName');
    ok(engine, 'both landed in the same gameName', hg && hg === jg, hg + ' vs ' + jg);
    // StartRoom renumbers seats; each browser must enter as the seat the SERVER gave it.
    const hp = new URL(host.url()).searchParams.get('playerID');
    const jp = new URL(join.url()).searchParams.get('playerID');
    ok(engine, 'the two browsers hold different seats', hp && jp && hp !== jp, hp + ' vs ' + jp);

    ok(engine, 'no JS errors across both browsers', errs.length === 0, errs.slice(0, 3));
  } catch (e) {
    ok(engine, 'two-seat flow ran to completion', false, String(e).split('\n')[0]);
  }
  await browser.close();
}

let current = '';
for (const [engine, name, pass, extra] of results) {
  if (engine !== current) { console.log('== ' + engine); current = engine; }
  console.log('  ' + (pass ? 'PASS' : 'FAIL') + '  ' + name +
              (pass || extra === undefined ? '' : '  -> ' + JSON.stringify(extra)));
}
console.log(allOk ? '\nALL CHECKS PASS' : '\nFAILURES ABOVE');
process.exit(allOk ? 0 : 1);
