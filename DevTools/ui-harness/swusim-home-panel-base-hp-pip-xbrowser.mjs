// Twin Suns Home Panels: every opponent's base thumbnail carries a STATIC printed-HP pip, top-left.
//
// Feature request, 2026-10-08: "add an HP badge to the Twin Suns home panels so that it is easier to see
// the total HP of a base card from that view at the start of a game. this counter should not count
// down. it should be a static printed HP of the base." Owner picked the TOP-LEFT corner off a design
// canvas: that is where a base card prints its own HP, so the pip reads as the card's number, enlarged.
// Then (same day): drawn with the usual HP badge art, and lifted to overhang the top edge, where the
// base health bar will run.
//
// WHAT WAS WRONG. The tile draws the whole base card (WebpImages), printed HP included, but at 68x49
// (desktop) / 46x34 (phone) that printed number is ~6px tall — on the screen, not readable.
//
// ⚠ STATIC, NOT A COUNTDOWN. The P4 base below carries 12 damage. A pip that shows HP - damage (16)
// instead of the printed 28 is exactly what the request ruled out, and the expected values here come
// from CardHp() itself, never from the damage-adjusted number.
//
// ⚠ BOTH SURFACES. The desktop tile (.swu-mb-base) and the phone row (.swu-sr-base) are drawn by two
// functions (swuRenderMiniBoard / swuRenderSeatRow) and sized by two stylesheets.
//
// ⚠ DEFEATED TILE KEEPS IT. P3 is eliminated on this board (LiveSeats=124): its tile stays, greyed, and
// so does its pip — the panel still describes that seat.
//
//   node DevTools/ui-harness/swusim-home-panel-base-hp-pip-xbrowser.mjs
//
// Seeds its own four-seat board; nothing to set up. Expected HP values are derived from the running
// container's card dictionary, so a card-data change cannot strand a literal here.
import { chromium, firefox, webkit } from 'playwright';
import { execFileSync } from 'node:child_process';

const BASE = process.env.BASE_URL || 'http://localhost:3400/TCGEngine/';
const CONTAINER = 'otmtcge-swusim-web-server-1';

let fails = 0, checks = 0;
const bad = (n, m) => { fails++; checks++; console.log(`FAIL ${n} :: ${m}`); };
const ok  = () => { checks++; };

// Three bases with three DIFFERENT printed HP values, so a pip that reads the wrong seat cannot pass:
// P2 Security Complex (25, fresh — seat 2's base is CommonSetup's theirBase, there is no WithP2Base), P3 Colossus (35, eliminated), P4 Starlight Temple (28, 12 damage).
const SCHEMA = [
  '## GIVEN',
  'CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010; theirBase:SOR_019}',
  'SkipPreGame: true',
  'WithSeatOrder: 1234',
  'WithLiveSeats: 124',
  'WithGamePhase: ActionPhase',
  'WithActivePlayer: 1',
  'WithP3Base: JTL_021:0',
  'WithP3Leader:  SHD_014',
  'WithP3Leader2: SHD_015',
  'WithP4Base: LOF_024:12',
  'WithP4Leader:  TWI_009',
  'WithP4Leader2: TWI_010',
  'WithP2GroundArena: SOR_095:1:0',
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
  const r = await fetch(BASE + 'SWUSim/TestSchemaSetup.php', {
    method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded', cookie },
    body: form({ schema: SCHEMA }), redirect: 'manual',
  });
  const j = JSON.parse(await r.text());
  if (j.error) throw new Error(`TestSchemaSetup refused the schema: ${j.error}`);
  if (j.seatCount !== 4) throw new Error(`expected a 4-seat board, got seatCount=${j.seatCount}`);
  return String(j.gameName);
}

// Printed HP straight from the engine's dictionary — the same CardHp() the base-defeat check uses.
function printedHP(cardIDs) {
  const out = execFileSync('docker', ['exec', '-w', '/var/www/html/TCGEngine', CONTAINER, 'php', '-d', 'xdebug.mode=off', '-r',
    'require "SWUSim/GeneratedCode/GeneratedCardDictionaries.php"; foreach (array_slice($argv, 1) as $c) echo $c, " ", intval(CardHp($c)), "\\n";',
    ...cardIDs], { encoding: 'utf8' });
  return Object.fromEntries(out.trim().split('\n').map(l => l.split(' ')).map(([c, h]) => [c, Number(h)]));
}

let GAME = process.env.GAME;
if (!GAME) {
  try { GAME = await seedBoard(); console.log(`seeded board ${GAME}`); }
  catch (e) { console.log(`FAIL :: could not seed a board :: ${e.message}`); process.exit(1); }
}
const HP = printedHP(['SOR_019', 'JTL_021', 'LOF_024']);
// The fixture is only discriminating if the three values differ and the damaged one is not its own
// countdown. Guard it so a card-data change cannot quietly make the board vacuous.
if (new Set(Object.values(HP)).size !== 3 || Object.values(HP).some(v => !(v > 12))) {
  console.log(`FAIL :: fixture bases no longer have three distinct printed HP values above 12: ${JSON.stringify(HP)}`);
  process.exit(1);
}

