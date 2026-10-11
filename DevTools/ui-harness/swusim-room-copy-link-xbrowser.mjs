// "Copy Link" on a PUBLIC Twin Suns waiting room, in all three engines.
//
// Owner feature request, 2026-09-26: "people are erroneously copying the URL and pasting it in a chat
// to find players. a strict copy link would be nice to have players be able drop into a forming public
// queue room or invite their friends."
//
// Public waiting rooms exist ONLY for the Twin Suns family (SWUSim/LobbyAdapter.php wantsWaitingRoom:
// isPrivate || maxPlayers > 2), and until now only PRIVATE rooms were issued an inviteCode — so the
// Copy button never rendered on a public one and both invite paths in JoinQueue.php refused it.
//
// ⚠ WHY A BROWSER GATE. The HTTP test
// (DevTools/tdd-regression/test_swusim_public_room_invite_link.php) proves the code is minted and that
// joining by it lands in the right room. It cannot see what the PAGE does with it: whether the button
// renders, what it says, whether the code leaks onto a public room, or what string actually reaches
// the clipboard. That last one is the whole feature.
//
// ⚠ THE CLIPBOARD IS STUBBED, ON PURPOSE. Reading it back needs permissions chromium grants, firefox
// does not, and webkit only under user activation — three different negotiations for one string. The
// harness replaces navigator.clipboard.writeText before the page loads and captures what the page
// passes it, which IS the thing under test. The real writeText path stays exercised by the click.
//
//   node DevTools/ui-harness/swusim-room-copy-link-xbrowser.mjs
//
// It creates (and leaves) its own rooms; nothing to set up.
import { chromium, firefox, webkit } from 'playwright';
import fs from 'node:fs';

const BASE = process.env.BASE_URL || 'http://localhost:3400/TCGEngine/';
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
  const text = await r.text();
  try { return JSON.parse(text); } catch { return { RAW: text.slice(0, 200) }; }
}
async function login(user) {
  const r = await fetch(BASE + 'AccountFiles/AttemptPasswordLogin.php', {
    method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: form({ submit: '1', userID: user, password: 'pass' }), redirect: 'manual',
  });
  return (r.headers.getSetCookie?.() || []).map(c => c.split(';')[0]).join('; ');
}

// ⚠ ONE ACCOUNT PER ROOM. A player holds ONE lobby at a time, so creating both from claudebot1
// abandoned the public room the moment the private one was created — and the page then rendered the
// "gone" state with no button, which reads exactly like the feature being broken. It cost a debugging
// round; keep the two accounts.
const cookies = { public: await login('claudebot1'), private: await login('claudebot2') };
for (const [kind, c] of Object.entries(cookies)) {
  if (!c) { console.log(`FAIL :: no session cookie for the ${kind} room's account`); process.exit(1); }
}

// Two rooms, same format, differing ONLY in public/private — the one axis this feature turns on.
const rooms = {
  public:  await api('APIs/Lobbies/JoinQueue.php', { rootName: 'SWUSim', format: 'teamsuns', queueType: 'bo1', deckLink: DECK }, cookies.public),
  private: await api('APIs/Lobbies/JoinQueue.php', { rootName: 'SWUSim', createPrivate: '1', format: 'teamsuns', deckLink: DECK }, cookies.private),
};
for (const [kind, r] of Object.entries(rooms)) {
  if (!r.success || !r.lobbyID) { console.log(`FAIL :: could not create the ${kind} room :: ${JSON.stringify(r).slice(0, 200)}`); process.exit(1); }
  if (!r.inviteCode) { console.log(`FAIL :: the ${kind} room was issued no inviteCode — the backend half is not in place`); process.exit(1); }
}
console.log(`public ${rooms.public.lobbyID} · private ${rooms.private.lobbyID}`);

