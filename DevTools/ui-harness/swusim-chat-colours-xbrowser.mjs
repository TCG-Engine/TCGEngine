// Chat colouring in the merged sidebar panel, in all three engines.
//
// Owner, 2026-09-26, three changes:
//   1. "make the text a player sends the same color as their player color in that chat"
//   2. "include that text coloring for Whisper chats. keep the italics."
//   3. "for all public pieces of whispers, keep the backwash to a grey hue now"
//
// ⚠ WHY A BROWSER GATE AND NOT ONLY THE SOURCE SCAN. The PHP half
// (SWUSim/DevTools/tests/chat_seat_colour_surfaces_test.php) proves the rules are in the stylesheets.
// It CANNOT see specificity — and specificity is the entire risk here. The body text is coloured by
// INHERITING from the row, and inheritance loses to any rule that matches the span directly, so a
// single stray ".swu-log-CHAT > span" would silently undo change 1 with all four seat rules still
// present and correct. Only a computed style can tell you. Same for change 3: the stub row carries
// BOTH .chatMsg-whisper and .chatMsg-whisperStub, so which wash you get is decided by rule ORDER.
//
//   node DevTools/ui-harness/swusim-chat-colours-xbrowser.mjs          (seeds its own board)
//   GAME=<id> node DevTools/ui-harness/swusim-chat-colours-xbrowser.mjs (reuse an existing one)
//
// ⚠ IT SEEDS ITS OWN BOARD ON PURPOSE. Chat rows live in APCu on a 3600s TTL and do not survive a web
// server restart, so a game id handed in by a human goes quietly stale: the board still loads, the
// chat is simply gone, and the run dies on a selector timeout that looks like a UI regression. The
// harness posts Tests/Visual/Chat_4P_WhisperMatrix.md to TestSchemaSetup.php instead — the same file
// the visual check uses, so the two can never describe different boards.
import { chromium, firefox, webkit } from 'playwright';
import fs from 'node:fs';

const BASE = process.env.BASE_URL || 'http://localhost:3400/TCGEngine/';

// TestSchemaSetup.php is mod-only, so this needs a real session; any account will do.
async function seedBoard() {
  const form = (o) => new URLSearchParams(o).toString();
  const post = async (path, body, cookie) => fetch(BASE + path, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', ...(cookie ? { cookie } : {}) },
    body: form(body), redirect: 'manual',
  });

  const login = await post('AccountFiles/AttemptPasswordLogin.php',
                           { submit: '1', userID: 'claudebot1', password: 'pass' });
  const cookie = (login.headers.getSetCookie?.() || [])
    .map(c => c.split(';')[0]).join('; ');
  if (!cookie) throw new Error('login returned no session cookie (is claudebot1 a mod on this box?)');

  const schema = fs.readFileSync(new URL('../../SWUSim/Tests/Visual/Chat_4P_WhisperMatrix.md', import.meta.url), 'utf8');
  const res = await post('SWUSim/TestSchemaSetup.php', { schema }, cookie);
  const json = JSON.parse(await res.text());
  if (json.error) throw new Error(`TestSchemaSetup refused the schema: ${json.error}`);
  if (json.seatCount !== 4) throw new Error(`expected a 4-seat board, got seatCount=${json.seatCount}`);
  return String(json.gameName);
}

let GAME = process.env.GAME;
if (!GAME) {
  try { GAME = await seedBoard(); console.log(`seeded board ${GAME}`); }
  catch (e) { console.log(`FAIL :: could not seed a board :: ${e.message}`); process.exit(1); }
}

// The palette, as every engine reports it. Keep in step with --swu-chat-p1..p4 in GameLayout.php.
const SEAT_RGB = {
  1: 'rgb(111, 184, 255)',   // #6fb8ff
  2: 'rgb(255, 155, 111)',   // #ff9b6f
  3: 'rgb(127, 216, 143)',   // #7fd88f
  4: 'rgb(215, 155, 255)',   // #d79bff
};
const GREY_WASH   = 'rgba(170, 182, 196, 0.1)';
const PURPLE_WASH = 'rgba(160, 110, 255, 0.1)';

