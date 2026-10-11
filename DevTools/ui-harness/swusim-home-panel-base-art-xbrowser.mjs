// Twin Suns Home Panels: the base thumbnail shows the WHOLE base card, not a crop.
//
// Owner feature request, 2026-09-26: "for Twin Suns Home Panels, use the whole base image instead of
// a crop."
//
// WHAT WAS WRONG. The base thumbnail was painted from Images/concat/ — the 450x450 SQUARE crop (art
// only) — into a LANDSCAPE box, with background-size:cover. Measured at 68x48 (ratio 1.417) against a
// 1:1 image, cover threw away 29% of the image's height: the card's title, its HP shield and its
// aspect icon were all outside the box. A SWU base card is natively landscape (WebpImages is 628x450,
// ratio 1.3956), so showing the whole thing only needs the right folder and a box at the card's own
// ratio.
//
// ⚠ THE UNITS MUST STAY ON concat/ AND THAT IS THE POINT OF THE CONTROL BELOW. A unit thumbnail is
// SQUARE on purpose: WebpImages for a unit is the 450x628 PORTRAIT card, of which two thirds is an
// unreadable rules box at this size. "Swap every concat/ for WebpImages/" would satisfy every other
// assertion here and wreck the arenas.
//
// ⚠ BOTH SURFACES. The desktop tile (.swu-mb-base, inside .swu-home-strip) and the phone's seat row
// (.swu-sr-base) are the same Home Panel on two layouts, drawn by two functions in the same file and
// sized by two different stylesheets. Fixing one and shipping is this codebase's standing mistake.
//
//   node DevTools/ui-harness/swusim-home-panel-base-art-xbrowser.mjs
//
// It seeds its own four-seat board from the chat-matrix visual schema; nothing to set up.
import { chromium, firefox, webkit } from 'playwright';

const BASE = process.env.BASE_URL || 'http://localhost:3400/TCGEngine/';
// A SWU base card's true proportions. The box must match so background-size:cover crops NOTHING.
const CARD_RATIO = 628 / 450;   // 1.3956
const TOLERANCE  = 0.02;        // ±1.4% — enough for a 1px border rounding, far tighter than a crop

let fails = 0, checks = 0;
const bad = (n, m) => { fails++; checks++; console.log(`FAIL ${n} :: ${m}`); };
const ok  = () => { checks++; };

// ⚠ THIS BOARD MUST HAVE UNITS IN PLAY, and that is not incidental. The first version reused
// Tests/Visual/Chat_4P_WhisperMatrix.md, whose arenas are EMPTY — so the "units stay on concat/"
// control below matched zero elements and passed no matter what. A mutation that swapped every unit
// to WebpImages (the exact mistake the control exists to catch) survived it. Every opponent seat gets
// a ground unit here, and the control asserts it FOUND some before judging them.
const SCHEMA = [
  '## GIVEN',
  'CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}',
  'SkipPreGame: true',
  'WithSeatOrder: 1234',
  'WithLiveSeats: 1234',
  'WithGamePhase: ActionPhase',
  'WithActivePlayer: 1',
  'WithP3Base: SOR_026:5',
  'WithP3Leader:  SHD_014',
  'WithP3Leader2: SHD_015',
  'WithP4Base: SOR_026:8',
  'WithP4Leader:  TWI_009',
  'WithP4Leader2: TWI_010',
  'WithP2GroundArena: SOR_095:1:0',
  'WithP3GroundArena: SOR_095:1:0',
  'WithP4GroundArena: SOR_095:1:0',
  '## WHEN',
  '## EXPECT',
  'SEATCOUNT:4',
].join('\n');

const form = (o) => new URLSearchParams(o).toString();
async function seedBoard() {
  const lr = await fetch(BASE + 'AccountFiles/AttemptPasswordLogin.php', {
    method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: form({ submit: '1', userID: 'claudebot1', password: 'pass' }), redirect: 'manual',
  });
  const cookie = (lr.headers.getSetCookie?.() || []).map(c => c.split(';')[0]).join('; ');
  if (!cookie) throw new Error('no session cookie for claudebot1');
  const schema = SCHEMA;
  const r = await fetch(BASE + 'SWUSim/TestSchemaSetup.php', {
    method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded', cookie },
    body: form({ schema }), redirect: 'manual',
  });
  const j = JSON.parse(await r.text());
  if (j.error) throw new Error(`TestSchemaSetup refused the schema: ${j.error}`);
  if (j.seatCount !== 4) throw new Error(`expected a 4-seat board, got seatCount=${j.seatCount}`);
  return String(j.gameName);
}

let GAME = process.env.GAME;
if (!GAME) {
  try { GAME = await seedBoard(); console.log(`seeded board ${GAME}`); }
  catch (e) { console.log(`FAIL :: could not seed a board :: ${e.message}`); process.exit(1); }
}

// The phone lays the Home Panels out as SEAT ROWS, not tiles, so each surface names its own selector.
// The layout is picked SERVER-SIDE; ?swuLayout=mobile is the documented override.
const SURFACES = [
  { label: 'desktop', viewport: { width: 1600, height: 1000 }, query: '',
    baseSel: '#swuHomeStrips .swu-mb-base', leadSel: '#swuHomeStrips .swu-mb-leader',
    unitSel: '#swuHomeStrips .swu-mb-unit' },
  { label: 'phone',   viewport: { width: 430,  height: 932  }, query: '&swuLayout=mobile',
    // ⚠ The phone row reports unit COUNTS, not thumbnails — by design, there is no room for them.
    // So the concat/ control has nothing to measure here and is skipped rather than faked.
    baseSel: '#swuHomeStrips .swu-sr-base',  leadSel: '#swuHomeStrips .swu-sr-lead',
    unitSel: null },
];