const SURFACES = [
  { label: 'desktop', viewport: { width: 1600, height: 1000 }, query: '',
    tileSel: '#swuHomeStrips .swu-home-strip', baseSel: '.swu-mb-base' },
  { label: 'phone',   viewport: { width: 430,  height: 932  }, query: '&swuLayout=mobile',
    tileSel: '#swuHomeStrips .swu-seat-row',   baseSel: '.swu-sr-base' },
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
    try { await p.waitForSelector(`${s.tileSel} ${s.baseSel}`, { timeout: 20000 }); }
    catch { bad(name, `no base thumbnail under "${s.tileSel}" — the Home Panels did not render`); await p.close(); continue; }
    await p.waitForTimeout(600);

    const tiles = await p.$$eval(s.tileSel, (els, baseSel) => els.map(t => {
      const base = t.querySelector(baseSel);
      const card = ((base && getComputedStyle(base).backgroundImage.match(/WebpImages\/([A-Za-z0-9_]+)\.webp/)) || [])[1] || null;
      const pips = base ? [...base.querySelectorAll('.swu-mb-basehp')] : [];
      const br = base ? base.getBoundingClientRect() : null;
      const pip = pips[0] || null, pr = pip ? pip.getBoundingClientRect() : null, cs = pip ? getComputedStyle(pip) : null;
      return {
        defeated: t.classList.contains('is-defeated'), card, pipCount: pips.length,
        text: pip ? pip.textContent.trim() : null, title: pip ? pip.getAttribute('title') : null,
        position: cs ? cs.position : null, pointer: cs ? cs.pointerEvents : null,
        dx: pr ? pr.left - br.left : null, dy: pr ? pr.top - br.top : null,
        right: pr ? br.right - pr.right : null, bottom: pr ? br.bottom - pr.bottom : null,
        pw: pr ? pr.width : 0, ph: pr ? pr.height : 0, bw: br ? br.width : 0, bh: br ? br.height : 0,
        tileDy: pr ? pr.top - t.getBoundingClientRect().top : null,
      };
    }), s.baseSel);

    // Three opponents — without this every per-tile check below is vacuously green on an empty list.
    if (tiles.length !== 3) bad(name, `expected 3 opponent tiles, got ${tiles.length}`); else ok();
    if (!tiles.some(t => t.defeated)) bad(name, 'no defeated tile — the eliminated-seat case is vacuous'); else ok();

    for (const t of tiles) {
      const want = HP[t.card];
      const who = `${t.card}${t.defeated ? ' (defeated)' : ''}`;
      if (want === undefined) { bad(name, `tile base ${t.card} is not one of the fixture bases`); continue; }
      // ── 1. ONE pip per base, carrying the PRINTED HP (not HP - damage) ──────────────────────
      if (t.pipCount !== 1) { bad(name, `${who}: expected exactly one HP pip, found ${t.pipCount}`); continue; }
      ok();
      if (t.text !== String(want)) bad(name, `${who}: pip reads "${t.text}", printed HP is ${want}`); else ok();
      if (!t.title || !t.title.includes(String(want))) bad(name, `${who}: tooltip "${t.title}" does not name ${want}`); else ok();
      // ── 2. STYLED: the rule applied (absolute corner overlay, click-through to the base) ────
      // A position other than absolute means the stylesheet rule never matched — the pip would sit
      // in the flex flow and push the damage token off-centre.
      if (t.position !== 'absolute') bad(name, `${who}: pip is position:${t.position} — its CSS did not apply`); else ok();
      if (t.pointer !== 'none') bad(name, `${who}: pip takes pointer events — it would block the base's hover/click`); else ok();
      // ── 3. TOP-LEFT, OVERHANGING the top edge ───────────────────────────────────────────────
      // Owner 2026-10-08: "move the badge up a bit. it can fall off the card some" — it sits where the
      // base health bar runs. So it must overhang (dy < 0), but by less than half its height, so it
      // still reads as belonging to this card.
      if (!(t.dx >= 0 && t.dx <= 4)) bad(name, `${who}: pip is not at the left edge (dx ${t.dx.toFixed(1)})`); else ok();
      if (!(t.dy < 0 && t.dy > -t.ph / 2))
        bad(name, `${who}: pip should overhang the top edge by under half its height (dy ${t.dy.toFixed(1)}, h ${t.ph.toFixed(1)})`); else ok();
      if (!(t.right >= 0 && t.bottom >= 0)) bad(name, `${who}: pip spills past the thumbnail's right/bottom`); else ok();
      // The overhang must stay inside its own panel: past the panel's top it collides with the row above
      // (phone) or the strip edge (desktop).
      if (!(t.tileDy >= 0)) bad(name, `${who}: pip pokes ${(-t.tileDy).toFixed(1)}px above its panel`); else ok();
      // Big enough to read, small enough to stay a corner marker rather than a lid over the card.
      if (!(t.ph >= 11 && t.ph <= t.bh * 0.5 && t.pw <= t.bw * 0.45))
        bad(name, `${who}: pip is ${t.pw.toFixed(1)}x${t.ph.toFixed(1)} on a ${t.bw.toFixed(1)}x${t.bh.toFixed(1)} base`); else ok();
    }

    // ── 4. OPPONENT TILES ONLY. The viewer's own base is drawn full size and gets no pip. ──────
    const total = await p.$$eval('.swu-mb-basehp', els => els.length);
    if (total !== tiles.length) bad(name, `${total} pips on the page for ${tiles.length} tiles — one leaked outside the Home Panels`); else ok();

    if (errs.length) bad(name, `page errors: ${errs.slice(0, 2).join(' | ')}`); else ok();
    await p.close();
  }
  await b.close();
}

console.log(fails === 0 ? `PASS (${checks} checks, 3 engines)` : `${fails} FAILED of ${checks}`);
process.exit(fails === 0 ? 0 : 1);
