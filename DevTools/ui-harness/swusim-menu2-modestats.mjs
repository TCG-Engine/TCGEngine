// The mode cards' stat lines must COUNT REAL LOBBIES.
//
// "4 players in queue" and "2 tables forming" were mockup fixtures hardcoded in MainMenu.php. The
// unit test (DevTools/tdd-regression/test_swusim_menu_lobby_stats.php) pins the derivation; this
// gate pins the WIRING end to end — a real public lobby, created through the real menu, must move
// the real card, both on the server's first paint and on the 20s poll's payload.
//
// ⚠ Its assertions are deliberately NOT a ±1 delta — see the note above check 3.
import { chromium, firefox, webkit } from 'playwright';
const SITE = 'http://localhost:3400/TCGEngine/SharedUI/Sites/SWUSim/';
const MENU = 'http://localhost:3400/TCGEngine/SharedUI/MainMenu.php';
const DECK = 'https://swudb.com/deck/LImIrpIS';               // premier: a PvP-side lobby
const TWIN = 'https://swudb.com/deck/oNDdHLCHkyz';               // Twin Suns: a 3-4 seat table
let fails = 0, checks = 0;
const bad = (n, m) => { fails++; checks++; console.log(`FAIL ${n} :: ${m}`); };
const ok  = () => { checks++; };

const statOf = (p, k) => p.evaluate(s => {
  const e = document.querySelector('.mode__stat[data-stat="' + s + '"]');
  return e ? e.textContent.trim() : null;
}, k);
// "4 players in queue" -> 4; "No one in queue" -> 0
const num = t => t == null ? null : (/^no /i.test(t) ? 0 : parseInt(t, 10));

