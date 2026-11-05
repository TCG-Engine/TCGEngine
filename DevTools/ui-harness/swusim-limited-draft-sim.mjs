// SWUSim Limited DRAFT simulation — a whole draft driven through the real pages by claudebot accounts (the owner's
// acceptance runs for Plan 3: a 3-player and an 8-player draft). Spec: docs/superpowers/specs/2026-11-04-swusim-limited-
// sealed-draft-design.md §3.2, §3.6; how-to: SWUSim/Tests/Visual/LimitedDraftSim.md.
//
//   1. one browser context per seat, logged in as claudebot1…claudebotN (seat 1 at 390x844, the rest at 1440x900);
//   2. claudebot1 creates a Draft room from the menu (Limited → Draft); every other seat opens the invite and Joins;
//   3. the host sets the packs on the page (--packs; with 3, Chaos SOR + SHD + HMW); Start is POSTed by the harness with
//      the host's room key and testPickSeconds (a real 5-minute clock would make an idle seat cost 5 minutes a pick —
//      only a LOCAL dev request may shorten it; the Start button itself is covered by swusim-limited-room-xbrowser.mjs);
//   4. every seat opens the pick screen and, whenever a pack is in front of it, clicks the first card and confirms. The
//      --idle seat stops after its first card pick of round 1: its packs time out and are auto-picked, and once it is
//      kickable every other seat votes Kick on the pick screen; it is dropped, its pick screen says it was removed, and it keeps auto-picking to the end;
//   5. each live seat, on the builder, takes its first leader and first base, adds cards to 30, Submits and Readies;
//   6. the built pod starts Swiss round 1 by itself (Plan 4); without --swiss the run ends there;
//   7. --swiss: every seat plays the event out. The room sends a seat with a table to its board; there the seat that
//      CONCEDES is match seat 2 in odd rounds and seat 1 in even rounds (window.SubmitInput('10006')), the other waits for
//      the end screen; back in the room between rounds every seat presses Ready and the host presses Start round. The run
//      ends at the final standings.
// It asserts each live seat's pool = N leaders + 14·N cards, the idle seat's auto-pick + kick, the poll / draft-state size
// budgets (60 KB / 120 KB), and no console errors; it prints a line per seat and the time per round.
//
// --swiss also asserts: 3 rounds, no rematch, ⌊N/2⌋ matches a round (+ a bye when N is odd), every live seat played or
// had a bye each round, one standings row per seat, rank 1 = the event's winner.
// --bots N (Plan 5): the host adds N bots on the room page; a human facing a bot always concedes.
// Usage: node swusim-limited-draft-sim.mjs [BASE] --players 1-8 [--bots N] [--packs N] [--idle <seat>] [--pick-seconds S] [--swiss] [--engine chromium]
import { chromium, firefox, webkit } from 'playwright';

const argv = process.argv.slice(2);
const opt = (name, def) => { const i = argv.indexOf('--' + name); return i >= 0 ? argv[i + 1] : def; };
const BASE = argv[0] && !argv[0].startsWith('--') ? argv[0] : 'http://localhost:3400/TCGEngine/';
const N = parseInt(opt('players', '3'), 10);
const BOTS = parseInt(opt('bots', '0'), 10);   // Plan 5: the host adds this many bots on the room page ("Add a bot")
const PACKS = parseInt(opt('packs', '3'), 10);
const IDLE = parseInt(opt('idle', '0'), 10);
const PICK_SECONDS = parseInt(opt('pick-seconds', '20'), 10);
const ENGINE = { chromium, firefox, webkit }[opt('engine', 'chromium')];
const SWISS = argv.includes('--swiss');
const T = 60000;
setTimeout(() => { console.log('WATCHDOG: the simulation took over 60 minutes'); process.exit(2); }, 3600000).unref();

let failures = 0;
const bad = (msg) => { failures++; console.log(`  FAIL ${msg}`); };
const ok = (msg) => console.log(`  ok   ${msg}`);
const check = (cond, msg, detail = '') => cond ? ok(msg) : bad(msg + (detail ? ' — ' + detail : ''));
const t0 = Date.now();
const secs = () => ((Date.now() - t0) / 1000).toFixed(0) + 's';

