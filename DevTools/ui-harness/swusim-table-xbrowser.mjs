// The multiplayer TABLE (docs/superpowers/specs/2026-11-21-swusim-multiplayer-table-design.md; plan
// docs/superpowers/plans/2026-11-21-swusim-multiplayer-table.md): every 4-seat game — Twin Suns 4P, Team Suns, 4P Always Two —
// shows all four boards on one page in the 1v1 Always Two table's look. Checked from EVERY seat, in Chromium, Firefox and WebKit.
// Who sits where is read from the card ART in each quarter (each seat's first ground unit is distinct in the fixture), not from
// the page's own markup.
//   node DevTools/ui-harness/swusim-table-xbrowser.mjs            (ENGINES=chromium to narrow; SHOTS=<dir> for screenshots)
import fs from 'fs';
import { chromium, firefox, webkit } from 'playwright';

const BASE = process.env.BASE_URL || 'http://localhost:3400/TCGEngine/';
const SHOTS = process.env.SHOTS || '';
const ENGINES = (process.env.ENGINES || 'chromium,firefox,webkit').split(',');
const SURFACES = (process.env.SURFACES || 'desktop').split(',');
const SECTIONS = (process.env.SECTIONS || 'layout,targets,phone,endgame,review').split(',');   // layout = Task 3, targets = Task 4, phone = Task 5, endgame = Task 6, review = final-review fixes
const IPHONE_UA = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';
const form = (o) => new URLSearchParams(o).toString();
const post = async (path, body, cookie) => fetch(BASE + path, { method: 'POST',
  headers: { 'Content-Type': 'application/x-www-form-urlencoded', ...(cookie ? { cookie } : {}) }, body: form(body), redirect: 'manual' });
const login = await post('AccountFiles/AttemptPasswordLogin.php', { submit: '1', userID: 'claudebot1', password: 'pass' });
const cookie = (login.headers.getSetCookie?.() || []).map(c => c.split(';')[0]).join('; ');
async function seed(schema) {
  const json = JSON.parse(await (await post('SWUSim/TestSchemaSetup.php', { schema }, cookie)).text());
  if (json.error) throw new Error(`TestSchemaSetup refused: ${json.error}`);
  for (const st of json.whenSteps || []) await post('SWUSim/TestSchemaStep.php', { gameName: json.gameName, step: st.raw }, cookie);
  return String(json.gameName);
}
const read = (f) => fs.readFileSync(new URL('../../SWUSim/Tests/Visual/' + f, import.meta.url), 'utf8');
const TS = read('TwinSuns_4P_AllZonesFilled.md');
const OVERFLOW = read('TwinSuns_4P_AllZonesOverflow.md');
const MODES = {
  twinsuns: TS,
  teamsuns: TS.replace('WithSeatOrder: 1234', 'WithSeatOrder: 1234\nWithTeams: true'),
  alwaystwo4p: TS.replace('WithSeatOrder: 1234', 'WithSeatOrder: 1234\nWithTeams: true\nWithAlwaysTwo: 4p'),
};
// The owner's seating, [left bottom, left top, right bottom, right top] per viewer seat.
const SEATING = {
  twinsuns:    { 1: [1, 2, 4, 3], 2: [2, 3, 1, 4], 3: [3, 4, 2, 1], 4: [4, 1, 3, 2] },
  teamsuns:    { 1: [1, 4, 3, 2], 2: [2, 3, 4, 1], 3: [3, 2, 1, 4], 4: [4, 1, 2, 3] },
};
SEATING.alwaystwo4p = SEATING.teamsuns;
// 4P Always Two, Tomb of Eilram (LOF_028): "exhaust a friendly unit" may use the PARTNER's unit; P1 proposed, P3 accepted, so P1's
// choice is open and its only legal answer is p3GroundArena-0 (board 3, the right column's bottom board) — board 1 fields no unit
// (as alwaystwo/FriendlyUnitCosts.md::TombOfEilram_UsableWithOnlyTheTeammatesUnit), so on a phone the only target is off-screen.
const PARTNER_UNIT = ['# T', '## GIVEN', 'CommonSetup4P: bbk/bbk/bbk/bbk/{myBase:LOF_028}', 'SkipPreGame: true', 'WithAlwaysTwo: 4p',
  'WithActivePlayer: 1', 'WithGamePhase: ActionPhase', 'WithP3GroundArena: SOR_128:1:0',
  '## WHEN', '- P1>UseBaseAbility', '- P3>AnswerDecision:YES', '## EXPECT', 'P1HASDECISION'].join('\n');
const FIRST_GROUND = { 1: 'SOR_032', 2: 'SOR_034', 3: 'SOR_036', 4: 'SOR_038' };   // each seat's ground unit 0 in the fixture

let fails = 0, checks = 0;
const check = (ok, name, msg) => { checks++; if (!ok) { fails++; console.log(`FAIL ${name} :: ${msg}`); } };
const url = (g, seat, extra = '') => `${BASE}NextTurn.php?folderPath=SWUSim&gameName=${g}&playerID=${seat}&authKey=testschema${extra}`;
const ready = (p) => p.waitForFunction(() => document.querySelectorAll('[id*="GroundArena-"]').length >= 8, null, { timeout: 60000 }).catch(() => {});
const SIZES = { desktop: [{ width: 1600, height: 1000 }, { width: 1920, height: 1080 }, { width: 1366, height: 768 }] };

