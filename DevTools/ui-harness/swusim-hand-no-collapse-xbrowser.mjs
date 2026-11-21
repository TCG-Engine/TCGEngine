// The hand has no collapse / expand button (owner, 2026-11-20: "i like how we did away with the hand tray open and close button. that
// always felt unnecessary. i'd like to get rid of that in our regular 1v1 game layout as well").
// Fixture: SWUSim/Tests/Visual/Hand_NoCollapseButton.md. Checked in Chromium, Firefox and WebKit, desktop and phone.
//   node DevTools/ui-harness/swusim-hand-no-collapse-xbrowser.mjs          (seeds its own board; claudebot1 must be a mod)
import fs from 'fs';
import { chromium, firefox, webkit } from 'playwright';

const BASE = process.env.BASE_URL || 'http://localhost:3400/TCGEngine/';
const SHOTS = process.env.SHOTS || '';
const form = (o) => new URLSearchParams(o).toString();
const post = async (path, body, cookie) => fetch(BASE + path, { method: 'POST',
  headers: { 'Content-Type': 'application/x-www-form-urlencoded', ...(cookie ? { cookie } : {}) }, body: form(body), redirect: 'manual' });
const login = await post('AccountFiles/AttemptPasswordLogin.php', { submit: '1', userID: 'claudebot1', password: 'pass' });
const cookie = (login.headers.getSetCookie?.() || []).map(c => c.split(';')[0]).join('; ');
const seeded = JSON.parse(await (await post('SWUSim/TestSchemaSetup.php',
  { schema: fs.readFileSync(new URL('../../SWUSim/Tests/Visual/Hand_NoCollapseButton.md', import.meta.url), 'utf8') }, cookie)).text());
if (seeded.error) throw new Error(`TestSchemaSetup refused the schema: ${seeded.error}`);
const game = String(seeded.gameName);
console.log(`seeded ${game}`);

let fails = 0, checks = 0;
const check = (ok, name, msg) => { checks++; if (!ok) { fails++; console.log(`FAIL ${name} :: ${msg}`); } };
const SURFACES = [
  { label: 'desktop', viewport: { width: 1600, height: 1000 }, q: '' },
  { label: 'phone',   viewport: { width: 390,  height: 844  }, q: '&swuLayout=mobile' },
];

for (const [engine, launcher] of [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]]) {
  let b;
  try { b = await launcher.launch(); } catch (e) { check(false, engine, `could not launch: ${e.message}`); continue; }
  for (const s of SURFACES) {
    const name = `${engine}/${s.label}`;
    const p = await b.newPage({ viewport: s.viewport });
    // A player who collapsed their hand before the button went away: the saved setting must not hide the hand.
    await p.addInitScript(() => { try { localStorage.setItem('swu-hand-collapsed-v1', '1'); } catch (e) {} });
    await p.goto(`${BASE}NextTurn.php?folderPath=SWUSim&gameName=${game}&playerID=1&authKey=testschema${s.q}`, { waitUntil: 'domcontentloaded' });
    await p.waitForFunction(() => document.querySelectorAll('#myHand > span img').length >= 5, null, { timeout: 90000 }).catch(() => {});
    await p.waitForTimeout(1500);
    const r = await p.evaluate(() => {
      const slot = document.getElementById('myHandSlot'), their = document.getElementById('theirHandSlot');
      const cards = [...document.querySelectorAll('#myHand > span img')];
      const inView = cards.filter(i => { const q = i.getBoundingClientRect(); return q.height > 20 && q.top >= 0 && q.bottom <= innerHeight + 1; }).length;
      return { buttons: document.querySelectorAll('.swu-hand-collapse-btn').length,
               collapsed: !!(slot && slot.classList.contains('is-collapsed')) || !!(their && their.classList.contains('is-collapsed')),
               cards: cards.length, inView };
    });
    check(r.buttons === 0, name, `no hand collapse / expand button (found ${r.buttons})`);
    check(!r.collapsed && r.cards === 5 && r.inView === 5, name, `the hand is open even with the old "collapsed" setting saved: ${JSON.stringify(r)}`);
    if (SHOTS) { await p.waitForTimeout(5000); await p.screenshot({ path: `${SHOTS}/handnocollapse-${engine}-${s.label}.png` }); }
    await p.close();
  }
  await b.close();
}
console.log(fails ? `${fails} FAILED of ${checks}` : `PASS (${checks} checks)`);
process.exit(fails ? 1 : 0);
