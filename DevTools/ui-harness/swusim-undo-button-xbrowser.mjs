// The Undo button must be visible whenever an undo exists — including a PUBLIC game with 3+ seats.
//
// Reported 2026-09-26 (game 1310334, 3-seat public Twin Suns): "the Undo button does not show up", for
// every seat. swuUpdateUndoUI sets the button to display:inline-block and UNDO_AVAILABLE is "true", but
// its wrapper #swuUndoSplit is display:none unless it carries .is-split — and .is-split is
// `isPrivate || seats <= 2`, i.e. off in exactly this game. Spec:
// SWUSim/Tests/Visual/UndoButton_VisibleInPublicMultiSeat.md
//
// ⚠ A schema fixture CANNOT reproduce it: TestSchemaSetup ships GAME_SEAT_COUNT empty and the client
// defaults it to 2, taking the `seats <= 2` branch. So this drives swuUpdateUndoUI directly over a real
// board page (real CSS, real header markup) with GetSWUDQVar stubbed — the technique
// ChooseOpponent_PickerShowsPlayerNames.md established for a pure function of its inputs.
//
// Usage: node swusim-undo-button-xbrowser.mjs [BASE] [SHOTS_DIR]  ENGINES=chromium,firefox,webkit
import { chromium, firefox, webkit } from 'playwright';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

const BASE   = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const SHOTS  = process.argv[3] || '/tmp';
const here   = dirname(fileURLToPath(import.meta.url));
const SCHEMA = readFileSync(resolve(here, '../../SWUSim/Tests/Visual/UndoButton_VisibleInPublicMultiSeat.md'), 'utf8');

const ALL = { chromium, firefox, webkit };
const ENGINES = Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n));

let allOk = true;
const results = [];
const ok = (name, cond, extra = '') => { if (!cond) allOk = false; results.push([name, !!cond, extra]); };

const LOGIN_USER = 'claudebot1';

