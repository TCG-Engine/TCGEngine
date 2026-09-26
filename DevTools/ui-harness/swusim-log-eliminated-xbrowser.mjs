// The ELIMINATED log line is RED — computed, on both boards, in all three engines.
//
// Owner, 2026-09-26: "add a red log when someone gets really eliminated that says '<player>
// eliminated! Game will end at the end of this phase'". A real elimination puts the whole game on a
// timer (CR 12.7.1 — it ends at the end of the current phase, highest remaining base HP wins), and
// in game 1311538 the removal and the abrupt ending were three turns apart with nothing on screen
// connecting them.
//
// ⚠ WHY A BROWSER GATE AND NOT ONLY THE SOURCE SCAN. The PHP half
// (SWUSim/DevTools/tests/log_eliminated_colour_surfaces_test.php) proves the token and the rule are
// in both stylesheets. It CANNOT see specificity: `.swu-log-entry { color: … }` sits right above the
// per-type rules and matches every row, so a single more-specific rule — or losing the cascade order
// — leaves the source scan green and every line grey. Only a computed style can tell you.
//
// ⚠ AND IT CHECKS THE NEGATIVE. "Is there a red line" passes for a build that reds the whole panel,
// and for one that reds a CONCEDE too — which would be a lie, since a concession removes the seat
// but does NOT end the game (owner ruling 2026-09-26). The fixture seeds both an eliminated seat and
// a conceded one for exactly that reason.
//
//   node DevTools/ui-harness/swusim-log-eliminated-xbrowser.mjs          (seeds its own board)
//   GAME=<id> node DevTools/ui-harness/swusim-log-eliminated-xbrowser.mjs (reuse an existing one)
import { chromium, firefox, webkit } from 'playwright';
import fs from 'node:fs';

const BASE = process.env.BASE_URL || 'http://localhost:3400/TCGEngine/';

// TestSchemaSetup.php is mod-only, so this needs a real session; any account will do.
async function seedBoard() {
  const form = (o) => new URLSearchParams(o).toString();
  const post = async (path, body, cookie) => fetch(BASE + path, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', ...(cookie ? { cookie } : {}) },
    body: form(body), redirect: 'manual',
  });
  const login = await post('AccountFiles/AttemptPasswordLogin.php',
                           { submit: '1', userID: 'claudebot1', password: 'pass' });
  const cookie = (login.headers.getSetCookie?.() || []).map(c => c.split(';')[0]).join('; ');
  if (!cookie) throw new Error('login returned no session cookie (is claudebot1 a mod on this box?)');
  const schema = fs.readFileSync(new URL('../../SWUSim/Tests/Visual/Log_EliminatedRed.md', import.meta.url), 'utf8');
  const res  = await post('SWUSim/TestSchemaSetup.php', { schema }, cookie);
  const json = JSON.parse(await res.text());
  if (json.error) throw new Error(`TestSchemaSetup refused the schema: ${json.error}`);
  if (json.seatCount !== 4) throw new Error(`expected a 4-seat board, got seatCount=${json.seatCount}`);
  return String(json.gameName);
}

let GAME = process.env.GAME;
if (!GAME) {
  try { GAME = await seedBoard(); console.log(`seeded board ${GAME}`); }
  catch (e) { console.log(`FAIL :: could not seed a board :: ${e.message}`); process.exit(1); }
}

const ELIM_RGB = 'rgb(224, 80, 80)';          // --swu-log-eliminated #e05050
const WARNING  = 'The game will end at the end of this phase';

let fails = 0, checks = 0;
const bad = (n, m) => { fails++; checks++; console.log(`FAIL ${n} :: ${m}`); };
const ok  = () => { checks++; };

// ⚠ BOTH BOARDS. The phone layout is a SEPARATE stylesheet (GameLayoutMobile.php) with its own copy
// of the palette, so a fix applied to GameLayout.php alone looks complete and ships half done. The
// layout is picked SERVER-SIDE and `?swuLayout=mobile` is the documented override.
const SURFACES = [
  { label: 'desktop', viewport: { width: 1600, height: 1000 }, query: '' },
  { label: 'phone',   viewport: { width: 390,  height: 844  }, query: '&swuLayout=mobile' },
];

