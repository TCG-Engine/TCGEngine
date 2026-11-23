// The 3-player TABLE (spec docs/superpowers/specs/2026-11-21-swusim-multiplayer-table-design.md "Twin Suns, 3 players"; plan Phase 2):
// your two opponents across the top, clockwise, and your board across the whole bottom (owner 2026-11-22: "[them1][them2] / [Me]").
// Who sits where is read from the card ART (each seat's first ground unit is distinct in the fixture), never from the page's markup.
//   node DevTools/ui-harness/swusim-table3-xbrowser.mjs     (ENGINES=chromium to narrow; SECTIONS=…; SHOTS=<dir> for screenshots)
import fs from 'fs';
import { chromium, firefox, webkit } from 'playwright';

const BASE = process.env.BASE_URL || 'http://localhost:3400/TCGEngine/';
const SHOTS = process.env.SHOTS || '';
const ENGINES = (process.env.ENGINES || 'chromium,firefox,webkit').split(',');
const SECTIONS = (process.env.SECTIONS || 'right,layout,narrow,targets,endgame,toggle').split(',');
const form = (o) => new URLSearchParams(o).toString();
const post = async (path, body, cookie) => fetch(BASE + path, { method: 'POST',
  headers: { 'Content-Type': 'application/x-www-form-urlencoded', ...(cookie ? { cookie } : {}) }, body: form(body), redirect: 'manual' });
const login = await post('AccountFiles/AttemptPasswordLogin.php', { submit: '1', userID: 'claudebot1', password: 'pass' });
const cookies = (login.headers.getSetCookie?.() || []).map(c => c.split(';')[0]);
const cookie = cookies.join('; ');
async function seed(schema) {
  const json = JSON.parse(await (await post('SWUSim/TestSchemaSetup.php', { schema }, cookie)).text());
  if (json.error) throw new Error(`TestSchemaSetup refused: ${json.error}`);
  for (const st of json.whenSteps || []) await post('SWUSim/TestSchemaStep.php', { gameName: json.gameName, step: st.raw }, cookie);
  return String(json.gameName);
}
const TS3 = fs.readFileSync(new URL('../../SWUSim/Tests/Visual/TwinSuns_3P_FullArenas.md', import.meta.url), 'utf8');
const url = (g, seat, extra = '') => `${BASE}NextTurn.php?folderPath=SWUSim&gameName=${g}&playerID=${seat}&authKey=testschema${extra}`;
const FIRST_GROUND = { 1: 'SOR_032', 2: 'SOR_034', 3: 'SOR_036' };
// [top-left, top-right, bottom] per viewer — the owner's seating (opponents clockwise across the top).
const SEATING3 = { 1: [2, 3, 1], 2: [3, 1, 2], 3: [1, 2, 3] };
const SIZES = [{ width: 1600, height: 1000 }, { width: 1920, height: 1080 }, { width: 1366, height: 768 }];

let fails = 0, checks = 0;
const check = (ok, name, msg) => { checks++; if (!ok) { fails++; console.log(`FAIL ${name} :: ${msg}`); } };
const ready = (p) => p.waitForFunction(() => document.querySelectorAll('[id*="GroundArena-"]').length >= 6, null, { timeout: 60000 }).catch(() => {});
async function open(b, g, seat, vp, extra = '', ctxOpts = {}) {
  const ctx = await b.newContext({ viewport: vp, ...ctxOpts });
  await ctx.addCookies(cookies.map(c => { const i = c.indexOf('='); return { name: c.slice(0, i), value: c.slice(i + 1), url: BASE }; }));
  const p = await ctx.newPage(); const errs = []; p.on('pageerror', e => errs.push(e.message));
  await p.goto(url(g, seat, extra), { waitUntil: 'domcontentloaded' }); await ready(p); await p.waitForTimeout(1000);
  return { ctx, p, errs };
}