try {
  for (const [name, launcher] of [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]]) {
    let b;
    try { b = await launcher.launch(); }
    catch (e) { bad(name, `could not launch: ${e.message}`); continue; }

    for (const kind of ['public', 'private']) {
      const room = rooms[kind];
      const label = `${name}/${kind}`;
      const ctx = await b.newContext({ viewport: { width: 1280, height: 900 } });
      await ctx.addCookies(cookies[kind].split('; ').map(c => {
        const [n, ...v] = c.split('=');
        return { name: n, value: v.join('='), domain: 'localhost', path: '/' };
      }));
      // Seat this browser, and capture whatever the page hands the clipboard.
      await ctx.addInitScript(([lobbyID, authKey]) => {
        try { localStorage.setItem('tcg:lobbyAuth:' + lobbyID, JSON.stringify({ authKey, ts: Date.now() })); } catch (e) {}
        window.__copied = null;
        // navigator.clipboard is not always configurable; never let this abort the init script (the
        // localStorage seat above would survive, but silently losing the stub is worse than knowing).
        try {
          Object.defineProperty(navigator, 'clipboard', {
            configurable: true,
            value: { writeText: (t) => { window.__copied = t; return Promise.resolve(); } },
          });
        } catch (e) { window.__clipboardStubFailed = String(e && e.message); }
      }, [room.lobbyID, room.authKey]);

      const p = await ctx.newPage();
      const errs = []; p.on('pageerror', e => errs.push(e.message));
      await p.goto(`${BASE}SharedUI/Sites/SWUSim/WaitingRoom.php?lobby=${encodeURIComponent(room.lobbyID)}`,
                   { waitUntil: 'domcontentloaded' });

      // ── 1. THE BUTTON EXISTS AT ALL. On a public room it never used to. ──────────────────────
      try {
        await p.waitForSelector('#wr-copy', { timeout: 20000 });
        ok();
      } catch {
        bad(label, 'no copy button rendered on this room');
        await ctx.close();
        continue;
      }

      // ── 2. ITS WORDING ────────────────────────────────────────────────────────────────────────
      const text = (await p.textContent('#wr-copy') || '').trim();
      const want = kind === 'public' ? 'Copy Link' : 'Copy Invite Link';
      if (text !== want) bad(label, `button reads "${text}", expected "${want}"`); else ok();

      // ── 3. THE CODE IS PRINTED ONLY FOR A PRIVATE ROOM ───────────────────────────────────────
      // A public room's code is an address, not a password; printing it is noise. A private room's
      // code IS the secret and people read it out, so it must stay visible.
      const box = (await p.textContent('#wr-invite') || '');
      const shows = box.includes(room.inviteCode);
      if (kind === 'public' && shows) bad(label, 'a PUBLIC room printed its code next to the button');
      else if (kind === 'private' && !shows) bad(label, 'a PRIVATE room stopped showing its code — that is the secret people read out');
      else ok();

      // ── 4. WHAT ACTUALLY REACHES THE CLIPBOARD — the feature itself ──────────────────────────
      await p.click('#wr-copy');
      await p.waitForFunction(() => window.__copied !== null, null, { timeout: 5000 }).catch(() => {});
      const copied = await p.evaluate(() => window.__copied);
      if (!copied) {
        bad(label, 'clicking the button copied nothing');
      } else if (!copied.includes(`?invite=${room.inviteCode}`)) {
        bad(label, `copied "${copied}" — expected it to carry ?invite=${room.inviteCode}`);
      } else if (!copied.includes('WaitingRoom.php')) {
        bad(label, `copied "${copied}" — it must point at the waiting room, not the menu`);
      } else ok();

      // ⚠ THE BARE ?lobby= URL IS *NOT* WHAT IS COPIED. That is the bug behind the request: a
      // hand-copied address-bar URL still routes a joiner into general matchmaking rather than this
      // room (owner chose not to fix that path). The button must never hand out that shape.
      if (copied && /[?&]lobby=/.test(copied)) {
        bad(label, `copied a ?lobby= URL (${copied}) — that shape misroutes joiners to the queue`);
      } else ok();

      // ── 5. AND THE BUTTON CONFIRMS ITSELF ────────────────────────────────────────────────────
      const after = (await p.textContent('#wr-copy') || '').trim();
      if (after !== 'Copied!') bad(label, `after clicking, the button reads "${after}", expected "Copied!"`); else ok();

      if (errs.length) bad(label, `page errors: ${errs.slice(0, 2).join(' | ')}`); else ok();
      await ctx.close();
    }
    await b.close();
  }
} finally {
  for (const [kind, r] of Object.entries(rooms)) {
    await api('APIs/Lobbies/LeaveQueue.php',
      { rootName: 'SWUSim', playerID: r.playerID || 1, lobbyID: r.lobbyID, authKey: r.authKey || '' }, cookies[kind]);
  }
}

console.log(fails === 0 ? `PASS (${checks} checks, 3 engines)` : `${fails} FAILED of ${checks}`);
process.exit(fails === 0 ? 0 : 1);
