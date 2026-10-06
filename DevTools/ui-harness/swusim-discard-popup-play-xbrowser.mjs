// A playable discard card that is NOT the top card must be playable from the discard POPUP.
//
// THE BUG (game 1438045, 2026-10-02). P2's Stolen AT-Hauler was defeated (OTPF: P1 may play it free),
// then P1 defeated P2's Emissary's Sheathipede — so the Sheathipede became the TOP of P2's discard.
// The discard slot is `Mode=Single(Latest)`: it renders ONLY the top card. The server still offered the
// AT-Hauler (opponentPlayableDiscards idx 10) and the slot still glowed gold, but:
//   • the glow pass (refreshDiscardCardGlows) only looked inside #theirDiscardSlot, where idx 10 is not
//     rendered — and the popup is built later, asynchronously, into #popupContainer;
//   • the play click (handleDiscardClick) was bound to the SLOT only, so a popup card fell through to
//     the generic CardClick → FSM, which does nothing for a discard card.
// So "may play it from the discard" silently worked only while the card happened to be on top. The
// same held for your OWN discard (TPF/TPP — playableDiscards). This drives both through the real UI.
//
// Fixture: fixtures/swusim-discard-popup-play.gamestate.txt — game 1438045 at the reported moment.
//   node DevTools/ui-harness/swusim-discard-popup-play-xbrowser.mjs
import { chromium, firefox, webkit } from 'playwright';
import { readFileSync, writeFileSync, mkdirSync, rmSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

const HERE = dirname(fileURLToPath(import.meta.url));
const ROOT = join(HERE, '..', '..');
const BASE = 'http://localhost:3400/TCGEngine/';
const FIXTURE = readFileSync(join(HERE, 'fixtures', 'swusim-discard-popup-play.gamestate.txt'), 'utf8');
// OWN-discard variant: P1's Snub Fighter Squadron (ASH_194, 6th of 7 — NOT the top) may be played free.
const OWN_FIXTURE = FIXTURE.replace(/^ASH_194 PLAY 6$/m, 'ASH_194 PLAY 6 TPF');
if (OWN_FIXTURE === FIXTURE) throw new Error('own-discard variant did not apply — fixture changed?');

let fails = 0, checks = 0;
const ok = (name, cond, extra) => {
  checks++;
  if (!cond) { fails++; console.log(`FAIL ${name}${extra === undefined ? '' : '  ' + JSON.stringify(extra)}`); }
};

const seed = (game, text) => {
  const dir = join(ROOT, 'SWUSim', 'Games', game);
  rmSync(dir, { recursive: true, force: true });
  mkdirSync(dir, { recursive: true });
  writeFileSync(join(dir, 'Gamestate.txt'), text);
};
const state = (game) => readFileSync(join(ROOT, 'SWUSim', 'Games', game, 'Gamestate.txt'), 'utf8');

// [slot, popup-card mzID that must be playable, a card that must NOT glow, what proves the play landed]
const CASES = [
  { tag: 'opponent', fixture: FIXTURE, slot: '#theirDiscardSlot', target: 'theirDiscard-10', decoy: 'theirDiscard-11',
    // ⚠ Judge by the PILE, not the log: the log already says "P1 played … from P2's discard pile" from
    // round 7 (the copy that WAS on top), so a log match passes with the bug in place.
    landed: s => !/^JTL_221 PLAY 8 OTPF$/m.test(s) },
  { tag: 'own', fixture: OWN_FIXTURE, slot: '#myDiscardSlot', target: 'myDiscard-5', decoy: 'myDiscard-6',
    landed: s => !/^ASH_194 PLAY 6/m.test(s) },
];
// The top card of each pile in the fixture (Single(Latest) renders only this one).
CASES[0].top = 'theirDiscard-11';
CASES[1].top = 'myDiscard-6';
// TOP-playable variant: P1's own top card (Onyx Squadron Brute, JTL_033) may be played free. Clicking the
// pile must still PLAY it in one click, as before — opening the viewer instead would cost a click.
const TOP_FIXTURE = FIXTURE.replace(/^JTL_033 PLAY 8$/m, 'JTL_033 PLAY 8 TPF');
if (TOP_FIXTURE === FIXTURE) throw new Error('top-playable variant did not apply — fixture changed?');

const openBoard = async (page, game) => {
  await page.goto(`${BASE}NextTurn.php?gameName=${game}&playerID=1&folderPath=SWUSim`, { waitUntil: 'load' });
  await page.waitForFunction(() => !!(window.myActionsData && (window.myActionsData.opponentPlayableDiscards
                                                               || window.myActionsData.playableDiscards)), null, { timeout: 15000 });
  await page.waitForTimeout(800);
};
const popupOpen = (page, zone) => page.evaluate(z => {
  const el = document.getElementById(z + 'Popup');
  return !!el && el.style.display !== 'none';
}, zone);

// Pile-click edge cases, once per engine × width.
async function pileEdgeCases(browser, engine, width) {
  const tag = `${engine}@${width}`;
  const fresh = () => String(90000000 + Math.floor(Math.random() * 9000000) + n++);
  const vp = { width, height: width > 600 ? 950 : 844 };

  // 1. The count bubble still opens the viewer, and a second click still CLOSES it. The bubble carries its
  //    own ShowZonePopup toggle; if the pile click let it through, both would toggle on every click. That
  //    is invisible when OPENING (the popup loads async, so both calls see it shut and both open it) but
  //    a second click closes then instantly re-opens. Dispatched with el.click(): the open viewer can sit
  //    over the pile, and a coordinate click would land on the viewer instead.
  let game = fresh(); seed(game, FIXTURE);
  let page = await browser.newPage({ viewport: vp });
  await openBoard(page, game);
  const clickBubble = () => page.evaluate(() => document.querySelector('#theirDiscardSlot .counter-bubble').click());
  await clickBubble();
  await page.waitForTimeout(1500);
  ok(`${tag}: the count bubble still opens the viewer`, await popupOpen(page, 'theirDiscard'));
  await clickBubble();
  await page.waitForTimeout(1500);
  ok(`${tag}: a second count-bubble click closes the viewer`, !(await popupOpen(page, 'theirDiscard')));

  // 2. While a selection is active, a pile click belongs to the selection — never open the viewer over it.
  await page.evaluate(() => { if (typeof ClosePopup === 'function') ClosePopup(); });
  await page.evaluate(() => { window.SelectionMode = Object.assign({}, window.SelectionMode || {}, { active: true }); });
  await page.locator('#theirDiscardSlot [data-mzid="theirDiscard-11"] img').first().click({ force: true });
  await page.waitForTimeout(1200);
  ok(`${tag}: a pile click during an active selection does not open the viewer`, !(await popupOpen(page, 'theirDiscard')));
  await page.close(); rmSync(join(ROOT, 'SWUSim', 'Games', game), { recursive: true, force: true });

  // 3. A PLAYABLE top card still plays in one click, with no viewer.
  game = fresh(); seed(game, TOP_FIXTURE);
  page = await browser.newPage({ viewport: vp });
  await openBoard(page, game);
  await page.locator('#myDiscardSlot [data-mzid="myDiscard-6"] img').first().click({ force: true });
  let played = false;
  for (let i = 0; i < 20 && !played; i++) { await page.waitForTimeout(300); played = !/^JTL_033 PLAY 8 TPF$/m.test(state(game)); }
  ok(`${tag}: a playable TOP card still plays straight from the pile`, played);
  ok(`${tag}: …without opening the viewer`, !(await popupOpen(page, 'myDiscard')));
  await page.close(); rmSync(join(ROOT, 'SWUSim', 'Games', game), { recursive: true, force: true });
}

let n = 0;
const ENGINES = [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]];

