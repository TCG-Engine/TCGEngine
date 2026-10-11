// A refused engine action must TELL the player — Chromium + Firefox + WebKit.
//
// Reported 2026-09-25: "clicking Ability did nothing." The server was answering "Invalid auth key"
// (a hosted game's seat auth lives only in APCu — no disk fallback — so an eviction or an httpd restart
// strands a live game), but the client threw the reply away: ProcessInput answers a refusal with HTTP
// 200 and the reason as the BODY, so SubmitInput's .catch never fired and its .then ignored the value.
// Every refusal on that channel was invisible, not just the auth one.
//
// ⚠ THE AUTH REFUSAL CANNOT BE PROVOKED LOCALLY: SimGameValidateSeatAuth's first line is
// `if (SimGameIsDevelopmentEnvironment()) return true;`, and the dev container is DEVENV. So the
// END-TO-END arm uses "Invalid game name." — checked in ProcessInput.php a few lines ABOVE the auth
// check, replied through the identical ProcessInputReply path, and equally silent before this change.
// The auth wording itself (and its reload hint) is asserted at the helper level, and that split is
// called out here rather than papered over.
//
// Usage: node swusim-action-refusal-surfaced-xbrowser.mjs [BASE]  ENGINES=chromium,firefox,webkit
import { chromium, firefox, webkit } from 'playwright';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

const BASE   = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const here   = dirname(fileURLToPath(import.meta.url));
const SCHEMA = readFileSync(resolve(here, '../../SWUSim/Tests/Visual/UndoButton_VisibleInPublicMultiSeat.md'), 'utf8');

const ALL = { chromium, firefox, webkit };
const ENGINES = Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n));

let allOk = true;
const results = [];
const ok = (name, cond, extra = '') => { if (!cond) allOk = false; results.push([name, !!cond, extra]); };

