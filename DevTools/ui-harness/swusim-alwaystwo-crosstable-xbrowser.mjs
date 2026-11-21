// 1v1 Always Two — crossing the 2×2 table (docs/superpowers/specs/2026-11-20-swusim-always-two-design.md, "The 2×2 table" → "Crossing
// the table"). Seat 1's board 1 (left column, facing P4) and board 3 (right column, facing P2) are two separate board views; a choice
// open on one of them may have answers drawn in the OTHER column. The table (SWUSim/AlwaysTwoTable.php) must light those cards there
// (class a2-cross-target, or a2-cross-subhost for a unit carrying an offered upgrade) and pass a click on them to the choosing board.
// Fixtures: SWUSim/Tests/Visual/AlwaysTwo_1v1CrossTable.md — each section is seeded as its own game.
// Checked in Chromium, Firefox and WebKit (CLAUDE.md). ENGINES=chromium narrows a quick iteration.
//   node DevTools/ui-harness/swusim-alwaystwo-crosstable-xbrowser.mjs          (claudebot1 must be a mod)
import fs from 'fs';
import { chromium, firefox, webkit } from 'playwright';

const BASE = process.env.BASE_URL || 'http://localhost:3400/TCGEngine/';
const SHOTS = process.env.SHOTS || '';
const ENGINES = (process.env.ENGINES || 'chromium,firefox,webkit').split(',');
const form = (o) => new URLSearchParams(o).toString();
const post = async (path, body, cookie) => fetch(BASE + path, { method: 'POST',
  headers: { 'Content-Type': 'application/x-www-form-urlencoded', ...(cookie ? { cookie } : {}) }, body: form(body), redirect: 'manual' });