const browser = await ENGINE.launch();
const seats = [];
const sizes = { poll: 0, state: 0 };
const consoleErrors = [];
const rounds = {};   // "stage:round" -> first time seen
for (let i = 1; i <= N; i++) {
  const ctx = await browser.newContext({ viewport: i === 1 ? { width: 390, height: 844 } : { width: 1440, height: 900 } });
  await ctx.request.post(`${BASE}AccountFiles/AttemptPasswordLogin.php`, { form: { submit: '1', userID: `claudebot${i}`, password: 'pass' } });
  const page = await ctx.newPage();
  // A 409 is the server refusing a pick for a pack that moved on (or a busy room) — the page shows the next pack; not an error.
  page.on('console', (m) => { if (m.type() === 'error' && !/status of 409/.test(m.text()) && !(/Failed to fetch/.test(m.text()) && /swuRenderBookmarksPanel/.test(m.text()))) consoleErrors.push(`claudebot${i}: ${m.text().slice(0, 160)}`); });
  // Leaving a board right after its game ends cancels the board's own bookmarks fetch (swuRenderBookmarksPanel) — the
  // browser reports the cancelled fetch; it is the sim navigating away, not a Limited error.
  page.on('pageerror', (e) => { if (/Failed to fetch/.test(e.message) && /swuRenderBookmarksPanel/.test(String(e.stack))) return; consoleErrors.push(`claudebot${i}: ${e.message.slice(0, 160)}`); });
  page.on('response', async (r) => {
    const u = r.url();
    if (!u.includes('LimitedDraftAction.php') && !u.includes('PollLobbyUpdates.php')) return;
    try {
      const body = await r.text();
      if (u.includes('PollLobbyUpdates')) sizes.poll = Math.max(sizes.poll, body.length);
      else {
        sizes.state = Math.max(sizes.state, body.length);
        const j = JSON.parse(body);
        if (j.stage) { const k = j.stage === 'leaders' ? 'leaders' : 'round ' + j.round; if (!rounds[k]) rounds[k] = secs(); }
      }
    } catch (e) {}
  });
  seats.push({ i, ctx, page, cardPicks: 0, idleNow: false, dropped: false, built: false, pool: 0 });
}

// ── 1–2. create and fill the room ─────────────────────────────────────────────────────────────────────────────────
const host = seats[0];
await host.page.goto(`${BASE}SharedUI/Sites/SWUSim/MainMenu.php`, { waitUntil: 'domcontentloaded', timeout: T });
await host.page.locator('a.mode[href="#setup-limited"]').click();
await host.page.locator('#setup-limited label[for="lt-mode-2"]').click();
await host.page.locator('#setup-limited button[data-act="limited"]').click();
await host.page.waitForURL(/WaitingRoom\.php\?lobby=/, { timeout: T });
await host.page.waitForSelector('#lt-root .lt-panel', { timeout: T });
const lobbyID = new URL(host.page.url()).searchParams.get('lobby');
const invite = ((await host.page.textContent('#wr-invite')) || '').match(/[0-9a-f]{24}/)?.[0] || '';
console.log(`room ${lobbyID} · invite ${invite} · ${N} players · ${PACKS} packs · idle seat ${IDLE || 'none'} · ${PICK_SECONDS}s picks`);
for (const s of seats.slice(1)) {
  await s.page.goto(`${BASE}SharedUI/WaitingRoom.php?invite=${invite}`, { waitUntil: 'domcontentloaded', timeout: T });
  await s.page.locator('#lt-root .lt-join').click({ timeout: T });
  await s.page.waitForSelector('#lt-root .lt-seat--me', { timeout: T });
}
await host.page.waitForFunction((n) => document.querySelectorAll('#lt-root .lt-seat').length === n, N, { timeout: T });
for (let b = 0; b < BOTS; b++) {   // Plan 5: fill the rest with bots, through the page
  await host.page.waitForFunction(() => { const x = document.querySelector('#lt-root .lt-add-bot'); return x && !x.disabled; }, null, { timeout: T });
  await host.page.locator('#lt-root .lt-add-bot').click();
  await host.page.waitForFunction((n) => document.querySelectorAll('#lt-root .lt-seat').length === n, N + b + 1, { timeout: T });
}
ok(`${N + BOTS} seats in the room${BOTS ? ` (${BOTS} bots)` : ''} (${secs()})`);

