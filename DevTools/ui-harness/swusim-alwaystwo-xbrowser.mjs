// 1v1 Always Two — the two-board table (docs/superpowers/specs/2026-11-20-swusim-always-two-design.md, layout A as built:
// "two real boards side by side, each with its own opponent row" — owner, 2026-11-20).
// A seat of a 1v1 Always Two game opening NextTurn.php gets a TABLE: two real board views, its first board (playerID = seat) and its
// second board (playerID = seat + 2), each the phone-style layout for a tall column. Desktop: side by side. Phone: stacked.
// Each half hides the teammate's panel (it is the other half); round / undo / log / chat show once, in the first half.
// Control: a game that is not 1v1 Always Two opens the ordinary board.
// Checked in Chromium, Firefox and WebKit, desktop and phone (CLAUDE.md: UI changes in all three engines).
//   node DevTools/ui-harness/swusim-alwaystwo-xbrowser.mjs          (seeds its own boards; claudebot1 must be a mod)
import fs from 'fs';
import { chromium, firefox, webkit } from 'playwright';

const BASE = process.env.BASE_URL || 'http://localhost:3400/TCGEngine/';
const SHOTS = process.env.SHOTS || '';
const form = (o) => new URLSearchParams(o).toString();
const post = async (path, body, cookie) => fetch(BASE + path, { method: 'POST',
  headers: { 'Content-Type': 'application/x-www-form-urlencoded', ...(cookie ? { cookie } : {}) }, body: form(body), redirect: 'manual' });
const login = await post('AccountFiles/AttemptPasswordLogin.php', { submit: '1', userID: 'claudebot1', password: 'pass' });
const cookie = (login.headers.getSetCookie?.() || []).map(c => c.split(';')[0]).join('; ');
async function seed(schema) {
  const json = JSON.parse(await (await post('SWUSim/TestSchemaSetup.php', { schema }, cookie)).text());
  if (json.error) throw new Error(`TestSchemaSetup refused the schema: ${json.error}`);
  return String(json.gameName);
}
const A2 = await seed(fs.readFileSync(new URL('../../SWUSim/Tests/Visual/AlwaysTwo_1v1Board.md', import.meta.url), 'utf8'));
const TEAMSUNS = await seed(`# Control\n## GIVEN\nCommonSetup4P: bbk/bbk/bbk/bbk\nSkipPreGame: true\nWithTeams: true\nWithActivePlayer: 1\n## WHEN\n## EXPECT\nSEATCOUNT:4\n`);
console.log(`seeded always-two ${A2}, team suns ${TEAMSUNS}`);

// ENGINES=chromium (comma list) narrows a quick iteration; the default — all three — is the gate.
const ENGINES = (process.env.ENGINES || 'chromium,firefox,webkit').split(',');
let fails = 0, checks = 0;
const check = (ok, name, msg) => { checks++; if (!ok) { fails++; console.log(`FAIL ${name} :: ${msg}`); } };
const SURFACES = [
  { label: 'desktop', viewport: { width: 1600, height: 1000 } },
  { label: 'phone',   viewport: { width: 390,  height: 844  } },
];
const url = (g, seat) => `${BASE}NextTurn.php?folderPath=SWUSim&gameName=${g}&playerID=${seat}&authKey=testschema`;

