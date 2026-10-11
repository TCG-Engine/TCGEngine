// Twin Suns home tiles: arena units must fill LEFT→RIGHT, then TOP→BOTTOM (owner request 2026-10-03).
//
// The tile arenas (.swu-mb-row, SWUSim/Custom/GameLayoutShared.php) are a grid with a fixed row count
// (--swu-mb-rows: 2 on a tall window, 1 at max-height 900px). They used `grid-auto-flow: column`, so units
// filled DOWN then ACROSS — unit 1 sat under unit 0. Now the renderer emits each arena's column count
// (--swu-mb-c1 / --swu-mb-c2) and the grid flows by ROW. With one row the two orders are identical, so the
// 1-row height is checked only as a control.
//
// Seeds Tests/Visual/TwinSuns_4P_AllZonesOverflow.md (8 ground / 7 space per seat — enough to need several
// columns and overflow). For every tile arena it checks, in DOM (= server) order:
//   • row-major placement: each unit is right of the previous on the same row, or starts the next row;
//   • the arena uses its full row count and never more;
//   • every unit can be scrolled into view (the grid's scroll width tracks its columns).
// Run: node DevTools/ui-harness/swusim-home-tile-fill-order-xbrowser.mjs [chromium firefox webkit]
// Exit 0 = pass, 1 = regression, 2 = environment not ready.
import { chromium, firefox, webkit } from 'playwright';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

const HERE = dirname(fileURLToPath(import.meta.url));
const FIXTURE = readFileSync(join(HERE, '..', '..', 'SWUSim', 'Tests', 'Visual', 'TwinSuns_4P_AllZonesOverflow.md'), 'utf8');
const BASE = 'http://localhost:3400/TCGEngine/';

const engines = process.argv.slice(2).length ? process.argv.slice(2) : ['chromium', 'firefox', 'webkit'];
let fails = 0, checks = 0;
const ok = (name, cond, extra) => {
  checks++;
  if (!cond) { fails++; console.log(`FAIL ${name}${extra === undefined ? '' : '  ' + JSON.stringify(extra)}`); }
};

const fd = new FormData();
fd.append('schema', FIXTURE);
let game;
try { game = (await (await fetch(BASE + 'SWUSim/TestSchemaSetup.php', { method: 'POST', body: fd })).json()).gameName; }
catch (e) { console.log('ENVIRONMENT NOT READY: ' + e.message); process.exit(2); }
if (!game) { console.log('ENVIRONMENT NOT READY: no gameName'); process.exit(2); }

for (const engine of engines) {
  const browser = await ({ chromium, firefox, webkit })[engine].launch();
  for (const [width, height, rows] of [[1728, 1000, 2], [1920, 1080, 2], [1440, 860, 1]]) {
    const tag = `${engine}@${width}x${height}`;
    const page = await (await browser.newContext({ viewport: { width, height } })).newPage();
    page.on('pageerror', e => { fails++; console.log(`FAIL ${tag} pageerror ${e.message}`); });
    await page.goto(`${BASE}NextTurn.php?folderPath=SWUSim&gameName=${game}&playerID=1&authKey=testschema`, { waitUntil: 'load' });
    try { await page.waitForSelector('#swuHomeStrips .swu-mb-row > *', { timeout: 15000 }); }
    catch { console.log(`ENVIRONMENT NOT READY: ${tag} no home-tile arena units`); process.exit(2); }
    await page.waitForTimeout(800);

    const arenas = await page.evaluate(() => [...document.querySelectorAll('#swuHomeStrips .swu-mb-row')].map(row => {
      const items = [...row.children].filter(c => c.getBoundingClientRect().width > 0);
      // Grid positions in DOM order, measured relative to the row's scroll origin.
      const rr = row.getBoundingClientRect();
      const pos = items.map(c => { const r = c.getBoundingClientRect();
        return { x: Math.round(r.left - rr.left + row.scrollLeft), y: Math.round(r.top - rr.top + row.scrollTop) }; });
      // Reachability: scroll each unit into view and confirm it lands inside the row's visible box.
      const reach = items.map(c => {
        c.scrollIntoView({ block: 'nearest', inline: 'nearest' });
        const r = c.getBoundingClientRect(), v = row.getBoundingClientRect();
        return r.left >= v.left - 2 && r.right <= v.right + 2;
      });
      row.scrollLeft = 0;
      return { n: items.length, pos, reach, rowsVar: getComputedStyle(row).getPropertyValue('--swu-mb-rows').trim() };
    }));
    ok(`${tag} found the 3 tiles' 6 arenas`, arenas.length === 6, arenas.length);

    arenas.forEach((a, i) => {
      const t = `${tag} arena#${i} (${a.n} units)`;
      ok(`${t} rows var is ${rows}`, a.rowsVar === String(rows), a.rowsVar);
      const ys = [...new Set(a.pos.map(p => p.y))].sort((p, q) => p - q);
      ok(`${t} uses exactly min(${rows}, n) rows`, ys.length === Math.min(rows, a.n), ys);
      const cols = Math.ceil(a.n / rows);
      for (let k = 1; k < a.pos.length; k++) {
        const prev = a.pos[k - 1], cur = a.pos[k];
        const sameRow = cur.y === prev.y && cur.x > prev.x;
        const nextRow = cur.y > prev.y && k % cols === 0 && cur.x === a.pos[0].x;
        if (!(sameRow || nextRow)) { ok(`${t} unit ${k} follows unit ${k - 1} left-to-right, then top-to-bottom`, false, { prev, cur, cols }); break; }
      }
      ok(`${t} every unit can be scrolled into view`, a.reach.every(Boolean), a.reach);
    });
    await page.close();
  }
  await browser.close();
}
console.log(`${checks - fails}/${checks} checks passed`);
process.exit(fails ? 1 : 0);