for (const [engine, launcher] of ENGINES) {
  const browser = await launcher.launch();
  for (const width of [1600, 390]) {
    await pileEdgeCases(browser, engine, width);
    for (const c of CASES) {
      const tag = `${engine}@${width} ${c.tag}`;
      // A FRESH game name every run, never a fixed one: the engine caches gamestate in APCu by game name,
      // so re-seeding a reused name's Gamestate.txt is ignored and the board loads the PREVIOUS run's
      // post-play state (nothing playable) — which once made a mutation run fail for the wrong reason.
      const game = String(90000000 + Math.floor(Math.random() * 9000000) + n++);
      seed(game, c.fixture);
      const page = await browser.newPage({ viewport: { width, height: width > 600 ? 950 : 844 } });
      const errs = []; page.on('pageerror', e => errs.push(e.message));
      await page.goto(`${BASE}NextTurn.php?gameName=${game}&playerID=1&folderPath=SWUSim`, { waitUntil: 'load' });
      await page.waitForFunction(() => !!(window.myActionsData && (window.myActionsData.opponentPlayableDiscards
                                                                   || window.myActionsData.playableDiscards)), null, { timeout: 15000 });
      await page.waitForTimeout(800);

      // Open the full pile the way a player does: click the PILE ITSELF — its top card's art, not the count
      // bubble (owner, 2026-10-02: players did not find the bubble). The top card here is NOT playable.
      await page.locator(`${c.slot} [data-mzid="${c.top}"] img`).first().click({ force: true });
      const popupCard = page.locator(`#popupContainer [data-mzid="${c.target}"]`);
      await popupCard.waitFor({ state: 'attached', timeout: 8000 }).catch(() => {});
      ok(`${tag}: the popup lists the playable card`, (await popupCard.count()) === 1);
      await page.waitForTimeout(300);

      const glow = await page.evaluate(([t, d]) => {
        const q = id => document.querySelector(`#popupContainer [data-mzid="${id}"]`);
        // The CLASS alone is not a glow: it shipped with no popup CSS rule and was invisible (owner
        // screenshot, 2026-10-02). Read the drawn shadow off the card image as well.
        const img = q(t) && q(t).querySelector('img:not(.counter-image-icon)');
        return { target: !!q(t) && q(t).classList.contains('discard-playable'),
                 decoy:  !!q(d) && q(d).classList.contains('discard-playable'),
                 shadow: img ? getComputedStyle(img).boxShadow : null };
      }, [c.target, c.decoy]);
      ok(`${tag}: the playable card glows in the popup`, glow.target, glow);
      ok(`${tag}: the popup glow is actually drawn (gold shadow on the card image)`,
         /rgba?\(240, 192, 64/.test(glow.shadow || ''), glow.shadow);
      ok(`${tag}: a non-playable card does not glow`, !glow.decoy, glow);

      const before = state(game);
      await popupCard.click({ force: true }).catch(() => {});
      let landed = false;
      for (let i = 0; i < 20 && !landed; i++) { await page.waitForTimeout(300); landed = c.landed(state(game)); }
      ok(`${tag}: clicking it in the popup plays it`, landed && state(game) !== before);
      ok(`${tag}: no page errors`, errs.length === 0, errs.slice(0, 3));
      await page.close();
      rmSync(join(ROOT, 'SWUSim', 'Games', game), { recursive: true, force: true });
    }
  }
  await browser.close();
}
console.log(fails ? `${fails}/${checks} FAILED` : `PASS (${checks} checks)`);
process.exit(fails ? 1 : 0);