// The four quarters, found by GEOMETRY: every ground-arena slot on the page, split by its centre into left/right and top/bottom.
const measure = (page) => page.evaluate(() => {
  const r = (el) => { const b = el.getBoundingClientRect(); return { x: b.left, y: b.top, w: b.width, h: b.height, cx: b.left + b.width / 2, cy: b.top + b.height / 2 }; };
  const vis = (el) => !!el && el.getBoundingClientRect().width > 0 && getComputedStyle(el).display !== 'none' && getComputedStyle(el).visibility !== 'hidden';
  const grounds = [...document.querySelectorAll('[id$="GroundArenaSlot"]')].filter(vis);
  const spaces = [...document.querySelectorAll('[id$="SpaceArenaSlot"]')].filter(vis);
  const bases = [...document.querySelectorAll('[id$="BaseSlot"]')].filter(vis);
  const leaders = [...document.querySelectorAll('[id$="LeaderSlot"]')].filter(vis);
  const W = document.documentElement.clientWidth, H = window.innerHeight;
  const art = (slot) => { const img = slot.querySelector('img[src*="/concat/"]'); const m = img && /\/concat\/([A-Z0-9]+_[0-9A-Z]+)/.exec(img.getAttribute('src')); return m ? m[1] : ''; };
  const sb = document.getElementById('swuSidebar');
  return {
    W, H, hscroll: document.documentElement.scrollWidth > document.documentElement.clientWidth + 1,
    grounds: grounds.map(s => ({ id: s.id, ...r(s), art: art(s) })), spaces: spaces.map(s => ({ id: s.id, ...r(s) })),
    bases: bases.map(s => ({ id: s.id, ...r(s) })), leaders: leaders.map(s => ({ id: s.id, ...r(s) })),
    homeVisible: vis(document.getElementById('swuHomeStrips')) || vis(document.getElementById('swuPairPrev')) || vis(document.getElementById('swuGoBack')),
    sidebar: sb ? r(sb) : null,
    rows: [...document.querySelectorAll('[data-trow]')].filter(vis).map(e => ({ col: e.closest('.swu-tcol') ? e.closest('.swu-tcol').id : '', row: e.getAttribute('data-trow'), ...r(e) })),
    cardSize: typeof window.cardSize === 'number' ? window.cardSize : null,
    sidebarW: window.SWUTableLayout && window.SWUTableLayout.sidebarW,
    tableRight: !!document.getElementById('swuTableRight'),
  };
});

