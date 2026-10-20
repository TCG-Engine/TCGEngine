// Cross-browser check for the Twin Suns TABLE REMATCH on the end-game screen (2026-10-20).
// Spec: docs/superpowers/specs/2026-10-20-twinsuns-rematch-design.md
//
// A finished private 4-seat table (seats 1-3 humans, seat 4 a bot, Plan Scry 2) comes from the local-dev fixture
// DevTools/swusim-finished-table-fixture.php. Three browser contexts sit at seats 1-3:
//   QUICK — both buttons show a 0/3 tally; seats 1+2 vote Quick and seat 3 sees 2/3; seat 3 votes → all three pages
//           land on the SAME new game.
//   ROOM  — seat 2 presses Return to Main Menu → Quick goes disabled "… left" for seats 1 and 3; they vote Rematch →
//           both land in the new waiting room seated and NOT READY, Plan Scry lit, the bot seated READY. The room is
//           deleted afterwards through the fixture.
// Chromium + Firefox + WebKit, desktop (1728) and phone (430, &swuLayout=mobile). Screenshots → $SHOTS or
// <tmpdir>/swusim-table-rematch (the end screen with a live tally). ONLY=chromium to run a subset.
import { chromium, firefox, webkit } from 'playwright';
import { mkdirSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const BASE = process.env.BASE_URL || 'http://localhost:3400/TCGEngine/';
const ONLY = (process.env.ONLY || '').split(',').map((s) => s.trim()).filter(Boolean);
const SHOTS = process.env.SHOTS || join(tmpdir(), 'swusim-table-rematch');
mkdirSync(SHOTS, { recursive: true });
const POLL = 3200;   // the end screen polls every 2.5s

let allOk = true;
const results = [];
const ok = (tag, name, cond, extra) => { if (!cond) allOk = false; results.push([tag, name, !!cond, extra]); };
const fixture = async (q = '') => (await fetch(BASE + 'DevTools/swusim-finished-table-fixture.php' + q)).json();

const btn = (page, id) => page.evaluate((i) => {
  const b = document.getElementById(i);
  return b ? { text: b.textContent.trim(), disabled: b.disabled, shown: b.getBoundingClientRect().width > 0 } : null;
}, id);

async function openSeats(browser, viewport, q, t) {
  const pages = [];
  for (const s of [1, 2, 3]) {
    const ctx = await browser.newContext({ viewport });
    const page = await ctx.newPage();
    await page.goto(`${BASE}NextTurn.php?folderPath=SWUSim&gameName=${t.gameName}&playerID=${s}&authKey=${t.keys[s]}${q}`, { waitUntil: 'load' });
    pages.push(page);
  }
  for (const p of pages) await p.waitForSelector('#swu-tr-quick', { timeout: 20000 }).catch(() => {});
  return pages;
}

const ENGINES = Object.entries({ chromium, firefox, webkit }).filter(([n]) => !ONLY.length || ONLY.includes(n));
const LAYOUTS = [['desktop', { width: 1728, height: 1000 }, ''], ['mobile', { width: 430, height: 932 }, '&swuLayout=mobile']];

for (const [engine, driver] of ENGINES) {
  let browser;
  try {
    browser = await driver.launch();
    for (const [layout, viewport, q] of LAYOUTS) {
      const tag = `${engine}/${layout}`;

      // ── QUICK ──────────────────────────────────────────────────────────────────────────────
      let t = await fixture();
      let [p1, p2, p3] = await openSeats(browser, viewport, q, t);
      let b = await btn(p1, 'swu-tr-quick');
      ok(tag, 'quick: both rematch buttons are on the end screen', b && b.shown && (await btn(p1, 'swu-tr-room'))?.shown, JSON.stringify(b));
      ok(tag, 'quick: the tally starts at 0/3', b && /\(0\/3\)/.test(b.text), b && b.text);
      await p1.click('#swu-tr-quick');
      await p2.click('#swu-tr-quick');
      await p3.waitForTimeout(POLL);
      b = await btn(p3, 'swu-tr-quick');
      ok(tag, 'quick: seat 3 sees 2/3', b && /\(2\/3\)/.test(b.text), b && b.text);
      ok(tag, 'quick: seat 1 sees its own vote and who is missing',
         /✓/.test((await btn(p1, 'swu-tr-quick'))?.text || '') && /Waiting for claudebot3/.test(await p1.evaluate(() => (document.getElementById('swu-tr-status') || {}).textContent || '')));
      // Nothing may sit on top of the rematch buttons or their status line (on a 3-4 seat end screen the save-replay
      // block used to land in the buttons cell, overlapping them).
      const overlap = await p3.evaluate(() => {
        const box = (e) => e && e.getBoundingClientRect();
        const hit = (a, b) => a && b && a.width > 0 && b.width > 0 && a.left < b.right && b.left < a.right && a.top < b.bottom && b.top < a.bottom;
        const wrap = box(document.querySelector('.match-replay-stats-actions'));
        return ['swu-tr-quick', 'swu-tr-room', 'swu-tr-status'].filter((id) => hit(box(document.getElementById(id)), wrap));
      });
      ok(tag, 'quick: nothing overlaps the rematch buttons or status line', overlap.length === 0, JSON.stringify(overlap));
      await p3.screenshot({ path: join(SHOTS, `table-rematch-${engine}-${layout}.png`) });
      await p3.click('#swu-tr-quick');
      const landed = [];
      for (const p of [p1, p2, p3]) {
        await p.waitForURL((u) => u.searchParams.get('gameName') && u.searchParams.get('gameName') !== t.gameName, { timeout: 20000 }).catch(() => {});
        landed.push(new URL(p.url()).searchParams.get('gameName'));
      }
      ok(tag, 'quick: all three pages land on the SAME new game', landed[0] !== t.gameName && landed.every((g) => g === landed[0]), JSON.stringify(landed));
      for (const p of [p1, p2, p3]) await p.context().close();

      // ── ROOM ───────────────────────────────────────────────────────────────────────────────
      t = await fixture();
      [p1, p2, p3] = await openSeats(browser, viewport, q, t);
      await p2.getByRole('button', { name: 'Return to Main Menu' }).click();
      await p1.waitForTimeout(POLL + 1000);
      for (const [n, p] of [[1, p1], [3, p3]]) {
        const qb = await btn(p, 'swu-tr-quick');
        ok(tag, `room: seat ${n} sees Quick disabled because claudebot2 left`, qb && qb.disabled && /claudebot2 left/.test(qb.text), qb && qb.text);
      }
      await p1.click('#swu-tr-room');
      await p3.click('#swu-tr-room');
      let lobby = '';
      for (const [n, p] of [[1, p1], [3, p3]]) {
        await p.waitForURL(/WaitingRoom\.php\?lobby=/, { timeout: 20000 }).catch(() => {});
        lobby = new URL(p.url()).searchParams.get('lobby') || lobby;
        await p.waitForTimeout(2500);
        const st = await p.evaluate(() => ({
          seated: !!document.querySelector('.wr-seat-mine'),
          notReady: !!document.querySelector('.wr-seat-mine .wr-pill-notready'),
          scryLit: !!document.querySelector('#wr-hr-rows [data-rule="planScry"].is-active'),
          readyPills: document.querySelectorAll('.wr-pill-ready').length,
          seats: document.querySelectorAll('.wr-seat:not(.wr-seat-empty)').length,
        }));
        ok(tag, `room: seat ${n} is seated in the new room, NOT ready`, /WaitingRoom/.test(p.url()) && st.seated && st.notReady, JSON.stringify(st));
        ok(tag, `room: seat ${n} sees Plan Scry still lit and the bot READY`, st.scryLit && st.readyPills >= 1, JSON.stringify(st));
        ok(tag, `room: seat ${n} sees 3 seated (the leaver's seat is empty)`, st.seats === 3, JSON.stringify(st));
      }
      if (lobby) await fixture('?deleteLobby=' + encodeURIComponent(lobby));
      for (const p of [p1, p2, p3]) await p.context().close();
    }
  } catch (e) {
    ok(engine, 'harness ran', false, String(e && e.message ? e.message : e));
  } finally {
    if (browser) await browser.close().catch(() => {});
  }
}

for (const [tag, name, pass, extra] of results) console.log(`${pass ? 'ok  ' : 'BAD '} [${tag}] ${name}${pass || !extra ? '' : '  ' + extra}`);
console.log(allOk ? '\nALL PASS' : '\nFAILURES');
process.exit(allOk ? 0 : 1);