function measure3(p) {
  return p.evaluate(() => {
    const r = (e) => { if (!e) return null; const b = e.getBoundingClientRect(); return { x: b.left, y: b.top, w: b.width, h: b.height, r: b.right, b: b.bottom, cx: b.left + b.width / 2, cy: b.top + b.height / 2 }; };
    const vis = (e) => !!e && e.getBoundingClientRect().width > 0 && getComputedStyle(e).display !== 'none';
    const cfg = window.SWUTableLayout || null; const rt = cfg && cfg.seating ? cfg.seating.right.top : 0;
    const art = (slot) => { const img = slot.querySelector('img[src*="/concat/"]'); const mm = img && /\/concat\/([A-Z0-9]+_[0-9A-Z]+)/.exec(img.getAttribute('src')); return mm ? mm[1] : ''; };
    const grounds = [...document.querySelectorAll('[id$="GroundArenaSlot"]')].filter(vis).map(s => ({ id: s.id, ...r(s), art: art(s) }));
    const $ = (id) => document.getElementById(id);
    const sb = $('swuSidebar'); const foot = document.querySelector('#swuTableLeft > [data-trow="footer"]');
    return { cfg, W: document.documentElement.clientWidth, H: window.innerHeight, sbW: sb ? sb.getBoundingClientRect().width : 0,
      hscroll: document.documentElement.scrollWidth > document.documentElement.clientWidth + 1, cardSize: window.cardSize, grounds,
      myArenas: r(document.querySelector('#swuTableLeft > [data-trow="arena-bot"]')), mySpace: r($('mySpaceArenaSlot')), myGround: r($('myGroundArenaSlot')),
      t1Space: r($('theirSpaceArenaSlot')), t1Ground: r($('theirGroundArenaSlot')), t2Space: r($('p' + rt + 'SpaceArenaSlot')), t2Ground: r($('p' + rt + 'GroundArenaSlot')),
      t1Base: r($('theirBaseSlot')), t1Leader: r($('theirLeaderSlot')), t2Base: r($('p' + rt + 'BaseSlot')), t2Leader: r($('p' + rt + 'LeaderSlot')),
      myBase: r($('myBaseSlot')), myL0: r($('myLeader-0')), myL1: r($('myLeader-1')),
      bandL: r($('swuTheirControlBand')), bandR: r(document.querySelector('#swuTableRight > [data-trow="band"]')),
      arenaL: r(document.querySelector('#swuTableLeft > [data-trow="arena-top"]')), arenaR: r(document.querySelector('#swuTableRight > [data-trow="arena-top"]')),
      footerB: foot ? Math.round(foot.getBoundingClientRect().bottom) : -1, allInView: !!foot && foot.getBoundingClientRect().bottom <= window.innerHeight + 1,
      homeVisible: vis($('swuHomeStrips')) || vis($('swuPairPrev')) || vis($('swuGoBack')) };
  });
}

