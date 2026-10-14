// Team Suns "Fill Seat with Bot" (owner 2026-10-12: a fill button per team) on a live waiting room, in Chromium, Firefox and WebKit,
// desktop and 400px phone. The server rules are pinned by SWUSim/DevTools/tests/lobby_room_bots_test.php; this checks the page:
//   • the host (Red seat 1) sees an ENABLED "Fill Seat with Bot" on every empty slot — Red seat 3, Blue seats 2 and 4 — each naming its team;
//   • a Blue button's popup is titled for Team Blue; adding a pre-con puts an Arenabot in the BLUE column;
//   • the next Blue popup greys out that pre-con (its leaders are on Blue now) and pre-selects another; the Red popup does not grey it;
//   • the popup fits the viewport and the room never scrolls sideways (desktop and phone).
// Usage: node swusim-teamsuns-fill-bot-xbrowser.mjs [baseURL]   ENGINES=chromium,firefox,webkit (default all)
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
async function postForm(path, params) {
  const r = await fetch(BASE + path, { method: 'POST', body: new URLSearchParams(params), redirect: 'manual' });
  const text = await r.text();
  try { return JSON.parse(text); } catch (e) { return { RAW: text.slice(0, 300) }; }
}
// A private Team Suns room with its host on Red seat 1.
async function teamRoom() {
  const host = await postForm('APIs/Lobbies/JoinQueue.php',
    { rootName: 'SWUSim', createPrivate: '1', format: 'teamsuns', deckLink: DECK, preconstructedDeck: '', game_type: '' });
  if (!host || !host.success) throw new Error('room create failed: ' + JSON.stringify(host));
  const t = await postForm('APIs/Lobbies/SetTeam.php', { lobbyID: host.lobbyID, authKey: host.authKey, team: 'red' });
  if (!t || !t.success) throw new Error('set team failed: ' + JSON.stringify(t));
  return { lobbyID: host.lobbyID, key: host.authKey };
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
// Every fill button with the seat tile it sits in and the team column it belongs to.
const fills = (page) => page.evaluate(() => Array.from(document.querySelectorAll('.wr-fill-bot')).map(b => {
  const col = b.closest('.wr-teamcol'), seat = b.closest('.wr-seat');
  return { team: b.getAttribute('data-bot-team'), seat: seat && seat.getAttribute('data-seat'), disabled: b.disabled, text: b.textContent.trim(),
           column: col ? col.querySelector('.wr-teamhdr').textContent.trim().split(' ')[0].toLowerCase() : null };
}));
const dialog = (page) => page.evaluate(() => {
  const d = document.querySelector('.wr-botdlg');
  if (!d) return { open: false };
  const r = d.getBoundingClientRect();
  const rows = Array.from(d.querySelectorAll('.wr-botdlg-pc')).map(li => {
    const inp = li.querySelector('input'), row = li.querySelector('.wr-botdlg-pcrow');
    return { id: inp.value, disabled: inp.disabled, checked: inp.checked, off: li.classList.contains('wr-botdlg-pc-off'),
             opacity: Number(getComputedStyle(row).opacity), meta: (li.querySelector('.wr-botdlg-pcmeta') || {}).textContent || '' };
  });
  return { open: true, title: d.querySelector('.wr-botdlg-head').textContent.trim(), rows,
           fits: r.left >= 0 && r.top >= 0 && r.right <= innerWidth + 1 && r.bottom <= innerHeight + 1, w: Math.round(r.width), h: Math.round(r.height) };
});
// Arenabots per team column.
const botsByTeam = (page) => page.evaluate(() => {
  const out = {};
  document.querySelectorAll('.wr-teamcol').forEach(col => {
    const t = col.querySelector('.wr-teamhdr').textContent.trim().split(' ')[0].toLowerCase();
    out[t] = Array.from(col.querySelectorAll('.wr-seat-who')).filter(e => /Arenabot/.test(e.textContent)).length;
  });
  return out;
});
const noSideScroll = (page) => page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1);
const clickFill = async (page, team) => { await page.locator(`.wr-fill-bot[data-bot-team="${team}"]`).first().click(); await page.waitForTimeout(1200); };

