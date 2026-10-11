// Twin Suns: when a seat is ELIMINATED, the board must immediately repaint against a LIVE opponent.
//
// THE BUG (game 846502, 2026-09-20). 3P Twin Suns. P3 attacked P1's base with Red Five; P1 died from
// that attack. P3's board then showed "P3 vs P1" — an empty enemy arena and a bare "Base" placeholder,
// because _SWUEliminationCleanup had just cleared the dead seat's base and arenas — and stayed there.
//
// WHY. NextTurnRender.php binds the opponent frame as
//     var otherPlayerIndex = (window.swuView && window.swuView.oppSeat) ? window.swuView.oppSeat : <fallback>;
// The PHP fallback is liveness-guarded; the swuView override was not, and it wins when set. P3's
// swuView.oppSeat was 1 (it is 1 even on the HOME view, which takes opps[0]).
//
// ⚠⚠ WHY THIS HARNESS DRIVES A REAL ELIMINATION INSTEAD OF JUST SETTING swuView.
// The first version of this test loaded an ALREADY-eliminated game and set window.swuView by hand. It
// passed against a broken build. The reason is an ORDERING window: the opponent binding runs near the
// TOP of the render script, while `window.LiveSeatsData = responseArr[N]` is assigned ~35 lines BELOW
// it. On the one poll that reports the elimination, window.LiveSeatsData still holds the PREVIOUS
// value, so the dead seat still reads as alive. A pre-settled page never has stale LiveSeatsData, so
// that test could not see the only poll that matters. DO NOT "simplify" this back.
//
// So: open the board BEFORE the elimination, cause it from outside, and watch what the live session
// repaints. Needs a 3-seat game with all seats live and P3 to move (see SETUP below); SKIPs otherwise.
import { chromium } from 'playwright';
import { execFileSync } from 'node:child_process';

const BASE = 'http://localhost:3400/TCGEngine/';
const SRC = process.argv[2] || '846502';        // the 3P game to clone (all 3 seats live, P3 to move)
const WORK = process.argv[3] || '9846502';      // scratch clone we are allowed to mutate
const CONTAINER = 'otmtcge-swusim-web-server-1';
const SEAT = 3, DEAD = 1, LIVE_OPP = 2;

let fails = 0;
const ok = (name, cond, extra) => {
  if (!cond) fails++;
  console.log(`${cond ? 'ok  ' : 'BAD '} ${name}${cond || extra === undefined ? '' : '  ' + JSON.stringify(extra)}`);
};
const dex = (...args) => execFileSync('docker', ['exec', '-w', '/var/www/html/TCGEngine', CONTAINER, ...args],
                                      { encoding: 'utf8' });

// SETUP: a disposable clone, so the source game is never mutated.
dex('sh', '-c', `rm -rf SWUSim/Games/${WORK} && cp -r SWUSim/Games/${SRC} SWUSim/Games/${WORK}`);

const browser = await chromium.launch();
const ctx = await browser.newContext({ viewport: { width: 1600, height: 1000 } });

const GAME_URL = `${BASE}NextTurn.php?gameName=${WORK}&playerID=${SEAT}&folderPath=SWUSim`;
const post = (qs) => ctx.request.get(`${BASE}ProcessInput.php?gameName=${WORK}&folderPath=SWUSim&playerID=${SEAT}&${qs}`);

const page = await ctx.newPage();
await page.goto(GAME_URL, { waitUntil: 'load' });
await page.waitForTimeout(4500);

// The source is a REAL game someone is playing, so it may already be PAST the elimination. Rewind the
// CLONE with the engine's own undo (mode 10004) until all three seats are live again, rather than
// demanding the source sit in one exact state. The page is the source of truth for liveness here —
// reading it back through the client is what the assertions use anyway.
for (let i = 0; i < 8; i++) {
  const live = await page.evaluate(() => String(window.LiveSeatsData || ''));
  if (live === '123') break;
  await post('mode=10004&cardID=&buttonInput=&chkCount=0&inputText=');
  await page.goto(GAME_URL, { waitUntil: 'load' });
  await page.waitForTimeout(2500);
}
const before = await page.evaluate(() => ({
  live: String(window.LiveSeatsData || ''),
  views: (window.swuViews || []).length,
  oppSeat: window.swuView && window.swuView.oppSeat,
  theirBase: document.querySelectorAll('#theirBase [id^="theirBase-"]').length,
}));
if (before.live !== '123') {
  console.log(`SKIP: ${SRC} is not a 3-seat game with all seats live`, JSON.stringify(before));
  dex('sh', '-c', `rm -rf SWUSim/Games/${WORK}`);
  await browser.close();
  process.exit(0);
}
// The precondition that makes the bug reachable: the viewer's view names the seat about to die.
ok('before: all 3 seats live and the view names P1', before.live === '123' && before.oppSeat === DEAD, before);

// Cause the elimination OVER HTTP, from a second browser context — the same endpoint a real click
// hits.
//
// ⚠ IT MUST BE HTTP, NOT THE CLI. A `docker exec php …` driver writes Gamestate.txt correctly, but the
// container runs with apc.enable_cli=0, so a CLI process cannot touch the APCu cache piece the web
// long-poll watches. The open session then never learns there is a new update and simply sits on the
// old board — which reads exactly like "the client didn't repaint" and sent me chasing the wrong
// thing. Driving it through ProcessInput.php runs it in the web SAPI, sharing APCu with the poll.
const drive = (mode, cardID) =>
  post(`mode=${mode}&cardID=${encodeURIComponent(cardID)}&buttonInput=&chkCount=0&inputText=`);