for (const [name, launcher] of [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]]) {
  const b = await launcher.launch();
  const p = await b.newPage({ viewport: { width: 1440, height: 1000 } });
  const errs = []; p.on('pageerror', e => errs.push(e.message));

  await p.goto(SITE + 'LoginPage.php', { waitUntil: 'networkidle' });
  await p.fill('input[name="userID"]', 'claudebot1');
  await p.fill('input[name="password"]', 'pass');
  await p.click('button[type="submit"], input[type="submit"]');
  await p.waitForTimeout(2200);

  // ── 1. the line is DERIVED, not the fixture ────────────────────────────────
  await p.goto(MENU, { waitUntil: 'networkidle' });
  const pvp0 = await statOf(p, 'pvp'), multi0 = await statOf(p, 'multi');
  if (pvp0 === null || multi0 === null) { bad(name, 'the mode cards carry no [data-stat] line'); await b.close(); continue; }
  ok();
  // The fixture strings must be gone even when the counts coincidentally match.
  if (!/players? in queue|no one in queue/i.test(pvp0)) bad(name, `pvp stat reads "${pvp0}"`); else ok();
  if (!/tables? forming|no tables forming/i.test(multi0)) bad(name, `multi stat reads "${multi0}"`); else ok();

  // ── 2. the endpoint agrees with the page ───────────────────────────────────
  const api = await p.evaluate(async () => {
    const r = await fetch('/TCGEngine/SWUSim/PublicGames.php');
    return r.json();
  });
  if (!api || !api.lobbyLabels) bad(name, 'PublicGames.php carries no lobbyLabels');
  else if (api.lobbyLabels.pvp !== pvp0) bad(name, `server paint "${pvp0}" vs poll "${api.lobbyLabels.pvp}" — two wordings for one state`);
  else ok();
  if (api && api.lobbyStats && typeof api.lobbyStats.pvpPlayers === 'number') ok();
  else bad(name, 'PublicGames.php carries no numeric lobbyStats');

  // ⚠ WHY THESE ASSERTIONS ARE NOT A ±1 DELTA.
  // A lobby carries a 600s TTL, so a dev box routinely has strays from earlier runs, and a second
  // visitor joining the SAME public queue MATCHES the first — which starts a game and empties the
  // queue. A "+1" assertion is therefore red on a dirty cache and red again on a clean one that
  // happened to match. Both shapes below are true whatever else is in the cache.

  // ── 3. a PRIVATE room is never counted ─────────────────────────────────────
  // Deterministic: a private room cannot match with anyone, so nothing about it can drift, and
  // the rule it pins is the load-bearing one — counting a private room would both overstate the
  // queue and advertise that the room exists.
  const p2 = await b.newPage({ viewport: { width: 1440, height: 1000 } });
  await p2.goto(MENU, { waitUntil: 'networkidle' });
  const before = await p2.evaluate(async () => (await (await fetch('/TCGEngine/SWUSim/PublicGames.php')).json()).lobbyStats);

  await p.click('a.mode[href="#setup-pvp"]');
  await p.waitForTimeout(500);
  await p.fill('#setup-pvp input[data-detect]', DECK);
  await p.dispatchEvent('#setup-pvp input[data-detect]', 'change');
  await p.waitForTimeout(4500);
  await p.click('#setup-pvp [data-act="private"]');
  await p.waitForTimeout(7000);
  if (!/WaitingRoom\.php/.test(p.url())) bad(name, `private room did not open a lobby (at ${p.url()})`);
  else ok();

  await p2.waitForTimeout(11000);          // outlast the endpoint's own 10s payload cache
  const after = await p2.evaluate(async () => (await (await fetch('/TCGEngine/SWUSim/PublicGames.php')).json()).lobbyStats);
  if (after.pvpLobbies !== before.pvpLobbies || after.pvpPlayers !== before.pvpPlayers)
    bad(name, `a private room changed the PvP queue: ${JSON.stringify(before)} -> ${JSON.stringify(after)}`);
  else ok();
  if (after.multiTables !== before.multiTables)
    bad(name, `a private room counted as a forming table: ${JSON.stringify(before)} -> ${JSON.stringify(after)}`);
  else ok();

  // ── 4. a REAL public queue entry is counted, and the card says so ──────────
  // Twin Suns, not PvP: a table needs 3-4 players, so one joiner can never complete it into a
  // game the way two PvP joiners would. The assertion is ">= 1 table", not a delta — a stray
  // Twin Suns lobby would absorb this join rather than adding a second table.
  await p.goto(MENU, { waitUntil: 'networkidle' });
  await p.click('a.mode[href="#setup-twin-suns"]');
  await p.waitForTimeout(600);
  await p.fill('#setup-twin-suns input[data-detect]', TWIN);
  await p.dispatchEvent('#setup-twin-suns input[data-detect]', 'change');
  await p.waitForTimeout(5000);
  await p.click('#setup-twin-suns [data-act="join"]');
  await p.waitForTimeout(6000);

  await p2.waitForTimeout(11000);
  await p2.reload({ waitUntil: 'networkidle' });
  const multi1 = await statOf(p2, 'multi');
  const stats1 = await p2.evaluate(async () => (await (await fetch('/TCGEngine/SWUSim/PublicGames.php')).json()).lobbyStats);
  if (!(stats1.multiTables >= 1))
    bad(name, `a Twin Suns queue entry was not counted as a table (${JSON.stringify(stats1)})`);
  else ok();
  if (num(multi1) === null || num(multi1) < 1)
    bad(name, `second visitor's card does not show the forming table (reads "${multi1}")`);
  else ok();

  // ── 5. cancelling removes it ───────────────────────────────────────────────
  // Escape is the real cancel path (APIs/Lobbies/LeaveQueue.php). Navigation is NOT — the lobby
  // survives on its TTL — so this also stops each engine leaving a stray for the next one.
  await p.keyboard.press('Escape');
  await p.waitForTimeout(3000);
  const stats2 = await p2.evaluate(async () => (await (await fetch('/TCGEngine/SWUSim/PublicGames.php')).json()).lobbyStats);
  if (stats2.multiTables > stats1.multiTables)
    bad(name, `cancelling grew the table count: ${stats1.multiTables} -> ${stats2.multiTables}`);
  else ok();

  if (errs.length) bad(name, `pageerror ${errs[0]}`);
  await b.close();
}
console.log(fails ? `\n${fails}/${checks} mode-stat checks failed` : `\nMODE STATS LIVE — ${checks} checks`);
process.exit(fails ? 1 : 0);
