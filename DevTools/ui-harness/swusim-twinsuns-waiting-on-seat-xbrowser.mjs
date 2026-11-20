// Twin Suns: a seat that holds the turn must be told when the game is waiting on ANOTHER seat's decision.
//
// Bug report P-16 (2026-10-31, "I cant do anything"): 4-seat Twin Suns, pregame. P2, P3 and P4 had kept
// and picked resources; P1 never answered the mulligan. P3 was the turn player, held the initiative and
// had 2/2 resources and a hand — the board looked like P3's own action phase, every click did nothing, and
// nothing on screen said the game was waiting on P1. The "Waiting for…" pill only read the ONE opponent
// queue a two-seat game has (theirDecisionQueueData), and for seat 3 that is not P1's, so the turn
// player's pill stayed hidden. Seats 2 and 4 did see a pill, but it said "the other player".
//
// Fixture: fixtures/swusim-twinsuns-waiting-on-seat.gamestate.txt — game 2547 at the reported moment.
// CONTROL: the same board with P1's queue emptied, so nobody owes a decision and the game waits only on
// the turn player (P3). That keeps the fix honest: a pill that is simply always on fails it.
//
//   node DevTools/ui-harness/swusim-twinsuns-waiting-on-seat-xbrowser.mjs
// Screenshots: <tmpdir>/swusim-twinsuns-waiting-on-seat/<engine>-<width>-<case>.png (the path is printed)
import { chromium, firefox, webkit } from 'playwright';
import { readFileSync, writeFileSync, mkdirSync, rmSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';
import { tmpdir } from 'node:os';

const HERE = dirname(fileURLToPath(import.meta.url));
const ROOT = join(HERE, '..', '..');
const OUT = join(tmpdir(), 'swusim-twinsuns-waiting-on-seat');
const BASE = 'http://localhost:3400/TCGEngine/';
const FIXTURE = readFileSync(join(HERE, 'fixtures', 'swusim-twinsuns-waiting-on-seat.gamestate.txt'), 'utf8');
// P1's queue: a count line of 4 followed by the mulligan YESNO and its three follow-ups.
const P1_QUEUE = /\r\n4\r\nYESNO mulligan [^\r\n]*\r\nCUSTOM MulliganDecision\|1[^\r\n]*\r\nCUSTOM PushPregameSnapshot\|1[^\r\n]*\r\nCUSTOM ChooseStartingResource[^\r\n]*\r\n/;
const CONTROL = FIXTURE.replace(P1_QUEUE, '\r\n0\r\n');
if (CONTROL === FIXTURE) throw new Error('control variant did not apply — fixture changed?');

let fails = 0, checks = 0;
const ok = (name, cond, extra) => {
  checks++;
  if (!cond) { fails++; console.log(`FAIL ${name}${extra === undefined ? '' : '  ' + JSON.stringify(extra)}`); }
};

// ⚠ A FRESH game id per board: the web process caches a game as it was first created.
let n = 0;
const seed = (text) => {
  const game = String(97000000 + Math.floor(Math.random() * 900000) * 10 + (n++ % 10));
  const dir = join(ROOT, 'SWUSim', 'Games', game);
  rmSync(dir, { recursive: true, force: true });
  mkdirSync(dir, { recursive: true });
  writeFileSync(join(dir, 'Gamestate.txt'), text);
  return game;
};
const unseed = (game) => rmSync(join(ROOT, 'SWUSim', 'Games', game), { recursive: true, force: true });

const pill = async (page, game, seat) => {
  await page.goto(`${BASE}NextTurn.php?gameName=${game}&playerID=${seat}&folderPath=SWUSim`, { waitUntil: 'load' });
  await page.waitForFunction(() => typeof window.TurnPlayerData !== 'undefined' && !!document.getElementById('turn-miasma-message'),
                             null, { timeout: 15000 });
  await page.waitForTimeout(1500);
  return page.evaluate(() => {
    const el = document.getElementById('turn-miasma-message');
    const r = el.getBoundingClientRect();
    const cs = getComputedStyle(el);
    const shown = cs.display !== 'none' && cs.visibility !== 'hidden' && parseFloat(cs.opacity) > 0 && r.width > 0 && r.height > 0;
    return { shown, text: el.textContent.trim(), inView: r.left >= 0 && r.right <= innerWidth && r.top >= 0 && r.bottom <= innerHeight,
             mulliganPrompt: /Take a mulligan/.test(document.body.innerText) };
  });
};

const ENGINES = { chromium, firefox, webkit };
const WIDTHS = { desktop: { width: 1600, height: 1000 }, phone: { width: 390, height: 844 } };
mkdirSync(OUT, { recursive: true });
const reported = seed(FIXTURE), control = seed(CONTROL);
try {
  for (const [engine, driver] of Object.entries(ENGINES)) {
    const browser = await driver.launch();
    try {
      for (const [wName, viewport] of Object.entries(WIDTHS)) {
        const ctx = await browser.newContext({ viewport, isMobile: wName === 'phone' && engine !== 'firefox', hasTouch: wName === 'phone' });
        const page = await ctx.newPage();
        const tag = `${engine}@${wName}`;
        const shot = (c) => page.screenshot({ path: join(OUT, `${engine}-${wName}-${c}.png`) });

        // The reported moment, as the turn player: the game is waiting on P1, and P3 must be told so.
        let p = await pill(page, reported, 3);
        await shot('reported-seat3');
        ok(`${tag} reported seat 3 (turn player): pill shown`, p.shown, p);
        ok(`${tag} reported seat 3: names P1`, p.text === 'Waiting for P1', p.text);
        ok(`${tag} reported seat 3: pill on screen`, !p.shown || p.inView, p);
        // Not the turn player: the pill names the seat that owes the decision, not "the other player".
        p = await pill(page, reported, 2);
        ok(`${tag} reported seat 2: pill shown`, p.shown, p);
        ok(`${tag} reported seat 2: names P1`, p.text === 'Waiting for P1', p.text);
        // The stalled seat itself: its own prompt, no waiting pill.
        p = await pill(page, reported, 1);
        await shot('reported-seat1');
        ok(`${tag} reported seat 1: no pill`, !p.shown, p);
        ok(`${tag} reported seat 1: mulligan prompt`, p.mulliganPrompt, p);

        // CONTROL: nobody owes a decision. The turn player sees no pill; anyone else waits on the turn player.
        p = await pill(page, control, 3);
        ok(`${tag} control seat 3 (turn player): no pill`, !p.shown, p);
        p = await pill(page, control, 2);
        await shot('control-seat2');
        ok(`${tag} control seat 2: pill shown`, p.shown, p);
        ok(`${tag} control seat 2: names the turn player P3`, p.text === 'Waiting for P3', p.text);
        await ctx.close();
      }
    } finally { await browser.close(); }
  }
} finally { unseed(reported); unseed(control); }

console.log(`screenshots: ${OUT}`);
console.log(fails ? `FAIL ${fails}/${checks}` : `PASS ${checks}/${checks}`);
process.exit(fails ? 1 : 0);
