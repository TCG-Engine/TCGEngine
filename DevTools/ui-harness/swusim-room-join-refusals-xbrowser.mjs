// WHY YOU COULD NOT JOIN — the waiting room's refusal states, in all three engines.
//
// Owner, 2026-09-26: "we need better error messages to the user that explains better what happened.
// why they couldn't join." Before this, a visitor following a PUBLIC Twin Suns room's Copy Link was
// told "That invite is invalid or has expired." in every case — full room, free seat, closed room,
// mistyped code — because PollLobbyUpdates.php resolved an invite code only for a PRIVATE lobby.
//
// ⚠ WHY A BROWSER GATE AND NOT JUST THE HTTP TEST.
// SWUSim/DevTools/tests/lobby_invite_messages_test.php proves the payload: which reason the server
// returns for which situation. It cannot see the thing the owner actually asked for — what the PAGE
// says, whether the Join button is offered or disabled, whether there is a way out, and above all
// whether a full room's page RE-ENABLES ITSELF when a seat opens. That last one is the promise the
// copy makes ("you will be able to join as soon as one does") and only a live page can be caught
// breaking it.
//
//   node DevTools/ui-harness/swusim-room-join-refusals-xbrowser.mjs
//
// It creates and then drains its own rooms; nothing to set up.
import { chromium, firefox, webkit } from 'playwright';
import fs from 'node:fs';

const BASE = process.env.BASE_URL || 'http://localhost:3400/TCGEngine/';
const SHOT = new URL('./_shots/', import.meta.url);
const DECK = fs.readFileSync(new URL('../../SWUSim/Tests/BotFixtures/twinsuns_deck_a.txt', import.meta.url), 'utf8')
  .split('\n').filter(l => !l.startsWith('#')).join('\n').trim();

let fails = 0, checks = 0;
const bad = (n, m) => { fails++; checks++; console.log(`FAIL ${n} :: ${m}`); };
const ok  = () => { checks++; };

const form = (o) => new URLSearchParams(o).toString();
async function api(path, body, cookie) {
  const r = await fetch(BASE + path, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', ...(cookie ? { cookie } : {}) },
    body: form(body), redirect: 'manual',
  });
  const t = await r.text();
  try { return JSON.parse(t); } catch { return { RAW: t.slice(0, 200) }; }
}
async function login(user) {
  const r = await fetch(BASE + 'AccountFiles/AttemptPasswordLogin.php', {
    method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: form({ submit: '1', userID: user, password: 'pass' }), redirect: 'manual',
  });
  return (r.headers.getSetCookie?.() || []).map(c => c.split(';')[0]).join('; ');
}
// ⚠ casterMode PARTITIONS THIS FIXTURE FROM EVERY OTHER ROOM ON THE DEV BOX, and that is the only
// reason it is set. Public matchmaking pairs a joiner only into a lobby whose casterMode MATCHES
// (JoinQueue.php: `!empty($lobby->casterMode) === $casterMode`), so a caster room can never absorb —
// or be absorbed by — the ordinary rooms that earlier test runs leave behind for their 600-900s TTL.
// Without it this harness passed or failed depending on how much debris was in APCu: the same nine
// assertions failed in firefox on one run and webkit on the next, because whichever engine ran after
// the debris was consumed got dropped into somebody else's 1/4 room.
//
// Nothing under test reads the flag. PollLobbyUpdates does not return casterMode in its room payload
// and WaitingRoom.php never branches on it, so every message, button and seat count below is the same
// one a normal room renders.
const queue = (cookie) => api('APIs/Lobbies/JoinQueue.php',
  { rootName: 'SWUSim', format: 'twinsuns', queueType: 'bo1', casterMode: '1', deckLink: DECK }, cookie);
const joinByCode = (cookie, inviteCode) => api('APIs/Lobbies/JoinQueue.php',
  { rootName: 'SWUSim', privateInviteCode: inviteCode, casterMode: '1', deckLink: DECK,
    preconstructedDeck: '', game_type: '' }, cookie);
const pollRoom = (lobbyID, authKey) => api('APIs/Lobbies/PollLobbyUpdates.php',
  { rootName: 'SWUSim', lobbyID, inviteCode: '', playerID: 0, authKey });