for (const [engine, launcher] of [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]]) {
  let b;
  try { b = await launcher.launch(); }
  catch (e) { bad(engine, `could not launch: ${e.message}`); continue; }

  for (const surface of SURFACES) {
    const name = `${engine}/${surface.label}`;
    const p = await b.newPage({ viewport: surface.viewport });
    await p.goto(`${BASE}NextTurn.php?folderPath=SWUSim&gameName=${GAME}&playerID=1&authKey=testschema${surface.query}`,
                 { waitUntil: 'domcontentloaded' });
    try {
      await p.waitForSelector('.swu-log-entry', { timeout: 20000 });
    } catch {
      bad(name, 'no log rows on the board — it never seeded');
      await p.close();
      continue;
    }

    const rows = await p.$$eval('.swu-log-entry', els => els.map(el => ({
      cls:    el.className,
      text:   (el.textContent || '').trim(),
      color:  getComputedStyle(el).color,
      weight: getComputedStyle(el).fontWeight,
    })));

    // Sanity: this really is the seeded board. Without it every check below is vacuously green.
    // ⚠ ONE genuine elimination and ONE administrative removal, and they carry DIFFERENT TYPES —
    // .swu-log-ELIMINATED vs .swu-log-REMOVED — even though both rows read "has been eliminated!".
    // That is the ruling in one line (owner, 2026-09-26): red is reserved for the case that means
    // the game is ending this phase.
    const elim    = rows.filter(r => /swu-log-ELIMINATED\b/.test(r.cls));
    const removed = rows.filter(r => /swu-log-REMOVED\b/.test(r.cls));
    const concede = rows.filter(r => /swu-log-CONCEDE\b/.test(r.cls));
    const plain   = rows.filter(r => /swu-log-(ATTACK|PLAY)\b/.test(r.cls));
    if (elim.length !== 1 || removed.length !== 1 || concede.length !== 1 || plain.length !== 2) {
      bad(name, `expected 1 ELIMINATED + 1 REMOVED + 1 CONCEDE + 2 plain rows, got ${elim.length}/${removed.length}/${concede.length}/${plain.length} of ${rows.length}`);
      await p.close();
      continue;
    }

    // 1. The genuine elimination is red, semibold, and carries the warning. The sentence, not the
    //    colour, is what tells a player what is about to happen — assert both.
    elim[0].color === ELIM_RGB
      ? ok() : bad(name, `ELIMINATED row is ${elim[0].color}, expected ${ELIM_RGB} :: "${elim[0].text.slice(0, 50)}"`);
    parseInt(elim[0].weight, 10) >= 600
      ? ok() : bad(name, `ELIMINATED row weight is ${elim[0].weight}, expected >= 600`);
    elim[0].text.includes(WARNING)
      ? ok() : bad(name, 'the ELIMINATED row does not carry the phase-end warning');

    // 2. THE NEGATIVES, and the real content of the check. The administrative removal says
    //    "eliminated" in its TEXT, so anything keying off the wording rather than the type would
    //    redden it — and a build that simply reddens the whole panel passes "is there a red line".
    removed[0].color !== ELIM_RGB
      ? ok() : bad(name, 'the REMOVED row is red — an administrative exit must stay neutral');
    parseInt(removed[0].weight, 10) < 600
      ? ok() : bad(name, `the REMOVED row is bold (${removed[0].weight}) — it must not read as urgent`);
    removed[0].text.includes(WARNING)
      ? bad(name, 'the REMOVED row promises the game is ending, which is false') : ok();
    // It must also match the ORDINARY rows, not merely differ from red.
    removed[0].color === plain[0].color
      ? ok() : bad(name, `the REMOVED row is ${removed[0].color} but ordinary rows are ${plain[0].color}`);
    concede[0].color !== ELIM_RGB
      ? ok() : bad(name, 'the CONCEDE row is red — a concession does not end the game');
    for (const r of plain) {
      r.color !== ELIM_RGB ? ok() : bad(name, `a plain ${r.cls} row is red — the rule is matching every entry`);
    }

    await p.close();
  }
  await b.close();
}

console.log(`\n${checks - fails}/${checks} checks passed`);
process.exit(fails === 0 ? 0 : 1);