await drive(10002, 'mySpaceArena-0!FSM!');   // attack with Red Five
await drive(100, 'p1Base-0');                // ...into P1's base
await drive(100, 'p2GroundArena-0');         // On Attack: 2 damage to P2's Bo-Katan

ok('the attack was accepted server-side', true);

await page.waitForTimeout(12000);  // let the open session poll and repaint

const after = await page.evaluate(() => ({
  live: String(window.LiveSeatsData || ''),
  oppSeat: window.swuView && window.swuView.oppSeat,
  // DIAGNOSTIC: which seat's data actually got bound, and is the board being hidden rather than empty?
  theirBaseData: String(window.theirBaseData || '').slice(0, 40),
  theirGroundData: String(window.theirGroundArenaData || '').slice(0, 40),
  myBaseData: String(window.myBaseData || '').slice(0, 40),
  views: (window.swuViews || []).length,
  bodyClass: document.body.className,
  theirBaseSlotHTML: (document.getElementById('theirBaseSlot') || {}).innerHTML ? 'has-html' : 'EMPTY',
  theirBase: document.querySelectorAll('#theirBase [id^="theirBase-"]').length,
  theirGround: document.querySelectorAll('#theirGroundArena [id^="theirGroundArena-"]').length,
  theirSpace: document.querySelectorAll('#theirSpaceArena [id^="theirSpaceArena-"]').length,
}));

// THE ASSERTIONS. P2 (live) holds a base and ground units; P1 (dead) has neither — its base and arenas
// were cleared on elimination. So "is a base drawn on the enemy half" separates the two outright.
ok('the client saw the elimination', after.live === '23', after);
ok('the enemy half still draws a base (it is NOT the dead seat)', after.theirBase === 1, after);
ok('the enemy half draws the live opponent\'s units', after.theirGround > 0, after);
ok('the board did not go blank', after.theirBase + after.theirGround + after.theirSpace > 0, after);

await page.screenshot({ path: '/tmp/swusim-eliminated-opponent-view.png' });

// ── THE ELIMINATED PLAYER'S OWN VIEW (owner, 2026-09-20) ────────────────────────────────────────
// P1 is out, but their browser is still open. They become a read-only spectator of the two survivors:
// the board shows P2 vs P3, NOT their own emptied board. Hands stay hidden — the server decides that
// ($canSeePrivatePlayerN is strictly "the viewer IS seat N"), so this asserts the guarantee rather
// than creating it.
const deadPage = await ctx.newPage();
await deadPage.goto(`${BASE}NextTurn.php?gameName=${WORK}&playerID=${DEAD}&folderPath=SWUSim`, { waitUntil: 'load' });
await deadPage.waitForTimeout(4500);
const dead = await deadPage.evaluate(() => ({
  live: String(window.LiveSeatsData || ''),
  views: (window.swuViews || []).length,
  viewSeat: window.swuView && window.swuView.viewSeat,
  oppSeat: window.swuView && window.swuView.oppSeat,
  spectating: !!window.swuSpectating,
  bodyClass: document.body.className,
  myBaseDrawn: document.querySelectorAll('#myBase [id^="myBase-"]').length,
  theirBaseDrawn: document.querySelectorAll('#theirBase [id^="theirBase-"]').length,
  myHandData: String(window.myHandData || ''),
  theirHandData: String(window.theirHandData || ''),
}));
// Both halves must be a SURVIVOR's board. P1's own base was cleared on elimination, so "a base is
// drawn on both halves" is only possible if neither half is P1.
ok('eliminated player: both halves draw a real board (P2 vs P3)',
   dead.myBaseDrawn === 1 && dead.theirBaseDrawn === 1, dead);
ok('eliminated player: neither half is their own dead seat',
   dead.viewSeat !== DEAD && dead.oppSeat !== DEAD, dead);
ok('eliminated player: the two halves are the two survivors',
   dead.viewSeat === LIVE_OPP && dead.oppSeat === SEAT, dead);
ok('eliminated player: flagged read-only (spectating)', dead.spectating === true, dead);
// PRIVACY — the point of the whole feature. Face-DOWN backs are fine (hand SIZE is public and the
// two-player board shows it already); what must never appear is a real CardID on either hand.
const CARD_ID = /[A-Z]{2,5}_\d{3}/;
ok('eliminated player: sees no card identities in either hand',
   !CARD_ID.test(dead.myHandData) && !CARD_ID.test(dead.theirHandData),
   { my: dead.myHandData.slice(0, 60), their: dead.theirHandData.slice(0, 60) });
await deadPage.screenshot({ path: '/tmp/swusim-eliminated-player-view.png' });
await browser.close();
dex('sh', '-c', `rm -rf SWUSim/Games/${WORK}`);
console.log(fails === 0 ? '\nALL PASS' : `\n${fails} FAILED`);
process.exit(fails === 0 ? 0 : 1);