const leave = (cookie, lobbyID, r) => api('APIs/Lobbies/LeaveQueue.php',
  { rootName: 'SWUSim', lobbyID, playerID: r.playerID ?? 0, authKey: r.authKey ?? '' }, cookie);

// ⚠ ONE ACCOUNT PER SEAT. A player holds ONE lobby at a time (LobbyReleaseOtherSeats), so filling a
// room from one account silently moves that account's single seat instead of adding three more.
const who = ['claudebot1', 'claudebot2', 'claudebot3', 'claudebot4'];
const cookies = [];
for (const u of who) {
  const c = await login(u);
  if (!c) { console.log(`FAIL :: no session cookie for ${u}`); process.exit(1); }
  cookies.push(c);
}

// ⚠ A FRESH ROOM PER ENGINE, NOT ONE SHARED BY ALL THREE. Case D starts its room, which ends it, and
// each seat's account can hold only ONE lobby — so re-queueing for a later engine silently releases
// the seat it still held in the shared room. Sharing produced a 1/4 room and four cascading failures
// in firefox that had nothing to do with firefox.
async function buildFullRoom() {
  const first = await queue(cookies[0]);
  if (!first.success || !first.lobbyID) {
    console.log(`FAIL :: could not open a room :: ${JSON.stringify(first).slice(0, 200)}`);
    process.exit(1);
  }
  // ⚠ THE CODE COMES FROM A POLL, NOT FROM THE CREATE RESPONSE. JoinQueue returns inviteCode only on
  // the branch that CREATED the lobby; if matchmaking dropped this seat into an existing room the
  // field is simply absent, and the harness then opened `?invite=undefined` and blamed the browser.
  // A poll answers for any seat, created or joined.
  const info = await pollRoom(first.lobbyID, first.authKey);
  const code = info.inviteCode;
  if (!code) {
    console.log(`FAIL :: room ${first.lobbyID} reports no invite code :: ${JSON.stringify(info).slice(0, 200)}`);
    process.exit(1);
  }
  // The other three join THAT room BY CODE rather than by matchmaking, so the fixture cannot end up
  // spread across two rooms if another one is open in the same format.
  const s = [first];
  for (let i = 1; i < 4; i++) s.push(await joinByCode(cookies[i], code));
  if (s.some(x => !x.success)) {
    console.log(`FAIL :: could not fill the room :: ${JSON.stringify(s.map(x => x.message))}`);
    process.exit(1);
  }
  const check = await pollRoom(first.lobbyID, first.authKey);
  if (Number(check.numPlayers) !== 4) {
    console.log(`FAIL :: fixture room is ${check.numPlayers}/4, not 4/4 — another room absorbed a seat`);
    process.exit(1);
  }
  // Case D starts this room, which only its HOST may do.
  const meHost = (check.roster || []).some(r => r.isHost && r.playerID === check.playerID);
  if (!meHost) {
    console.log(`FAIL :: seat 0 is not the host of ${first.lobbyID}; case D could not start it`);
    process.exit(1);
  }
  return { seats: s, lobbyID: first.lobbyID, code };
}
fs.mkdirSync(SHOT, { recursive: true });

const LINK = (c) => `${BASE}SharedUI/Sites/SWUSim/WaitingRoom.php?invite=${encodeURIComponent(c)}`;
const hint = (p) => p.textContent('#wr-hint').then(t => (t || '').trim());