// ── 3. packs, then start ──────────────────────────────────────────────────────────────────────────────────────────
if (PACKS === 3) {
  await host.page.locator('#lt-root label.seg__opt', { hasText: 'Chaos' }).first().click();
  for (const set of ['SOR', 'SHD', 'HMW']) { await host.page.locator(`#lt-root input[data-lt="chaos"][value="${set}"]`).check(); await host.page.waitForTimeout(400); }
} else {
  await host.page.selectOption('#lt-root select[data-lt="packCount"]', String(PACKS));
}
await host.page.waitForFunction(() => !document.querySelector('#lt-root .lt-err') && !document.querySelector('#lt-root .lt-start').disabled, null, { timeout: T });
const started = await host.page.evaluate(async ([lobbyID, s]) => {
  const k = JSON.parse(localStorage.getItem('tcg:lobbyAuth:' + lobbyID)).authKey;
  const body = new URLSearchParams({ lobbyID, authKey: k, action: 'start', testPickSeconds: String(s) });
  return (await fetch('/TCGEngine/SWUSim/LimitedAction.php', { method: 'POST', body })).json();
}, [lobbyID, PICK_SECONDS]);
check(started.success, 'the draft starts', JSON.stringify(started));

// ── 4–5. every seat drafts, then builds ───────────────────────────────────────────────────────────────────────────
async function runSeat(s) {
  const p = s.page;
  await p.goto(`${BASE}SWUSim/LimitedDraft.php?lobbyID=${lobbyID}`, { waitUntil: 'domcontentloaded', timeout: T });
  for (let guard = 0; guard < 20000; guard++) {
    if (/LimitedBuilder\.php/.test(p.url()) || /WaitingRoom\.php/.test(p.url())) break;
    // Kicked: the pick screen says so and stays put (its picks are made for it to the end).
    if (/You were removed from this draft/.test(await p.textContent('#ldStatus').catch(() => ''))) { s.toldRemoved = true; break; }
    // Kick prompts on the pick screen: vote ONLY on the idle seat. Under load a live seat can time out once too, which
    // makes it kickable by the spec's rule — voting on every prompt would kick the whole pod.
    const kick = p.locator('.ld-kick', { hasText: `Kick claudebot${IDLE}?` }).locator('.ld-kick-yes').first();
    if (IDLE && !s.idleNow && await kick.count()) { await kick.click().catch(() => {}); }
    const card = p.locator('#ldPack .ld-card').first();
    if (!s.idleNow && await card.count()) {
      const title = await p.textContent('#ldTitle').catch(() => '');
      await card.click().catch(() => {});
      await p.locator('#ldPick').click({ timeout: 5000 }).catch(() => {});
      if (/^Pack /.test(title || '')) {
        s.cardPicks++;
        if (s.i === IDLE && s.cardPicks === 1) { s.idleNow = true; console.log(`  · claudebot${s.i} goes idle (${secs()})`); }
      }
      await p.waitForTimeout(250);
    } else {
      await p.waitForTimeout(700);
    }
  }
  if (!s.toldRemoved) await p.waitForURL(/LimitedBuilder\.php|WaitingRoom\.php/, { timeout: 600000 });
  if (/WaitingRoom/.test(p.url())) return;
  if (s.toldRemoved) {   // the pod keeps drafting for this seat: wait for the build, then read its pool
    await p.waitForFunction(async (lobbyID) => {
      const k = JSON.parse(localStorage.getItem('tcg:lobbyAuth:' + lobbyID)).authKey;
      const r = await (await fetch('/TCGEngine/SWUSim/LimitedDraftAction.php', { method: 'POST', body: new URLSearchParams({ lobbyID, authKey: k, action: 'state' }) })).json();
      return r.phase !== 'drafting';
    }, lobbyID, { timeout: 1800000, polling: 3000 });
    const st = await p.evaluate(async (lobbyID) => {
      const k = JSON.parse(localStorage.getItem('tcg:lobbyAuth:' + lobbyID)).authKey;
      return (await fetch('/TCGEngine/SWUSim/LimitedBuilderAction.php', { method: 'POST', body: new URLSearchParams({ lobbyID, authKey: k, action: 'state' }) })).json();
    }, lobbyID);
    s.pool = (st.pool || []).length; s.dropped = true;
    return;
  }
  // the builder: first leader, first base, cards to 30, Submit, Ready
  await p.waitForSelector('#lbPoolGrid .lb-card', { timeout: T });
  const st = await p.evaluate(async (lobbyID) => {
    const k = JSON.parse(localStorage.getItem('tcg:lobbyAuth:' + lobbyID)).authKey;
    return (await fetch('/TCGEngine/SWUSim/LimitedBuilderAction.php', { method: 'POST', body: new URLSearchParams({ lobbyID, authKey: k, action: 'state' }) })).json();
  }, lobbyID);
  s.pool = (st.pool || []).length;
  const mine = (await p.evaluate(async (lobbyID) => {
    const k = JSON.parse(localStorage.getItem('tcg:lobbyAuth:' + lobbyID)).authKey;
    return (await fetch('/TCGEngine/APIs/Lobbies/PollLobbyUpdates.php', { method: 'POST', body: new URLSearchParams({ lobbyID, rootName: 'SWUSim', playerID: '0', authKey: k }) })).json();
  }, lobbyID)).limited || {};
  s.dropped = !!(mine.seats || []).find((x) => mine.me && x.playerID === mine.me.playerID && x.dropped);
  if (s.dropped) return;
  const phone = (await p.viewportSize()).width < 900;
  const showPool = async () => { if (phone) await p.locator('label[for="lb-view-pool"]').click(); };
  await showPool();
  await p.locator('label[for="lb-tab-leaders"]').click(); await p.locator('#lbPoolGrid .lb-card').first().click();
  await p.locator('label[for="lb-tab-bases"]').click(); await p.locator('#lbPoolGrid .lb-card').first().click();
  await p.locator('label[for="lb-tab-cards"]').click();
  for (let n = 0; n < 60 && parseInt(await p.textContent('#lbDeckCount'), 10) < 30; n++) {
    await p.locator('#lbPoolGrid .lb-card:not(.lb-card--used)').first().click();
  }
  await p.locator('#lbSubmit').click();
  await p.waitForFunction(() => !document.getElementById('lbReady').disabled, null, { timeout: T });
  await p.locator('#lbReady').click(); await p.locator('.sd-ok').click();
  await p.waitForFunction(() => document.body.classList.contains('lb-is-ready'), null, { timeout: T });
  s.built = true;
  if (SWISS) await playSwiss(s);
}