async function login(page) {
  await page.goto(BASE + 'SharedUI/LoginPage.php', { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="userID"]', 'claudebot1');
  await page.fill('input[name="password"]', 'pass');
  await Promise.all([page.waitForNavigation({ waitUntil: 'load' }).catch(() => {}), page.click('button[type="submit"]')]);
}

async function buildGame(page) {
  const setup = await (await page.request.post(BASE + 'SWUSim/TestSchemaSetup.php', { multipart: { schema: SCHEMA } })).json();
  if (setup.error) throw new Error('setup: ' + setup.error);
  for (const step of setup.whenSteps) {
    await page.request.post(BASE + 'SWUSim/TestSchemaStep.php',
      { multipart: { gameName: String(setup.gameName), step: step.raw } });
  }
  return setup.gameName;
}

// ⚠ THERE ARE TWO showFlashMessage IMPLEMENTATIONS and the GENERATED one wins on the game page
// (SWUSim/GeneratedUI_*.js, emitted by zzGameCodeGenerator). It appends an ANONYMOUS overlay — no id, no
// class — and removes it again after ~750ms, so querying for '#flash-message' (the Core/UILibraries one)
// finds nothing and a DOM-selector probe reports a silent client that is in fact working.
// So: SPY on the call for the text, and use a MutationObserver for the "something actually appeared"
// half, which is implementation-agnostic.
async function armSpy(page) {
  await page.evaluate(() => {
    window.__flashes = [];
    window.__added = 0;
    if (!window.__flashSpyArmed) {
      const orig = window.showFlashMessage;
      window.showFlashMessage = function (msg) {
        window.__flashes.push(String(msg));
        return orig ? orig.apply(this, arguments) : undefined;
      };
      new MutationObserver((recs) => {
        for (const r of recs) window.__added += r.addedNodes.length;
      }).observe(document.body, { childList: true });
      window.__flashSpyArmed = true;
    }
  });
}
const spyState = (page) => page.evaluate(() => ({ flashes: window.__flashes.slice(), added: window.__added }));
const resetSpy = (page) => page.evaluate(() => { window.__flashes = []; window.__added = 0; });

for (const [engineName, engine] of ENGINES) {
  const browser = await engine.launch();
  try {
    const ctx = await browser.newContext({ viewport: { width: 1400, height: 900 } });
    const page = await ctx.newPage();
    await login(page);
    let gameName;
    try { gameName = await buildGame(page); }
    catch (e) { ok(`${engineName}: board built`, false, e.message); await ctx.close(); continue; }
    await page.goto(BASE + `NextTurn.php?folderPath=SWUSim&gameName=${gameName}&playerID=1&authKey=testschema&viewerPerspective=1`,
      { waitUntil: 'load' });
    await page.waitForTimeout(2500);

    ok(`${engineName}: the surfacing helper exists`,
       await page.evaluate(() => typeof window.ShowEngineRefusal === 'function'));

    // ── END TO END: a REAL refusal from the real endpoint, through the real SubmitInput ─────────────
    await armSpy(page); await resetSpy(page);
    await page.evaluate(() => {
      // SubmitInput reads gameName from the page's form, so point it at a game that does not exist and
      // let the server refuse it for real. Restored immediately afterwards.
      const orig = window.FormInputValue;
      window.FormInputValue = (k) => (k === 'gameName' ? 'no_such_game_xyz' : orig(k));
      window.SubmitInput('10001', '&cardID=' + encodeURIComponent('myGroundArena-0!CustomInput!Activate'));
      setTimeout(() => { window.FormInputValue = orig; }, 0);
    });
    await page.waitForTimeout(2000);
    const refused = await spyState(page);
    ok(`${engineName}: a refused action shows the server's reason`,
       refused.flashes.some((m) => /invalid game name/i.test(m)), JSON.stringify(refused.flashes) || '(nothing shown)');
    ok(`${engineName}: and something is actually rendered for it`, refused.added > 0, `${refused.added} nodes added to body`);

    // ── The suppression rules: a SUCCESSFUL action must stay silent ────────────────────────────────
    // This is the spam risk — a successful action's body is '' or 'OK', and neither may raise a toast.
    for (const [body, label] of [['', 'empty body'], ['OK', "'OK'"]]) {
      await resetSpy(page);
      await page.evaluate((b) => window.ShowEngineRefusal(b), body);
      await page.waitForTimeout(250);
      const t = await spyState(page);
      ok(`${engineName}: ${label} raises no message`, t.flashes.length === 0, JSON.stringify(t.flashes));
    }

    // ── The auth wording + its recovery hint (helper level — see the header for why) ───────────────
    await resetSpy(page);
    await page.evaluate(() => window.ShowEngineRefusal('Invalid auth key'));
    await page.waitForTimeout(250);
    const authMsg = (await spyState(page)).flashes.join(' | ');
    ok(`${engineName}: the auth refusal is shown`, /invalid auth key/i.test(authMsg), authMsg);
    ok(`${engineName}: the auth refusal tells them how to recover`, /reload the page/i.test(authMsg), authMsg);

    // A JSON-shaped refusal (the spectator path returns an object, not a string).
    await resetSpy(page);
    await page.evaluate(() => window.ShowEngineRefusal({ success: false, message: 'Spectators are view-only.' }));
    await page.waitForTimeout(250);
    const specMsg = (await spyState(page)).flashes.join(' | ');
    ok(`${engineName}: an object-shaped refusal is shown too`, /spectators are view-only/i.test(specMsg), specMsg);
    // …and the same object shape with success:true must NOT be.
    await resetSpy(page);
    await page.evaluate(() => window.ShowEngineRefusal({ success: true, message: 'Something chatty' }));
    await page.waitForTimeout(250);
    ok(`${engineName}: a SUCCESSFUL object reply stays silent`, (await spyState(page)).flashes.length === 0);

    await ctx.close();
  } finally {
    await browser.close();
  }
}

for (const [name, pass, extra] of results) console.log(`${pass ? 'PASS' : 'FAIL'}  ${name}${extra ? '  — ' + extra : ''}`);
console.log(allOk ? '\nALL PASS' : '\nFAILURES');
process.exit(allOk ? 0 : 1);