for (const [name, launcher] of [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]]) {
    let b;
    try { b = await launcher.launch(); }
    catch (e) { bad(name, `could not launch: ${e.message}`); continue; }

    const { seats, lobbyID, code } = await buildFullRoom();
    console.log(`${name}: room ${lobbyID} · 4/4 · code ${code}`);

    // ── A. A FULL room. Resolvable, honest about capacity, and offers a way out. ─────────────────
    // The visitor is deliberately NOT logged in: a link recipient is not required to have an account,
    // and the guest path is the one people actually arrive on.
    {
      const label = `${name}/full`;
      const ctx = await b.newContext({ viewport: { width: 1280, height: 900 } });
      const p = await ctx.newPage();
      const errs = []; p.on('pageerror', e => errs.push(e.message));
      await p.goto(LINK(code), { waitUntil: 'domcontentloaded' });

      // The room must RESOLVE. Before the fix this page went straight to the red gone state.
      try { await p.waitForSelector('#wr-roster .wr-seat', { timeout: 20000 }); ok(); }
      catch { bad(label, 'a full public room rendered no roster — the invite did not resolve'); }

      const state = await p.getAttribute('#wr-root', 'data-state');
      if (state === 'gone') bad(label, 'a live full room rendered the GONE state');
      else ok();

      const h = await hint(p);
      if (!/room is full/i.test(h)) bad(label, `hint does not say the room is full: "${h}"`); else ok();
      if (!/waiting for a seat/i.test(h)) bad(label, `hint does not say it is waiting: "${h}"`); else ok();
      // The COUNT is the status box's job, not the sentence's — asserted where it is actually drawn,
      // and asserted as ABSENT from the sentence so the two can never start disagreeing.
      const count = (await p.textContent('#wr-count-n') || '').trim();
      if (count !== '4/4' && count !== '4') bad(label, `the seat count reads "${count}", expected 4/4`); else ok();
      if (/\d\/\d/.test(h)) bad(label, `the hint repeats the count the icon already shows: "${h}"`); else ok();

      // The Join control is offered but inert — disabled with a reason beats absent.
      const jb = await p.$('#wr-deck-btn');
      if (!jb) bad(label, 'no deck/join button at all');
      else if (!(await jb.isDisabled())) bad(label, 'the join button is enabled on a full room');
      else ok();

      // The spectator promise, and the escape hatch.
      const left = (await p.textContent('#wr-actions-left') || '');
      if (!/spectator/i.test(left)) bad(label, `no spectator note on a full room: "${left.trim()}"`); else ok();
      if (!(await p.$('#wr-requeue'))) bad(label, 'no "Find another room" button on a full room'); else ok();

      await p.screenshot({ path: new URL(`swusim-join-refusal-full-${name}.png`, SHOT).pathname, fullPage: false });

      // ── B. A SEAT OPENS while this page is open. The whole point of not dead-ending. ───────────
      // Drains seat 2 over HTTP and lets the page's own 1.5s poll notice. No reload: a reload would
      // prove only that a fresh page reads a 3/4 room correctly, which is case A with a different
      // number — the claim under test is that the OPEN page recovers.
      await leave(cookies[1], lobbyID, seats[1]);
      let flipped = false;
      try {
        await p.waitForFunction(() => {
          const t = (document.getElementById('wr-hint') || {}).textContent || '';
          const b = document.getElementById('wr-deck-btn');
          return /take a seat/i.test(t) && b && !b.disabled;
        }, null, { timeout: 15000 });
        flipped = true; ok();
      } catch { bad(`${name}/seat-opens`, `page did not re-enable joining after a seat freed; hint still "${await hint(p)}"`); }

      if (flipped) {
        const c2 = (await p.textContent('#wr-count-n') || '').trim();
        if (!/3/.test(c2)) bad(`${name}/seat-opens`, `the seat count did not drop to 3: "${c2}"`); else ok();
        await p.screenshot({ path: new URL(`swusim-join-refusal-seatopen-${name}.png`, SHOT).pathname });
      }

      // The seat STAYS out: case D below starts this same room, and 3 seats is a legal Twin Suns
      // start. Re-queueing that account would also move its single seat back out of this room.
      seats[1] = null;

      if (errs.length) bad(label, `page errors: ${errs.slice(0, 2).join(' | ')}`); else ok();
      await ctx.close();
    }

    // ── C. A code that names nothing. Names the LINK as the suspect, and still offers a way on. ──
    {
      const label = `${name}/badcode`;
      const ctx = await b.newContext({ viewport: { width: 1280, height: 900 } });
      const p = await ctx.newPage();
      const errs = []; p.on('pageerror', e => errs.push(e.message));
      await p.goto(LINK('deadbeefdeadbeefdeadbeef'), { waitUntil: 'domcontentloaded' });
      try { await p.waitForFunction(() => (document.getElementById('wr-root') || {}).getAttribute?.('data-state') === 'gone',
                                    null, { timeout: 20000 }); ok(); }
      catch { bad(label, 'a bogus code did not reach the gone state'); }

      const msg = (await p.textContent('#wr-state') || '').trim();
      if (!/isn't valid/i.test(msg)) bad(label, `message does not blame the link: "${msg}"`); else ok();
      if (/expired/i.test(msg) && /invalid/i.test(msg)) bad(label, `message still offers two causes at once: "${msg}"`); else ok();
      if (!(await p.$('#wr-requeue'))) bad(label, 'no way out of the gone state'); else ok();
      // The deck picker has to STAY UP here: "find another room" needs a deck, and bouncing them to
      // the menu to re-pick the one they already chose is the same dead end with extra steps.
      const deckVisible = await p.evaluate(() => {
        const d = document.getElementById('wr-deck');
        return !!d && getComputedStyle(d).display !== 'none';
      });
      if (!deckVisible) bad(label, 'the deck picker is hidden, so the requeue button cannot be used'); else ok();
      // ⚠ VISIBLE guidance, not merely assigned. #wr-hint is inside the status box this state hides, so
      // a string written there renders nowhere — which is exactly what the first cut did and what the
      // screenshot caught. Assert what a person can actually read.
      const guidance = await p.evaluate(() => {
        const m = document.getElementById('wr-deck-msg');
        if (!m) return '';
        const r = m.getBoundingClientRect();
        return (r.width > 0 && r.height > 0) ? (m.textContent || '').trim() : '';
      });
      if (!/pick a deck/i.test(guidance)) bad(label, `no visible guidance next to the deck bar: "${guidance}"`); else ok();
      await p.screenshot({ path: new URL(`swusim-join-refusal-badcode-${name}.png`, SHOT).pathname });
      if (errs.length) bad(label, `page errors: ${errs.slice(0, 2).join(' | ')}`); else ok();
      await ctx.close();
    }

    // ── D. THE HOST STARTS while an unseated visitor is waiting. The owner's ruling: they do not get
    // an error, they get the game — as a spectator (2026-09-26). ────────────────────────────────────
    //
    // ⚠ A ROOM OF ITS OWN, BUILT HERE AND CONSUMED HERE. Starting a room ends it, so this cannot reuse
    // the shared one and cannot run before A-C. Three seats is a legal Twin Suns start (3-4).
    {
      const label = `${name}/starts-while-waiting`;
      // This engine's own room, which case B left at 3/4 — a legal Twin Suns start (3-4 seats). No new
      // queueing: that would move these accounts' single seats OUT of the very room we are starting.
      const s = seats;
      {
        const ctx = await b.newContext({ viewport: { width: 1280, height: 900 } });
        const p = await ctx.newPage();
        const errs = []; p.on('pageerror', e => errs.push(e.message));
        await p.goto(LINK(code), { waitUntil: 'domcontentloaded' });
        await p.waitForSelector('#wr-roster .wr-seat', { timeout: 20000 }).catch(() => {});

        await api('APIs/Lobbies/StartRoom.php',
          { rootName: 'SWUSim', lobbyID, playerID: s[0].playerID, authKey: s[0].authKey }, cookies[0]);

        // The page is expected to navigate itself to the board. Waiting on the URL rather than on a
        // selector: the claim is about WHERE it sends them and AS WHOM.
        try {
          await p.waitForURL(/NextTurn\.php/, { timeout: 20000 });
          ok();
        } catch { bad(label, `page did not follow the started game; still at ${p.url()}`); }

        const url = p.url();
        // ⚠ THE WHOLE POINT. playerID=0 is what this used to send — not a seat and not 'S', so
        // NormalizeViewerIdentity returned an empty viewerID and the board came up broken.
        if (/[?&]playerID=S(&|$)/.test(url)) ok();
        else bad(label, `redirected as the wrong viewer: ${url.replace(/^.*NextTurn/, 'NextTurn')}`);
        if (/[?&]playerID=0(&|$)/.test(url)) bad(label, 'redirected as player 0 — the bug this replaces');
        else ok();

        // And it must actually be a board, not an auth refusal page.
        const body = (await p.textContent('body').catch(() => '')) || '';
        if (/not currently authenticated/i.test(body)) bad(label, 'the board refused the spectator');
        else ok();
        await p.screenshot({ path: new URL(`swusim-join-refusal-spectator-${name}.png`, SHOT).pathname });
        if (errs.length) bad(label, `page errors: ${errs.slice(0, 2).join(' | ')}`); else ok();
        await ctx.close();
      }
      // The room is started; its seats belong to a game now and Leave would not release them.
    }

  await b.close();
}

console.log(`\n${checks - fails}/${checks} checks passed`);
console.log(`screenshots: ${SHOT.pathname}swusim-join-refusal-*.png`);
process.exit(fails ? 1 : 0);