const login = await post('AccountFiles/AttemptPasswordLogin.php', { submit: '1', userID: 'claudebot1', password: 'pass' });
const cookie = (login.headers.getSetCookie?.() || []).map(c => c.split(';')[0]).join('; ');
const sections = fs.readFileSync(new URL('../../SWUSim/Tests/Visual/AlwaysTwo_1v1CrossTable.md', import.meta.url), 'utf8').split(/\n---\n/);
const byName = {};
for (const sec of sections) { const m = /^# (Cross_\w+)/m.exec(sec); if (m) byName[m[1]] = sec; }
async function seed(name) {
  const json = JSON.parse(await (await post('SWUSim/TestSchemaSetup.php', { schema: byName[name] }, cookie)).text());
  if (json.error) throw new Error(`TestSchemaSetup refused ${name}: ${json.error}`);
  // Setup seeds the GIVEN only; run the WHEN lines so the choice is actually open in the browser.
  for (const st of json.whenSteps || []) {
    const r = JSON.parse(await (await post('SWUSim/TestSchemaStep.php', { gameName: json.gameName, step: st.raw }, cookie)).text());
    if (r.error) throw new Error(`TestSchemaStep refused "${st.raw}" for ${name}: ${r.error}`);
  }
  return String(json.gameName);
}

let fails = 0, checks = 0;
const check = (ok, name, msg) => { checks++; if (!ok) { fails++; console.log(`FAIL ${name} :: ${msg}`); } };
const url = (g) => `${BASE}NextTurn.php?folderPath=SWUSim&gameName=${g}&playerID=1&authKey=testschema`;
const sleep = (ms) => new Promise(r => setTimeout(r, ms));

// Open the seat-1 table on a freshly seeded game; return the page and the two halves once the choosing half has its choice open.
async function openTable(b, viewport, scenario, chooserBoard) {
  const game = await seed(scenario);   // a fresh game every time: answering a choice changes the game
  const p = await b.newPage({ viewport });
  await p.goto(url(game), { waitUntil: 'domcontentloaded' });
  await p.waitForSelector('#a2Board1', { timeout: 60000 });
  const L = await (await p.$('#a2Board1')).contentFrame(), R = await (await p.$('#a2Board3')).contentFrame();
  const chooser = chooserBoard === 1 ? L : R;
  await chooser.waitForFunction(() => !!(window.SelectionMode && window.SelectionMode.active), null, { timeout: 90000 }).catch(() => {});
  await (chooserBoard === 1 ? R : L).waitForFunction(() => document.querySelectorAll('#swuMobileRoot [id$="-0"]').length > 0, null, { timeout: 90000 }).catch(() => {});
  // Precondition, so nothing below passes vacuously: the choosing board's choice IS open in its half.
  check(await chooser.evaluate(() => !!(window.SelectionMode && window.SelectionMode.active)), `${scenario}/board${chooserBoard}`, 'precondition: the choice is open in the choosing half');
  return { p, L, R, chooser, other: chooserBoard === 1 ? R : L };
}
const lit = (f, cls = 'a2-cross-target') => f.evaluate((c) => [...document.querySelectorAll('.' + c)].map(e => e.id).sort(), cls);
const waitLit = async (f, ids, cls = 'a2-cross-target', ms = 5000) => {
  const t0 = Date.now(); let got = [];
  while (Date.now() - t0 < ms) { got = await lit(f, cls); if (ids.every(i => got.includes(i))) return got; await sleep(250); }
  return got;
};
const choiceOpen = (f) => f.evaluate(() => !!(window.SelectionMode && window.SelectionMode.active));
const waitChoiceClosed = async (f, ms = 20000) => {
  const t0 = Date.now(); while (Date.now() - t0 < ms) { if (!(await choiceOpen(f))) return true; await sleep(300); } return false;
};
const clickIn = (f, id) => f.evaluate((i) => { const e = document.getElementById(i); const t = e && (e.querySelector('img') || e); if (t) t.click(); return !!t; }, id);

for (const [engine, launcher] of [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]].filter(([e]) => ENGINES.includes(e))) {
  let b;
  try { b = await launcher.launch(); } catch (e) { check(false, engine, `could not launch: ${e.message}`); continue; }
  const desk = { width: 1600, height: 1000 };

  // (a) Board 1 (left) attacks: P2's unit AND P2's 0-HP base light up in the RIGHT column; a click there resolves board 1's attack.
  {
    const name = `${engine}/board1-attacks-into-P2`;
    const t = await openTable(b, desk, 'Cross_Board1AttacksIntoP2', 1);
    const got = await waitLit(t.R, ['theirGroundArena-0', 'theirBase-0']);
    check(got.includes('theirGroundArena-0') && got.includes('theirBase-0'), name, `P2's unit and 0-HP base are lit in the right column: ${got}`);
    check((await lit(t.L)).length === 0, name, 'nothing in the chooser\'s own column is marked as a cross-table target (it lights its own)');
    // The glow survives a repaint of the other half (its poll redraws every card) and comes back within a second.
    await t.R.evaluate(() => { if (window.swuLastResponseArr && typeof RenderUpdate === 'function') RenderUpdate(window.swuLastResponseArr, window.__lastRenderedGameUpdate || 0); });
    const again = await waitLit(t.R, ['theirGroundArena-0'], 'a2-cross-target', 1500);
    check(again.includes('theirGroundArena-0'), name, `the glow comes back within a second of the other half repainting: ${again}`);
    // Own column wins: while the right half has its OWN choice open on a card, that card is its own, not a cross-table target.
    const own = await t.R.evaluate(async () => {
      const el = document.getElementById('theirGroundArena-0'); const sm = window.SelectionMode;
      window.SelectionMode = Object.assign({}, sm || {}, { active: true }); el.classList.add('selectable-card');
      await new Promise(r => setTimeout(r, 1500));
      const marked = el.classList.contains('a2-cross-target');
      el.classList.remove('selectable-card'); window.SelectionMode = sm; return marked;
    });
    check(!own, name, 'a card legal for its own column\'s open choice is left to that column');
    await waitLit(t.R, ['theirGroundArena-0']);              // the fake is over: the card is board 1's answer again
    if (SHOTS) await t.p.screenshot({ path: `${SHOTS}/cross-${engine}-a.png` });
    check(await clickIn(t.R, 'theirGroundArena-0'), name, 'clicked P2\'s unit in the right column');
    check(await waitChoiceClosed(t.L), name, 'board 1\'s attack resolved from a click in the other column');
    await sleep(3000);
    check((await lit(t.R)).length === 0, name, 'the cross-table glow clears when the choice ends');
    const gone = await t.R.evaluate(() => !document.querySelector('#theirGroundArena-0 img'));
    check(gone, name, 'the attack happened: P2\'s 3/3 Marine traded with board 1\'s 3/3 Marine');
    await t.p.close();
  }
  // (b) Board 3 (right) attacks into P4 (left column).
  {
    const name = `${engine}/board3-attacks-into-P4`;
    const t = await openTable(b, desk, 'Cross_Board3AttacksIntoP4', 3);
    const got = await waitLit(t.L, ['theirGroundArena-0']);
    check(got.includes('theirGroundArena-0') && got.includes('theirBase-0'), name, `P4's unit and base are lit in the left column: ${got}`);
    check(await clickIn(t.L, 'theirGroundArena-0') && await waitChoiceClosed(t.R), name, 'board 3\'s attack resolved from a click in the left column');
    await t.p.close();
  }
  // (c) Board 1 pays a "friendly unit" cost with teammate board 3's unit (right column, that half's own side).
  {
    const name = `${engine}/board1-pays-with-board3s-unit`;
    const t = await openTable(b, desk, 'Cross_Board1PaysWithBoard3sUnit', 1);
    const got = await waitLit(t.R, ['myGroundArena-0']);
    check(got.includes('myGroundArena-0'), name, `board 3's unit is lit in the right column: ${got}`);
    check(await clickIn(t.R, 'myGroundArena-0') && await waitChoiceClosed(t.L), name, 'board 1\'s cost paid with board 3\'s unit');
    await t.p.close();
  }
  // (d) "Up to 3" across both columns: one pick in each column accumulates in board 1's counter; Confirm submits both.
  {
    const name = `${engine}/up-to-three-across-columns`;
    const t = await openTable(b, desk, 'Cross_UpToThreeAcrossBothColumns', 1);
    await waitLit(t.R, ['theirGroundArena-0']);
    await clickIn(t.L, 'theirGroundArena-0');                 // P4's unit, in board 1's own column
    await sleep(500);
    await clickIn(t.R, 'theirGroundArena-0');                 // P2's unit, across the table
    await sleep(500);
    const picked = await t.L.evaluate(() => (window.SelectionMode && window.SelectionMode.multiSelected || []).slice().sort());
    check(picked.length === 2 && picked.some(m => /^p2GroundArena-0$/.test(m)), name, `both picks count toward board 1's "up to 3": ${JSON.stringify(picked)}`);
    await t.L.evaluate(() => { const c = document.getElementById('inline-multi-confirm'); if (c) c.click(); });
    check(await waitChoiceClosed(t.L), name, 'Confirm submits picks from both columns');
    await t.p.close();
  }
  // (e) An offered upgrade on a unit in the other column: the host is marked, and clicking it defeats that upgrade.
  {
    const name = `${engine}/upgrade-on-board3s-unit`;
    const t = await openTable(b, desk, 'Cross_UpgradeOnBoard3sUnit', 1);
    const got = await waitLit(t.R, ['myGroundArena-0'], 'a2-cross-subhost');
    check(got.includes('myGroundArena-0'), name, `board 3's unit, carrying the offered upgrade, is marked: ${got}`);
    check(await clickIn(t.R, 'myGroundArena-0') && await waitChoiceClosed(t.L), name, 'clicking it answers with the upgrade (the only one offered)');
    await t.p.close();
  }
  // Arena scroll: a lit target scrolled out of its arena is brought into view.
  {
    const name = `${engine}/target-scrolled-into-view`;
    const t = await openTable(b, desk, 'Cross_Board1AttacksIntoP2', 1);
    await waitLit(t.R, ['theirGroundArena-0']);
    const vis = await t.R.evaluate(async () => {
      const g = document.getElementById('theirGroundArena'), slot = document.getElementById('theirGroundArenaSlot');
      const first = document.getElementById('theirGroundArena-0');
      for (let i = 0; i < 14; i++) { const c = first.cloneNode(true); c.id = 'harnessFiller-' + i; c.classList.remove('a2-cross-target'); g.appendChild(c); }
      g.appendChild(first);                                   // the real target now sits below 14 fillers
      slot.scrollTop = 0;
      window.parent.__a2ChoiceKey = '';                       // the table re-runs its "new choice" pass (scroll to the first lit card)
      await new Promise(r => setTimeout(r, 1800));
      const a = first.getBoundingClientRect(), s = slot.getBoundingClientRect();
      return a.top >= s.top - 1 && a.bottom <= s.bottom + 1;
    });
    check(vis, name, 'the arena scrolled to show the first lit card');
    await t.p.close();
  }
  // Phone: the two columns stack; with board 1's choice open and targets in board 3's column off-screen below, a chip points there.
  {
    const name = `${engine}/phone-chip`;
    const t = await openTable(b, { width: 390, height: 844 }, 'Cross_Board1PaysWithBoard3sUnit', 1);
    await waitLit(t.R, ['myGroundArena-0']);
    await t.p.evaluate(() => window.scrollTo(0, 0)); await sleep(1800);
    const chip = await t.p.evaluate(() => { const c = document.getElementById('a2Nudge'); return c && getComputedStyle(c).display !== 'none' ? c.textContent.trim() : ''; });
    check(/Targets on board 3.*↓/.test(chip), name, `a chip points down to the targets on board 3 ("${chip}")`);
    await t.p.evaluate(() => document.getElementById('a2Nudge').click()); await sleep(1500);
    const top = await t.p.evaluate(() => document.getElementById('a2Board3').getBoundingClientRect().top);
    check(Math.abs(top) < 60, name, `tapping it scrolls to board 3's column (its top at ${Math.round(top)}px)`);
    if (SHOTS) await t.p.screenshot({ path: `${SHOTS}/cross-${engine}-phone.png` });
    await t.p.close();
  }
  await b.close();
}
console.log(fails ? `${fails} FAILED of ${checks}` : `PASS (${checks} checks)`);
process.exit(fails ? 1 : 0);
