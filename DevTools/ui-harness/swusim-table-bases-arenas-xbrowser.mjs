// Two owner reports on the DESKTOP 4-seat table (2026-11-22):
//  1. "move the 'Fortified' and 'Arrested' chips to be on the right of each base card. and make them smaller to fit the space" —
//     every board's chips sit right beside its base CARD (not beside the wider slot), inside the centre cell, and the base health bar
//     still sits over the card.
//  2. "on smaller screens, it seems i can scroll myself (P1) and P2. but i can't scroll P3 and P4" — the right column's arena slots
//     (#p{n}GroundArenaSlot / SpaceArenaSlot) only had the phone row class (overflow-y:hidden); the left column's are scroll boxes.
// Uses the overflow fixture (every arena over-full, both base tabs on every base).
//   node DevTools/ui-harness/swusim-table-bases-arenas-xbrowser.mjs     (ENGINES=chromium to narrow; SHOTS=<dir> for screenshots)
import fs from 'fs';
import { chromium, firefox, webkit } from 'playwright';

const BASE = process.env.BASE_URL || 'http://localhost:3400/TCGEngine/';
const SHOTS = process.env.SHOTS || '';
const ENGINES = (process.env.ENGINES || 'chromium,firefox,webkit').split(',');
const post = async (path, body) => fetch(BASE + path, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams(body).toString() });
async function seed(schema) {
  const json = JSON.parse(await (await post('SWUSim/TestSchemaSetup.php', { schema })).text());
  if (json.error) throw new Error(`TestSchemaSetup refused: ${json.error}`);
  return String(json.gameName);
}
const OVERFLOW = fs.readFileSync(new URL('../../SWUSim/Tests/Visual/TwinSuns_4P_AllZonesOverflow.md', import.meta.url), 'utf8');
const url = (g, seat) => `${BASE}NextTurn.php?folderPath=SWUSim&gameName=${g}&playerID=${seat}&authKey=testschema`;

let fails = 0, checks = 0;
const check = (ok, name, msg) => { checks++; if (!ok) { fails++; console.log(`FAIL ${name} :: ${msg}`); } };

const measure = (p) => p.evaluate(() => {
  const r = (e) => { const b = e.getBoundingClientRect(); return { x: b.left, y: b.top, w: b.width, h: b.height, r: b.right, b: b.bottom }; };
  const bases = [...document.querySelectorAll('.swu-tcol .swu-base-tabs')].filter(t => t.children.length).map(t => {
    const cell = t.closest('.swu-m-center');
    // The base CARD = its engine span (#…Base-0); the <img> inside runs ~5px past it each side (the health bar tracks the span).
    const img = cell.querySelector('[id$="BaseSlot"] [id$="Base-0"]');
    const bar = cell.querySelector('.swu-base-health .swu-hb');
    return { id: t.id, tabs: r(t), chips: [...t.children].map(r), cell: r(cell), img: img ? r(img) : null, bar: bar ? r(bar) : null };
  });
  const arenas = [...document.querySelectorAll('.swu-tcol [id$="ArenaSlot"]')].filter(e => e.getBoundingClientRect().width > 0).map(e => ({
    id: e.id, oy: getComputedStyle(e).overflowY, sh: e.scrollHeight, ch: e.clientHeight }));
  // Leader + base CARDS on every board (owner, 2026-11-22: "the card sizes of the leaders and base on desktop for the other players
  // are still wonky" — the right column drew them as portrait cards).
  const cards = [...document.querySelectorAll('.swu-tcol [data-mzid]')].filter(e => /^(my|their|p[1-4])(Leader|Base)-\d+$/.test(e.getAttribute('data-mzid')))
    .map(e => { const img = e.querySelector('img:not(.counter-image-icon)'); /* the card's art (no id on leader/base art) */ const kind = /Leader/.test(e.getAttribute('data-mzid')) ? 'leader' : 'base';
      return { id: e.getAttribute('data-mzid'), kind, left: !!e.closest('#swuTableLeft'), landscape: !!img && img.getAttribute('data-orientation') === 'landscape', ...r(e) }; });
  return { bases, arenas, cards };
});

