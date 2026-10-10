// "Return to Main Menu" tells the table you left, in all three engines, on both boards.
//
// Owner, 2026-10-10: "it's not clear when a player leaves a game at the end when they click 'Return to
// main menu'. it'd be nice to get a message that says 'Player N left.' or '<username> left.' so we know
// if we can keep chatting or not".
//
// SWUGoMainMenu (GameLayoutShared.php) beacons SWUSim/AnnounceLeave.php as the page navigates away; the
// server posts a seat-0 system row; the OTHER seat's board renders it in the merged log as a grey
// notice (swu-log-NOTICE), not as a chat message with a "Game:" label.
// The PHP half (SWUSim/DevTools/tests/announce_leave_test.php) proves the row is written and named. Only
// a browser can prove the two things that are left: the beacon survives the navigation it rides on, and
// the row lands on the other board looking like a notice.
//
//   node DevTools/ui-harness/swusim-leave-notice-xbrowser.mjs
//
// Each run seeds a FRESH goldfish game per engine/surface: the server says it once per seat per game, so
// a reused game would see the second run's notice suppressed and fail for the wrong reason.
import { chromium, firefox, webkit } from 'playwright';
import fs from 'node:fs';

const BASE = process.env.BASE_URL || 'http://localhost:3400/TCGEngine/';
const deck = fs.readFileSync(new URL('../../SWUSim/Tests/BotFixtures/premier_deck_a.txt', import.meta.url), 'utf8');

async function newGame() {
  const body = new URLSearchParams({ rootName: 'SWUSim', format: 'goldfish', queueType: 'bo1', deckLink: deck });
  const r = await fetch(BASE + 'APIs/Lobbies/JoinQueue.php', { method: 'POST', body });
  const j = JSON.parse(await r.text());
  if (!j.gameName) throw new Error('JoinQueue: ' + (j.message || 'no gameName'));
  return String(j.gameName);
}

const SURFACES = [
  { label: 'desktop', viewport: { width: 1600, height: 1000 }, query: '' },
  { label: 'phone',   viewport: { width: 390,  height: 844  }, query: '&swuLayout=mobile' },
];
const LOG_GREY = 'rgba(255, 255, 255, 0.78)';   // a game-log row (see swusim-chat-colours-xbrowser.mjs)

let fails = 0, checks = 0;
const bad = (n, m) => { fails++; checks++; console.log(`FAIL ${n} :: ${m}`); };
const ok  = () => { checks++; };

for (const [engine, launcher] of [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]]) {
  let b;
  try { b = await launcher.launch(); }
  catch (e) { bad(engine, `could not launch: ${e.message}`); continue; }

  for (const surface of SURFACES) {
    const name = `${engine}/${surface.label}`;
    let game;
    try { game = await newGame(); } catch (e) { bad(name, e.message); continue; }
    const url = (seat) => `${BASE}NextTurn.php?folderPath=SWUSim&gameName=${game}&playerID=${seat}${surface.query}`;

    // The one who stays (seat 2) and the one who leaves (seat 1), in separate contexts — two people.
    const stayCtx = await b.newContext({ viewport: surface.viewport });
    const leaveCtx = await b.newContext({ viewport: surface.viewport });
    const stay = await stayCtx.newPage(), leave = await leaveCtx.newPage();
    const errs = [];
    for (const p of [stay, leave]) p.on('pageerror', e => errs.push(e.message));
    await stay.goto(url(2), { waitUntil: 'domcontentloaded' });
    await leave.goto(url(1), { waitUntil: 'domcontentloaded' });
    await stay.waitForSelector('#swuLogPanel', { timeout: 20000 });
    await leave.waitForFunction(() => typeof window.SWUGearConcede === 'function', null, { timeout: 20000 });

    // The REAL gear-menu "Return to Main Menu" handler. In goldfish it ends the game and leaves at once
    // (no confirm), so the stayer is left on a finished game — exactly the owner's situation.
    const beacon = leave.waitForRequest(r => r.url().includes('SWUSim/AnnounceLeave.php'), { timeout: 10000 }).catch(() => null);
    await leave.evaluate(() => window.SWUGearConcede(true));
    const req = await beacon;
    if (!req) bad(name, 'leaving sent no AnnounceLeave request'); else ok();
    await leave.waitForURL(u => !String(u).includes('NextTurn.php'), { timeout: 10000 })
      .then(ok, () => bad(name, 'the leaver never navigated to the Main Menu'));

    // The stayer sees it.
    let row = null;
    try {
      await stay.waitForSelector('#swuLogPanel .swu-log-NOTICE', { timeout: 15000 });
      row = await stay.$eval('#swuLogPanel .swu-log-NOTICE', el => ({
        text: (el.textContent || '').trim(),
        spans: el.querySelectorAll('span').length,
        chat: el.classList.contains('swu-log-CHAT'),
        color: getComputedStyle(el).color,
        visible: el.getClientRects().length > 0,
      }));
    } catch { bad(name, 'no leave notice arrived on the other seat\'s board'); }
    if (row) {
      if (row.text !== 'Player 1 left the game.') bad(name, `notice reads "${row.text}"`); else ok();
      if (row.spans !== 1 || row.chat) bad(name, `notice is drawn as a chat message (spans=${row.spans}, chat=${row.chat})`); else ok();
      if (row.color !== LOG_GREY) bad(name, `notice is ${row.color}, expected the log grey ${LOG_GREY}`); else ok();
      if (!row.visible) bad(name, 'notice is in the DOM but not rendered'); else ok();
    }
    if (errs.length) bad(name, `page errors: ${errs.slice(0, 3).join(' | ')}`); else ok();
    if (row && process.env.SHOTS) await stay.screenshot({ path: `DevTools/ui-harness/_shots/leave-notice-${engine}-${surface.label}.png` });
    await stayCtx.close(); await leaveCtx.close();
  }
  await b.close();
}

console.log(fails ? `\n${fails} of ${checks} checks FAILED` : `\nALL ${checks} checks PASS`);
process.exit(fails ? 1 : 0);