for (const [engine, launcher] of [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]]) {
  let b;
  try { b = await launcher.launch(); }
  catch (e) { bad(engine, `could not launch: ${e.message}`); continue; }

  for (const s of SURFACES) {
    const name = `${engine}/${s.label}`;
    const p = await b.newPage({ viewport: s.viewport });
    const errs = []; p.on('pageerror', e => errs.push(e.message));
    await p.goto(`${BASE}NextTurn.php?folderPath=SWUSim&gameName=${GAME}&playerID=1&authKey=testschema${s.query}`,
                 { waitUntil: 'domcontentloaded' });
    try { await p.waitForSelector(s.baseSel, { timeout: 20000 }); }
    catch { bad(name, `no base thumbnail at "${s.baseSel}" — the Home Panels did not render`); await p.close(); continue; }
    await p.waitForTimeout(600);

    // ⚠ MEASURE THE PADDING BOX, FRACTIONALLY. Two traps, one after the other:
    //   • background-origin defaults to padding-box, so the box the image fills EXCLUDES the border.
    //     At 44px wide a 1px border either side shifts the ratio by 2% — the whole tolerance — and
    //     getBoundingClientRect alone reported a crop that was not there.
    //   • clientWidth/clientHeight are INTEGERS. The height is calc(44 * 0.717) = 31.55px, which
    //     rounds to 32 and reads as a 1% crop. Subtracting the border widths from the rect keeps it
    //     fractional and exact.
    const grab = (sel) => p.$$eval(sel, els => els.map(e => {
      const cs = getComputedStyle(e), r = e.getBoundingClientRect();
      const bx = (parseFloat(cs.borderLeftWidth) || 0) + (parseFloat(cs.borderRightWidth) || 0);
      const by = (parseFloat(cs.borderTopWidth) || 0) + (parseFloat(cs.borderBottomWidth) || 0);
      const m = cs.backgroundImage.match(/Images\/([A-Za-z]+)\/([A-Za-z0-9_]+)\.webp/) || [];
      return { w: r.width - bx, h: r.height - by, folder: m[1] || null, card: m[2] || null, size: cs.backgroundSize };
    }));

    const bases = await grab(s.baseSel);
    // A three-opponent board: without this every loop below is vacuously green on an empty list.
    if (bases.length !== 3) { bad(name, `expected 3 opponent base thumbnails, got ${bases.length}`); }
    else ok();

    for (const t of bases) {
      // ── 1. THE WHOLE CARD, not the art crop ────────────────────────────────────────────────
      if (t.folder !== 'WebpImages') {
        bad(name, `base ${t.card} is painted from Images/${t.folder}/ — expected WebpImages (the whole 628x450 card)`);
      } else ok();

      // ── 2. AND A BOX AT THE CARD'S OWN RATIO, so cover crops nothing ───────────────────────
      const ratio = t.w / t.h;
      if (!(t.w > 0 && t.h > 0)) {
        bad(name, `base ${t.card} has no size (${t.w}x${t.h})`);
      } else if (Math.abs(ratio - CARD_RATIO) > TOLERANCE) {
        const lost = Math.round(Math.abs(1 - CARD_RATIO / ratio) * 100);
        bad(name, `base ${t.card} box is ${t.w.toFixed(1)}x${t.h.toFixed(1)} (ratio ${ratio.toFixed(3)}), `
                + `expected ${CARD_RATIO.toFixed(3)} — cover is cropping about ${lost}% of the card`);
      } else ok();
    }

    // ── 3. THE CONTROL. Units stay SQUARE and stay on concat/. ────────────────────────────────
    // A unit's WebpImages is the 450x628 PORTRAIT card, mostly rules text at this size. If this goes
    // red, someone swapped every concat/ in the file and broke the arenas to fix the bases.
    if (s.unitSel) {
      const units = await grab(s.unitSel);
      // ⚠ Assert we FOUND some first. On an empty board this filter matches nothing and the control
      // is green however broken the code is — which is exactly how a blanket concat->WebpImages swap
      // survived the first cut of this harness. The schema above puts a unit on every opponent seat.
      if (units.length < 3) {
        bad(name, `expected a unit thumbnail per opponent seat, found ${units.length} — the control is vacuous`);
      } else ok();
      const wrongUnits = units.filter(u => u.folder && u.folder !== 'concat');
      if (wrongUnits.length) {
        bad(name, `${wrongUnits.length} unit thumbnail(s) left concat/ — units are square on purpose`);
      } else ok();
    }

    // ── 4. THE OTHER CONTROL. Leaders were ALREADY whole cards; they must stay that way. ──────
    const leaders = await grab(s.leadSel);
    const wrongLeads = leaders.filter(l => l.folder && l.folder !== 'WebpImages');
    if (wrongLeads.length) {
      bad(name, `${wrongLeads.length} leader thumbnail(s) left WebpImages/`);
    } else ok();

    if (errs.length) bad(name, `page errors: ${errs.slice(0, 2).join(' | ')}`); else ok();
    await p.close();
  }
  await b.close();
}

console.log(fails === 0 ? `PASS (${checks} checks, 3 engines)` : `${fails} FAILED of ${checks}`);
process.exit(fails === 0 ? 0 : 1);