// The room's poll as this seat sees it (its own room key).
const pollAs = (p) => p.evaluate(async (lobbyID) => {
  const k = JSON.parse(localStorage.getItem('tcg:lobbyAuth:' + lobbyID)).authKey;
  return ((await (await fetch('/TCGEngine/APIs/Lobbies/PollLobbyUpdates.php', { method: 'POST', body: new URLSearchParams({ lobbyID, rootName: 'SWUSim', playerID: '0', authKey: k }) })).json()).limited || {});
}, lobbyID);
// ── 7. the Swiss rounds ───────────────────────────────────────────────────────────────────────────────────────────
async function playSwiss(s) {
  const p = s.page;
  const room = `${BASE}SharedUI/WaitingRoom.php?lobby=${lobbyID}`;
  // A goto can collide with the page's own hand-off (builder → room → board): retry once it settles.
  const nav = async (url) => {
    for (let n = 0; n < 4; n++) {
      try { await p.goto(url, { waitUntil: 'domcontentloaded', timeout: T }); return; }
      catch (e) { if (!/ERR_ABORTED|interrupted|NS_BINDING_ABORTED|cancelled/i.test(e.message) || n === 3) throw e; await p.waitForTimeout(1000); }
    }
  };
  const onRoom = async () => { if (!/WaitingRoom\.php/.test(p.url())) await nav(room); };
  // The room's own way to a board: its seat cookie (the room key IS the seat key) and NextTurn.php.
  const goBoard = async (live) => {
    await p.evaluate((lobbyID) => {
      const k = JSON.parse(localStorage.getItem('tcg:lobbyAuth:' + lobbyID)).authKey;
      document.cookie = 'tcgengine_SWUSim_lastAuthKey=' + encodeURIComponent(k) + '; max-age=2592000; path=/TCGEngine/; SameSite=Lax';
      document.cookie = 'lastAuthKey=' + encodeURIComponent(k) + '; max-age=2592000; path=/; SameSite=Lax';
    }, lobbyID);
    await nav(`${BASE}NextTurn.php?gameName=${encodeURIComponent(live.gameName)}&playerID=${live.seat}&folderPath=SWUSim`);
  };
  const played = new Set();
  s.games = 0; s.conceded = 0;
  for (let guard = 0; guard < 3000; guard++) {
    const v = await pollAs(p).catch(() => ({}));
    if (v.phase === 'done') { s.finished = true; await onRoom(); return; }
    if (v.phase === 'playing' && v.live && !played.has(v.live.gameName)) {
      played.add(v.live.gameName);
      // The pages hand the seat on by themselves; only a seat left sitting goes to its board directly.
      const atBoard = () => p.url().includes('NextTurn.php') && p.url().includes('gameName=' + v.live.gameName);
      if (!atBoard()) await p.waitForURL((u) => u.href.includes('NextTurn.php'), { timeout: 20000 }).catch(() => {});
      if (!atBoard()) { await p.waitForLoadState('domcontentloaded').catch(() => {}); await goBoard(v.live); }
      // Against a bot the human always concedes: the sim's humans never play, and a bot only moves while a human acts.
      const myTable = (v.swiss.tables || []).find((t) => t.me) || {};
      const oppID = myTable.a === (v.me || {}).playerID ? myTable.b : myTable.a;
      const vsBot = !!(v.seats || []).find((x) => x.playerID === oppID && x.bot);
      const concede = vsBot || (v.swiss.round % 2 === 1 ? v.live.seat === 2 : v.live.seat === 1);
      if (concede) {
        await p.waitForFunction(() => typeof window.SubmitInput === 'function', null, { timeout: T });
        await p.waitForTimeout(1500);
        await p.evaluate(() => window.SubmitInput('10006', ''));
        s.conceded++;
      }
      await p.waitForSelector('#game-over-overlay', { timeout: 180000 });
      s.games++;
      continue;
    }
    await onRoom();
    if (v.phase === 'between') {
      const r = p.locator('#lt-root .lt-ready');
      if (await r.count() && !(await r.isDisabled())) await r.click().catch(() => {});
      const st = p.locator('#lt-root .lt-start-round');
      if (await st.count() && !(await st.isDisabled())) { await st.click().catch(() => {}); console.log(`  · the host starts round ${(v.swiss.round || 0) + 1} (${secs()})`); }
    }
    await p.waitForTimeout(1500);
  }
  bad(`claudebot${s.i}: the event never finished`);
}
await Promise.all(seats.map((s) => runSeat(s).catch((e) => bad(`claudebot${s.i}: ${e.message.split('\n')[0]}`))));

