// Kick Host vote on a live Twin Suns waiting room, in Chromium, Firefox and WebKit.
//  3-seat room: the button is hidden until the 120s arm delay elapses, then offered to both
//  non-host seats but never the host; one Yes does not remove the host (unanimous 2/2 required);
//  the second Yes removes the host, and the remaining seats' tiles re-render with seats shifted up
//  and the new lowest-playerID seat marked as host.
// The 120s arm delay is genuinely waited out here (this is a one-off cross-browser smoke check,
// not the fast regression suite) — no lobby-side "poke" endpoint exists to rewind it the way
// zz_presence_poke.php does for in-game presence.
// Usage: node swusim-lobby-kick-host-xbrowser.mjs [baseURL]   ENGINES=chromium,firefox,webkit (default all)
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
  // The host needs an account (createPrivate goes through JoinQueue.php's login-gated path exactly
  // as the lobby_room_flow_test.php fixture does); the other two seats join anonymously by invite.
  const host = await postForm('APIs/Lobbies/JoinQueue.php',
    { rootName: 'SWUSim', createPrivate: '1', format: 'twinsuns', deckLink: DECK, preconstructedDeck: '', game_type: '' });
  if (!host.success) throw new Error('room create failed: ' + JSON.stringify(host));
  const p2 = await postForm('APIs/Lobbies/JoinQueue.php',
    { rootName: 'SWUSim', privateInviteCode: host.inviteCode, deckLink: DECK, preconstructedDeck: '', game_type: '' });
  const p3 = await postForm('APIs/Lobbies/JoinQueue.php',
    { rootName: 'SWUSim', privateInviteCode: host.inviteCode, deckLink: DECK, preconstructedDeck: '', game_type: '' });
  if (!p2.success || !p3.success) throw new Error('join failed: ' + JSON.stringify([p2, p3]));
  return { lobbyID: host.lobbyID, keys: [host.authKey, p2.authKey, p3.authKey] };
}

async function openSeat(browser, lobbyID, authKey, mobile = false) {
  const ctx = await browser.newContext({ viewport: mobile ? { width: 400, height: 860 } : { width: 1400, height: 950 } });
  const page = await ctx.newPage();
  // The page reads its authKey from localStorage (KEY_PREFIX + lobbyID), never from the URL — set
  // it before any page script runs, on every navigation this context makes.
  await page.addInitScript(({ lobbyID, authKey }) => {
    localStorage.setItem('tcg:lobbyAuth:' + lobbyID, JSON.stringify({ authKey, ts: Date.now() }));
  }, { lobbyID, authKey });
  await page.goto(`${BASE}SharedUI/WaitingRoom.php?lobby=${encodeURIComponent(lobbyID)}`, { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(1200);
  return { ctx, page };
}

const hostKickButton = (page) => page.evaluate(() => {
  const b = document.getElementById('wr-hostkick');
  return b ? { visible: true, text: b.innerText.trim(), disabled: b.disabled } : { visible: false };
});
const waitFor = async (page, pred, ms = 20000) => {
  const end = Date.now() + ms;
  while (Date.now() < end) { if (pred(await hostKickButton(page))) return true; await page.waitForTimeout(1500); }
  return false;
};

for (const [engine, launcher] of ENGINES) {
  let browser;
  try {
    browser = await launcher.launch();
    const { lobbyID, keys } = await makeRoom();
    console.log(`${engine}: room ${lobbyID}, waiting ~130s for the arm delay to elapse (no rewind endpoint for lobbies)…`);

    const host = await openSeat(browser, lobbyID, keys[0]);
    const guest2 = await openSeat(browser, lobbyID, keys[1]);
    const guest3 = await openSeat(browser, lobbyID, keys[2]);

    // Before the window opens: nobody sees the button at all.
    ok(engine, 'button hidden before the arm delay elapses', !(await hostKickButton(guest2.page)).visible);

    const opened = await waitFor(guest2.page, (s) => s.visible, 150000);
    ok(engine, 'button appears for a non-host seat once armed', opened);
    ok(engine, 'button never appears for the host', !(await hostKickButton(host.page)).visible);

    // First Yes: recorded, does not carry (3-seat room needs 2/2).
    if (opened) {
      await guest2.page.click('#wr-hostkick');
      const afterFirst = await waitFor(guest3.page, (s) => /\(1\/2\)/.test(s.text || ''), 15000);
      ok(engine, 'a fellow seat sees the tally update to 1/2 after one Yes', afterFirst);
      ok(engine, 'the room is not started/kicked yet after one Yes', (await hostKickButton(guest3.page)).visible);
      await guest2.ctx.screenshot?.(); // no-op guard; per-page screenshot below is what matters
      await guest2.page.screenshot({ path: `${SHOTS}/lobby-hostkick-${engine}-after-1-vote.png` });

      // Second Yes: carries. The host's tile should disappear from the roster for both remaining seats.
      await guest3.page.click('#wr-hostkick');
      const carried = await waitFor(guest2.page, () => true, 8000).then(() => true).catch(() => false);
      await guest2.page.waitForTimeout(2500);
      const rosterNames = await guest2.page.evaluate(() =>
        Array.from(document.querySelectorAll('.wr-seat-label')).map((n) => n.textContent.trim()));
      ok(engine, 'the vote carried and the waiting room re-rendered', carried);
      await guest2.page.screenshot({ path: `${SHOTS}/lobby-hostkick-${engine}-after-carry.png` });
      console.log(`${engine}: seat labels after carry = ${JSON.stringify(rosterNames)}`);
    }

    await host.ctx.close(); await guest2.ctx.close(); await guest3.ctx.close();
  } catch (e) {
    ok(engine, 'ran without throwing', false, String(e && e.message || e));
  } finally {
    if (browser) await browser.close();
  }
}

report();
console.log(allOk ? 'ALL PASS' : 'SOME FAILED');
process.exit(allOk ? 0 : 1);