let fails = 0, checks = 0;
const bad = (n, m) => { fails++; checks++; console.log(`FAIL ${n} :: ${m}`); };
const ok  = () => { checks++; };

// Seat 3 is the viewer worth measuring: it RECEIVES two whispers (from P1 and P4), SENDS one, and sees
// P2's as a redacted stub — all three whisper states plus four publics on one screen.
const VIEWER = 3;

// ⚠ BOTH BOARDS. The phone layout is a SEPARATE stylesheet (GameLayoutMobile.php) with its own COPY of
// the four hexes, so a fix applied to GameLayout.php alone looks complete and ships half done. The
// layout is picked SERVER-SIDE, and `?swuLayout=mobile` is the documented override (GameLayoutDevice.php)
// — far more reliable here than hoping a spoofed user agent survives to PHP.
const SURFACES = [
  { label: 'desktop', viewport: { width: 1600, height: 1000 }, query: '' },
  { label: 'phone',   viewport: { width: 390,  height: 844  }, query: '&swuLayout=mobile' },
];

for (const [engine, launcher] of [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]]) {
 let b;
 try { b = await launcher.launch(); }
 catch (e) { bad(engine, `could not launch: ${e.message}`); continue; }

 for (const surface of SURFACES) {
  const name = `${engine}/${surface.label}`;
  const p = await b.newPage({ viewport: surface.viewport });
  const errs = []; p.on('pageerror', e => errs.push(e.message));
  await p.goto(`${BASE}NextTurn.php?folderPath=SWUSim&gameName=${GAME}&playerID=${VIEWER}&authKey=testschema${surface.query}`,
               { waitUntil: 'domcontentloaded' });
  // A graceful failure, not a stack trace: an empty board is a RESULT (usually expired APCu chat or a
  // board that never seeded), and it should be reported next to the other engines' results.
  try {
    await p.waitForSelector('.chatMsg', { timeout: 20000 });
  } catch {
    bad(name, 'no chat rows on the board — it never seeded, or its APCu chat expired');
    await p.close();
    continue;
  }

  const rows = await p.$$eval('.chatMsg', els => els.map(el => {
    const seat = (el.className.match(/chatMsg-p(\d)/) || [])[1] || null;
    const spans = el.querySelectorAll('span');
    // A readable row is <name><body>; a redacted stub is a single generated span.
    const bodyEl = spans.length > 1 ? spans[spans.length - 1] : spans[0];
    const rowCS  = getComputedStyle(el);
    const bodyCS = bodyEl ? getComputedStyle(bodyEl) : null;
    return {
      seat,
      stub:      el.classList.contains('chatMsg-whisperStub'),
      whisper:   el.classList.contains('chatMsg-whisper'),
      bodyText:  bodyEl ? (bodyEl.textContent || '').trim().slice(0, 40) : '',
      bodyColor: bodyCS ? bodyCS.color : null,
      bodyStyle: bodyCS ? bodyCS.fontStyle : null,
      rowBg:     rowCS.backgroundColor,
    };
  }));

  // Sanity: the board really is the seeded one. Without this every loop below is vacuously green.
  const publics  = rows.filter(r => !r.whisper);
  const readable = rows.filter(r => r.whisper && !r.stub);
  const stubs    = rows.filter(r => r.stub);
  if (rows.length !== 8 || publics.length !== 4 || readable.length !== 3 || stubs.length !== 1) {
    bad(name, `expected 4 public + 3 readable whispers + 1 stub, got ${publics.length}/${readable.length}/${stubs.length} of ${rows.length}`);
    await p.close();
    continue;
  }
  ok();

  // ── 1. A PUBLIC message's BODY is its sender's seat colour ─────────────────────────────────────
  for (const r of publics) {
    const want = SEAT_RGB[r.seat];
    if (!want) { bad(name, `public row has no seat class ("${r.bodyText}")`); continue; }
    if (r.bodyColor !== want) {
      bad(name, `P${r.seat} public body is ${r.bodyColor}, expected ${want} ("${r.bodyText}")`);
    } else ok();
  }

  // ── 2. A READABLE whisper's body is ALSO its sender's colour, and still italic ──────────────────
  for (const r of readable) {
    const want = SEAT_RGB[r.seat];
    if (r.bodyColor !== want) {
      bad(name, `P${r.seat} whisper body is ${r.bodyColor}, expected ${want} ("${r.bodyText}")`);
    } else ok();
    if (r.bodyStyle !== 'italic') {
      bad(name, `P${r.seat} whisper is ${r.bodyStyle}, expected italic — the italics were to stay`);
    } else ok();
  }

  // ── 3. The PUBLIC piece of a whisper — the stub — washes GREY, not purple ──────────────────────
  for (const r of stubs) {
    if (r.rowBg === PURPLE_WASH) {
      bad(name, `the whisper stub still washes PURPLE (${r.rowBg}) — it collides with P4's seat colour`);
    } else if (r.rowBg !== GREY_WASH) {
      bad(name, `the whisper stub washes ${r.rowBg}, expected the neutral ${GREY_WASH}`);
    } else ok();
    // The stub still names its sender in the sender's colour; only the backwash changed.
    if (r.bodyColor !== SEAT_RGB[r.seat]) {
      bad(name, `the stub's text is ${r.bodyColor}, expected its sender's ${SEAT_RGB[r.seat]}`);
    } else ok();
  }

  // ── 4. THE CONTROL. A readable whisper must NOT have gone grey too — "make it all grey" would
  //      otherwise satisfy every assertion above.
  for (const r of readable) {
    if (r.rowBg === GREY_WASH) {
      bad(name, `a READABLE whisper went grey as well; only the public stub was meant to`);
    } else ok();
  }

  // ── 5. THE LOG HALF OF THE PANEL. Owner, 2026-09-26, on the phone board: "the chat patterns did
  //      not carry over." GameLayoutMobile.php had copied the CHAT rules and nothing else, so log
  //      rows fell back to the panel's inherited near-white with no separator and no card links —
  //      the log read LOUDER than the chat, inverting the panel's whole hierarchy. Measured on BOTH
  //      surfaces so the two boards cannot drift apart again.
  const log = await p.$$eval('.swu-log-entry:not(.chatMsg)', els => els.map(el => {
    const cs = getComputedStyle(el);
    const link = el.querySelector('.swu-card-link');
    return {
      color: cs.color,
      border: cs.borderBottomWidth,
      last: el === el.parentElement.lastElementChild,
      linkDecoration: link ? getComputedStyle(link).textDecorationStyle : null,
      linkColor: link ? getComputedStyle(link).color : null,
      text: (el.textContent || '').trim().slice(0, 34),
    };
  }));
  if (log.length !== 8) {
    bad(name, `expected 8 game-log rows, got ${log.length}`);
  } else ok();

  for (const r of log) {
    // Quieter than chat: 0.78 alpha against chat's 0.92/full seat colour.
    if (r.color !== 'rgba(255, 255, 255, 0.78)') {
      bad(name, `a log row is ${r.color}, expected rgba(255, 255, 255, 0.78) ("${r.text}")`);
    } else ok();
    // Every row separated except the last.
    const wantBorder = r.last ? '0px' : '2px';
    if (r.border !== wantBorder) {
      bad(name, `a log row's separator is ${r.border}, expected ${wantBorder}${r.last ? ' (last row)' : ''} ("${r.text}")`);
    } else ok();
  }

  const linked = log.filter(r => r.linkDecoration !== null);
  if (linked.length < 4) {
    bad(name, `expected card links in at least 4 log rows, found ${linked.length}`);
  } else ok();
  for (const r of linked) {
    if (r.linkDecoration !== 'dotted') {
      bad(name, `a card link is underlined "${r.linkDecoration}", expected dotted ("${r.text}")`);
    } else ok();
  }

  if (errs.length) bad(name, `page errors: ${errs.slice(0, 2).join(' | ')}`); else ok();
  await p.close();
 }
 await b.close();
}

console.log(fails === 0 ? `PASS (${checks} checks, 3 engines)` : `${fails} FAILED of ${checks}`);
process.exit(fails === 0 ? 0 : 1);
