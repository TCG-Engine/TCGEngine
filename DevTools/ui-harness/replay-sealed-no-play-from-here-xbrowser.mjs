// A SEALED replay (a PvP game's Save Replay, seat-bound) has no "Play from Here": the server refuses mode 11104 for it,
// so the panel must not offer the button. A plain replay keeps it.
//
// Sealed: a 2-seat PvP fixture game (SWUSim/TestSchemaSetup.php), P1 plays a card and concedes, seat 2 downloads its
// sealed replay and imports it. Plain: the goldfish fixture replay (fixtures/replay-optionchoose-goldfish.json).
//
// Usage: node replay-sealed-no-play-from-here-xbrowser.mjs [baseURL]   ENGINES=chromium,firefox,webkit (default: all three)
import { chromium, firefox, webkit } from 'playwright';
import fs from 'node:fs';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const PLAIN = JSON.parse(fs.readFileSync(new URL('./fixtures/replay-optionchoose-goldfish.json', import.meta.url), 'utf8'));
const ALL = { chromium, firefox, webkit };
const ENGINES = Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n));
const SCHEMA = `## GIVEN
CommonSetup: bbw/rrk/{myResources:5; theirResources:5}
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithP1Hand: [SOR_095 LOF_158]
WithP2Hand: [SOR_237]

## WHEN

## EXPECT
TURNPLAYER:1`;
let allOk = true;
const results = [];
const ok = (engine, name, cond, extra = '') => { if (!cond) allOk = false; results.push([engine, name, !!cond, extra]); };

const post = async (path, form) => (await fetch(BASE + path, { method: 'POST', body: new URLSearchParams(form) })).json();
const input = (g, pid, mode, cardID = '') => fetch(BASE + 'ProcessInput.php?' + new URLSearchParams({
  gameName: g, playerID: pid, authKey: 'testschema', folderPath: 'SWUSim', mode: String(mode), cardID, responseFormat: 'json' })).then((r) => r.json());
async function importReplay(replay) {
  const j = await (await fetch(BASE + 'APIs/MatchReplay.php?action=import', {
    method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ replay }) })).json();
  if (!j.success) throw new Error('import failed: ' + JSON.stringify(j).slice(0, 300));
  return j;
}
async function sealedReplay() {
  const g = String((await post('SWUSim/TestSchemaSetup.php', { schema: SCHEMA })).gameName || '');
  if (!g) throw new Error('no fixture game');
  await input(g, '1', 10002, 'myHand-0!FSM!');
  await input(g, '1', 10006);
  const dl = await (await fetch(BASE + 'APIs/MatchReplay.php?' + new URLSearchParams({ action: 'download', folderPath: 'SWUSim', gameName: g, playerID: '2', authKey: 'testschema' }))).json();
  if ((dl.replay || {}).format !== 'tcgengine-match-replay-sealed-v1') throw new Error('not a sealed replay: ' + JSON.stringify(dl).slice(0, 200));
  return dl.replay;
}
const panelButtons = (page) => page.evaluate(() => [...document.querySelectorAll('.match-replay-actions button')].map((b) => b.textContent.trim()));

const sealed = await sealedReplay();
for (const [name, launcher] of ENGINES) {
  let browser;
  try {
    browser = await launcher.launch();
    const page = await browser.newPage();
    await page.setViewportSize({ width: 1400, height: 900 });
    for (const [label, replay, wantBranch] of [['sealed', sealed, false], ['plain', PLAIN, true]]) {
      const imp = await importReplay(replay);
      await page.goto(BASE + imp.nextTurnUrl.replace(/^\.\//, ''), { waitUntil: 'load' });
      await page.waitForFunction(() => document.querySelectorAll('.match-replay-actions button').length > 0, null, { timeout: 20000 }).catch(() => {});
      const buttons = await panelButtons(page);
      ok(name, `${label} replay: the panel is shown (Play All)`, buttons.some((t) => /Play All|Stop/.test(t)), JSON.stringify(buttons));
      ok(name, `${label} replay: ${wantBranch ? 'offers' : 'does not offer'} "Play from Here"`, buttons.includes('Play from Here') === wantBranch, JSON.stringify(buttons));
      if (label === 'sealed') {
        fs.mkdirSync(new URL('./_shots/', import.meta.url), { recursive: true });
        await page.screenshot({ path: new URL(`./_shots/replay-sealed-panel-${name}.png`, import.meta.url).pathname });
      }
    }
  } catch (e) {
    ok(name, 'harness ran', false, String(e).slice(0, 300));
  } finally {
    if (browser) await browser.close();
  }
}
for (const [e, n, p, x] of results) console.log(`${p ? 'PASS' : 'FAIL'}  ${e.padEnd(9)} ${n}${x && !p ? ' — ' + x : ''}`);
console.log(allOk ? 'ALL PASS' : 'SOME FAILED');
process.exit(allOk ? 0 : 1);
