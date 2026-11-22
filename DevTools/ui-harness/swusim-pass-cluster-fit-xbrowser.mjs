// The footer's action cluster (#swuPassControl) must FIT its bar. In Twin Suns the cluster can hold Take/Keep Initiative + Blast +
// Plan (+ the house-rule Credit/Restore) at once, and stacking them in one column overflowed the 54px footer — the third button hung
// below the bar (owner screenshot 2026-11-22, 4P table). With counters on offer the cluster lays out in a ROW, wrapping on a narrow
// footer; the two-player Take/Keep-over-Pass stack is unchanged.
//   node DevTools/ui-harness/swusim-pass-cluster-fit-xbrowser.mjs     (ENGINES=chromium to narrow; SHOTS=<dir> for screenshots)
import fs from 'fs';
import { chromium, firefox, webkit } from 'playwright';

const BASE = process.env.BASE_URL || 'http://localhost:3400/TCGEngine/';
const SHOTS = process.env.SHOTS || '';
const ENGINES = (process.env.ENGINES || 'chromium,firefox,webkit').split(',');
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
const url = (g, seat) => `${BASE}NextTurn.php?folderPath=SWUSim&gameName=${g}&playerID=${seat}&authKey=testschema`;

// [fixture, label, minimum visible buttons, counters expected, phone only]. The 4P table (phone markup at every size), 3P (phone
// markup only on a phone — its desktop cluster lives in GameLayout.php, outside the bar), and a two-player phone board as the control.
const CASES = [
  [read('TwinSuns_4P_AllZonesFilled.md'), '4p-table', 3, true, false],   // a table on desktop; Home panels on a phone
  [read('TwinSuns_3P_FullArenas.md'), '3p', 3, true, true],
  [read('BaseHealthBar_1v1.md'), '2p', 1, false, true],
];
const VIEWPORTS = [
  { tag: 'desktop1600', vp: { width: 1600, height: 1000 } },
  { tag: 'desktop1366', vp: { width: 1366, height: 768 } },
  { tag: 'phone390', vp: { width: 390, height: 844 }, phone: true },
  // A narrow DESKTOP window (no phone user agent): the 4-seat game keeps the table, columns stacked — phones get the Home panels.
  { tag: 'narrow390', vp: { width: 390, height: 844 }, narrow: true },
];

let fails = 0, checks = 0;
const check = (ok, name, msg) => { checks++; if (!ok) { fails++; console.log(`FAIL ${name} :: ${msg}`); } };

const measure = (p) => p.evaluate(() => {
  const r = (el) => { const b = el.getBoundingClientRect(); return { x: Math.round(b.left), y: Math.round(b.top), w: Math.round(b.width), h: Math.round(b.height),
    r: Math.round(b.right), b: Math.round(b.bottom) }; };
  const band = document.getElementById('swuMyControlBand');
  const ctrl = document.getElementById('swuPassControl');
  if (!band || !ctrl) return null;
  // The visible buttons: Take/Keep (its wrapper on the phone markup) + Pass + the counter buttons.
  const btns = [...ctrl.querySelectorAll('button')].filter(b => !b.closest('[hidden]') && b.getBoundingClientRect().width > 0)
    .map(b => ({ text: b.textContent.trim(), counter: b.classList.contains('swu-take-counter'), ...r(b) }));
  // Siblings in the bar the cluster must not overlap (piles, resources, the table's seat name).
  const others = [...band.children].filter(c => c !== ctrl && c.getBoundingClientRect().width > 0 && getComputedStyle(c).display !== 'none')
    .map(c => ({ id: c.id || c.className, ...r(c) }));
  return { band: r(band), btns, others };
});

for (const [engine, launcher] of [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]].filter(([e]) => ENGINES.includes(e))) {
  let b;
  try { b = await launcher.launch(); } catch (e) { check(false, engine, `could not launch: ${e.message}`); continue; }
  for (const [schema, label, minBtns, counters, phoneOnly] of CASES) {
    const g = await seed(schema);
    for (const { tag, vp, phone, narrow } of VIEWPORTS) {
      if (phoneOnly && !phone) continue;
      if (narrow && label !== '4p-table') continue;
      const name = `${engine}/${label}/${tag}`;
      const ctx = await b.newContext({ viewport: vp, ...(phone ? { userAgent: IPHONE_UA, isMobile: engine !== 'firefox', hasTouch: true } : {}) });
      const p = await ctx.newPage();
      const errs = []; p.on('pageerror', e => errs.push(e.message));
      await p.goto(url(g, 1), { waitUntil: 'domcontentloaded' });
      await p.waitForFunction(() => { const c = document.getElementById('swuPassControl'); return c && !c.classList.contains('is-idle'); }, null, { timeout: 60000 }).catch(() => {});
      await p.waitForTimeout(1200);
      const m = await measure(p);
      if (!m) { check(false, name, 'footer bar + pass control present'); await ctx.close(); continue; }
      check(m.btns.length >= minBtns, name, `at least ${minBtns} action button(s) visible: ${JSON.stringify(m.btns.map(x => x.text))}`);
      check(m.btns.some(x => x.counter) === counters, name, `counter buttons ${counters ? 'offered' : 'absent'}: ${JSON.stringify(m.btns.map(x => x.text))}`);
      // Every button inside the bar (1px tolerance for subpixel rounding).
      for (const x of m.btns) {
        check(x.y >= m.band.y - 1 && x.b <= m.band.b + 1 && x.x >= m.band.x - 1 && x.r <= m.band.r + 1, name,
          `"${x.text}" sits inside the footer bar: btn ${JSON.stringify([x.x, x.y, x.r, x.b])} bar ${JSON.stringify([m.band.x, m.band.y, m.band.r, m.band.b])}`);
      }
      // No button overlaps anything else in the bar.
      for (const x of m.btns) for (const o of m.others) {
        const hit = x.x < o.r - 1 && o.x < x.r - 1 && x.y < o.b - 1 && o.y < x.b - 1;
        check(!hit, name, `"${x.text}" does not overlap ${o.id}`);
      }
      const tops = [...new Set(m.btns.map(x => x.y))];
      if (counters && !phone && !narrow) {
        // A wide footer has the room: ONE row.
        check(m.btns.every(x => Math.abs(x.y - m.btns[0].y) <= 1), name, `one row on a wide footer: tops ${JSON.stringify(m.btns.map(x => [x.text, x.y]))}`);
      }
      if (counters) check(tops.length <= 2 || m.btns.every(x => Math.abs(x.y - m.btns[0].y) <= 1), name, `at most two rows: ${JSON.stringify(m.btns.map(x => [x.text, x.y]))}`);
      if (!counters && m.btns.length === 2) {
        // 1v1 control: Take/Keep stays stacked over Pass, sharing its width.
        const [a, c] = m.btns;
        check(a.b <= c.y + 1 && Math.abs(a.x - c.x) <= 1 && Math.abs(a.w - c.w) <= 1, name, `two-player Take/Keep stays stacked over Pass: ${JSON.stringify(m.btns)}`);
      }
      check(errs.length === 0, name, `no page errors: ${errs.slice(0, 2).join(' | ')}`);
      if (SHOTS) await p.screenshot({ path: `${SHOTS}/pass-${label}-${tag}-${engine}.png` });
      await ctx.close();
    }
  }
  await b.close();
}
console.log(fails === 0 ? `PASS (${checks} checks)` : `${fails} FAILED of ${checks}`);
process.exit(fails === 0 ? 0 : 1);