for (const [engine, launcher] of [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]].filter(([e]) => ENGINES.includes(e))) {
  let b;
  try { b = await launcher.launch(); } catch (e) { check(false, engine, `could not launch: ${e.message}`); continue; }
  const g = await seed(OVERFLOW);
  for (const vp of [{ width: 1366, height: 768 }, { width: 1600, height: 1000 }, { width: 1920, height: 1080 }]) {
    for (const seat of [1, 2]) {
      const name = `${engine}/${vp.width}x${vp.height}/P${seat}`;
      const ctx = await b.newContext({ viewport: vp });
      const p = await ctx.newPage();
      const errs = []; p.on('pageerror', e => errs.push(e.message));
      await p.goto(url(g, seat), { waitUntil: 'domcontentloaded' });
      await p.waitForFunction(() => document.querySelectorAll('.swu-tcol .swu-base-tab').length >= 8, null, { timeout: 60000 }).catch(() => {});
      await p.waitForTimeout(1500);
      const m = await measure(p);

      // 1. Base chips: right beside every base card.
      check(m.bases.length === 4, name, `all four bases carry their chips: ${m.bases.map(x => x.id)}`);
      for (const x of m.bases) {
        if (!x.img) { check(false, name, `${x.id}: base card found`); continue; }
        const gap = x.tabs.x - x.img.r;
        check(gap >= -0.5 && gap <= 8, name, `${x.id}: chips sit just RIGHT of the base card (gap ${gap.toFixed(1)}px)`);
        check(x.tabs.r <= x.cell.r + 0.5 && x.img.x >= x.cell.x - 0.5, name, `${x.id}: card + chips fit their cell: card ${x.img.x.toFixed(0)}, chips end ${x.tabs.r.toFixed(0)}, cell ${x.cell.x.toFixed(0)}..${x.cell.r.toFixed(0)}`);
        check(x.tabs.y >= x.img.y - 1 && x.tabs.b <= x.img.b + 1, name, `${x.id}: chips within the card's height`);
        check(x.chips.every(c => c.h <= 14), name, `${x.id}: chips are compact (≤14px tall): ${x.chips.map(c => c.h.toFixed(1))}`);
        if (x.bar) check(Math.abs(x.bar.x - x.img.x) <= 2 && Math.abs(x.bar.w - x.img.w) <= 3, name, `${x.id}: the health bar still sits over the card: bar ${x.bar.x.toFixed(0)}+${x.bar.w.toFixed(0)} card ${x.img.x.toFixed(0)}+${x.img.w.toFixed(0)}`);
      }

      // 1b. Leaders and bases: landscape and the SAME size on all four boards.
      for (const kind of ['leader', 'base']) {
        const list = m.cards.filter(c => c.kind === kind);
        const ref = list.find(c => c.left);
        check(list.length >= (kind === 'base' ? 4 : 8), name, `${kind} cards on all four boards: ${list.map(c => c.id)}`);
        for (const c of list) {
          check(c.landscape, name, `${c.id} is drawn landscape, as the left column's`);
          if (ref) check(Math.abs(c.w - ref.w) <= 1 && Math.abs(c.h - ref.h) <= 1, name, `${c.id} is the same size as ${ref.id}: ${c.w.toFixed(1)}x${c.h.toFixed(1)} vs ${ref.w.toFixed(1)}x${ref.h.toFixed(1)}`);
        }
      }

      // 2. Every arena scrolls when it overflows — both columns.
      check(m.arenas.length === 8, name, `eight arena slots: ${m.arenas.length}`);
      for (const a of m.arenas) {
        check(a.oy === 'auto' || a.oy === 'scroll', name, `${a.id} is a vertical scroll box (overflow-y ${a.oy})`);
        if (a.sh > a.ch + 1) {
          const moved = await p.evaluate((id) => { const e = document.getElementById(id); e.scrollTop = 40; return e.scrollTop; }, a.id);
          check(moved > 0, name, `${a.id} overflows (${a.sh} > ${a.ch}) and actually scrolls (scrollTop ${moved})`);
          // A real wheel over the arena, not only a script: the right column is display-only, its cards must not eat the wheel.
          if (vp.width === 1366) {
            await p.evaluate((id) => { document.getElementById(id).scrollTop = 0; }, a.id);
            const box = await p.evaluate((id) => { const b = document.getElementById(id).getBoundingClientRect(); return { x: b.left + b.width / 2, y: b.top + b.height / 2 }; }, a.id);
            // Settle first: Firefox/WebKit drop a wheel that lands right after a scripted scrollTop reset (probed 2026-11-22 — the
            // same wheel 500ms later scrolls every arena), then poll rather than read once.
            await p.mouse.move(box.x, box.y); await p.waitForTimeout(500); await p.mouse.wheel(0, 120);
            const wheel = await p.waitForFunction((id) => document.getElementById(id).scrollTop > 0 ? document.getElementById(id).scrollTop : 0, a.id, { timeout: 2000 })
              .then(h => h.jsonValue()).catch(() => 0);
            check(wheel > 0, name, `${a.id} scrolls with the mouse wheel (scrollTop ${wheel})`);
          }
        }
      }
      check(m.arenas.some(a => a.sh > a.ch + 1) || vp.width > 1366, name, 'fixture: at 1366x768 the arenas overflow');
      check(errs.length === 0, name, `no page errors: ${errs.slice(0, 2).join(' | ')}`);
      if (SHOTS && seat === 1) await p.screenshot({ path: `${SHOTS}/bases-${vp.width}-${engine}.png` });
      await ctx.close();
    }
  }
  await b.close();
}
console.log(fails === 0 ? `PASS (${checks} checks)` : `${fails} FAILED of ${checks}`);
process.exit(fails === 0 ? 0 : 1);
