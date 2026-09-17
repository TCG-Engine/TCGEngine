// Whisper chat on live SWUSim boards, in Chromium, Firefox and WebKit.
//  Twin Suns: seat 1 ticks P3 and sends a secret. Seats 1+3 read it; seats 2, 4 and a spectator see only
//  "P1 whispered something to P3" and never the text (DOM and network). Placeholder + sticky box. Spectator
//  has no row. Mobile layout: the row is visible and the page does not scroll horizontally.
//  Team Suns: seat 1 has exactly one "Team only" box; seat 3 reads; seats 2+4 get the stub.
//  Premier: no row.
// Spec: docs/superpowers/specs/2026-09-17-swusim-twinsuns-whisper-chat-design.md · visual specs SWUSim/Tests/Visual/WhisperChat_*.md
// Usage: node swusim-whisper-chat-xbrowser.mjs [baseURL]   ENGINES=chromium,firefox,webkit (default all)
import { chromium, firefox, webkit } from 'playwright';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const ALL = { chromium, firefox, webkit };
const ENGINES = Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n));
const VIS = new URL('../../SWUSim/Tests/Visual/', import.meta.url);
const SCHEMA_TS = fs.readFileSync(new URL('WhisperChat_TwinSuns.md', VIS), 'utf8');
const SCHEMA_TM = fs.readFileSync(new URL('WhisperChat_TeamSuns.md', VIS), 'utf8');
const SCHEMA_PM = `## GIVEN\nCommonSetup: bbw/rrk/{myResources:5; theirResources:5}\nWithGamePhase: ActionPhase\nWithActivePlayer: 1\n\n## WHEN\n\n## EXPECT\nTURNPLAYER:1\n`;
const SHOTS = process.env.SHOTS_DIR || os.tmpdir();

let allOk = true;
const results = [];
const ok = (engine, name, cond, extra = '') => { if (!cond) allOk = false; results.push([engine, name, !!cond, extra]); };
const report = () => { for (const [e, n, pass, extra] of results) console.log(`${pass ? 'PASS' : 'FAIL'}  ${e.padEnd(8)} ${n}${extra ? '  — ' + extra : ''}`); };
setTimeout(() => { console.log('WATCHDOG: timed out after 300s'); report(); process.exit(9); }, 300000).unref();

async function makeGame(schema) {
  const res = await fetch(BASE + 'SWUSim/TestSchemaSetup.php', { method: 'POST', body: new URLSearchParams({ schema }) });
  const j = await res.json();
  if (!j.gameName) throw new Error('TestSchemaSetup failed: ' + JSON.stringify(j));
  return String(j.gameName);
}
// The phone layout is chosen server-side by user agent or ?swuLayout=mobile (SWUSim/Custom/GameLayoutDevice.php),
// never by viewport width, so the mobile pass must pass the param.
const url = (gn, pid, mobile = false) => `${BASE}NextTurn.php?folderPath=SWUSim&gameName=${gn}&playerID=${pid}`
  + (pid === 'S' ? '' : '&authKey=testschema') + (mobile ? '&swuLayout=mobile' : '');