for (const [engine, launcher] of [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]].filter(([e]) => ENGINES.includes(e))) {
  let b;
  try { b = await launcher.launch(); } catch (e) { check(false, engine, `could not launch: ${e.message}`); continue; }
  if (SECTIONS.includes('layout')) for (const surface of SURFACES) for (const vp of (SIZES[surface] || [])) {
    const tag = `${engine}/${vp.width}x${vp.height}`;
    for (const [mode, schema] of Object.entries(MODES)) {
      if (vp.width !== 1600 && mode !== 'twinsuns') continue;   // the extra sizes check the layout, not every mode again
      const g = await seed(schema);
      for (const seat of [1, 2, 3, 4]) {
        const name = `${tag}/${mode}/P${seat}`;
        const ctx = await b.newContext({ viewport: vp });
        const p = await ctx.newPage();
        const errs = []; p.on('pageerror', e => errs.push(e.message));
        await p.goto(url(g, seat), { waitUntil: 'domcontentloaded' }); await ready(p); await p.waitForTimeout(800);
        const m = await measure(p);
        check(m.tableRight, name, 'the page is a table (#swuTableRight present)');
        check(m.grounds.length === 4, name, `four ground arenas on screen (${m.grounds.length})`);
        const midX = (m.W - (m.sidebar ? m.sidebar.w : 0)) / 2;
        const q = (pred) => m.grounds.find(pred);
        const ys = m.grounds.map(s => s.cy).sort((a, c) => a - c); const midY = (ys[0] + ys[ys.length - 1]) / 2;
        const quarters = [q(s => s.cx < midX && s.cy > midY), q(s => s.cx < midX && s.cy < midY), q(s => s.cx > midX && s.cy > midY), q(s => s.cx > midX && s.cy < midY)];
        const want = SEATING[mode][seat].map(s => FIRST_GROUND[s]);
        const got = quarters.map(x => x ? x.art : '');
        check(JSON.stringify(got) === JSON.stringify(want), name, `seating [lb, lt, rb, rt] by art: want ${want} got ${got}`);
        // Arenas: ground inside, space outside — left column space|ground, right column ground|space.
        for (const sp of m.spaces) {
          const gr = m.grounds.find(x => Math.abs(x.cy - sp.cy) < 5 && (x.cx < midX) === (sp.cx < midX));
          if (!gr) { check(false, name, `space slot ${sp.id} has a ground slot beside it`); continue; }
          check(sp.cx < midX ? sp.cx < gr.cx : sp.cx > gr.cx, name, `${sp.id}: space is on the OUTSIDE of its column`);
        }
        // Bases on the inside corners.
        for (const bs of m.bases) {
          const ls = m.leaders.filter(l => Math.abs(l.cy - bs.cy) < 30 && (l.cx < midX) === (bs.cx < midX));
          check(ls.length > 0 && ls.every(l => bs.cx < midX ? bs.cx > l.cx : bs.cx < l.cx), name, `${bs.id}: base sits on the inside corner`);
        }
        // Rows line up across the columns, row for row.
        const byRow = {};
        for (const r of m.rows) (byRow[r.row] = byRow[r.row] || []).push(r);
        for (const [row, list] of Object.entries(byRow)) {
          check(list.length === 2 && Math.abs(list[0].y - list[1].y) <= 1 && Math.abs(list[0].h - list[1].h) <= 1, name, `row "${row}" lines up across both columns: ${JSON.stringify(list.map(x => [x.col, Math.round(x.y), Math.round(x.h)]))}`);
        }
        check(Object.keys(byRow).length >= 6, name, `every table row is marked (${Object.keys(byRow)})`);
        // Card size from the column width.
        const colW = (vp.width - m.sidebarW) / 2;   // the sidebar's ONE width (TableLayout.php SWUTableSidebarWidth)
        const wantSize = Math.max(36, Math.min(80, Math.floor((colW - 24 - 28) / 7)));
        check(m.cardSize === wantSize, name, `card size ${m.cardSize} = max(36, min(80, floor((${colW}-24-28)/7))) = ${wantSize}`);
        check(!m.homeVisible, name, 'no Home strip / carousel / Go back');
        check(!!m.sidebar && Math.abs(m.sidebar.x + m.sidebar.w - m.W) <= 1 && Math.round(m.sidebar.w) === m.sidebarW && m.sidebarW > 0 && m.sidebar.h >= m.H - 1, name, `full-height ${m.sidebarW}px sidebar on the right: ${JSON.stringify(m.sidebar)}`);
        check(!m.hscroll, name, 'no horizontal page scroll');
        if (vp.width === 1600 && seat === 1) {
          // The teammate's hand: face-up in 4P Always Two, hidden (backs) in Team Suns; never clickable.
          const hand = await p.evaluate(() => { const s = document.getElementById('p3HandSlot'); if (!s) return null;
            const imgs = [...s.querySelectorAll('img')].filter(i => !i.classList.contains('counter-image-icon'));
            return { n: imgs.length, backs: imgs.filter(i => /CardBack|cardback|card-back|sleeve/i.test(i.getAttribute('src') || '')).length }; });
          if (mode === 'alwaystwo4p') check(!!hand && hand.n === 6 && hand.backs === 0, name, `board 3's hand face-up (6): ${JSON.stringify(hand)}`);
          if (mode === 'teamsuns') check(!hand || hand.n === 0 || hand.backs === hand.n, name, `Team Suns: board 3's hand stays hidden: ${JSON.stringify(hand)}`);
        }
        // The right column is display-only: nothing there is playable or clickable on its own (a choice's targets are lit by the
        // table's targeting glue, Task 4). The 4P Always Two partner's face-up hand must not glow or play.
        const live = await p.evaluate(() => [...document.querySelectorAll('#swuTableRight [onclick], #swuTableRight .selectable-card')]
          .filter(e => !e.closest('.swu-table-target') && !e.closest('.swu-base-tab'))   // a base tab opens the list of attached cards: viewing, not acting
          .map(e => e.id || e.className).slice(0, 4));
        check(live.length === 0, name, `nothing in the right column is clickable or glowing: ${JSON.stringify(live)}`);
        check(errs.length === 0, name, `no page errors: ${errs.slice(0, 2).join(' | ')}`);
        if (SHOTS && seat <= 2) await p.screenshot({ path: `${SHOTS}/table-${mode}-P${seat}-${engine}-${vp.width}.png` });
        await ctx.close();
      }
    }
    // Static rows: a board with every arena overflowing lines up exactly like the ordinary one (nothing grows).
    if (vp.width === 1600) {
      const g1 = await seed(TS), g2 = await seed(OVERFLOW);
      const rowsOf = async (g) => { const ctx = await b.newContext({ viewport: vp }); const p = await ctx.newPage();
        await p.goto(url(g, 1), { waitUntil: 'domcontentloaded' }); await ready(p); await p.waitForTimeout(800);
        const m = await measure(p); await ctx.close(); return m.rows.map(r => `${r.col}:${r.row}:${Math.round(r.y)}:${Math.round(r.h)}`).sort().join(','); };
      const a = await rowsOf(g1), c = await rowsOf(g2);
      check(a !== '' && a === c, `${tag}/overflow`, `rows do not move when every arena overflows: ${a === c ? 'same' : a + ' vs ' + c}`);
    }
    // Control: a two-player game is untouched — no table, and the same slot geometry with the table switched off.
    if (vp.width === 1600) {
      const g = await seed(read('TwinSuns_4P_AllZonesFilled.md').replace(/^With(SeatOrder|LiveSeats): 1234\n/mg, '').replace(/^WithP[34][A-Za-z0-9]*:.*\n/mg, ''));
      const slots = async (extra) => { const ctx = await b.newContext({ viewport: vp }); const p = await ctx.newPage();
        await p.goto(url(g, 1, extra), { waitUntil: 'domcontentloaded' });
        await p.waitForFunction(() => document.querySelectorAll('[id*="GroundArena-"]').length >= 2, null, { timeout: 60000 }).catch(() => {});
        // Measure once every image has loaded: a leader/base slot is 1px taller while its art is still arriving.
        await p.waitForFunction(() => [...document.images].every(i => i.complete), null, { timeout: 30000 }).catch(() => {}); await p.waitForTimeout(800);
        const s = await p.evaluate(() => ({ table: !!document.getElementById('swuTableRight'),
          rects: [...document.querySelectorAll('[id$="Slot"]')].map(e => { const b = e.getBoundingClientRect(); return [e.id, b.left, b.top, b.width, b.height]; }) }));
        await ctx.close(); return s; };
      const on = await slots(''), off = await slots('&swuTable=off');
      const same = on.rects.length === off.rects.length && on.rects.every((r, i) => r[0] === off.rects[i][0] && r.slice(1).every((v, k) => Math.abs(v - off.rects[i][k + 1]) <= 1));
      check(!on.table && same, `${tag}/two-player`, 'a two-player board is unchanged (no table, same slot geometry within 1px)');
    }
  }
  if (SECTIONS.includes('targets')) await targetChecks(b, engine);
  if (SECTIONS.includes('phone')) await phoneChecks(b, engine);
  if (SECTIONS.includes('endgame')) await endgameChecks(b, engine);
  if (SECTIONS.includes('review')) await reviewChecks(b, engine);
  await b.close();
}