for (const [engine, launcher] of [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]].filter(([e]) => ENGINES.includes(e))) {
  let b;
  try { b = await launcher.launch(); } catch (e) { check(false, engine, `could not launch: ${e.message}`); continue; }
  const g = await seed(TS3);

  // ── Task 8: them 2 alone in the right column ──
  if (SECTIONS.includes('right')) for (const seat of [1, 2, 3]) {
    const name = `${engine}/right/P${seat}`;
    const { ctx, p, errs } = await open(b, g, seat, SIZES[0]);
    const r = await p.evaluate(() => ({ kind: window.SWUTableLayout && window.SWUTableLayout.kind,
      p0: [...document.querySelectorAll('[id^="p0"]')].map(e => e.id).slice(0, 5),
      rightBoards: [...document.querySelectorAll('#swuTableRight [data-trow^="arena"]')].length }));
    check(r.kind === 'table3', name, `a 3-seat game is the 3P table: ${r.kind}`);
    check(r.p0.length === 0, name, `no seat-0 elements: ${JSON.stringify(r.p0)}`);
    check(r.rightBoards === 1, name, `the right column holds ONE board (them 2): ${r.rightBoards}`);
    check(errs.length === 0, name, `no page errors: ${errs.slice(0, 2).join(' | ')}`);
    await ctx.close();
  }
  // ── Task 9: the 3P layout ──
  if (SECTIONS.includes('layout')) for (const vp of SIZES) for (const seat of [1, 2, 3]) {
    const name = `${engine}/layout/${vp.width}x${vp.height}/P${seat}`;
    const { ctx, p, errs } = await open(b, g, seat, vp);
    const m = await measure3(p);
    check(!!m.cfg && m.cfg.kind === 'table3', name, `table3: ${JSON.stringify(m.cfg && m.cfg.kind)}`);
    const [tl, tr, me] = SEATING3[seat].map(s => FIRST_GROUND[s]);
    const gs = m.grounds.slice().sort((a, c) => a.cy - c.cy);
    const bottom = gs[gs.length - 1], tops = gs.slice(0, -1).sort((a, c) => a.cx - c.cx);
    check(m.grounds.length === 3 && tops.length === 2 && tops[0].art === tl && tops[1].art === tr && bottom.art === me, name,
      `seating by art [top-left, top-right, bottom]: want ${[tl, tr, me]} got ${[tops[0] && tops[0].art, tops[1] && tops[1].art, bottom && bottom.art]}`);
    const W = m.W - m.sbW, mid = W / 2;
    check(Math.abs(m.myArenas.x) <= 2 && Math.abs(m.myArenas.r - W) <= 2, name, `your arenas span the whole table: ${JSON.stringify([m.myArenas.x, m.myArenas.r])} of ${W}`);
    check(m.mySpace.cx < mid && m.myGround.cx > mid, name, `your space LEFT, ground RIGHT: ${[m.mySpace.cx, m.myGround.cx]} vs seam ${mid}`);
    check(m.t1Space.cx < m.t1Ground.cx && m.t1Ground.r <= mid + 1, name, 'them 1 (top-left): space outside, ground inside');
    check(m.t2Ground.cx < m.t2Space.cx && m.t2Ground.x >= mid - 1, name, 'them 2 (top-right): ground inside, space outside');
    check(m.t1Base.cx > m.t1Leader.cx && m.t2Base.cx < m.t2Leader.cx, name, 'opponents\' bases on the inside corners, leaders outward');
    check(Math.abs(m.myBase.cx - mid) <= 4, name, `your base centred under the seam: ${m.myBase.cx.toFixed(1)} vs ${mid}`);
    check(!!m.myL0 && !!m.myL1 && m.myL0.r <= m.myBase.x + 1 && m.myL1.x >= m.myBase.r - 1, name, `one leader each side of your base: ${JSON.stringify([m.myL0 && m.myL0.r, m.myBase.x, m.myBase.r, m.myL1 && m.myL1.x])}`);
    check(m.myBase.y > m.t1Base.b - 1, name, 'your leader/base line sits below the opponents\' line');
    check(Math.abs(m.bandL.y - m.bandR.y) <= 1 && Math.abs(m.bandL.h - m.bandR.h) <= 1 && Math.abs(m.arenaL.y - m.arenaR.y) <= 1 && Math.abs(m.arenaL.h - m.arenaR.h) <= 1,
      name, `the top rows line up across both columns: bands ${[m.bandL.y, m.bandR.y]} arenas ${[m.arenaL.y, m.arenaR.y, m.arenaL.h, m.arenaR.h]}`);
    const want = Math.max(36, Math.min(80, Math.floor(((vp.width - m.sbW) / 2 - 52) / 7)));
    check(m.cardSize === want, name, `card size ${m.cardSize} = ${want} (same as the 4-place table)`);
    check(!m.hscroll, name, 'no horizontal page scroll');
    check(!m.homeVisible, name, 'no Home strip / carousel');
    check(m.allInView, name, `every row fits the screen (footer bottom ${m.footerB} <= ${m.H})`);
    check(errs.length === 0, name, `no page errors: ${errs.slice(0, 2).join(' | ')}`);
    if (SHOTS && seat === 1) await p.screenshot({ path: `${SHOTS}/table3-${vp.width}-${engine}.png` });
    await ctx.close();
  }
  // Overflow: them 2's ground fills up at 1366x768 — it scrolls in its box, no row moves.
  if (SECTIONS.includes('layout')) {
    const name = `${engine}/layout/overflow`;
    const extra = Array.from({ length: 10 }, () => 'SOR_095:1:0').join(' ');
    const g2 = await seed(TS3.replace('WithP3GroundArena: [SOR_036:1:0 SOR_037:1:2]', `WithP3GroundArena: [SOR_036:1:0 SOR_037:1:2 ${extra}]`));
    const { ctx, p } = await open(b, g2, 1, SIZES[2]);
    const before = await measure3(p);
    const sc = await p.evaluate(() => { const e = document.getElementById('p3GroundArenaSlot'); const oy = getComputedStyle(e).overflowY; const over = e.scrollHeight > e.clientHeight + 1; e.scrollTop = 40; return { oy, over, top: e.scrollTop }; });
    check(sc.over && (sc.oy === 'auto' || sc.oy === 'scroll') && sc.top > 0, name, `them 2's full ground arena scrolls inside its box: ${JSON.stringify(sc)}`);
    check(before.allInView && Math.abs(before.myArenas.r - (before.W - before.sbW)) <= 2, name, 'the rows still fit and your arenas still span the table');
    if (SHOTS) await p.screenshot({ path: `${SHOTS}/table3-overflow-${engine}.png` });
    await ctx.close();
  }
  // Narrow DESKTOP window (no phone UA): them 1, them 2, then you — stacked.
  if (SECTIONS.includes('narrow')) {
    const name = `${engine}/narrow`;
    const { ctx, p, errs } = await open(b, g, 1, { width: 800, height: 900 });
    const m = await measure3(p);
    const ys = [m.t1Ground, m.t2Ground, m.myGround].map(x => x.cy);
    check(ys[0] < ys[1] && ys[1] < ys[2], name, `stacked them 1, them 2, you: ${ys.map(Math.round)}`);
    check(!m.hscroll, name, 'no horizontal page scroll');
    // Final review: the log/chat sidebar (Undo, gear, chat) ends the page at FULL width — not squeezed into one grid column.
    // W = the ROOT scroller's content width: WebKit keeps a 12px scrollbar on it, so the page's width would over-ask.
    const sbw = await p.evaluate(() => { const e = document.getElementById('swuSidebar'); const r = e.getBoundingClientRect(); return { x: Math.round(r.left), w: Math.round(r.width), W: document.getElementById('swuMobileRoot').clientWidth }; });
    check(sbw.x <= 1 && sbw.w >= sbw.W - 2, name, `the sidebar spans the window: ${JSON.stringify(sbw)}`);
    check(errs.length === 0, name, `no page errors: ${errs.slice(0, 2).join(' | ')}`);
    // The ROOT scrolls on a narrow window (not the document), so a full-page capture is empty: two viewport shots instead.
    if (SHOTS) {
      await p.screenshot({ path: `${SHOTS}/table3-narrow-top-${engine}.png` });
      await p.evaluate(() => { const r = document.getElementById('swuMobileRoot'); r.scrollTop = r.scrollHeight * 0.45; });
      await p.waitForTimeout(300);
      await p.screenshot({ path: `${SHOTS}/table3-narrow-mid-${engine}.png` });
      await p.evaluate(() => { const r = document.getElementById('swuMobileRoot'); r.scrollTop = r.scrollHeight; });
      await p.waitForTimeout(300);
      await p.screenshot({ path: `${SHOTS}/table3-narrow-bottom-${engine}.png` });
    }
    await ctx.close();
  }
  // ── Task 10: 3P behaviour ──
  const lit = (p) => p.evaluate(() => [...document.querySelectorAll('#swuTableRight .swu-table-target, #swuTableRight .swu-table-subhost')].map(e => e.id));
  const waitLit = (p) => p.waitForFunction(() => document.querySelectorAll('#swuTableRight .swu-table-target').length > 0, null, { timeout: 8000 }).then(() => true).catch(() => false);
  const logText = (p) => p.evaluate(() => (document.getElementById('swuLogPanel') || {}).innerText || '');
  if (SECTIONS.includes('targets')) {
    { // Attack from your board into them 2 (P3 for seat 1, the right column).
      const name = `${engine}/targets/attack`;
      const g3 = await seed(TS3);
      const { ctx, p, errs } = await open(b, g3, 1, SIZES[0]);
      await p.click('#myGroundArena-0');
      check(await waitLit(p), name, "starting an attack lights them 2's targets in the right column");
      const t = await lit(p);
      check(t.length > 0 && t.every(id => /^p3/.test(id)), name, `only P3's cards are lit there: ${t}`);
      const unit = t.find(id => /GroundArena-\d+$/.test(id)) || t[0];
      const before = await logText(p);
      if (unit) await p.click('#' + unit);
      const ok = await p.waitForFunction((b4) => { const x = (document.getElementById('swuLogPanel') || {}).innerText || ''; return x !== b4 && /attacked P3/.test(x); }, before, { timeout: 15000 }).then(() => true).catch(() => false);
      check(ok, name, `clicking ${unit} attacks it (log: "attacked P3")`);
      check((await lit(p)).length === 0, name, 'nothing stays lit');
      check(errs.length === 0, name, `no page errors: ${errs.slice(0, 2)}`);
      if (SHOTS) await p.screenshot({ path: `${SHOTS}/table3-attack-${engine}.png` });
      await ctx.close();
    }
    { // Them 2's bar: name, turn glow, and the initiative token when they hold it.
      const name = `${engine}/targets/bar`;
      const g3 = await seed(TS3.replace('WithActivePlayer: 1', 'WithActivePlayer: 3\nWithInitiativePlayer: 3'));
      const { ctx, p } = await open(b, g3, 1, SIZES[0]);
      await p.waitForTimeout(800);
      const st = await p.evaluate(() => { const bar = document.querySelector('#swuTableRight .swu-tbar[data-seat="3"]');
        return { bar: !!bar, name: bar ? (bar.querySelector('.swu-tbl-name') || {}).textContent : '', turn: !!bar && bar.classList.contains('is-turn'),
          init: !!bar && !!bar.querySelector('#swuInitControl'), bars: document.querySelectorAll('#swuTableRight .swu-tbar').length }; });
      check(st.bar && st.bars === 1, name, `them 2 has exactly one bar: ${JSON.stringify(st)}`);
      check(/3/.test(st.name || ''), name, `the bar names seat 3: "${st.name}"`);
      check(st.turn, name, "it glows on them 2's turn");
      check(st.init, name, 'the initiative token sits on their bar');
      await ctx.close();
    }
    { // Them 2 eliminated: greyed in place, nothing of theirs is offered.
      const name = `${engine}/targets/defeated-seat`;
      const g3 = await seed(TS3.replace('WithSeatOrder: 123', 'WithSeatOrder: 123\nWithLiveSeats: 12'));
      const { ctx, p } = await open(b, g3, 1, SIZES[0]);
      const grey = await p.evaluate(() => [...document.querySelectorAll('#swuTableRight [data-seat="3"]')].some(e => e.classList.contains('swu-tbl-defeated')));
      check(grey, name, "them 2's board is greyed in place");
      await p.click('#myGroundArena-0');
      await p.waitForTimeout(1500);
      check((await lit(p)).length === 0, name, `nothing on the defeated seat is lit: ${await lit(p)}`);
      check(await p.evaluate(() => window.SWUTableLayout.kind === 'table3'), name, 'the table keeps its three places');
      await ctx.close();
    }
  }
  if (SECTIONS.includes('endgame')) {
    { // You eliminated: the table stays, your board greyed, read-only.
      const name = `${engine}/endgame/eliminated`;
      const g3 = await seed(TS3.replace('WithSeatOrder: 123', 'WithSeatOrder: 123\nWithLiveSeats: 23'));
      const { ctx, p } = await open(b, g3, 1, SIZES[0]);
      const st = await p.evaluate(() => ({ kind: window.SWUTableLayout && window.SWUTableLayout.kind, greyed: document.body.classList.contains('swu-self-defeated') }));
      check(st.kind === 'table3' && st.greyed, name, `table kept, your board greyed: ${JSON.stringify(st)}`);
      // Final review: your two leaders are lifted out of #myLeaderSlot in 3P (display:contents), so the slot's own grey-out filter
      // draws nothing — the leader CARDS must carry it.
      const lf = await p.evaluate(() => ['myLeader-0', 'myLeader-1'].map(id => { const e = document.getElementById(id); return e ? getComputedStyle(e).filter : 'missing'; }));
      check(lf.every(f => /grayscale/.test(f)), name, `both of your leaders are greyed too: ${JSON.stringify(lf)}`);
      if (SHOTS) await p.screenshot({ path: `${SHOTS}/table3-eliminated-${engine}.png` });
      await ctx.close();
    }
    { // A spectator who picked seat 2 sees seat 2's table.
      const name = `${engine}/endgame/spectator`;
      const ctx = await b.newContext({ viewport: SIZES[0] }); const p = await ctx.newPage();
      await p.goto(`${BASE}NextTurn.php?folderPath=SWUSim&gameName=${g}&playerID=S&viewerPerspective=2`, { waitUntil: 'domcontentloaded' });
      await ready(p); await p.waitForTimeout(1200);
      const m = await measure3(p);
      const gs = m.grounds.slice().sort((a, c) => a.cy - c.cy); const bottom = gs[gs.length - 1], tops = gs.slice(0, -1).sort((a, c) => a.cx - c.cx);
      const want = SEATING3[2].map(s => FIRST_GROUND[s]);
      check(m.cfg && m.cfg.kind === 'table3' && tops.length === 2 && tops[0].art === want[0] && tops[1].art === want[1] && bottom.art === want[2], name,
        `seat 2's table: want ${want} got ${[tops[0] && tops[0].art, tops[1] && tops[1].art, bottom && bottom.art]}`);
      await ctx.close();
    }
    { // The game ends once: P2 already out, P1 takes P3's last HP.
      const name = `${engine}/endgame/gameover`;
      const g3 = await seed(TS3.replace('WithSeatOrder: 123', 'WithSeatOrder: 123\nWithLiveSeats: 13').replace('WithP3Base: SOR_026:5', 'WithP3Base: SOR_026:29')
        .replace('WithP3GroundArena: [SOR_036:1:0 SOR_037:1:2]', 'WithP3GroundArena: [SOR_036:1:0]'));   // two targets, so the base is a real pick
      const { ctx, p } = await open(b, g3, 1, SIZES[0]);
      await p.click('#myGroundArena-0');
      await waitLit(p);
      const base = (await lit(p)).find(id => /^p3Base-/.test(id));
      check(!!base, name, `P3's base is offered: ${await lit(p)}`);
      // Move off the attacker first: its hover preview (#cardDetail) is still up where the mouse rests and covers this base.
      await p.mouse.move(5, 5); await p.waitForTimeout(300);
      if (base) await p.click('#' + base);
      const over = await p.waitForFunction(() => !!document.getElementById('game-over-overlay'), null, { timeout: 20000 }).then(() => true).catch(() => false);
      await p.waitForTimeout(2500);
      const n = await p.evaluate(() => document.querySelectorAll('#game-over-overlay').length);
      check(over && n === 1, name, `one game-over overlay: ${n}`);
      if (SHOTS) await p.screenshot({ path: `${SHOTS}/table3-gameover-${engine}.png` });
      await ctx.close();
    }
  }
  // ── Task 11: the "Home Panels Layout" toggle ──
  if (SECTIONS.includes('toggle')) {
    const name = `${engine}/toggle`;
    const api = (p, body) => p.evaluate(async ([base, body]) => { const r = await fetch(base + 'SWUSim/PlayerSettingsApi.php', { method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams(body).toString() }); return r.json(); }, [BASE, body]);
    const flip = async (p, on) => { await Promise.all([p.waitForNavigation({ timeout: 20000 }).catch(() => {}), p.evaluate((on) => {
      const el = document.getElementById('swuSetHomePanels'); el.checked = on; el.dispatchEvent(new Event('change', { bubbles: true })); }, on)]);
      await ready(p); await p.waitForTimeout(1200); };
    const state = (p) => p.evaluate(() => { const row = document.getElementById('swuSetHomePanels'); const hs = document.getElementById('swuHomeStrips');
      return { row: !!row, checked: !!row && row.checked, table: !!document.getElementById('swuTableRight'), kind: (window.SWUTableLayout || {}).kind || '',
        strips: !!hs && getComputedStyle(hs).display !== 'none', choosing: !!(window.SelectionMode && window.SelectionMode.active) }; });
    const g3 = await seed(TS3);
    const { ctx, p, errs } = await open(b, g3, 1, SIZES[0]);
    await api(p, { action: 'setHomePanels', on: '0' });
    await ctx.clearCookies({ name: 'swuHomePanels' }).catch(() => {});
    await p.reload({ waitUntil: 'domcontentloaded' }); await ready(p); await p.waitForTimeout(1000);
    let st = await state(p);
    check(st.row && !st.checked && st.table, name, `3P table: the gear menu has "Home Panels Layout", unchecked: ${JSON.stringify(st)}`);
    const rowText = await p.evaluate(() => (document.getElementById('swuSetHomePanels') || { closest: () => null }).closest('label')?.textContent.trim());
    check(rowText === 'Home Panels Layout', name, `the row reads "Home Panels Layout": "${rowText}"`);
    // An open choice survives the flip (the reload shows it again).
    await p.click('#myGroundArena-0'); await p.waitForTimeout(1500);
    const chooseBefore = (await state(p)).choosing;
    await flip(p, true);
    st = await state(p);
    check(!st.table && st.kind === '' && st.strips, name, `ticked: the board reloads into the Home panels: ${JSON.stringify(st)}`);
    check(st.row && st.checked, name, 'the row is still there (checked) to switch back');
    check(!chooseBefore || st.choosing, name, `the open choice is still open after the flip (before: ${chooseBefore}, after: ${st.choosing})`);
    const ck = (await ctx.cookies()).find(c => c.name === 'swuHomePanels');
    check(!!ck && ck.value === '1', name, `the browser layer: cookie swuHomePanels=1 (${ck && ck.value})`);
    check((await api(p, { action: 'get' })).homePanels === 1, name, 'logged in: the account follows (homePanels 1)');
    await flip(p, false);
    st = await state(p);
    check(st.table && st.kind === 'table3' && !st.checked, name, `unticked: back to the table: ${JSON.stringify(st)}`);
    check((await api(p, { action: 'get' })).homePanels === 0, name, 'the account follows back (homePanels 0)');
    check(errs.length === 0, name, `no page errors: ${errs.slice(0, 2).join(' | ')}`);
    // Profile → Game Settings shows the same setting and saves it.
    await p.goto(BASE + 'SharedUI/Sites/SWUSim/Profile.php', { waitUntil: 'domcontentloaded' });
    const prof = await p.evaluate(() => { const el = document.getElementById('profileHomePanels'); return el ? { checked: el.checked, text: el.closest('label').textContent.trim() } : null; });
    check(!!prof && prof.text === 'Home Panels Layout' && !prof.checked, name, `Profile has the row, unchecked: ${JSON.stringify(prof)}`);
    if (prof) {
      await p.evaluate(() => { const el = document.getElementById('profileHomePanels'); el.checked = true; el.dispatchEvent(new Event('change', { bubbles: true })); });
      const saved = await p.waitForFunction(() => /Saved/.test((document.getElementById('profileHomePanelsStatus') || {}).textContent || ''), null, { timeout: 10000 }).then(() => true).catch(() => false);
      check(saved && (await api(p, { action: 'get' })).homePanels === 1, name, 'the Profile change saves to the account');
      if (SHOTS) await p.screenshot({ path: `${SHOTS}/table3-profile-${engine}.png` });
      await api(p, { action: 'setHomePanels', on: '0' });
      await ctx.clearCookies({ name: 'swuHomePanels' }).catch(() => {});
    }
    await ctx.close();
    // The row is on a 4-place table too, and absent where there is nothing to switch (two players).
    const g4 = await seed(fs.readFileSync(new URL('../../SWUSim/Tests/Visual/TwinSuns_4P_AllZonesFilled.md', import.meta.url), 'utf8'));
    const o4 = await open(b, g4, 1, SIZES[0]);
    check((await state(o4.p)).row, `${name}/4p`, 'a 4-place table has the row');
    await o4.ctx.close();
    const g2 = await seed(fs.readFileSync(new URL('../../SWUSim/Tests/Visual/BaseHealthBar_1v1.md', import.meta.url), 'utf8'));
    const ctx2 = await b.newContext({ viewport: SIZES[0] }); const p2 = await ctx2.newPage();
    await p2.goto(url(g2, 1), { waitUntil: 'domcontentloaded' }); await p2.waitForTimeout(2500);
    check(!(await state(p2)).row, `${name}/2p`, 'a two-player board has no such row');
    await ctx2.close();
  }
  await b.close();
}
console.log(fails === 0 ? `PASS (${checks} checks)` : `${fails} FAILED of ${checks}`);
process.exit(fails === 0 ? 0 : 1);
