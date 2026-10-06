// "Fill Seat with Bot" popup, three deck sources (owner, 2026-10-03), in Chromium, Firefox and WebKit, desktop and a
// 400px phone. Layout top to bottom: [deck link/list input] [Saved Decks dropdown] [Pre-Cons well] [Cancel | Add Bot].
//   • order: paste box above the saved-deck select above the pre-con well, buttons last;
//   • the pre-cons are a scrolling WELL of 4 selectable rows (art + name + "leaders / base · N cards" meta), the first
//     chosen by default, with a visible check on the chosen row only;
//   • one source at a time: typing clears the pre-con; picking a saved deck clears the box and the pre-con; picking a
//     pre-con clears both;
//   • a signed-in host's saved decks are offered (the page's own library list); a pre-con still fills a seat;
//   • the popup fits the desktop viewport and a 400px phone.
// The original popup flows (refusal inside the popup, paste fills a seat, public countdown) are swusim-fill-bot-xbrowser.mjs.
// Usage: node swusim-fill-bot-sources-xbrowser.mjs [baseURL]   ENGINES=chromium,firefox,webkit (default all)
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
  return (await postForm('AccountFiles/AttemptPasswordLogin.php', { submit: '1', userID: user, password: 'pass' })).cookie;
}
// A private room hosted by a SIGNED-IN account (so the page renders its saved-deck library), plus one guest, leaving two
// empty seats.
async function privateRoom(cookie) {
  const host = (await postForm('APIs/Lobbies/JoinQueue.php',
    { rootName: 'SWUSim', createPrivate: '1', format: 'twinsuns', deckLink: DECK, preconstructedDeck: '', game_type: '' }, cookie)).json;
  if (!host || !host.success) throw new Error('room create failed: ' + JSON.stringify(host));
  const p2 = (await postForm('APIs/Lobbies/JoinQueue.php',
    { rootName: 'SWUSim', privateInviteCode: host.inviteCode, deckLink: DECK, preconstructedDeck: '', game_type: '' })).json;
  if (!p2 || !p2.success) throw new Error('join failed: ' + JSON.stringify(p2));
  return { lobbyID: host.lobbyID, key: host.authKey };
}
async function openSeat(browser, room, cookie, mobile = false) {
  const ctx = await browser.newContext({ viewport: mobile ? { width: 400, height: 860 } : { width: 1400, height: 950 } });
  const [name, value] = cookie.split('=');
  await ctx.addCookies([{ name, value, url: new URL(BASE).origin }]);
  const page = await ctx.newPage();
  await page.addInitScript(({ lobbyID, authKey }) => {
    localStorage.setItem('tcg:lobbyAuth:' + lobbyID, JSON.stringify({ authKey, ts: Date.now() }));
  }, { lobbyID: room.lobbyID, authKey: room.key });
  await page.goto(`${BASE}SharedUI/WaitingRoom.php?lobby=${encodeURIComponent(room.lobbyID)}`, { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(2500);
  return { ctx, page };
}
const dialog = (page) => page.evaluate(() => {
  const d = document.querySelector('.wr-botdlg');
  if (!d) return { open: false };
  const top = (s) => { const e = d.querySelector(s); return e ? Math.round(e.getBoundingClientRect().top) : null; };
  const r = d.getBoundingClientRect();
  const rows = Array.from(d.querySelectorAll('.wr-botdlg-pcrow'));
  const imgs = Array.from(d.querySelectorAll('.wr-botdlg-pccards img'));
  const marks = rows.map(row => getComputedStyle(row.querySelector('.wr-botdlg-mark')).opacity);
  const saved = d.querySelector('.wr-botdlg-saved');
  const pcs = d.querySelector('.wr-botdlg-pcs');
  return {
    open: true,
    order: [top('.wr-botdlg-paste'), top('.wr-botdlg-saved'), top('.wr-botdlg-well'), top('.wr-botdlg-actions')],
    rows: rows.length, metas: Array.from(d.querySelectorAll('.wr-botdlg-pcmeta')).map(m => m.textContent),
    checked: Array.from(d.querySelectorAll('.wr-botdlg-pcin')).map(i => i.checked),
    marks, imgs: imgs.length, loaded: imgs.filter(i => i.complete && i.naturalWidth > 0).length,
    pasteVal: (d.querySelector('.wr-botdlg-paste') || {}).value,
    savedIdx: saved ? saved.selectedIndex : null, savedOpts: saved ? saved.options.length : 0, savedDisabled: saved ? saved.disabled : null,
    wellScrolls: pcs ? getComputedStyle(pcs).overflowY : '',
    fits: r.left >= 0 && r.top >= 0 && r.right <= innerWidth + 1 && r.bottom <= innerHeight + 1,
    hscroll: document.documentElement.scrollWidth > innerWidth + 1,
    err: (d.querySelector('.wr-botdlg-err') || {}).textContent || '', w: Math.round(r.width), h: Math.round(r.height),
  };
});
const botSeats = (page) => page.evaluate(() => Array.from(document.querySelectorAll('.wr-seat-who')).filter(e => /Arenabot/.test(e.textContent)).length);
const ascending = (a) => a.every(v => v !== null) && a.every((v, i) => i === 0 || v > a[i - 1]);

const cookie = await login('claudebot1');
if (!cookie) { console.log('FAIL  login as claudebot1 failed'); process.exit(1); }

for (const [engine, launcher] of ENGINES) {
  let browser;
  try {
    browser = await launcher.launch();
    const room = await privateRoom(cookie);
    const host = await openSeat(browser, room, cookie);
    await host.page.click('.wr-fill-bot');
    await host.page.waitForTimeout(1500);
    let d = await dialog(host.page);
    ok(engine, 'order: input, Saved Decks, Pre-Cons, then the buttons', d.open && ascending(d.order), JSON.stringify(d.order));
    ok(engine, 'the pre-con well holds 4 rows and scrolls inside itself', d.rows === 4 && /auto|scroll/.test(d.wellScrolls), `${d.rows} ${d.wellScrolls}`);
    ok(engine, 'each row reads "leaders / base · 80 cards · singleton"', d.metas.length === 4 && d.metas.every(m => / \/ .+ · 80 cards · singleton$/.test(m)), JSON.stringify(d.metas));
    ok(engine, 'pre-con art loads (2 leaders + base per row)', d.imgs === 12 && d.loaded === 12, `${d.loaded}/${d.imgs}`);
    ok(engine, 'the first pre-con is chosen, and only its check shows', d.checked.join() === 'true,false,false,false' && d.marks.map(Number).join() === '1,0,0,0', `${d.checked} / ${d.marks}`);
    ok(engine, 'a signed-in host is offered their saved decks', d.savedOpts >= 1 && d.savedDisabled !== null, `opts=${d.savedOpts} disabled=${d.savedDisabled}`);
    ok(engine, 'the popup fits the desktop viewport', d.fits, `${d.w}x${d.h}`);
    await host.page.screenshot({ path: `${SHOTS}/fillbot-sources-${engine}-popup.png` });

    // One source at a time.
    await host.page.fill('.wr-botdlg-paste', 'https://swudb.com/deck/example');
    await host.page.waitForTimeout(300);   // the check fades out over 150ms
    d = await dialog(host.page);
    ok(engine, 'typing in the box clears the pre-con', d.checked.every(c => !c) && d.marks.every(m => Number(m) === 0), `${d.checked} / ${d.marks}`);
    await host.page.locator('.wr-botdlg-pcrow').nth(2).click();
    d = await dialog(host.page);
    ok(engine, 'clicking a pre-con row chooses it and clears the box', d.checked.join() === 'false,false,true,false' && d.pasteVal === '', `${d.checked} "${d.pasteVal}"`);
    if (d.savedOpts > 1 && !d.savedDisabled) {
      await host.page.selectOption('.wr-botdlg-saved', { index: 1 });
      d = await dialog(host.page);
      ok(engine, 'picking a saved deck clears the box and the pre-con', d.savedIdx === 1 && d.checked.every(c => !c) && d.pasteVal === '', `${d.savedIdx} ${d.checked}`);
      await host.page.locator('.wr-botdlg-pcrow').nth(1).click();
      d = await dialog(host.page);
      ok(engine, 'picking a pre-con resets the saved deck', d.savedIdx === 0 && d.checked[1] === true, String(d.savedIdx));
    } else {
      console.log(`${engine}: note — claudebot1 has no saved decks; saved-deck exclusivity not exercised`);
      await host.page.locator('.wr-botdlg-pcrow').nth(1).click();
    }
    // Nothing chosen at all → refused in the popup.
    await host.page.evaluate(() => document.querySelectorAll('.wr-botdlg-pcin').forEach(i => { i.checked = false; }));
    await host.page.click('.wr-botdlg-add');
    await host.page.waitForTimeout(300);
    d = await dialog(host.page);
    ok(engine, 'with nothing chosen, Add Bot says so and stays open', d.open && /pre-con/i.test(d.err), d.err);
    // A pre-con still fills a seat.
    await host.page.locator('.wr-botdlg-pcrow').nth(1).click();
    await host.page.click('.wr-botdlg-add');
    await host.page.waitForTimeout(3500);
    ok(engine, 'a pre-con fills the seat and the popup closes', !(await dialog(host.page)).open && (await botSeats(host.page)) === 1);
    await host.ctx.close();

    // Phone width.
    const room2 = await privateRoom(cookie);
    const phone = await openSeat(browser, room2, cookie, true);
    await phone.page.click('.wr-fill-bot');
    await phone.page.waitForTimeout(1500);
    d = await dialog(phone.page);
    ok(engine, 'the popup fits a 400px phone with no sideways scroll', d.open && d.fits && d.w <= 400 && !d.hscroll, `${d.w}x${d.h}`);
    await phone.page.screenshot({ path: `${SHOTS}/fillbot-sources-${engine}-phone.png` });
    await phone.ctx.close();
  } catch (e) {
    ok(engine, 'ran without throwing', false, String(e && e.message || e));
  } finally {
    if (browser) await browser.close();
  }
}
report();
console.log(allOk ? 'ALL PASS' : 'SOME FAILED');
process.exit(allOk ? 0 : 1);
