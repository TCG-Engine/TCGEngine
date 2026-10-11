// "Fill Seat with Bot" popup on a live Twin Suns waiting room, in Chromium, Firefox and WebKit, desktop and 400px phone.
// SWUSim/docs/todo-twinsuns-fill-bot.md step 4 (owner Decisions 3 and 5). The server rules are pinned by
// SWUSim/DevTools/tests/lobby_room_bots_test.php; this checks the page:
//   • PRIVATE room: every empty seat offers an ENABLED "Fill Seat with Bot"; the popup lists the four pre-cons (with
//     their card art, in the pre-con well) and a paste box; it fits the viewport; Escape closes it; an unreadable list is refused INSIDE
//     the popup, which stays open; a pre-con and a pasted list each fill a seat with an Arenabot.
//   • PUBLIC room: the button is DISABLED with a "(Ns)" countdown that falls in place.
// Usage: node swusim-fill-bot-xbrowser.mjs [baseURL]   ENGINES=chromium,firefox,webkit (default all)
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
setTimeout(() => { console.log('WATCHDOG: timed out'); report(); process.exit(9); }, 600000).unref();

const DECK = fs.readFileSync(new URL('../../SWUSim/DevTools/tests/fixtures/twinsuns_deck.json', import.meta.url), 'utf8').trim();
async function postForm(path, params, cookie = '') {
  const r = await fetch(BASE + path, { method: 'POST', body: new URLSearchParams(params), headers: cookie ? { Cookie: cookie } : {}, redirect: 'manual' });
  const text = await r.text();
  let json = null; try { json = JSON.parse(text); } catch (e) {}
  return { json, cookie: (r.headers.get('set-cookie') || '').split(';')[0] };
}
async function login(user) {
  const r = await postForm('AccountFiles/AttemptPasswordLogin.php', { submit: '1', userID: user, password: 'pass' });
  return r.cookie;
}
async function privateRoom() {
  const host = (await postForm('APIs/Lobbies/JoinQueue.php',
    { rootName: 'SWUSim', createPrivate: '1', format: 'twinsuns', deckLink: DECK, preconstructedDeck: '', game_type: '' })).json;
  if (!host || !host.success) throw new Error('room create failed: ' + JSON.stringify(host));
  const p2 = (await postForm('APIs/Lobbies/JoinQueue.php',
    { rootName: 'SWUSim', privateInviteCode: host.inviteCode, deckLink: DECK, preconstructedDeck: '', game_type: '' })).json;
  if (!p2 || !p2.success) throw new Error('join failed: ' + JSON.stringify(p2));
  return { lobbyID: host.lobbyID, key: host.authKey };
}
async function publicRoom() {
  const cookie = await login('claudebot3');
  const r = (await postForm('APIs/Lobbies/JoinQueue.php',
    { rootName: 'SWUSim', format: 'twinsuns', queueType: 'bo1', deckLink: DECK, preconstructedDeck: '', game_type: '' }, cookie)).json;
  if (!r || !r.success) throw new Error('public room failed: ' + JSON.stringify(r));
  return { lobbyID: r.lobbyID, key: r.authKey, playerID: r.playerID, fresh: r.message !== 'Successfully joined queue.' };
}
// Leave the public room so the next engine opens its OWN (rejoining your own room is not a new join, so its quiet-minute
// clock would already be running).
async function leave(room) {
  await postForm('APIs/Lobbies/LeaveQueue.php', { rootName: 'SWUSim', playerID: String(room.playerID || 1), lobbyID: room.lobbyID, authKey: room.key });
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
const fillButtons = (page) => page.evaluate(() => Array.from(document.querySelectorAll('.wr-fill-bot')).map(b => ({ text: b.textContent.trim(), disabled: b.disabled })));
const dialog = (page) => page.evaluate(() => {
  const d = document.querySelector('.wr-botdlg');
  if (!d) return { open: false };
  const r = d.getBoundingClientRect();
  const imgs = Array.from(d.querySelectorAll('.wr-botdlg-pccards img'));
  return { open: true, opts: d.querySelectorAll('.wr-botdlg-pcrow').length, paste: !!d.querySelector('.wr-botdlg-paste'),
           fits: r.left >= 0 && r.top >= 0 && r.right <= innerWidth + 1 && r.bottom <= innerHeight + 1,
           imgs: imgs.length, loaded: imgs.filter(i => i.complete && i.naturalWidth > 0).length,
           err: (d.parentNode.querySelector('.wr-botdlg-err') || {}).textContent || '', w: Math.round(r.width), h: Math.round(r.height) };
});
const botSeats = (page) => page.evaluate(() => Array.from(document.querySelectorAll('.wr-seat-who')).filter(e => /Arenabot/.test(e.textContent)).length);

for (const [engine, launcher] of ENGINES) {
  let browser;
  try {
    browser = await launcher.launch();
    // ── private room ──
    const room = await privateRoom();
    const host = await openSeat(browser, room.lobbyID, room.key);
    const fb = await fillButtons(host.page);
    ok(engine, 'private: both empty seats offer "Fill Seat with Bot", enabled', fb.length === 2 && fb.every(b => !b.disabled && b.text === 'Fill Seat with Bot'), JSON.stringify(fb));
    await host.page.click('.wr-fill-bot');
    await host.page.waitForTimeout(1500);
    let d = await dialog(host.page);
    ok(engine, 'the popup opens with 4 pre-cons + a paste box', d.open && d.opts === 4 && d.paste, JSON.stringify(d));
    ok(engine, 'pre-con card art loads', d.imgs >= 8 && d.loaded === d.imgs, `${d.loaded}/${d.imgs}`);
    ok(engine, 'the popup fits the desktop viewport', d.fits, `${d.w}x${d.h}`);
    await host.page.screenshot({ path: `${SHOTS}/fillbot-${engine}-popup.png` });
    await host.page.keyboard.press('Escape');
    await host.page.waitForTimeout(400);
    ok(engine, 'Escape closes it', !(await dialog(host.page)).open);

    // An unreadable pasted list: refused in the popup, which stays open.
    await host.page.click('.wr-fill-bot');
    await host.page.waitForTimeout(800);
    await host.page.fill('.wr-botdlg-paste', '{"not":"a deck"}');
    await host.page.click('.wr-botdlg-add');
    await host.page.waitForTimeout(3000);
    d = await dialog(host.page);
    ok(engine, 'an unreadable list is refused INSIDE the popup (it stays open, with the reason)', d.open && /cannot be used|recogni/i.test(d.err), d.err);
    // A pre-con: the second one.
    await host.page.locator('.wr-botdlg-pcrow').nth(1).click();   // also clears the refused paste
    await host.page.click('.wr-botdlg-add');
    await host.page.waitForTimeout(3500);
    ok(engine, 'a pre-con fills the seat and the popup closes', !(await dialog(host.page)).open && (await botSeats(host.page)) === 1);
    // A pasted, legal list.
    await host.page.click('.wr-fill-bot');
    await host.page.waitForTimeout(800);
    await host.page.fill('.wr-botdlg-paste', DECK);
    await host.page.click('.wr-botdlg-add');
    await host.page.waitForTimeout(5000);
    ok(engine, 'a pasted list fills the last seat', (await botSeats(host.page)) === 2 && (await fillButtons(host.page)).length === 0);
    await host.page.screenshot({ path: `${SHOTS}/fillbot-${engine}-filled.png` });
    await host.ctx.close();

    // Phone width: a fresh room, the popup must fit 400px and scroll inside itself.
    const room2 = await privateRoom();
    const phone = await openSeat(browser, room2.lobbyID, room2.key, true);
    await phone.page.click('.wr-fill-bot');
    await phone.page.waitForTimeout(1500);
    d = await dialog(phone.page);
    ok(engine, 'the popup fits a 400px phone', d.open && d.fits && d.w <= 400, `${d.w}x${d.h}`);
    await phone.page.screenshot({ path: `${SHOTS}/fillbot-${engine}-popup-phone.png` });
    await phone.ctx.close();

    // ── public room: the quiet-minute countdown ──
    const pub = await publicRoom();
    if (!pub.fresh) console.log(`${engine}: note — joined an existing public room, not a fresh one`);
    const ph = await openSeat(browser, pub.lobbyID, pub.key);
    const a = await fillButtons(ph.page);
    const secs = (t) => { const m = /\((\d+)s\)/.exec(t || ''); return m ? Number(m[1]) : null; };
    ok(engine, 'public: the button is DISABLED with a "(Ns)" countdown', a.length > 0 && a[0].disabled && secs(a[0].text) > 0 && secs(a[0].text) <= 60, JSON.stringify(a[0]));
    await ph.page.waitForTimeout(3200);
    const b = await fillButtons(ph.page);
    ok(engine, 'public: the countdown falls in place', b.length > 0 && secs(b[0].text) !== null && secs(b[0].text) < secs(a[0].text), `${a[0] && a[0].text} -> ${b[0] && b[0].text}`);
    await ph.page.screenshot({ path: `${SHOTS}/fillbot-${engine}-public.png` });
    await ph.ctx.close();
    await leave(pub);
  } catch (e) {
    ok(engine, 'ran without throwing', false, String(e && e.message || e));
  } finally {
    if (browser) await browser.close();
  }
}
report();
console.log(allOk ? 'ALL PASS' : 'SOME FAILED');
process.exit(allOk ? 0 : 1);