for (const [engine, launcher] of ENGINES) {
  let browser;
  try {
    browser = await launcher.launch();
    const room = await teamRoom();
    const host = await openSeat(browser, room.lobbyID, room.key);
    const f = await fills(host.page);
    const want = { red: ['3'], blue: ['2', '4'] };
    ok(engine, 'every empty slot offers an enabled fill button for ITS team (Red 3, Blue 2 + 4)',
      f.length === 3 && f.every(b => !b.disabled && b.team === b.column && want[b.team].includes(b.seat)), JSON.stringify(f));
    ok(engine, 'the team room does not scroll sideways (desktop)', await noSideScroll(host.page));
    await host.page.screenshot({ path: `${SHOTS}/teamfill-${engine}-room.png` });

    await clickFill(host.page, 'blue');
    let d = await dialog(host.page);
    ok(engine, 'the Blue popup is titled for Team Blue, 4 pre-cons, none greyed, the first pre-selected',
      d.open && d.title === 'Add a Bot to Team Blue' && d.rows.length === 4 && d.rows.every(r => !r.disabled) && d.rows[0].checked, JSON.stringify(d));
    ok(engine, 'the popup fits the desktop viewport', d.fits, `${d.w}x${d.h}`);
    const first = d.rows[0] && d.rows[0].id;
    await host.page.click('.wr-botdlg-add');
    await host.page.waitForTimeout(3500);
    ok(engine, 'adding it puts an Arenabot in the BLUE column and closes the popup',
      !(await dialog(host.page)).open && JSON.stringify(await botsByTeam(host.page)) === JSON.stringify({ red: 0, blue: 1 }), JSON.stringify(await botsByTeam(host.page)));

    await clickFill(host.page, 'blue');
    d = await dialog(host.page);
    const r0 = d.rows.find(r => r.id === first) || {};
    ok(engine, 'the next Blue popup greys out that pre-con (disabled, dimmed, says why) and pre-selects another',
      d.open && r0.disabled && r0.off && r0.opacity < 0.6 && /already has this leader/.test(r0.meta) && !r0.checked && d.rows.some(r => r.checked && !r.disabled), JSON.stringify(d.rows));
    await host.page.screenshot({ path: `${SHOTS}/teamfill-${engine}-clash.png` });
    await host.page.keyboard.press('Escape');
    await host.page.waitForTimeout(400);
    await clickFill(host.page, 'red');
    d = await dialog(host.page);
    ok(engine, 'the Red popup does NOT grey it (no clash on Red)', d.open && d.title === 'Add a Bot to Team Red' && d.rows.every(r => !r.disabled), JSON.stringify(d.rows));
    await host.page.keyboard.press('Escape');
    await host.ctx.close();

    // Phone width: the same room, the team columns stack, nothing scrolls sideways, the popup fits.
    const phone = await openSeat(browser, room.lobbyID, room.key, true);
    ok(engine, 'phone: the room does not scroll sideways', await noSideScroll(phone.page));
    ok(engine, 'phone: the fill buttons are still there (Red 3, Blue 4)', (await fills(phone.page)).length === 2, JSON.stringify(await fills(phone.page)));
    await phone.page.screenshot({ path: `${SHOTS}/teamfill-${engine}-room-phone.png`, fullPage: true });
    await clickFill(phone.page, 'blue');
    d = await dialog(phone.page);
    ok(engine, 'phone: the popup fits 400px', d.open && d.fits && d.w <= 400, `${d.w}x${d.h}`);
    await phone.page.screenshot({ path: `${SHOTS}/teamfill-${engine}-clash-phone.png` });
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