// ── Task 4: targeting across the table ─────────────────────────────────────────────────────────────────────────────────────
async function openSeat(b, g, seat, vp = { width: 1600, height: 1000 }) {
  const ctx = await b.newContext({ viewport: vp }); const p = await ctx.newPage();
  const errs = []; p.on('pageerror', e => errs.push(e.message));
  await p.goto(url(g, seat), { waitUntil: 'domcontentloaded' });
  await p.waitForFunction(() => !!document.getElementById('swuTableRight') && document.querySelectorAll('[id*="Arena-"]').length >= 2, null, { timeout: 60000 }).catch(() => {});
  await p.waitForTimeout(1000);
  return { ctx, p, errs };
}
function lit(p) { return p.evaluate(() => [...document.querySelectorAll('#swuTableRight .swu-table-target, #swuTableRight .swu-table-subhost')].map(e => e.id)); }
function waitLit(p, n = 1) { return p.waitForFunction((n) => document.querySelectorAll('#swuTableRight .swu-table-target').length >= n, n, { timeout: 8000 }).then(() => true).catch(() => false); }
function logText(p) { return p.evaluate(() => (document.getElementById('swuLogPanel') || {}).innerText || ''); }
async function targetChecks(b, engine) {
  // A. Attack from your column into the right column (Twin Suns 4P, seat 1: right column = P4 bottom, P3 top).
  {
    const name = `${engine}/targets/attack`;
    const g = await seed(MODES.twinsuns);
    const { ctx, p, errs } = await openSeat(b, g, 1);
    await p.click('#myGroundArena-0'); 
    check(await waitLit(p), name, 'starting an attack lights enemy targets in the right column');
    const targets = await lit(p);
    if (SHOTS) await p.screenshot({ path: `${SHOTS}/table-targets-attack-${engine}.png` });
    check(targets.length > 0 && targets.every(id => /^p[34]/.test(id)), name, `only P3/P4 cards are lit there: ${targets}`);
    const unit = targets.find(id => /GroundArena-\d+$/.test(id)) || targets[0];
    const before = await logText(p);
    if (unit) await p.click('#' + unit);
    const resolved = await p.waitForFunction((b4) => { const t = (document.getElementById('swuLogPanel') || {}).innerText || '';
      return t !== b4 && /attacked P[34]/.test(t); }, before, { timeout: 15000 }).then(() => true).catch(() => false);
    check(resolved, name, `clicking ${unit} in the right column attacks it (log says "attacked P3/P4")`);
    check((await lit(p)).length === 0, name, 'after the attack nothing stays lit');
    check(errs.length === 0, name, `no page errors: ${errs.slice(0, 2)}`);
    await ctx.close();
  }
  // B. Undo while targets are lit → the glow clears with the withdrawn choice (Review Focus 4).
  {
    const name = `${engine}/targets/undo`;
    const g = await seed(MODES.twinsuns);
    const { ctx, p } = await openSeat(b, g, 1);
    await p.click('#myGroundArena-0');
    const was = await waitLit(p);
    await p.evaluate(() => SubmitInput(10004, ''));
    const cleared = await p.waitForFunction(() => document.querySelectorAll('#swuTableRight .swu-table-target').length === 0, null, { timeout: 10000 }).then(() => true).catch(() => false);
    check(was && cleared, name, `Undo while targets are lit clears them (lit first: ${was})`);
    await ctx.close();
  }
  // C. A defeated seat offers nothing (Review Focus 1): P4 is out; an attack lights P3's cards only.
  {
    const name = `${engine}/targets/defeated-seat`;
    const g = await seed(MODES.twinsuns.replace('WithSeatOrder: 1234', 'WithSeatOrder: 1234\nWithLiveSeats: 123'));
    const { ctx, p } = await openSeat(b, g, 1);
    await p.click('#myGroundArena-0');
    const any = await waitLit(p);
    const t = await lit(p);
    check(any && t.length > 0 && t.every(id => !/^p4/.test(id)), name, `P3's targets are lit and nothing on defeated P4 is: ${t}`);
    await ctx.close();
  }
  // D. 4P Always Two: a cost that exhausts "a friendly unit" may use the PARTNER's unit — bottom-right — after the partner accepts.
  {
    const name = `${engine}/targets/partner-unit`;
    const g = await seed(PARTNER_UNIT);
    const { ctx, p, errs } = await openSeat(b, g, 1);
    check(await waitLit(p), name, "the partner's unit is lit in the right column");
    check(JSON.stringify(await lit(p)) === JSON.stringify(['p3GroundArena-0']), name, `exactly p3GroundArena-0: ${await lit(p)}`);
    const before = await logText(p);
    await p.click('#p3GroundArena-0').catch(() => {});
    const done = await p.waitForFunction((b4) => ((document.getElementById('swuLogPanel') || {}).innerText || '') !== b4
      && document.querySelectorAll('#swuTableRight .swu-table-target').length === 0, before, { timeout: 15000 }).then(() => true).catch(() => false);
    check(done, name, "picking the partner's unit pays the cost (log moves, nothing left lit)");
    check(errs.length === 0, name, `no page errors: ${errs.slice(0, 2)}`);
    await ctx.close();
  }
  // E. "Up to 3 units" across both columns: a right-column pick is accumulated (gold), not submitted.
  {
    const name = `${engine}/targets/multi`;
    const g = await seed(MODES.twinsuns.replace('WithP1Hand:    [SOR_031 SOR_034 SOR_037 SOR_040 SOR_043 SOR_046]', 'WithP1Hand:    [JTL_140]')
      .replace('WithP1Resources: 4:SOR_074:0,4:SOR_123:1', 'WithP1Resources: 8:SOR_123:1').replace('WithP1Credits: 2\n', '')   // no Credit-payment prompt first
      .replace('## WHEN', '## WHEN\n- P1>PlayHand:0'));
    const { ctx, p } = await openSeat(b, g, 1);
    const ok = await waitLit(p, 2);
    check(ok, name, 'an "up to 3" choice lights right-column units');
    const t = (await lit(p)).find(id => /Arena-\d+$/.test(id));
    if (t) await p.click('#' + t);
    await p.waitForTimeout(600);
    const st = await p.evaluate((t) => ({ picked: !!document.querySelector('#' + t + '.swu-table-picked'),
      inSel: !!(window.SelectionMode && Array.isArray(window.SelectionMode.multiSelected) && window.SelectionMode.multiSelected.indexOf(t) >= 0) }), t || 'x');
    check(st.picked && st.inSel, name, `the pick is accumulated and marked: ${JSON.stringify(st)} (${t})`);
    await ctx.close();
  }
}
// ── Task 5: the NARROW table ──────────────────────────────────────────────────────────────────────────────────────────────
// PHONES keep the Home panel tiles (owner, 2026-11-22: "on mobile, let's revert to the home panel tiles layout we had before. desktop
// looks great though" — TableLayout.php, by the phone user-agent test). The stacked table is now what a NARROW DESKTOP window gets
// (no phone user agent), so the stacking / chip checks run there; the first block pins that a real phone gets no table.
async function phoneChecks(b, engine) {
  const vp = { width: 390, height: 844 };
  for (const [mode, schema] of Object.entries(MODES)) {
    const g = await seed(schema);
    const name = `${engine}/phone/${mode}/home-panels`;
    const ctx = await b.newContext({ viewport: vp, userAgent: IPHONE_UA, isMobile: engine !== 'firefox', hasTouch: true });
    const p = await ctx.newPage(); const errs = []; p.on('pageerror', e => errs.push(e.message));
    await p.goto(url(g, 1), { waitUntil: 'domcontentloaded' });
    await p.waitForFunction(() => document.querySelectorAll('[id*="GroundArena-"]').length > 0, null, { timeout: 60000 }).catch(() => {});
    await p.waitForTimeout(1500);
    const st = await p.evaluate(() => {
      const strips = document.getElementById('swuHomeStrips');
      return { table: !!document.getElementById('swuTableRight') || !!(window.SWUTableLayout && window.SWUTableLayout.kind),
        tiles: strips ? strips.children.length : 0, stripsShown: !!strips && getComputedStyle(strips).display !== 'none' };
    });
    check(!st.table, name, 'a phone gets no table');
    check(st.stripsShown && st.tiles >= 3, name, `a phone gets the Home panel tiles, one per opponent: ${JSON.stringify(st)}`);
    check(errs.length === 0, name, `no page errors: ${errs.slice(0, 2)}`);
    if (SHOTS) await p.screenshot({ path: `${SHOTS}/phone-home-${mode}-${engine}.png` });
    await ctx.close();
  }
  for (const [mode, schema] of Object.entries(MODES)) {
    const g = await seed(schema);
    for (const seat of [1, 2]) {
      const name = `${engine}/narrow/${mode}/P${seat}`;
      const ctx = await b.newContext({ viewport: vp, hasTouch: true });
      const p = await ctx.newPage(); const errs = []; p.on('pageerror', e => errs.push(e.message));
      await p.goto(url(g, seat), { waitUntil: 'domcontentloaded' }); await ready(p); await p.waitForTimeout(1000);
      const m = await measure(p);
      // Stacked, your column first: its top board, you, then the other column's top board, its bottom board.
      const order = m.grounds.slice().sort((a, c) => a.y - c.y).map(x => x.art);
      const [lb, lt, rb, rt] = SEATING[mode][seat];
      const want = [lt, lb, rt, rb].map(s => FIRST_GROUND[s]);
      check(JSON.stringify(order) === JSON.stringify(want), name, `stacked top to bottom: want ${want} got ${order}`);
      check(!m.hscroll, name, 'no horizontal page scroll');
      check(m.cardSize === Math.max(36, Math.min(80, Math.floor((vp.width - 24 - 28) / 7))), name, `card size from the window width: ${m.cardSize}`);
      check(m.grounds.every(x => x.h >= 60), name, `every arena has real height on a narrow window: ${m.grounds.map(x => Math.round(x.h))}`);
      check(errs.length === 0, name, `no page errors: ${errs.slice(0, 2)}`);
      if (SHOTS && seat === 1) await p.screenshot({ path: `${SHOTS}/table-phone-${mode}-${engine}.png`, fullPage: true });
      await ctx.close();
    }
  }
  // The nudge chip: a choice whose ONLY target sits in the other column, off-screen (4P Always Two: the partner's unit pays a
  // "friendly unit" cost — after the partner accepted), says so; tapping it brings the target into view, and it can be picked.
  {
    const name = `${engine}/narrow/chip`;
    const g = await seed(PARTNER_UNIT);
    const ctx = await b.newContext({ viewport: vp, hasTouch: true });
    const p = await ctx.newPage();
    await p.goto(url(g, 1), { waitUntil: 'domcontentloaded' });
    await p.waitForFunction(() => !!document.getElementById('swuTableRight'), null, { timeout: 60000 }).catch(() => {});
    await p.waitForTimeout(1500);
    const chip = await p.waitForFunction(() => { const c = document.getElementById('swuTblChip'); return c && getComputedStyle(c).display !== 'none' && /Targets on/.test(c.textContent) ? c.textContent : ''; }, null, { timeout: 8000 }).then(h => h.jsonValue()).catch(() => '');
    check(/Targets on P3's board/.test(chip), name, `a chip points to the off-screen target on P3's board: "${chip}"`);
    if (SHOTS) await p.screenshot({ path: `${SHOTS}/table-phone-chip-${engine}.png` });
    await p.tap('#swuTblChip').catch(() => {});
    await p.waitForTimeout(1200);
    const inView = await p.evaluate(() => { const t = document.getElementById('p3GroundArena-0'); if (!t) return false; const r = t.getBoundingClientRect(); return r.top >= 0 && r.bottom <= window.innerHeight; });
    check(inView, name, 'tapping the chip brings the target into view');
    const before = await logText(p);
    await p.tap('#p3GroundArena-0').catch(() => {});
    const done = await p.waitForFunction((b4) => ((document.getElementById('swuLogPanel') || {}).textContent || '') !== b4
      && document.querySelectorAll('#swuTableRight .swu-table-target').length === 0, before, { timeout: 15000 }).then(() => true).catch(() => false);
    check(done, name, 'the target is pickable from there (the cost is paid, nothing left lit)');
    await ctx.close();
  }
  // Review Focus 3: resizing across the 900px line re-lays the table out without a reload, and the columns line up again.
  {
    const name = `${engine}/phone/resize`;
    const g = await seed(MODES.twinsuns);
    const ctx = await b.newContext({ viewport: { width: 1600, height: 1000 } }); const p = await ctx.newPage();
    await p.goto(url(g, 1), { waitUntil: 'domcontentloaded' }); await ready(p); await p.waitForTimeout(1000);
    await p.setViewportSize({ width: 700, height: 900 }); await p.waitForTimeout(1200);
    const narrow = await p.evaluate(() => document.documentElement.classList.contains('swu-table-wide'));
    await p.setViewportSize({ width: 1600, height: 1000 }); await p.waitForTimeout(1500);
    const m = await measure(p);
    const byRow = {}; for (const r of m.rows) (byRow[r.row] = byRow[r.row] || []).push(r);
    const aligned = Object.values(byRow).every(l => l.length === 2 && Math.abs(l[0].y - l[1].y) <= 1);
    check(!narrow && aligned && m.cardSize === 80, name, `700px stacks (wide=${narrow}); back at 1600 the rows line up (${aligned}) at card size 80 (${m.cardSize})`);
    await ctx.close();
  }
}
// ── Task 6: spectators, eliminated players, the end of the game ───────────────────────────────────────────────────────────
async function endgameChecks(b, engine) {
  const vp = { width: 1600, height: 1000 };
  // A spectator who picked seat 3 sees seat 3's table, read-only.
  {
    const name = `${engine}/endgame/spectator`;
    const g = await seed(MODES.twinsuns);
    const ctx = await b.newContext({ viewport: vp }); const p = await ctx.newPage();
    await p.goto(`${BASE}NextTurn.php?folderPath=SWUSim&gameName=${g}&playerID=S&viewerPerspective=3`, { waitUntil: 'domcontentloaded' });
    await ready(p); await p.waitForTimeout(1200);
    const m = await measure(p);
    const midX = (m.W - (m.sidebar ? m.sidebar.w : 0)) / 2;
    const ys = m.grounds.map(x => x.cy).sort((a, c) => a - c); const midY = (ys[0] + ys[ys.length - 1]) / 2;
    const q = (f) => (m.grounds.find(f) || {}).art || '';
    const got = [q(x => x.cx < midX && x.cy > midY), q(x => x.cx < midX && x.cy < midY), q(x => x.cx > midX && x.cy > midY), q(x => x.cx > midX && x.cy < midY)];
    const want = SEATING.twinsuns[3].map(s => FIRST_GROUND[s]);
    check(m.tableRight && JSON.stringify(got) === JSON.stringify(want), name, `seat 3's table: want ${want} got ${got}`);
    const badge = await p.evaluate(() => { const e = document.getElementById('swuSpectateBadge'); return !!e && getComputedStyle(e).display !== 'none' && e.getBoundingClientRect().width > 0; });
    check(badge, name, 'the read-only badge is shown');
    await p.evaluate(() => { const u = document.getElementById('myGroundArena-0'); if (u) u.click(); }); await p.waitForTimeout(1500);
    const acted = await p.evaluate(() => !!(window.SelectionMode && window.SelectionMode.active) || document.querySelectorAll('.swu-table-target').length > 0);
    check(!acted, name, 'clicking a unit does nothing for a spectator');
    await ctx.close();
  }
  // An eliminated player keeps the table, their own board greyed in place, the seating unchanged.
  {
    const name = `${engine}/endgame/eliminated`;
    const g = await seed(MODES.twinsuns.replace('WithSeatOrder: 1234', 'WithSeatOrder: 1234\nWithLiveSeats: 234'));
    const ctx = await b.newContext({ viewport: vp }); const p = await ctx.newPage();
    await p.goto(url(g, 1), { waitUntil: 'domcontentloaded' });
    await p.waitForFunction(() => !!document.getElementById('swuTableRight'), null, { timeout: 60000 }).catch(() => {}); await p.waitForTimeout(1500);
    const st = await p.evaluate(() => ({ table: !!document.getElementById('swuTableRight'), greyed: document.body.classList.contains('swu-self-defeated'),
      lt: (document.getElementById('swuTableLeft') || {}).dataset, rt: (document.getElementById('swuTableRight') || {}).dataset }));
    check(st.table && st.greyed && st.lt && st.lt.top === '2' && st.rt && st.rt.top === '3' && st.rt.bottom === '4', name, `table kept, own board greyed, seats unchanged: ${JSON.stringify(st)}`);
    if (SHOTS) await p.screenshot({ path: `${SHOTS}/table-eliminated-${engine}.png` });
    await ctx.close();
  }
  // Review Focus 5: the game ends once on the page — one overlay, both winners named, no sound played twice.
  {
    const name = `${engine}/endgame/gameover`;
    const g = await seed(['# T', '## GIVEN', 'CommonSetup4P: bbk/bbk/bbk/bbk/{theirBaseDamage:99}', 'SkipPreGame: true', 'WithAlwaysTwo: 4p',
      'WithActivePlayer: 1', 'WithGamePhase: ActionPhase', 'WithP1GroundArena: SOR_095:1:0', 'WithP4Base: SOR_026:29',
      '## WHEN', '- P1>AttackGroundArena:0:?', '- P3>AnswerDecision:YES', '## EXPECT', 'P1HASDECISION'].join('\n'));
    const ctx = await b.newContext({ viewport: vp }); const p = await ctx.newPage();
    await p.addInitScript(() => { window.__plays = []; const orig = HTMLMediaElement.prototype.play;
      HTMLMediaElement.prototype.play = function () { window.__plays.push({ src: this.currentSrc || this.src || '', t: Date.now() }); return orig.apply(this, arguments); }; });
    await p.goto(url(g, 1), { waitUntil: 'domcontentloaded' });
    await p.waitForFunction(() => !!document.getElementById('swuTableRight') && !!(window.SelectionMode && window.SelectionMode.active), null, { timeout: 60000 }).catch(() => {});
    await p.waitForTimeout(1000);
    await p.evaluate(() => { window.__plays = []; const e = document.getElementById('theirBase-0') || document.querySelector('#theirBaseSlot [id^="theirBase-"]'); if (e) e.click(); });
    const over = await p.waitForFunction(() => !!document.getElementById('game-over-overlay'), null, { timeout: 20000 }).then(() => true).catch(() => false);
    await p.waitForTimeout(3000);
    const st = await p.evaluate(() => ({ n: document.querySelectorAll('#game-over-overlay').length,
      text: ((document.getElementById('game-over-overlay') || {}).innerText || '').replace(/\s+/g, ' ').slice(0, 200),
      dup: (function () { const c = {}; (window.__plays || []).forEach(x => { c[x.src] = (c[x.src] || 0) + 1; }); return Object.keys(c).filter(k => c[k] > 1); })() }));
    check(over && st.n === 1, name, `one game-over overlay: ${st.n}`);
    check(/P1|Player 1/.test(st.text) && /P3|Player 3/.test(st.text), name, `both winners are named: "${st.text}"`);
    check(st.dup.length === 0, name, `no sound plays twice: ${JSON.stringify(st.dup)}`);
    if (SHOTS) await p.screenshot({ path: `${SHOTS}/table-gameover-${engine}.png` });
    await ctx.close();
  }
}
// ── Final-review fixes (each check written before its fix) ────────────────────────────────────────────────────────────────
async function reviewChecks(b, engine) {
  const vp = { width: 1600, height: 1000 };
  const open = async (schema, seat = 1, v = vp) => { const g = await seed(schema); const r = await openSeat(b, g, seat, v); await r.p.waitForTimeout(800); return r; };
  // 1 (Critical). An any-player Action (TS26_15 C-3P0) on a RIGHT-column seat is usable: it glows and opens the Ability-only menu.
  {
    const name = `${engine}/review/opponent-unit-action`;
    const { ctx, p } = await open(read('TwinSuns_OpponentUnitAction_C3P0.md'));
    const has = await p.evaluate(() => { const e = document.querySelector('#swuTableRight [data-mzid="p3GroundArena-0"]'); return !!e && e.classList.contains('unit-has-actions'); });
    check(has, name, "C-3P0 on P3's board (right column) is marked as having an Action");
    await p.evaluate(() => { const e = document.querySelector('#swuTableRight [data-mzid="p3GroundArena-0"]'); if (e) e.click(); });
    const menu = await p.waitForFunction(() => { const m = document.getElementById('swuUnitActionMenu'); return m && m.querySelector('button') ? m.innerText : ''; }, null, { timeout: 5000 }).then(h => h.jsonValue()).catch(() => '');
    // Named Actions label their button with the ability's name ("C-3P0"), so the check is: a menu with its Action, and no Attack.
    const btns = await p.evaluate(() => [...document.querySelectorAll('#swuUnitActionMenu button')].map(x => x.innerText.trim()));
    check(!!menu && btns.length >= 1 && !btns.some(t => /Attack/i.test(t)), name, `clicking it opens its Action menu, no Attack: ${JSON.stringify(btns)}`);
    await ctx.close();
  }
  // 2. An idle table page does not keep rewriting itself (the bars' observer re-triggered itself every frame).
  {
    const name = `${engine}/review/idle-quiet`;
    const { ctx, p } = await open(MODES.twinsuns);
    const n = await p.evaluate(() => new Promise(res => { let c = 0; const col = document.getElementById('swuTableRight');
      const o = new MutationObserver(r => { c += r.length; }); o.observe(col, { childList: true, subtree: true, characterData: true });
      setTimeout(() => { o.disconnect(); res(c); }, 2000); }));
    check(n === 0, name, `no DOM writes in the right column over 2s of idle: ${n}`);
    await ctx.close();
  }
  // 3. Defeated seats: the seat above you dying greys ONLY that board; a defeated right-column seat is greyed.
  {
    const name = `${engine}/review/defeated-greying`;
    let r = await open(MODES.twinsuns.replace('WithSeatOrder: 1234', 'WithSeatOrder: 1234\nWithLiveSeats: 134'));
    const live = await r.p.evaluate(() => [...document.querySelectorAll('#swuTableRight .swu-m-arena-row')].map(e => getComputedStyle(e).filter));
    check(live.length === 2 && live.every(f => f === 'none'), name, `P2 (above you) out: the right column stays in colour: ${live}`);
    await r.ctx.close();
    r = await open(MODES.twinsuns.replace('WithSeatOrder: 1234', 'WithSeatOrder: 1234\nWithLiveSeats: 123'));
    const dead = await r.p.evaluate(() => ({ p4: [...document.querySelectorAll('#swuTableRight [data-seat="4"]')].map(e => getComputedStyle(e).filter).filter(f => /grayscale/.test(f)).length,
      p3: [...document.querySelectorAll('#swuTableRight .swu-m-arena-row[data-seat="3"]')].map(e => getComputedStyle(e).filter) }));
    check(dead.p4 >= 1 && dead.p3.every(f => f === 'none'), name, `P4 out: P4's right-column board is greyed, P3's is not: ${JSON.stringify(dead)}`);
    await r.ctx.close();
  }
  // 4. Turn, initiative and names live on the boards: P3's turn + P3's initiative show on P3's board; every board is named.
  {
    const name = `${engine}/review/turn-initiative-names`;
    const { ctx, p } = await open(MODES.twinsuns.replace('WithActivePlayer: 1', 'WithActivePlayer: 3\nWithInitiativePlayer: 3'));
    const st = await p.evaluate(() => ({ turn: !!document.querySelector('#swuTableRight [data-seat="3"].is-turn'),
      notTurnLeft: !document.querySelector('#swuTableLeft .is-turn'),
      initOn: (function () { const c = document.getElementById('swuInitControl'); const h = c && c.closest('[data-seat]'); return h ? h.getAttribute('data-seat') : (c && c.parentNode ? c.parentNode.id : ''); })(),
      names: [...document.querySelectorAll('.swu-tbl-name')].map(e => e.getAttribute('data-seat') + ':' + e.textContent.trim()) }));
    check(st.turn && st.notTurnLeft, name, `P3's board shows it is P3's turn, nothing on the left does: ${JSON.stringify(st)}`);
    check(st.initOn === '3', name, `the initiative token sits on P3's board: ${st.initOn}`);
    const seats = st.names.map(x => x.split(':')[0]).sort().join('');
    check(seats === '1234' && st.names.every(x => x.split(':')[1] !== ''), name, `every board is named: ${st.names}`);
    await ctx.close();
  }
  // 5. The right column shows what the left does: base tabs, base health bars, the Extra Base HP pip, a discard pile that opens.
  {
    const name = `${engine}/review/right-column-info`;
    const { ctx, p } = await open(MODES.twinsuns.replace('WithSeatOrder: 1234', 'WithSeatOrder: 1234\nWithHouseRules: extraBaseHp=10'));
    await p.waitForTimeout(800);
    const st = await p.evaluate(() => ({ tabs3: ((document.getElementById('p3BaseTabs') || {}).textContent || '').replace(/\s+/g, ' '),
      tabsTheir: ((document.getElementById('theirBaseTabs') || {}).textContent || '').replace(/\s+/g, ' '),
      hb3: !!document.querySelector('#p3BaseHealth .swu-hb'), hb4: !!document.querySelector('#p4BaseHealth .swu-hb'),
      pip3: ((document.querySelector('#p3Base-0 .swu-basehp-pip') || {}).textContent || '') }));
    check(/Fortified/.test(st.tabs3) && /Arrested/.test(st.tabs3), name, `P3's base tabs: "${st.tabs3}"`);
    check(/Fortified/.test(st.tabsTheir) && /Arrested/.test(st.tabsTheir), name, `left column base tabs too: "${st.tabsTheir}"`);
    check(st.hb3 && st.hb4, name, `base health bars on both right-column bases: ${st.hb3}/${st.hb4}`);
    check(st.pip3 === '40', name, `Extra Base HP: P3's base shows 40 (30 + 10): "${st.pip3}"`);
    await p.evaluate(() => { const s = document.getElementById('p3DiscardSlot'); if (s) s.click(); });
    const pop = await p.waitForFunction(() => /TWI_049|TWI_064/.test([...document.querySelectorAll('img')].filter(i => i.getBoundingClientRect().width > 0 && !i.closest('#swuTableRight') && !i.closest('#swuTableLeft')).map(i => i.getAttribute('src')).join(' ')), null, { timeout: 6000 }).then(() => true).catch(() => false);
    check(pop, name, "clicking P3's discard pile opens it (P3's discards on screen)");
    await ctx.close();
  }
  // Review Focus 3, tightened: crossing 900px redraws at the new card size (950 → size 40; 899 → size 80), no reload.
  {
    const name = `${engine}/review/resize-redraw`;
    const { ctx, p } = await open(MODES.twinsuns, 1, { width: 950, height: 900 });
    const sizeAt = () => p.evaluate(() => { const i = document.querySelector('#myHandSlot img'); return { cs: window.cardSize, w: i ? Math.round(i.getBoundingClientRect().width) : 0 }; });
    const a = await sizeAt();
    const sbW = await p.evaluate(() => window.SWUTableLayout && window.SWUTableLayout.sidebarW);
    const want950 = Math.max(36, Math.min(80, Math.floor(((950 - sbW) / 2 - 52) / 7)));   // derived from the sidebar's ONE width
    await p.setViewportSize({ width: 899, height: 900 }); await p.waitForTimeout(1500);
    const c = await sizeAt();
    check(a.cs === want950 && c.cs === 80 && Math.abs(c.w - a.w) >= 20, name, `950px → ${JSON.stringify(a)}, 899px → ${JSON.stringify(c)}: the hand redraws bigger`);
    await ctx.close();
  }
}
console.log(`${checks - fails}/${checks} checks passed`);
process.exit(fails ? 1 : 0);