// Inside one half: the card size and the top of each of your own rows (leader/base, arenas, hand, controls).
const ROW_KEYS = ['band', 'tarena', 'centers', 'arena', 'hand', 'foot'];
const rowsOf = (f) => f.evaluate(() => {
  const y = (sel) => { const e = document.querySelector(sel); return e ? Math.round(e.getBoundingClientRect().top) : -1; };
  return { cs: getComputedStyle(document.documentElement).getPropertyValue('--swu-cardsize').trim(),
           band: y('#swuMobileRoot .swu-m-band.is-theirs'), tarena: y('#swuMobileRoot .swu-m-arena-row.is-theirs'),
           centers: y('#swuMobileRoot .swu-m-centers-row'), arena: y('#swuMobileRoot .swu-m-arena-row.is-mine'),
           hand: y('#myHandSlot'), foot: y('#swuMobileRoot .swu-m-footer-stack') };
});
// Inside one half: where the four leader/base cells sit, and whether everything fits the window.
const gridOf = (f) => f.evaluate(() => {
  const r = (id) => { const e = document.getElementById(id); const c = e && e.closest('.swu-m-center'); const q = (c || e) ? (c || e).getBoundingClientRect() : null;
                      return q ? { x: Math.round(q.left + q.width / 2), top: Math.round(q.top), bottom: Math.round(q.bottom) } : null; };
  const q = (sel) => { const e = document.querySelector(sel); return e ? e.getBoundingClientRect() : null; };
  const band = q('#swuMobileRoot .swu-m-band.is-theirs'), ta = q('#swuMobileRoot .swu-m-arena-row.is-theirs'), foot = q('#swuMobileRoot .swu-m-footer-stack');
  return { tl: r('theirLeaderSlot'), tb: r('theirBaseSlot'), ml: r('myLeaderSlot'), mb: r('myBaseSlot'),
           bandTop: band ? Math.round(band.top) : -1, tArenaTop: ta ? Math.round(ta.top) : -1, footBottom: foot ? Math.round(foot.bottom) : 99999, vh: innerHeight };
});
const sameRows = (a, b) => ROW_KEYS.every(k => Math.abs(a[k] - b[k]) <= 1);

// Inside one half: which seat rows show, is the sidebar there, how many hand cards, which opponent it faces.
async function inspect(frame) {
  await frame.waitForFunction(() => document.querySelectorAll('#myHand > span').length > 0, null, { timeout: 90000 }).catch(() => {});
  return frame.evaluate(() => {
    const vis = (el) => !!el && getComputedStyle(el).display !== 'none' && getComputedStyle(el).visibility !== 'hidden' && el.getBoundingClientRect().height > 0;
    const rows = [...document.querySelectorAll('#swuHomeStrips .swu-seat-row')].filter(vis).map(r => Number(r.dataset.seat));
    const nav = ['swuPairPrev', 'swuPairNext', 'swuGoBack'].filter(id => vis(document.getElementById(id)));
    return { rows, nav, sidebar: vis(document.getElementById('swuSidebar')), hand: document.querySelectorAll('#myHand > span').length,
             face: (window.swuView && window.swuView.mode === 'matchup') ? Number(window.swuView.oppSeat) : 0,
             hScroll: document.documentElement.scrollWidth > document.documentElement.clientWidth + 1 };
  });
}
// The 2×2 table (spec "The 2×2 table", owner 2026-11-20): the seat's first board bottom-LEFT, its second bottom-RIGHT, each facing the
// opponent above it — seat 1: board 1 → P4, board 3 → P2; seat 2: board 2 → P3, board 4 → P1. The RIGHT half carries log / chat / undo.
const FACE = { 1: 4, 3: 2, 2: 3, 4: 1 };
async function tableFrames(p, seat) {
  const all = await p.$$('iframe.a2-board');
  const bySrc = async (n) => { for (const f of all) if (new RegExp(`[?&]playerID=${n}(&|$)`).test(await f.getAttribute('src'))) return f; return null; };
  const L = await bySrc(seat), R = await bySrc(seat + 2);
  return { all, L, R };
}
async function checkTable(p, s, name, seat, hands) {
  const { all, L, R } = await tableFrames(p, seat);
  check(all.length === 2 && L && R, name, `seat ${seat} gets a two-board table of board ${seat} and board ${seat + 2} (iframes: ${all.length})`);
  if (!(L && R)) return null;
  const [bl, br] = await Promise.all([L.boundingBox(), R.boundingBox()]);
  if (s.label === 'desktop') {
    check(Math.abs(bl.y - br.y) < 2 && bl.x < br.x && br.x + br.width >= s.viewport.width - 2, name,
      `desktop: board ${seat} LEFT | board ${seat + 2} RIGHT (+ sidebar): ${JSON.stringify([bl, br])}`);
  } else {
    check(br.y >= bl.y + bl.height - 1 && bl.width >= s.viewport.width - 2, name, `phone: board ${seat}'s column above board ${seat + 2}'s: ${JSON.stringify([bl, br])}`);
  }
  const fl = await L.contentFrame(), fr = await R.contentFrame();
  const il = await inspect(fl), ir = await inspect(fr);
  check(il.face === FACE[seat] && ir.face === FACE[seat + 2], name,
    `each half faces the opponent above it: board ${seat} → P${FACE[seat]}, board ${seat + 2} → P${FACE[seat + 2]} (got ${il.face}, ${ir.face})`);
  check(il.rows.length === 0 && ir.rows.length === 0 && il.nav.length === 0 && ir.nav.length === 0, name,
    `no opponent summary rows, no matchup arrows / Go back: ${JSON.stringify({ l: [il.rows, il.nav], r: [ir.rows, ir.nav] })}`);
  check(ir.sidebar && !il.sidebar, name, `round/undo/log/chat once, in the RIGHT half (left ${il.sidebar}, right ${ir.sidebar})`);
  check(il.hand === hands[0] && ir.hand === hands[1], name, `each half holds its own hand face-up (${il.hand}/${hands[0]}, ${ir.hand}/${hands[1]})`);
  check(!il.hScroll && !ir.hScroll, name, 'no horizontal scroll inside either half');
  return { L, R, fl, fr };
}