async function login(page) {
  await page.goto(BASE + 'SharedUI/LoginPage.php', { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="userID"]', LOGIN_USER);
  await page.fill('input[name="password"]', 'pass');
  await Promise.all([page.waitForNavigation({ waitUntil: 'load' }).catch(() => {}), page.click('button[type="submit"]')]);
}

async function buildGame(page) {
  const setup = await (await page.request.post(BASE + 'SWUSim/TestSchemaSetup.php', { multipart: { schema: SCHEMA } })).json();
  if (setup.error) throw new Error('setup: ' + setup.error);
  for (const step of setup.whenSteps) {
    const r = await (await page.request.post(BASE + 'SWUSim/TestSchemaStep.php',
      { multipart: { gameName: String(setup.gameName), step: step.raw } })).json();
    if (r.error) throw new Error(`step "${step.raw}": ${r.error}`);
  }
  return setup.gameName;
}

// Drive the real swuUpdateUndoUI with the lobby facts of the cell under test, then measure.
async function cell(page, { isPrivate, seats, available }) {
  return page.evaluate(({ isPrivate, seats, available }) => {
    const vars = {
      GAME_IS_PRIVATE: isPrivate ? 'true' : 'false',
      GAME_SEAT_COUNT: String(seats),
      UNDO_AVAILABLE: available ? 'true' : 'false',
      UNDO_REQUIRES_CONSENT: 'false',
      PENDING_UNDO_FROM: '',
    };
    if (!window.__origGetSWUDQVar) window.__origGetSWUDQVar = window.GetSWUDQVar;
    window.GetSWUDQVar = (k) => (k in vars ? vars[k] : '');
    // The fallback path must not rescue a cell: swuUpdateUndoUI falls back to myVersionsData only when
    // UNDO_AVAILABLE is the EMPTY string, and it never is here — but blank it so a future change to that
    // rule cannot silently make the "unavailable" control pass for the wrong reason.
    window.myVersionsData = '';
    if (typeof window.swuUpdateUndoUI !== 'function') return { missing: 'swuUpdateUndoUI' };
    window.swuUpdateUndoUI(1);
    const btn = document.getElementById('swuUndoBtn');
    const split = document.getElementById('swuUndoSplit');
    const caret = document.getElementById('swuUndoMenuBtn');
    const vis = (el) => !!(el && (el.offsetWidth || el.offsetHeight));
    return {
      btnVisible: vis(btn),
      caretVisible: vis(caret),
      splitDisplay: split ? getComputedStyle(split).display : null,
      isSplit: split ? split.classList.contains('is-split') : null,
      btnDisplay: btn ? getComputedStyle(btn).display : null,
    };
  }, { isPrivate, seats, available });
}

for (const [engineName, engine] of ENGINES) {
  const browser = await engine.launch();
  try {
    const ctx = await browser.newContext({ viewport: { width: 1600, height: 900 } });
    const page = await ctx.newPage();
    await login(page);
    let gameName;
    try { gameName = await buildGame(page); }
    catch (e) { ok(`${engineName}: board built`, false, e.message); await ctx.close(); continue; }
    await page.goto(BASE + `NextTurn.php?folderPath=SWUSim&gameName=${gameName}&playerID=1&authKey=testschema`
      + `&viewerPerspective=1&opponentID=2`, { waitUntil: 'load' });
    await page.waitForTimeout(2500);

    for (const isPrivate of [false, true]) {
      for (const seats of [2, 3, 4]) {
        const label = `${isPrivate ? 'private' : 'public'}/${seats}-seat`;

        const on = await cell(page, { isPrivate, seats, available: true });
        if (on.missing) { ok(`${engineName} ${label}: undo UI present`, false, 'missing ' + on.missing); continue; }
        // THE assertion. An undo exists, so the player must be able to reach it — in every cell.
        ok(`${engineName} ${label}: Undo button is visible when an undo exists`, on.btnVisible,
           `split=${on.splitDisplay} is-split=${on.isSplit} btn=${on.btnDisplay}`);
        // The caret keeps its OWN rule: it is the thing .is-split was always about.
        const caretExpected = isPrivate || seats <= 2;
        ok(`${engineName} ${label}: caret ${caretExpected ? 'shown' : 'hidden'} per its own rule`,
           on.caretVisible === caretExpected, `caretVisible=${on.caretVisible}`);

        // NEGATIVE CONTROL — without this "always show the wrapper" would pass and leave an empty
        // control floating in the header between actions.
        const off = await cell(page, { isPrivate, seats, available: false });
        ok(`${engineName} ${label}: Undo button hidden when there is no undo`, !off.btnVisible,
           `split=${off.splitDisplay} btn=${off.btnDisplay}`);
      }
    }
    // ── The request popup reaches EVERY opponent (owner ruling 2026-09-26) ──────────────────────────
    // `otherPlayer = myPlayerID === 1 ? 2 : 1` asked exactly one seat: a request from seat 3 was shown
    // to nobody, and a request from seat 1 was shown only to seat 2.
    const popupFor = (viewerSeat, requesterSeat, isTeamGame) => page.evaluate(
      ({ viewerSeat, requesterSeat, isTeamGame }) => {
        const vars = { GAME_IS_PRIVATE: 'false', GAME_SEAT_COUNT: '4', UNDO_AVAILABLE: 'true',
                       UNDO_REQUIRES_CONSENT: 'true', PENDING_UNDO_FROM: String(requesterSeat) };
        window.GetSWUDQVar = (k) => (k in vars ? vars[k] : '');
        window.SWUIsTeamGame = !!isTeamGame;
        const old = document.getElementById('swu-undo-request-modal');
        if (old) old.remove();
        window.swuUpdateUndoUI(viewerSeat);
        const m = document.getElementById('swu-undo-request-modal');
        return { shown: !!m, text: m ? (m.textContent || '').trim().slice(0, 60) : '' };
      }, { viewerSeat, requesterSeat, isTeamGame });

    for (const [viewer, requester, expected, why] of [
      [1, 3, true,  'opponent of the requester'],
      [2, 3, true,  'the OTHER opponent — both are asked, it is not one nominated seat'],
      [3, 3, false, 'the requester is never asked to answer their own request'],
      [2, 1, true,  '2-seat shape still works'],
      [1, 1, false, 'requester, 2-seat shape'],
    ]) {
      const r = await popupFor(viewer, requester, false);
      ok(`${engineName} seat ${viewer} vs request from ${requester}: popup ${expected ? 'shown' : 'hidden'} (${why})`,
         r.shown === expected, r.text || `shown=${r.shown}`);
    }
    // Team Suns: a teammate must NOT be asked — the server (OpponentsOf) would refuse their answer, so
    // offering them the buttons would be a dead end.
    {
      const mate = await popupFor(3, 1, true);   // seats 1 and 3 are the same team under swuTeamOf
      ok(`${engineName} team game: a TEAMMATE of the requester is not asked`, mate.shown === false, mate.text);
      const foe = await popupFor(2, 1, true);
      ok(`${engineName} team game: an OPPONENT is still asked`, foe.shown === true, foe.text);
    }
    await page.evaluate(() => { const m = document.getElementById('swu-undo-request-modal'); if (m) m.remove(); });

    // Restore, then shoot the reported cell for a human look.
    await cell(page, { isPrivate: false, seats: 3, available: true });
    await page.screenshot({ path: `${SHOTS}/swusim-undo-${engineName}-public3.png` }).catch(() => {});
    await ctx.close();
  } finally {
    await browser.close();
  }
}

for (const [name, pass, extra] of results) console.log(`${pass ? 'PASS' : 'FAIL'}  ${name}${extra ? '  — ' + extra : ''}`);
console.log(allOk ? '\nALL PASS' : '\nFAILURES');
process.exit(allOk ? 0 : 1);
