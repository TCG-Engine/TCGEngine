// The target prompt (#selection-message — "Choose a unit… / PASS") must never cover one of its own targets.
// iPad Pro report 2026-11-06: "Chimaera wouldn't let me sac my Mando token because the window to pass was blocking
// the unit", and "Grievous: couldn't do 4 damage to his base because the window didn't let me click on it".
// Two causes, both pinned here, Chromium / Firefox / WebKit:
//   · STRETCH (SWUSim/Custom/GameLayout.php `#selection-message[style*="bottom: auto"]`): the lift-above-the-hand
//     rule's `bottom … !important` beat the drag helper's inline bottom:auto, so a dragged / saved prompt was pinned
//     at both edges and became a tall see-through slab over the middle of the board (both bases unclickable);
//   · BLIND PLACEMENT (GameLayoutShared.php swuKeepPromptOffTargets): bottom-centre covered the second row of a
//     go-wide ground arena at the iPad Pro 11" landscape Safari viewport (~1194x760).
//   · CONTROL: on a board where nothing is covered, the prompt stays exactly where Core puts it.
// Not covered: the third unit row disappearing under the HAND band (separate overflow, not the prompt).
//   node DevTools/ui-harness/swusim-prompt-off-targets-xbrowser.mjs     (seeds its own boards)
import { chromium, firefox, webkit } from 'playwright';
import fs from 'node:fs';
const BASE = process.env.BASE_URL || 'http://localhost:3400/TCGEngine/';
const SHOTS = process.env.SHOTS || '/tmp/prompt-off-targets';
fs.mkdirSync(SHOTS, { recursive: true });
let fails = 0, checks = 0;
const bad = (n, m) => { fails++; checks++; console.log(`FAIL ${n} :: ${m}`); };
const ok  = () => { checks++; };
const want = (n, c, m) => c ? ok() : bad(n, m);

const SAVED_KEY = 'mzchoose-inline-position-v1';   // Core EnableDraggableModal's key for this prompt
const wide = (n) => '[' + Array.from({ length: n }, (_, i) => i % 2 ? 'SOR_095:1:0' : 'ASH_T01:1:0').join(' ') + ']';
// ASH_052 Chimaera: optional pick of a FRIENDLY unit (MZMAYCHOOSE → a PASS button) — six friendly ground units
// wrap to a second row, which is the row the prompt used to cover.
const CHIMAERA = ['## GIVEN', 'CommonSetup: bbk/bbk/{myResources:7;handCardIds:ASH_052}', 'P1OnlyActions: true',
  'WithP1GroundArena: ' + wide(6), 'WithP2GroundArena: [SEC_080:1:0 SEC_080:1:0 SEC_080:1:0]', '## WHEN', '## EXPECT'].join('\n');
// HMW_159 General Grievous: mandatory "Deal 4 damage to a base" — the two bases are the only targets.
const GRIEVOUS = ['## GIVEN', 'CommonSetup: rrk/bbw/{myResources:7}', 'P1OnlyActions: true', 'WithP1Hand: HMW_159',
  '## WHEN', '## EXPECT'].join('\n');