for (const [engine, launcher] of [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]].filter(([e]) => ENGINES.includes(e))) {
  let b;
  try { b = await launcher.launch(); } catch (e) { check(false, engine, `could not launch: ${e.message}`); continue; }
  for (const s of SURFACES) {
    const name = `${engine}/${s.label}`;
    const p = await b.newPage({ viewport: s.viewport });
    // Logged in as claudebot1: chat (the composer in the sidebar) is for account holders only.
    await p.context().addCookies(cookie.split('; ').filter(Boolean).map(kv => {
      const i = kv.indexOf('='); return { name: kv.slice(0, i), value: kv.slice(i + 1), url: BASE };
    }));
    await p.goto(url(A2, 1), { waitUntil: 'domcontentloaded' });
    const t = await checkTable(p, s, name, 1, [5, 3]);
    const frames = t ? [t.L, t.R] : [];          // [board 1 (left), board 3 (right)]
    if (t && s.label === 'desktop') {
      const f1 = t.fl, f3 = t.fr;
      // Owner, 2026-11-20: "the board should not shift … static like a real tabletop". The two halves are one table: same card
      // size, and every row (leader/base, arenas, hand, controls) at the same height on both sides.
      const [r1, r3] = [await rowsOf(f1), await rowsOf(f3)];
      check(r1.cs === r3.cs && sameRows(r1, r3), name, `both halves line up like one table: ${JSON.stringify({ b1: r1, b3: r3 })}`);
      const sb = await f3.evaluate(() => { const e = document.getElementById('swuSidebar'); const r = e.getBoundingClientRect(); return { right: r.right, w: r.width, top: r.top, h: r.height, vw: window.innerWidth, vh: window.innerHeight }; });
      check(Math.abs(sb.right - sb.vw) < 2 && sb.top < 2 && sb.h > sb.vh * 0.9 && sb.w >= 200, name, `log / chat / undo is a full-height column on the right: ${JSON.stringify(sb)}`);
      // Owner, 2026-11-20: the "Message | Send" row pinned to the BOTTOM of that column, as in regular games — the log fills the space
      // above it and scrolls on its own.
      const chat = await f3.evaluate(() => {
        const r = (id) => { const e = document.getElementById(id); return e ? e.getBoundingClientRect() : null; };
        const t = r('chatText'), sb = r('swuSidebar'), log = r('swuLogPanel');
        return { inputBottom: t ? Math.round(t.bottom) : -1, sbBottom: sb ? Math.round(sb.bottom) : -1, logH: log ? Math.round(log.height) : -1,
                 logBottom: log ? Math.round(log.bottom) : -1, inputTop: t ? Math.round(t.top) : -1, vh: innerHeight };
      });
      check(chat.inputBottom > 0 && chat.sbBottom - chat.inputBottom <= 16 && chat.logBottom <= chat.inputTop && chat.logH > chat.vh * 0.4, name,
        `Message | Send sits at the bottom of the sidebar, the log fills above it: ${JSON.stringify(chat)}`);
    }
    // Chat from the RIGHT half (board 3) is still the seat's person: the new line carries the account name, not a board label.
    if (t && s.label === 'desktop') {
      const msg = `harness hello ${engine} ${Date.now() % 100000}`;
      try { await t.fr.fill('#chatText', msg, { timeout: 5000 }); await t.fr.click('#chatWidget button:not(#chatToggleBtn)', { timeout: 5000 }); } catch (e) {}
      await t.fr.waitForFunction((m) => [...document.querySelectorAll('#swuLogPanel .swu-log-CHAT')].some(e => e.textContent.includes(m)), msg, { timeout: 15000 }).catch(() => {});
      const line = await t.fr.evaluate((m) => ([...document.querySelectorAll('#swuLogPanel .swu-log-CHAT')].map(e => e.textContent.trim()).reverse().find(x => x.includes(m)) || ''), msg);
      check(line.includes(msg) && /claudebot1/i.test(line), name, `chat sent from board 3's half shows the seat's account name: "${line.slice(0, 80)}"`);
    }
    const pageHScroll = await p.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth + 1);
    check(!pageHScroll, name, 'no horizontal page scroll');
    // A freshly seeded test board flashes its default "-" message for ~5s (every board does — not the table); wait it out for the picture.
    if (SHOTS) { await p.waitForTimeout(7000); await p.screenshot({ path: `${SHOTS}/a2-${engine}-${s.label}.png`, fullPage: s.label === 'phone' }); }
    // Turn + prompt awareness (final review 2026-11-20): the TAB shows your team's turn (favicon dot on the table page), and a half that
    // owes a decision is flagged — on a phone, where one half is off-screen, a chip points to it.
    if (frames.length === 2) {
      await p.waitForTimeout(2500);
      const fav = await p.evaluate(() => { const l = document.querySelector('link[rel="icon"]'); return l ? l.getAttribute('href') : ''; });
      check(/^data:image\/png/.test(fav), name, `the tab's favicon carries the turn dot on your team's turn (href ${fav.slice(0, 40)})`);
      const f3 = await frames[1].contentFrame();
      await f3.evaluate(() => { window.myDecisionQueueData = '- 0 {"Type":"YESNO","Param":"harness","removed":false}'; });
      await p.waitForTimeout(2500);
      const flagged = await p.evaluate(() => document.getElementById('a2Board3').classList.contains('a2-owes') && !document.getElementById('a2Board1').classList.contains('a2-owes'));
      check(flagged, name, 'the half that owes a decision (board 3) is flagged, the other is not');
      if (s.label === 'phone') {
        // Board 3 is the BOTTOM column on a phone: at the top of the page it is off-screen below.
        await p.evaluate(() => window.scrollTo(0, 0)); await p.waitForTimeout(1500);
        const chip = await p.evaluate(() => { const c = document.getElementById('a2Nudge'); return c && getComputedStyle(c).display !== 'none' ? c.textContent.trim() : ''; });
        check(/Board 3.*↓/.test(chip), name, `with board 3 off-screen below, a chip points down to it ("${chip}")`);
      }
      await f3.evaluate(() => { window.myDecisionQueueData = ''; });
    }
    // The 2×2 rows (spec "The 2×2 table"): bases on the INSIDE corners — left column both bases right of their leaders, right column
    // both left — the opponent's pair directly above yours, the opponent's bar on top, both columns one table, nothing below the window.
    if (frames.length === 2 && s.label === 'desktop') {
      const f1 = await frames[0].contentFrame(), f3 = await frames[1].contentFrame();
      for (const [w, h] of [[1600, 1000], [1920, 1080], [1366, 768]]) {
        await p.setViewportSize({ width: w, height: h }); await p.waitForTimeout(2000);
        const [L, R] = [await gridOf(f1), await gridOf(f3)];
        const ok = (g) => g.tl && g.tb && g.ml && g.mb;
        check(ok(L) && L.tb.x > L.tl.x && L.mb.x > L.ml.x, name, `${w}x${h} left column: both bases on the right (inside corner): ${JSON.stringify(L)}`);
        check(ok(R) && R.tb.x < R.tl.x && R.mb.x < R.ml.x, name, `${w}x${h} right column: both bases on the left (inside corner): ${JSON.stringify(R)}`);
        check(ok(L) && L.tb.bottom <= L.mb.top + 1 && L.bandTop >= 0 && L.bandTop < L.tArenaTop, name,
          `${w}x${h} the opponent's pair sits directly above yours, their bar on top: ${JSON.stringify(L)}`);
        const [rl, rr] = [await rowsOf(f1), await rowsOf(f3)];
        check(sameRows(rl, rr) && rl.cs === rr.cs && L.footBottom <= L.vh + 1 && R.footBottom <= R.vh + 1, name,
          `${w}x${h} one table: rows line up across the columns and everything is in the window: ${JSON.stringify({ rl, rr, Lb: L.footBottom, Rb: R.footBottom, vh: L.vh })}`);
      }
      await p.setViewportSize(s.viewport); await p.waitForTimeout(2000);
    }
    if (frames.length === 2 && s.label === 'phone') {
      // Phone: a 390px column keeps the single leader/base row (yours · theirs, bases meeting mid).
      for (const f of [await frames[0].contentFrame(), await frames[1].contentFrame()]) {
        const g = await gridOf(f);
        const tops = [g.tl, g.tb, g.ml, g.mb].map(c => c ? c.top : -999);
        check(Math.max(...tops) - Math.min(...tops) <= 2, name, `phone: one leader/base row per column: ${JSON.stringify(tops)}`);
      }
    }
    // Owner, 2026-11-20: "the board should not shift when things change. it should stay static like a real tabletop game".
    if (frames.length === 2 && s.label === 'desktop') {
      const f1 = await frames[0].contentFrame(), f3 = await frames[1].contentFrame();
      // A 1920x1080 screen: board 3's frame also holds the sidebar, so it is wider than board 1's — the cards must still be the same size
      // on both halves (the board width decides, not the frame width), and the rows still line up.
      await p.setViewportSize({ width: 1920, height: 1080 }); await p.waitForTimeout(2000);
      const [w1, w3] = [await rowsOf(f1), await rowsOf(f3)];
      check(w1.cs === w3.cs && sameRows(w1, w3), name, `1920x1080: same card size and rows on both halves: ${JSON.stringify({ b1: w1, b3: w3 })}`);
      await p.setViewportSize(s.viewport); await p.waitForTimeout(2000);
      // Things that change during a game must not move any row: the hand growing past the width (scrollbar), Take Initiative showing
      // in the controls, the leader/base art not drawn yet (or redrawn).
      for (const f of [f1, f3]) {
        const before = await rowsOf(f);
        await f.evaluate(() => {
          const h = document.getElementById('myHand'), c = h && h.querySelector(':scope > span');
          for (let i = 0; c && i < 12; i++) h.appendChild(c.cloneNode(true));
          const t = document.getElementById('swuMobileTakeInit'); if (t) t.hidden = false;
          document.querySelectorAll('#swuMobileRoot .swu-m-centers-row img').forEach(i => { i.style.display = 'none'; });
          const g = document.getElementById('theirGroundArena'), u = g && g.querySelector(':scope > span');
          for (let i = 0; u && i < 8; i++) g.appendChild(u.cloneNode(true));   // a go-wide opponent: their arena overflows
        });
        await p.waitForTimeout(400);
        const after = await rowsOf(f);
        check(sameRows(before, after), name, `rows stay put when the hand, the controls or the art change: ${JSON.stringify({ before, after })}`);
      }
    }
    // Leaving the game from either half (Main Menu, concede-and-leave, sideboard …) takes the WHOLE table there, not one frame.
    if (frames.length === 2) {
      const f3 = await frames[1].contentFrame();
      await f3.evaluate(() => { window.location.href = '../SharedUI/MainMenu.php'; });
      await p.waitForURL(/MainMenu\.php/, { timeout: 30000 }).catch(() => {});
      check(/MainMenu\.php/.test(p.url()) && (await p.$$('iframe.a2-board')).length === 0, name, `leaving from a half navigates the whole page (now ${p.url()})`);
    }
    await p.close();

    // Seat 2 is the mirror, not seat 1 relabelled: board 2 bottom-LEFT facing P3, board 4 bottom-RIGHT facing P1, log in board 4's half.
    if (s.label === 'desktop') {
      const p2 = await b.newPage({ viewport: s.viewport });
      await p2.goto(url(A2, 2), { waitUntil: 'domcontentloaded' });
      await checkTable(p2, s, `${name}/seat2`, 2, [5, 4]);
      await p2.close();
    }
    // Control: a SPECTATOR of the Always Two game (playerID=S) never gets the table — only the two seats do.
    {
      const sp = await b.newPage({ viewport: s.viewport });
      await sp.goto(`${BASE}NextTurn.php?folderPath=SWUSim&gameName=${A2}&playerID=S`, { waitUntil: 'domcontentloaded' });
      await sp.waitForTimeout(3000);
      check((await sp.$$('iframe.a2-board')).length === 0, name, 'control: a spectator of the Always Two game does not get the table');
      await sp.close();
    }
    // Control: Team Suns opens the ordinary board.
    const c = await b.newPage({ viewport: s.viewport });
    await c.goto(url(TEAMSUNS, 1), { waitUntil: 'domcontentloaded' });
    await c.waitForSelector('#swuHomeStrips', { timeout: 90000 }).catch(() => {});
    check((await c.$$('iframe.a2-board')).length === 0 && !!(await c.$('#swuHomeStrips')), name, 'control: a Team Suns game opens the ordinary board');
    await c.close();
  }
  await b.close();
}
// ── The menu: Fun Formats → 1v1 Always Two → the PvP setup in its 'alwaystwo' mode (plan Task 13). ──────────────────────────
const MENU = BASE + 'SharedUI/Sites/SWUSim/MainMenu.php';
const shown = (page, sel) => page.evaluate((s) => { const el = document.querySelector(s);
  return !!el && !el.hidden && getComputedStyle(el).display !== 'none' && el.getBoundingClientRect().width > 0; }, sel);
