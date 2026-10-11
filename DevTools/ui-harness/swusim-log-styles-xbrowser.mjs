// SWUSim game-log line styles — Chromium + Firefox + WebKit, desktop AND mobile layout (2026-09-11).
//
// Why this exists: the schema suite never renders the page (TestRunner walks Tests/Cases and stubs the UI),
// so a log STYLE is invisible to it. This builds the board from the Visual schema
// SWUSim/Tests/Visual/GameLog_UndoneAndDeckLines.md through the Test Schema Editor's own endpoints
// (TestSchemaSetup.php + TestSchemaStep.php — mod login required), opens it, and measures the log lines:
//   · the "(undone) P1 played …" line (type UNDONE) is dimmed AND struck through;
//   · the live "P1 played …" and "P1 undid …" lines are NOT (the negative control);
//   · the search's "P1 revealed and drew …" line is REVEAL gold;
//   · the search's "P1 put 4 cards on the bottom …" line is type DECK and NOT gold.
// Screenshots of the log panel go to $SHOTS (default /tmp) for a human look.
//
// Usage: node swusim-log-styles-xbrowser.mjs [BASE] [SHOTS_DIR]
import { chromium, firefox, webkit } from 'playwright';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

const BASE  = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const SHOTS = process.argv[3] || '/tmp';
const here  = dirname(fileURLToPath(import.meta.url));
const SCHEMA = readFileSync(resolve(here, '../../SWUSim/Tests/Visual/GameLog_UndoneAndDeckLines.md'), 'utf8');
const GOLD = 'rgb(240, 192, 64)';

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

// Build the board through the editor's endpoints (the request context shares the page's login cookie).
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

async function measure(page) {
  return page.evaluate(([gold, LOGIN_USER]) => {
    const rows = Array.from(document.querySelectorAll('.swu-log-entry'));
    // ⚠ SEAT 1 IS NAMED, NOT "P1". This harness logs in (the editor endpoints need it), and the log renders a
    // logged-in seat by USERNAME (swuNameSeatsInLog, GameLayoutShared.php — owner request 2026-09-22): on a
    // matchless board the viewer's own seat is filled from their session, so these lines read "claudebot1 played
    // …". Match either form, so the file keeps working logged in OR out, and under any account name.
    const P1 = '(?:P1|' + LOGIN_USER + ')';
    const rx = (body) => new RegExp(body.replace(/@P1@/g, P1));
    const pick = (re) => rows.find(r => re.test(r.textContent || ''));
    const info = (r) => r ? {
      cls: r.className, text: (r.textContent || '').trim().slice(0, 90),
      opacity: parseFloat(getComputedStyle(r).opacity),
      deco: getComputedStyle(r).textDecorationLine, color: getComputedStyle(r).color,
    } : null;
    return {
      n: rows.length,
      undone:   info(pick(rx('^\\s*\\(undone\\) @P1@ played'))),
      livePlay: info(rows.find(r => rx('@P1@ played').test(r.textContent) && !/\(undone\)/.test(r.textContent) && !/Recruit/.test(r.textContent))),
      undoLine: info(pick(rx('@P1@ undid their last action'))),
      reveal:   info(pick(rx('@P1@ revealed and drew'))),
      bottom:   info(pick(rx('@P1@ put 4 cards on the bottom of their deck'))),
      gold,
    };
  }, [GOLD, LOGIN_USER]);
}

for (const [engineName, engine] of Object.entries({ chromium, firefox, webkit })) {
  const browser = await engine.launch();
  try {
    for (const layout of ['desktop', 'mobile']) {
      const tag = `${engineName}/${layout}`;
      const ctx = await browser.newContext(layout === 'desktop'
        ? { viewport: { width: 1600, height: 900 } }
        : { viewport: { width: 390, height: 844 } });
      const page = await ctx.newPage();
      await login(page);
      let gameName;
      try { gameName = await buildGame(page); } catch (e) { ok(`${tag}: board built`, false, e.message); await ctx.close(); continue; }
      await page.goto(BASE + `NextTurn.php?folderPath=SWUSim&gameName=${gameName}&playerID=1&authKey=testschema`
        + `&viewerPerspective=1&opponentID=2${layout === 'mobile' ? '&swuLayout=mobile' : ''}`, { waitUntil: 'load' });
      await page.waitForSelector('.swu-log-entry', { state: 'attached', timeout: 15000 }).catch(() => {});
      await page.waitForTimeout(2500);
      const m = await measure(page);
      ok(`${tag}: log rendered`, m.n > 0, `${m.n} lines`);
      ok(`${tag}: undone line has type UNDONE`, !!m.undone && /swu-log-UNDONE/.test(m.undone.cls), m.undone?.cls);
      ok(`${tag}: undone line is dimmed`, !!m.undone && m.undone.opacity < 0.6, String(m.undone?.opacity));
      ok(`${tag}: undone line is struck through`, !!m.undone && /line-through/.test(m.undone.deco), m.undone?.deco);
      ok(`${tag}: live play line NOT dimmed/struck`, !!m.livePlay && m.livePlay.opacity === 1 && !/line-through/.test(m.livePlay.deco),
        m.livePlay ? `${m.livePlay.opacity} ${m.livePlay.deco}` : 'missing');
      ok(`${tag}: undo line NOT dimmed/struck`, !!m.undoLine && m.undoLine.opacity === 1 && !/line-through/.test(m.undoLine.deco),
        m.undoLine ? `${m.undoLine.opacity} ${m.undoLine.deco}` : 'missing');
      ok(`${tag}: search pick line is REVEAL`, !!m.reveal && /swu-log-REVEAL/.test(m.reveal.cls), m.reveal?.cls);
      ok(`${tag}: search rest line is DECK`, !!m.bottom && /swu-log-DECK/.test(m.bottom.cls), m.bottom?.cls);
      if (layout === 'desktop') {
        // Only the desktop layout tints types (mobile colors no type but CHAT).
        ok(`${tag}: search pick line is gold`, !!m.reveal && m.reveal.color === GOLD, m.reveal?.color);
        ok(`${tag}: search rest line is NOT gold`, !!m.bottom && m.bottom.color !== GOLD, m.bottom?.color);
      }
      const panel = page.locator('#swuLogPanel');
      if (await panel.isVisible().catch(() => false)) {
        await panel.screenshot({ path: `${SHOTS}/swusim-log-${engineName}-${layout}.png` }).catch(() => {});
      }
      await ctx.close();
    }
  } finally {
    await browser.close();
  }
}

for (const [name, pass, extra] of results) console.log(`${pass ? 'PASS' : 'FAIL'}  ${name}${extra ? '  — ' + extra : ''}`);
console.log(allOk ? '\nALL PASS' : '\nFAILURES');
process.exit(allOk ? 0 : 1);