const form = (o) => new URLSearchParams(o).toString();
let cookie = null;
async function seed(schema) {
  if (!cookie) {
    const lr = await fetch(BASE + 'AccountFiles/AttemptPasswordLogin.php', { method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: form({ submit: '1', userID: 'claudebot1', password: 'pass' }), redirect: 'manual' });
    cookie = (lr.headers.getSetCookie?.() || []).map(c => c.split(';')[0]).join('; ');
  }
  const r = await fetch(BASE + 'SWUSim/TestSchemaSetup.php', { method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', cookie }, body: form({ schema }), redirect: 'manual' });
  const j = JSON.parse(await r.text());
  if (j.error) throw new Error(j.error);
  return String(j.gameName);
}

// Open a fresh board, optionally with a saved drag position, play the hand card, wait for the prompt.
async function openPrompt(b, schema, vw, vh, saved) {
  const game = await seed(schema);
  const p = await b.newPage({ viewport: { width: vw, height: vh } });
  const url = `${BASE}NextTurn.php?folderPath=SWUSim&gameName=${game}&playerID=1&authKey=testschema`;
  await p.goto(url, { waitUntil: 'domcontentloaded' });
  await p.waitForSelector('#myHand-0', { timeout: 20000 });
  await p.evaluate(([k, s]) => { try { s ? localStorage.setItem(k, JSON.stringify(s)) : localStorage.removeItem(k); } catch (e) {} }, [SAVED_KEY, saved]);
  // Errors are collected from the reload on: WebKit reports the first load's polls, cancelled by the reload
  // itself, as "... due to access control checks".
  await p.reload({ waitUntil: 'domcontentloaded' });
  const errs = []; p.on('pageerror', e => errs.push(e.message));
  await p.waitForSelector('#myHand-0', { timeout: 20000 });
  await p.waitForTimeout(1500);
  // A plain click: no hover, so no card preview muddies the board. Retried, because under load the server-rendered
  // hand can be on screen before the client is ready to play from it, and that first click goes nowhere.
  for (let attempt = 1; ; attempt++) {
    if (await p.$('#myHand-0')) await p.$eval('#myHand-0', e => e.click());
    try { await p.waitForSelector('#selection-message', { state: 'visible', timeout: 6000 }); break; }
    catch (e) {
      if (attempt >= 4) {
        await p.screenshot({ path: `${SHOTS}/NO-PROMPT-${game}.png` });
        throw new Error(`no prompt after ${attempt} clicks (game ${game}, ${vw}x${vh}) — see NO-PROMPT-${game}.png`);
      }
    }
  }
  await p.waitForTimeout(900);
  return { p, errs };
}
// The prompt's rect, and for every target which of five sample points the PROMPT sits on.
const measure = (p) => p.evaluate(() => {
  const ban = document.getElementById('selection-message');
  const br = ban.getBoundingClientRect();
  const targets = [...document.querySelectorAll('.selectable-card')].map(el => {
    const r = el.getBoundingClientRect();
    const pts = [[.5, .5], [.2, .2], [.8, .2], [.2, .85], [.8, .85]];
    const under = pts.filter(([fx, fy]) => {
      const t = document.elementFromPoint(r.left + r.width * fx, r.top + r.height * fy);
      return t && ban.contains(t);
    }).length;
    return { id: el.id, under };
  });
  return { left: Math.round(br.left), top: Math.round(br.top), w: Math.round(br.width), h: Math.round(br.height),
    vw: innerWidth, vh: innerHeight, targets };
});
const covered = (m) => m.targets.filter(t => t.under > 0).map(t => `${t.id}(${t.under}/5)`);

// ENGINES=chromium,webkit narrows the run (all three by default).
const ENGINES = (process.env.ENGINES || 'chromium,firefox,webkit').split(',');
for (const [engine, launcher] of [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]].filter(([e]) => ENGINES.includes(e))) {
  const b = await launcher.launch();

  // 1. Crowded go-wide board at the iPad Pro 11" landscape Safari viewport: no target under the prompt.
  {
    const n = `${engine}/ipad11-crowded`;
    const { p, errs } = await openPrompt(b, CHIMAERA, 1194, 760, null);
    const m = await measure(p);
    want(n, m.targets.length >= 7, `expected the 6 ground units + Chimaera offered, got ${m.targets.length}`);
    want(n, covered(m).length === 0, `prompt covers ${covered(m).join(' ')}`);
    want(n, m.h < 90, `prompt is ${m.h}px tall — stretched`);
    want(n, m.top >= 0 && m.top + m.h <= m.vh && m.left >= 0 && m.left + m.w <= m.vw, `prompt off-screen ${JSON.stringify(m)}`);
    want(n, await p.isVisible('#selection-message button'), 'the PASS button is gone');
    want(n, errs.length === 0, `page errors: ${errs.join(' | ')}`);
    await p.screenshot({ path: `${SHOTS}/${engine}-ipad11-crowded.png` });
    await p.close();
  }

  // 2. A saved drag position over the middle of the board (what a player who once dragged it up has): the prompt
  //    keeps its natural height — no stretch to the hand band — and neither base is under it.
  {
    const n = `${engine}/saved-over-bases`;
    const { p, errs } = await openPrompt(b, GRIEVOUS, 1194, 760, { left: 410, top: 300 });
    const m = await measure(p);
    want(n, m.targets.length === 2, `expected both bases offered, got ${m.targets.map(t => t.id)}`);
    want(n, m.h < 60, `prompt is ${m.h}px tall — the lift stretched it`);
    want(n, covered(m).length === 0, `prompt covers ${covered(m).join(' ')}`);
    const stored = await p.evaluate((k) => { try { return localStorage.getItem(k); } catch (e) { return 'ERR'; } }, SAVED_KEY);
    want(n, stored === JSON.stringify({ left: 410, top: 300 }), `the player's saved position was rewritten: ${stored}`);
    want(n, errs.length === 0, `page errors: ${errs.join(' | ')}`);
    await p.screenshot({ path: `${SHOTS}/${engine}-saved-over-bases.png` });
    await p.close();
  }

  // 3. Dragging the prompt MOVES it: its height is unchanged after a 150px drag up.
  {
    const n = `${engine}/drag-moves-not-stretches`;
    const { p } = await openPrompt(b, GRIEVOUS, 1600, 1000, null);
    const before = await measure(p);
    const h = await p.$eval('#selection-message > span', e => { const r = e.getBoundingClientRect(); return [r.left + 10, r.top + r.height / 2]; });
    await p.mouse.move(h[0], h[1]); await p.mouse.down();
    await p.mouse.move(h[0], h[1] - 150, { steps: 10 }); await p.mouse.up();
    await p.waitForTimeout(300);
    const after = await measure(p);
    want(n, Math.abs(after.h - before.h) <= 1, `height ${before.h} → ${after.h} — the drag stretched it`);
    want(n, Math.abs((before.top - after.top) - 150) <= 2, `top ${before.top} → ${after.top}, expected a 150px move`);
    await p.close();
  }

  // 4. CONTROL: nothing covered → the prompt stays at Core's default spot (centred in the viewport, above the hand).
  {
    const n = `${engine}/control-default-spot`;
    const { p } = await openPrompt(b, GRIEVOUS, 1600, 1000, null);
    const m = await measure(p);
    want(n, covered(m).length === 0, `prompt covers ${covered(m).join(' ')}`);
    want(n, Math.abs((m.left + m.w / 2) - m.vw / 2) <= 2, `prompt moved off centre: left ${m.left} w ${m.w}`);
    want(n, m.top > m.vh * 0.7, `prompt left the bottom of the board: top ${m.top}`);
    await p.close();
  }
  await b.close();
}
console.log(`${checks - fails}/${checks} checks passed${fails ? ` — ${fails} FAILED` : ''}`);
process.exit(fails ? 1 : 0);