async function openSeat(browser, gn, pid, viewport = { width: 1700, height: 1050 }, mobile = false) {
  const ctx = await browser.newContext({ viewport });
  const page = await ctx.newPage();
  const bodies = [];
  page.on('response', async (r) => { if (r.url().includes('GetNextTurn.php')) { try { bodies.push(await r.text()); } catch {} } });
  await page.goto(url(gn, pid, mobile), { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(1500);
  return { ctx, page, bodies };
}
const bodyText = (page) => page.evaluate(() => document.body.innerText);
const waitForText = async (page, needle, ms = 12000) => {
  const end = Date.now() + ms;
  while (Date.now() < end) { if ((await bodyText(page)).includes(needle)) return true; await page.waitForTimeout(300); }
  return false;
};

for (const [engine, launcher] of ENGINES) {
  let browser;
  try {
    browser = await launcher.launch();
    const SECRET = 'meet me at the Death Star ' + Math.random().toString(36).slice(2, 7);

    // ── Twin Suns ──
    const gn = await makeGame(SCHEMA_TS);
    const seats = {};
    for (const pid of ['1', '2', '3', '4', 'S']) seats[pid] = await openSeat(browser, gn, pid);
    const p1 = seats['1'].page;
    const rowLabels = await p1.$$eval('#swuWhisperRow label', (ls) => ls.map((l) => l.textContent.trim()));
    ok(engine, 'TS seat 1 has one box per other seat', JSON.stringify(rowLabels) === JSON.stringify(['P2', 'P3', 'P4']), JSON.stringify(rowLabels));
    ok(engine, 'TS spectator has no whisper row', (await seats['S'].page.$('#swuWhisperRow')) === null);
    await p1.check('#swuWhisperRow input[data-seats="3"]');
    ok(engine, 'TS placeholder names the target', (await p1.getAttribute('#chatText', 'placeholder')) === 'Whisper to P3…');
    await p1.fill('#chatText', SECRET);
    await p1.press('#chatText', 'Enter');
    ok(engine, 'TS seat 1 reads own whisper', await waitForText(p1, SECRET));
    ok(engine, 'TS seat 3 reads whisper', await waitForText(seats['3'].page, SECRET));
    ok(engine, 'TS seat 3 label says you', (await bodyText(seats['3'].page)).includes('P1 → you:'));
    for (const pid of ['2', '4', 'S']) {
      const pg = seats[pid].page;
      ok(engine, `TS seat ${pid} sees stub`, await waitForText(pg, 'P1 whispered something to P3'));
      ok(engine, `TS seat ${pid} DOM has no secret`, !(await bodyText(pg)).includes(SECRET));
      ok(engine, `TS seat ${pid} network has no secret`, !seats[pid].bodies.some((b) => b.includes(SECRET)));
    }
    ok(engine, 'TS box is sticky after send', await p1.isChecked('#swuWhisperRow input[data-seats="3"]'));
    await p1.screenshot({ path: path.join(SHOTS, `whisper-desktop-seat1-${engine}.png`) }).catch(() => {});
    await seats['2'].page.screenshot({ path: path.join(SHOTS, `whisper-desktop-seat2-${engine}.png`) }).catch(() => {});
    await p1.uncheck('#swuWhisperRow input[data-seats="3"]');
    ok(engine, 'TS placeholder resets', (await p1.getAttribute('#chatText', 'placeholder')) === 'Message...');
    for (const s of Object.values(seats)) await s.ctx.close();

    // ── Twin Suns, phone layout ──
    const mob = await openSeat(browser, gn, '1', { width: 400, height: 860 }, true);
    const m = await mob.page.evaluate(() => {
      const row = document.getElementById('swuWhisperRow');
      return { exists: !!row, overflow: document.documentElement.scrollWidth > window.innerWidth + 1 };
    });
    ok(engine, 'mobile: whisper row exists', m.exists);
    ok(engine, 'mobile: no horizontal page scroll', !m.overflow);
    await mob.page.screenshot({ path: path.join(SHOTS, `whisper-mobile-${engine}.png`) }).catch(() => {});
    await mob.ctx.close();

    // ── Team Suns ──
    const tm = await makeGame(SCHEMA_TM);
    const TEAM = 'focus their base ' + Math.random().toString(36).slice(2, 7);
    const t = {};
    for (const pid of ['1', '2', '3', '4']) t[pid] = await openSeat(browser, tm, pid);
    const tLabels = await t['1'].page.$$eval('#swuWhisperRow label', (ls) => ls.map((l) => l.textContent.trim()));
    ok(engine, 'Team seat 1 has exactly "Team only"', JSON.stringify(tLabels) === JSON.stringify(['Team only']), JSON.stringify(tLabels));
    ok(engine, 'Team seat 2 box targets seat 4', (await t['2'].page.getAttribute('#swuWhisperRow input', 'data-seats')) === '4');
    await t['1'].page.check('#swuWhisperRow input');
    await t['1'].page.fill('#chatText', TEAM);
    await t['1'].page.press('#chatText', 'Enter');
    ok(engine, 'Team seat 3 reads', await waitForText(t['3'].page, TEAM));
    for (const pid of ['2', '4']) {
      ok(engine, `Team seat ${pid} sees stub`, await waitForText(t[pid].page, 'P1 whispered something to P3'));
      ok(engine, `Team seat ${pid} has no secret`, !(await bodyText(t[pid].page)).includes(TEAM) && !t[pid].bodies.some((b) => b.includes(TEAM)));
    }
    for (const s of Object.values(t)) await s.ctx.close();

    // ── Premier ──
    const pm = await makeGame(SCHEMA_PM);
    const pr = await openSeat(browser, pm, '1');
    ok(engine, 'Premier has no whisper row', (await pr.page.$('#swuWhisperRow')) === null);
    await pr.ctx.close();
  } catch (e) {
    ok(engine, 'engine run completed', false, String(e && e.message || e));
  } finally {
    if (browser) await browser.close().catch(() => {});
  }
}
report();
console.log(allOk ? '\nALL PASS' : '\nSOME FAILED');
process.exit(allOk ? 0 : 1);
