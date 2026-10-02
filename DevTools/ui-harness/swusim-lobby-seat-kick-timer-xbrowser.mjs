// Remove-seat countdown on a live Twin Suns waiting room, in Chromium, Firefox and WebKit.
//  A seat's first minute is protected (owner 2026-10-01; SWUSeatKickableIn / KickSeat.php): the host's Remove
//  button on a just-joined seat reads "Remove (Ns)", is disabled, and counts DOWN in place; clicking it does
//  nothing. Once the minute is up it reads "Remove", is enabled, and removes the seat.
// The 60s is genuinely waited out (one-off cross-browser smoke check, not the fast regression suite — the
// endpoint rule is pinned by SWUSim/DevTools/tests/lobby_seat_kick_timer_test.php and lobby_room_flow_test.php).
// Usage: node swusim-lobby-seat-kick-timer-xbrowser.mjs [baseURL]   ENGINES=chromium,firefox,webkit (default all)
import { chromium, firefox, webkit } from 'playwright';
import fs from 'fs';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const ALL = { chromium, firefox, webkit };
const ENGINES = Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n));
const SHOTS = process.env.SHOTS_DIR || '/tmp';

let allOk = true;
const results = [];
const ok = (e, n, cond, extra = '') => { if (!cond) allOk = false; results.push([e, n, !!cond, extra]); };
const report = () => { for (const [e, n, pass, extra] of results) console.log(`${pass ? 'PASS' : 'FAIL'}  ${e.padEnd(8)} ${n}${extra ? '  — ' + extra : ''}`); };
setTimeout(() => { console.log('WATCHDOG: timed out after 420s'); report(); process.exit(9); }, 420000).unref();

async function postForm(path, params) {
  const r = await fetch(BASE + path, { method: 'POST', body: new URLSearchParams(params) });
  return r.json();
}
const DECK = fs.readFileSync(new URL('../../SWUSim/DevTools/tests/fixtures/twinsuns_deck.json', import.meta.url), 'utf8').trim();

async function makeRoom() {
  const host = await postForm('APIs/Lobbies/JoinQueue.php',
    { rootName: 'SWUSim', createPrivate: '1', format: 'twinsuns', deckLink: DECK, preconstructedDeck: '', game_type: '' });
  if (!host.success) throw new Error('room create failed: ' + JSON.stringify(host));
  const p2 = await postForm('APIs/Lobbies/JoinQueue.php',
    { rootName: 'SWUSim', privateInviteCode: host.inviteCode, deckLink: DECK, preconstructedDeck: '', game_type: '' });
  if (!p2.success) throw new Error('join failed: ' + JSON.stringify(p2));
  return { lobbyID: host.lobbyID, hostKey: host.authKey, guestID: p2.playerID };
}

async function openSeat(browser, lobbyID, authKey, mobile = false) {
  const ctx = await browser.newContext({ viewport: mobile ? { width: 400, height: 860 } : { width: 1400, height: 950 } });
  const page = await ctx.newPage();
  await page.addInitScript(({ lobbyID, authKey }) => {
    localStorage.setItem('tcg:lobbyAuth:' + lobbyID, JSON.stringify({ authKey, ts: Date.now() }));
  }, { lobbyID, authKey });
  await page.goto(`${BASE}SharedUI/WaitingRoom.php?lobby=${encodeURIComponent(lobbyID)}`, { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(2500);
  return { ctx, page };
}

const kickBtn = (page) => page.evaluate(() => {
  const b = document.querySelector('.wr-kick');
  if (!b) return { present: false };
  const r = b.getBoundingClientRect(), p = b.parentElement.getBoundingClientRect();
  return { present: true, text: b.textContent.trim(), disabled: b.disabled,
           inside: r.right <= p.right + 1 && r.left >= p.left - 1, w: Math.round(r.width) };
});
const secs = (t) => { const m = /\((\d+)s\)/.exec(t || ''); return m ? Number(m[1]) : null; };
const seatCount = (page) => page.evaluate(() => document.querySelectorAll('.wr-kick').length);

for (const [engine, launcher] of ENGINES) {
  let browser;
  try {
    browser = await launcher.launch();
    const { lobbyID, hostKey } = await makeRoom();
    const host = await openSeat(browser, lobbyID, hostKey);
    const phone = await openSeat(browser, lobbyID, hostKey, true);

    const a = await kickBtn(host.page);
    ok(engine, 'host sees a Remove button on the guest seat', a.present);
    ok(engine, 'it is DISABLED during the first minute', a.disabled === true, a.text);
    ok(engine, 'it shows a countdown "Remove (Ns)" with N in 1..60', secs(a.text) !== null && secs(a.text) <= 60 && secs(a.text) > 0, a.text);
    ok(engine, 'the button stays inside its tile (desktop)', a.inside, `w=${a.w}`);
    const ph = await kickBtn(phone.page);
    ok(engine, 'the button stays inside its tile (400px phone)', ph.present && ph.inside, `${ph.text} w=${ph.w}`);
    await host.page.screenshot({ path: `${SHOTS}/lobby-seatkick-${engine}-countdown.png` });
    await phone.page.screenshot({ path: `${SHOTS}/lobby-seatkick-${engine}-countdown-phone.png` });

    await host.page.waitForTimeout(3200);
    const b = await kickBtn(host.page);
    ok(engine, 'the countdown falls in place (no reload)', secs(b.text) !== null && secs(b.text) < secs(a.text), `${a.text} -> ${b.text}`);

    // A click while disabled must not remove anyone.
    await host.page.evaluate(() => document.querySelector('.wr-kick').click());
    await host.page.waitForTimeout(2500);
    ok(engine, 'clicking during the countdown removes nobody', (await seatCount(host.page)) === 1);

    console.log(`${engine}: waiting out the minute…`);
    await host.page.waitForTimeout((secs(b.text) ?? 60) * 1000 + 3000);
    const c = await kickBtn(host.page);
    ok(engine, 'after the minute it reads "Remove" and is enabled', c.text === 'Remove' && c.disabled === false, `${c.text} disabled=${c.disabled}`);
    await host.page.screenshot({ path: `${SHOTS}/lobby-seatkick-${engine}-armed.png` });

    await host.page.click('.wr-kick');
    await host.page.waitForTimeout(4000);
    ok(engine, 'Remove now removes the seat', (await seatCount(host.page)) === 0);

    await host.ctx.close(); await phone.ctx.close();
  } catch (e) {
    ok(engine, 'ran without throwing', false, String(e && e.message || e));
  } finally {
    if (browser) await browser.close();
  }
}

report();
console.log(allOk ? 'ALL PASS' : 'SOME FAILED');
process.exit(allOk ? 0 : 1);
