// SWUSim resource box — while a decision is picking resources the box shows ONLY the offered ones.
// Chromium + Firefox + WebKit, desktop AND mobile layout.
//
// Why this exists: the schema suite never renders the page, so "which cards the resource box shows"
// is invisible to it. The box auto-opens for a decision that offers resources
// (refreshResourceSelectionPanel in SWUSim/Custom/GameLayoutShared.php) and used to render the whole
// resource row, so being asked to defeat a Credit meant hunting for the lit card among near-identical
// resources. It now renders only the resources in the offer.
//
// Board: SWUSim/Tests/Visual/ResourceBox_ShowsOnlyOfferedCredits.md — 7 resources with Credits
// INTERLEAVED at index 1 and 3, plus an Experience token, and Han Solo's "[defeat a friendly token]"
// Action already used, so the cost prompt is open with three candidates.
//
// Checks, per engine and layout:
//   · FILTERED run (the WHEN step is executed): the box is open, carries .is-filtered, renders
//     EXACTLY the two Credits (indices 1 and 3), hides the five ordinary resources, and its header
//     reads "SELECTABLE RESOURCES".
//   · CONTROL run (same board, WHEN step NOT executed → no decision): opening the box by hand renders
//     ALL SEVEN resources, nothing hidden, header "RESOURCES". This is what proves the narrowing is
//     driven by the offer rather than by something that always hides non-Credits.
//
// Usage: node swusim-resource-filter-xbrowser.mjs [BASE] [SHOTS_DIR]
import { chromium, firefox, webkit } from 'playwright';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

const BASE  = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const SHOTS = process.argv[3] || '/tmp';
const here  = dirname(fileURLToPath(import.meta.url));
const SCHEMA = readFileSync(resolve(here, '../../SWUSim/Tests/Visual/ResourceBox_ShowsOnlyOfferedCredits.md'), 'utf8');
const CREDIT = 'LAW_T01';

let allOk = true;
const results = [];
const ok = (name, cond, extra = '') => { if (!cond) allOk = false; results.push([name, !!cond, extra]); };

async function login(page) {
  await page.goto(BASE + 'SharedUI/LoginPage.php', { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="userID"]', 'claudebot1');
  await page.fill('input[name="password"]', 'pass');
  await Promise.all([page.waitForNavigation({ waitUntil: 'load' }).catch(() => {}), page.click('button[type="submit"]')]);
}

// Build the board through the editor's endpoints (the request context shares the page's login cookie).
// runSteps=false leaves the board in its GIVEN state with no decision pending — the control run.
async function buildGame(page, runSteps) {
  const setup = await (await page.request.post(BASE + 'SWUSim/TestSchemaSetup.php', { multipart: { schema: SCHEMA } })).json();
  if (setup.error) throw new Error('setup: ' + setup.error);
  if (runSteps) {
    for (const step of setup.whenSteps) {
      const r = await (await page.request.post(BASE + 'SWUSim/TestSchemaStep.php',
        { multipart: { gameName: String(setup.gameName), step: step.raw } })).json();
      if (r.error) throw new Error(`step "${step.raw}": ${r.error}`);
    }
  }
  return setup.gameName;
}

// Read the box: which resource cards exist, which are hidden, and what the header says.
async function measure(page) {
  return page.evaluate(() => {
    const panel = document.getElementById('myResourcesSlot');
    if (!panel) return null;
    const cards = Array.from(panel.querySelectorAll('[data-mzid]'))
      .map(el => ({ mz: el.getAttribute('data-mzid') || '', el }))
      .filter(c => /^myResources-\d+$/.test(c.mz))
      .map(c => ({
        index: parseInt(c.mz.split('-')[1], 10),
        cardID: (c.el.querySelector('img')?.getAttribute('src') || '').split('/').pop().replace(/\.[a-z]+$/i, ''),
        hidden: getComputedStyle(c.el).display === 'none',
      }));
    return {
      open: panel.classList.contains('is-open'),
      filtered: panel.classList.contains('is-filtered'),
      header: getComputedStyle(panel, '::before').content.replace(/^"|"$/g, ''),
      shown: cards.filter(c => !c.hidden),
      hiddenCount: cards.filter(c => c.hidden).length,
      total: cards.length,
    };
  });
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

      // ── FILTERED: the decision is open, so the box should narrow to the two Credits ──
      let gameName;
      try { gameName = await buildGame(page, true); }
      catch (e) { ok(`${tag}: board built`, false, e.message); await ctx.close(); continue; }
      await page.goto(BASE + `NextTurn.php?folderPath=SWUSim&gameName=${gameName}&playerID=1&authKey=testschema`
        + `&viewerPerspective=1&opponentID=2${layout === 'mobile' ? '&swuLayout=mobile' : ''}`, { waitUntil: 'load' });
      await page.waitForTimeout(2500);

      const m = await measure(page);
      ok(`${tag}: box present`, !!m);
      if (m) {
        ok(`${tag}: box auto-opened`, m.open);
        ok(`${tag}: box marked filtered`, m.filtered);
        ok(`${tag}: shows exactly the 2 offered Credits`, m.shown.length === 2,
          `shown ${m.shown.map(c => `${c.index}:${c.cardID}`).join(' ') || 'none'}`);
        ok(`${tag}: the shown cards ARE the Credits`, m.shown.every(c => c.cardID === CREDIT) &&
          m.shown.map(c => c.index).sort((a, b) => a - b).join(',') === '1,3',
          m.shown.map(c => `${c.index}:${c.cardID}`).join(' '));
        ok(`${tag}: the 5 other resources are hidden`, m.hiddenCount === 5, `hidden ${m.hiddenCount} of ${m.total}`);
        ok(`${tag}: header says SELECTABLE RESOURCES`, m.header === 'SELECTABLE RESOURCES', m.header);
      }
      const panelEl = page.locator('#myResourcesSlot');
      if (await panelEl.isVisible().catch(() => false)) {
        await panelEl.screenshot({ path: `${SHOTS}/swusim-resbox-${engineName}-${layout}.png` }).catch(() => {});
      }

      // ── CONTROL: same board, no decision → opening by hand shows every resource ──
      let controlGame;
      try { controlGame = await buildGame(page, false); }
      catch (e) { ok(`${tag}: control board built`, false, e.message); await ctx.close(); continue; }
      await page.goto(BASE + `NextTurn.php?folderPath=SWUSim&gameName=${controlGame}&playerID=1&authKey=testschema`
        + `&viewerPerspective=1&opponentID=2${layout === 'mobile' ? '&swuLayout=mobile' : ''}`, { waitUntil: 'load' });
      await page.waitForTimeout(2500);
      await page.evaluate(() => { if (typeof window.swuToggleMyResources === 'function') window.swuToggleMyResources(); });
      await page.waitForTimeout(400);

      const c = await measure(page);
      ok(`${tag}: control box opens`, !!c && c.open);
      if (c) {
        ok(`${tag}: control shows ALL 7 resources`, c.shown.length === 7, `shown ${c.shown.length} of ${c.total}`);
        ok(`${tag}: control hides nothing`, c.hiddenCount === 0, `hidden ${c.hiddenCount}`);
        ok(`${tag}: control header says RESOURCES`, c.header === 'RESOURCES', c.header);
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