// ── 6. the built pod's round 1 starts by itself (Plan 4) ──────────────────────────────────────────────────────────
let r1 = false;
for (let n = 0; n < 80 && !r1; n++) { const v = await pollAs(host.page).catch(() => ({})); r1 = ['playing', 'between', 'done'].includes(v.phase); if (!r1) await host.page.waitForTimeout(1500); }
check(r1, `everyone built, and Swiss round 1 started by itself (${secs()})`);

const fin = (await host.page.evaluate(async (lobbyID) => {
  const k = JSON.parse(localStorage.getItem('tcg:lobbyAuth:' + lobbyID)).authKey;
  return (await fetch('/TCGEngine/APIs/Lobbies/PollLobbyUpdates.php', { method: 'POST', body: new URLSearchParams({ lobbyID, rootName: 'SWUSim', playerID: '0', authKey: k }) })).json();
}, lobbyID)).limited || {};
console.log(`  · final: phase ${fin.phase}, dropped ${JSON.stringify((fin.seats || []).filter((x) => x.dropped).map((x) => x.name))}, result ${JSON.stringify(fin.result)}`);
const want = PACKS + 14 * PACKS;
for (const s of seats) {
  const line = `claudebot${s.i}: ${s.pool ? s.pool + ' cards' : '—'}${s.dropped ? ', dropped (auto-picked to the end)' : ''}${s.built ? ', built' : ''}`;
  console.log('  · ' + line);
  if (!s.dropped) check(s.pool === want && s.built, `claudebot${s.i} drafted ${PACKS} leaders + ${14 * PACKS} cards and built`, line);
}
if (IDLE) {
  const idle = seats.find((s) => s.i === IDLE);
  check(idle && idle.dropped, `the idle seat claudebot${IDLE} was auto-picked, kicked by the vote, and dropped`);
  check(idle && idle.toldRemoved, `claudebot${IDLE}'s own pick screen says "You were removed from this draft" (and never opens the builder)`);
}
if (SWISS) {
  const sw = fin.swiss || {};
  const blob = await (await host.ctx.request.get(`${BASE}SWUSim/DevTools/tests/limited_swiss_peek.php?lobbyID=${lobbyID}`)).json().catch(() => null);
  check(fin.phase === 'done' && sw.round === 3, 'the event ran three rounds and ended', JSON.stringify({ phase: fin.phase, round: sw.round }));
  const liveN = (fin.seats || []).filter((x) => !x.dropped).length;
  const pr = (blob && blob.pairings) || {};
  const met = {}; let shapeOk = true, coverOk = true;
  for (const [rn, tables] of Object.entries(pr)) {
    const games = tables.filter((t) => !t.bye), byes = tables.filter((t) => t.bye);
    if (games.length !== Math.floor(liveN / 2) || byes.length !== liveN % 2) shapeOk = false;
    const seen = new Set(tables.flatMap((t) => [t.a, t.b]).filter((x) => x !== null));
    if (seen.size !== liveN) coverOk = false;
    for (const t of games) { const k = [t.a, t.b].sort().join('-'); met[k] = (met[k] || 0) + 1; }
    console.log(`  · round ${rn}: ` + tables.map((t) => t.bye ? `${t.aName} bye` : `${t.aName} ${t.games[0]}–${t.games[1]} ${t.bName}`).join(' · '));
  }
  check(Object.keys(pr).length === 3 && Object.values(met).every((n) => n === 1), 'three rounds recorded, nobody met twice', JSON.stringify(met));
  check(shapeOk, `each round had ⌊${liveN}/2⌋ matches${liveN % 2 ? ' and one bye' : ''}`);
  check(coverOk, 'every live seat played or had a bye in every round');
  const st = sw.standings || [];
  check(st.length === N + BOTS, `the final standings list every seat (${st.length} of ${N + BOTS})`);
  check(st.length && fin.result && st[0].playerID === fin.result.winner, `rank 1 (${st[0] && st[0].name}) is the event's winner`);
  console.log('  · final standings: ' + st.slice(0, 3).map((r) => `${r.rank}. ${r.name} ${r.points} pts`).join(' · '));
  for (const s of seats.filter((x) => !x.dropped)) check(s.finished, `claudebot${s.i} played ${s.games} game(s) (conceded ${s.conceded}) and saw the end`);
}
console.log('  · first seen: ' + Object.entries(rounds).map(([k, v]) => `${k} @ ${v}`).join(' · '));
check(sizes.poll < 60000 && sizes.state < 120000, `payloads in budget: poll ${sizes.poll} B (< 60 KB), draft state ${sizes.state} B (< 120 KB)`);
check(consoleErrors.length === 0, 'no console errors', consoleErrors.slice(0, 5).join(' | '));
console.log(`total ${secs()}`);
await browser.close();
console.log(failures === 0 ? 'ALL PASS' : `${failures} FAILED`);
process.exit(failures === 0 ? 0 : 1);