for (const [engine, launcher] of [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]].filter(([e]) => ENGINES.includes(e))) {
  let b;
  try { b = await launcher.launch(); } catch (e) { check(false, engine, `could not launch: ${e.message}`); continue; }
  for (const s of SURFACES) {
    const name = `${engine}/${s.label}/menu`;
    const page = await (await b.newContext({ viewport: s.viewport })).newPage();
    await page.goto(BASE + 'SharedUI/LoginPage.php', { waitUntil: 'domcontentloaded' });
    await page.fill('input[name="userID"]', 'claudebot1'); await page.fill('input[name="password"]', 'pass');
    await Promise.all([page.waitForNavigation({ waitUntil: 'load' }).catch(() => {}), page.click('button[type="submit"]')]);
    await page.goto(MENU, { waitUntil: 'load' });
    await page.locator('a.mode[href="#setup-alternate"]').scrollIntoViewIfNeeded();
    await page.locator('a.mode[href="#setup-alternate"]').click(); await page.waitForTimeout(400);
    const offered = await page.locator('#setup-alternate label[for="alt-fmt-3"]').count();
    check(offered === 1 && (await page.locator('#setup-alternate label[for="alt-fmt-3"]').textContent()).trim() === '1v1 Always Two', name, 'Fun Formats offers "1v1 Always Two"');
    if (offered) {
      await page.locator('#setup-alternate label[for="alt-fmt-3"]').click(); await page.waitForTimeout(200);
      check(await shown(page, '#setup-alternate [data-alt-note="setup-alwaystwo"]'), name, 'its rules note shows');
      await page.locator('dialog[open] button[data-act="alternate"]').click(); await page.waitForTimeout(500);
      const d = await page.evaluate(() => { const d = document.querySelector('dialog.setup[open]'); return d ? { id: d.id, mode: d.getAttribute('data-mode'), title: d.querySelector('.setup__title').textContent } : null; });
      check(d && d.id === 'setup-pvp' && d.mode === 'alwaystwo' && d.title === '1v1 Always Two', name, `Continue opens the PvP setup in its Always Two mode: ${JSON.stringify(d)}`);
      check(await shown(page, '#setup-pvp .a2-deck2') && await shown(page, '#setup-pvp button[data-act="alwaystwo"]'), name, 'a second deck box and "Create a 1v1 Always Two Room"');
      check(!(await shown(page, '#setup-pvp button[data-act="join"]')) && !(await shown(page, '#setup-pvp button[data-act="private"]')), name, 'no Join Queue / plain private room in this mode');
      const pools = await page.evaluate(() => [...document.querySelectorAll('#pvp-pool option')].filter(o => !o.hidden).map(o => o.value));
      check(JSON.stringify(pools) === JSON.stringify(['premier', 'eternal']), name, `only Premier and Eternal: ${pools}`);
      await page.waitForTimeout(1500);   // let deck detection (the remembered last deck) settle — it may pick another pool
      const picked = await page.evaluate(() => document.getElementById('pvp-pool').value);
      check(['premier', 'eternal'].includes(picked), name, `the picker shows Premier or Eternal after deck detection (shows ${picked})`);
      check(!(await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth + 1)), name, 'no horizontal page scroll');
      if (SHOTS) await page.screenshot({ path: `${SHOTS}/a2menu-${engine}-${s.label}.png` });
      // Control: the ordinary PvP setup is unchanged (no second deck box, its own buttons back).
      await page.evaluate(() => document.querySelectorAll('dialog[open]').forEach((x) => x.close()));
      await page.evaluate(() => window.SETUP_OPEN_ID('setup-pvp', null)); await page.waitForTimeout(400);
      check(!(await shown(page, '#setup-pvp .a2-deck2')) && await shown(page, '#setup-pvp button[data-act="private"]')
        && await page.evaluate(() => [...document.querySelectorAll('#pvp-pool option')].every(o => !o.hidden)), name, 'control: plain PvP setup unchanged');
    }
    await page.close();
  }
  await b.close();
}

// ── The waiting room: a 1v1 Always Two room asks each seat for a second deck (plan Task 8/13). ────────────────────────────────
// Eternal: fixtures/premier_deck.txt plays ASH_248, banned in Premier since 2026-11-09.
const DECK = fs.readFileSync(new URL('../../SWUSim/DevTools/tests/fixtures/premier_deck.txt', import.meta.url), 'utf8').trim();
async function openRoom(page, format) {
  await page.goto(BASE + 'SharedUI/MainMenu.php', { waitUntil: 'load' });
  const r = await page.evaluate(async ({ deck, format }) => {
    const body = new URLSearchParams({ deckLink: deck, game_type: 'casual', preconstructedDeck: '', rootName: 'SWUSim', format, queueType: 'bo1', createPrivate: '1' });
    return (await fetch('../APIs/Lobbies/JoinQueue.php', { method: 'POST', body })).json();
  }, { deck: DECK, format });
  if (!r.success || !r.lobbyID) throw new Error('JoinQueue refused: ' + JSON.stringify(r));
  await page.evaluate(({ id, key }) => localStorage.setItem('tcg:lobbyAuth:' + id, JSON.stringify({ authKey: key, ts: Date.now() })), { id: r.lobbyID, key: r.authKey });
  await page.goto(BASE + 'SharedUI/WaitingRoom.php?lobby=' + encodeURIComponent(r.lobbyID), { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(2500);
  return r;
}
const leave = (page, r) => page.request.post(BASE + 'APIs/Lobbies/LeaveQueue.php', { form: { rootName: 'SWUSim', playerID: String(r.playerID || 1), lobbyID: r.lobbyID, authKey: r.authKey } }).catch(() => {});
for (const [engine, launcher] of [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]].filter(([e]) => ENGINES.includes(e))) {
  let b;
  try { b = await launcher.launch(); } catch (e) { check(false, engine, `could not launch: ${e.message}`); continue; }
  for (const s of SURFACES) {
    const name = `${engine}/${s.label}/room`;
    const page = await (await b.newContext({ viewport: s.viewport })).newPage();
    await page.goto(BASE + 'SharedUI/LoginPage.php', { waitUntil: 'domcontentloaded' });
    await page.fill('input[name="userID"]', 'claudebot1'); await page.fill('input[name="password"]', 'pass');
    await Promise.all([page.waitForNavigation({ waitUntil: 'load' }).catch(() => {}), page.click('button[type="submit"]')]);
    const r = await openRoom(page, 'at1v1-eternal');
    try {
      check(await shown(page, '#wr-deck2'), name, 'the room asks for a second deck');
      check((await page.locator('input[name="wr-bestof"]').count()) === 0, name, 'no Best of 1 / Best of 3 switch (Bo1 only)');
      check(/NO SECOND DECK/.test(await page.locator('#wr-roster').innerText()), name, 'the seat reads NO SECOND DECK until it brings one');
      await page.fill('#wr-deck2-input', DECK); await page.click('#wr-deck2-btn');
      await page.waitForFunction(() => /Second deck accepted/.test((document.getElementById('wr-deck2-msg') || {}).textContent || ''), null, { timeout: 60000 }).catch(() => {});
      await page.waitForTimeout(3500);
      const roster = await page.locator('#wr-roster').innerText();
      check(/READY/.test(roster) && !/NO SECOND DECK/.test(roster), name, 'with both decks the seat is READY');
      const strips = await page.evaluate(() => { const me = document.querySelector('#wr-roster .wr-strip'); return me ? me.parentElement.querySelectorAll('.wr-strip').length : 0; });
      check(strips === 2, name, `the seat shows both decks' leader + base (${strips} strips)`);
      check(!(await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth + 1)), name, 'no horizontal page scroll');
      if (SHOTS) await page.screenshot({ path: `${SHOTS}/a2room-${engine}-${s.label}.png`, fullPage: true });
    } finally { await leave(page, r); }
    const c = await openRoom(page, 'eternal');
    try {
      check(!(await shown(page, '#wr-deck2')), name, 'control: an ordinary private room asks for one deck');
      check((await page.locator('input[name="wr-bestof"]').count()) === 2, name, 'control: an ordinary private room keeps its Bo1 / Bo3 switch');
    } finally { await leave(page, c); }
    await page.close();
  }
  await b.close();
}

console.log(fails === 0 ? `PASS (${checks} checks)` : `${fails} FAILED of ${checks}`);
process.exit(fails === 0 ? 0 : 1);
