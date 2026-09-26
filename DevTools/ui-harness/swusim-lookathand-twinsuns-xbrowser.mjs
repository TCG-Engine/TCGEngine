// "Look at an opponent's hand" must open a usable picker for EITHER opponent — Chromium/Firefox/WebKit.
//
// Reported 2026-09-25 (game 1310334, 3-seat Twin Suns): play Remnant Lookouts (ASH_220), choose P2,
// nothing happens. The server queues the MZMAYCHOOSE over "p2Hand-0" correctly (pinned by
// ash/RemnantLookouts.md::TwinSuns_PickAnOpponent_ThenTheDiscardOfferAppears) — the CLIENT never turns
// that pool into a UI, because an opponent's hand is only ever pickable through the MZChoose popup and
// both gates that route to the popup are two-seat shaped. See
// SWUSim/Tests/Visual/LookAtOpponentHand_TwinSunsPicker.md.
//
// The discriminator is which opponent the client currently has IN VIEW (&opponentID=N): the in-view seat
// is the negative control, the off-view seat is the bug.
//
// Usage: node swusim-lookathand-twinsuns-xbrowser.mjs [BASE] [SHOTS_DIR]  ENGINES=chromium,firefox,webkit
import { chromium, firefox, webkit } from 'playwright';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

const BASE   = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const SHOTS  = process.argv[3] || '/tmp';
const here   = dirname(fileURLToPath(import.meta.url));
const SCHEMA = readFileSync(resolve(here, '../../SWUSim/Tests/Visual/LookAtOpponentHand_TwinSunsPicker.md'), 'utf8');

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

// Build the board and run `stepCount` of its WHEN steps.
//
// ⚠ HOW MANY STEPS YOU RUN IS THE WHOLE EXPERIMENT. Running BOTH steps server-side leaves the page to
// open with the discard decision already pending, and the decision dispatcher then runs on page load —
// which PASSES even when the live game is broken. The real player CLICKS the opponent button, so the
// follow-up decision has to be rendered by the post-answer update cycle instead. Only the click path
// exercises that. (Same shape as the 2026-09-17 ClearSelectionMode report, inverted: there only the
// click-less path exposed the gap, here only the click path does.)
async function buildGame(page, stepCount) {
  const setup = await (await page.request.post(BASE + 'SWUSim/TestSchemaSetup.php', { multipart: { schema: SCHEMA } })).json();
  if (setup.error) throw new Error('setup: ' + setup.error);
  for (const step of setup.whenSteps.slice(0, stepCount)) {
    const r = await (await page.request.post(BASE + 'SWUSim/TestSchemaStep.php',
      { multipart: { gameName: String(setup.gameName), step: step.raw } })).json();
    if (r.error) throw new Error(`step "${step.raw}": ${r.error}`);
  }
  return setup.gameName;
}

// What can the player actually DO with the pending decision right now?
async function pickerState(page) {
  return page.evaluate(() => {
    const popup = document.getElementById('mzchoose-popup');
    const popupCards = popup ? popup.querySelectorAll('.mzchoose-popup-card').length : 0;
    // The zone label is an absolutely-positioned strip across the bottom of each card, so it grows
    // UPWARD over the art when it wraps. It now carries a seat name (an arbitrary username), and
    // unconstrained it swallowed the whole preview on a phone — reported 2026-09-25. Measure the ratio
    // rather than the text: any label taller than a third of the card is covering the thing being chosen.
    const labels = popup ? Array.from(popup.querySelectorAll('.mzchoose-popup-zone-label')).map((l) => {
      const card = l.closest('.mzchoose-popup-card');
      const lh = l.getBoundingClientRect().height;
      const ch = card ? card.getBoundingClientRect().height : 0;
      return { text: (l.textContent || '').trim(), lh, ch, ratio: ch ? lh / ch : 1 };
    }) : [];
    // The picker's own shape (owner, 2026-09-25: bigger modal, two columns, vertical scroll).
    const panel = popup ? popup.querySelector('.mzchoose-popup-panel') : null;
    const cardsBox = popup ? popup.querySelector('.mzchoose-popup-cards') : null;
    const cs = cardsBox ? getComputedStyle(cardsBox) : null;
    const shape = {
      vw: window.innerWidth,
      panelW: panel ? panel.getBoundingClientRect().width : 0,
      display: cs ? cs.display : '',
      // A grid's resolved gridTemplateColumns is one length per COLUMN, so counting them counts columns.
      columns: cs && cs.display === 'grid' ? String(cs.gridTemplateColumns).trim().split(/\s+/).length : 0,
      overflowY: cs ? cs.overflowY : '',
      cardH: popup && popup.querySelector('.mzchoose-popup-card')
        ? popup.querySelector('.mzchoose-popup-card').getBoundingClientRect().height : 0,
    };
    const glowing = document.querySelectorAll('.selectable-card').length;
    const sm = window.SelectionMode || {};
    return {
      pending: !!(sm.active),
      popupShown: !!popup,
      popupCards,
      glowing,
      // The client's own classification — the two buckets a spec can land in, plus the "off-view" holdout
      // that is the reported bug: a spec in NEITHER bucket has no UI anywhere.
      inline: (sm.inlineSpecs || []).length,
      popupSpecs: (sm.popupCards || []).length,
      offView: (sm._twOffView || []).length,
      zones: (sm._twAllSpecs || []).map((s) => s && s.zone).filter(Boolean),
      labels,
      shape,
    };
  });
}

for (const [engineName, engine] of ENGINES) {
  const browser = await engine.launch();
  try {
    // opponentID=2 -> the seat we pick IS the one on screen (control).
    // opponentID=3 -> we picked P2 while viewing P3 (the reported case).
    // The VIEWER is seat 3 (the reported configuration). Its opponents are seats 1 and 2, and the pick is
    // seat 2 — so opponentID=2 has the picked seat on screen and opponentID=1 does not.
    for (const oppId of ['2', '1']) {
      // ⚠ MOBILE IS NOT OPTIONAL HERE. The popup's own zone label overflowed only at phone width — a
      // desktop-only probe reported it healthy while it covered the entire card preview on a phone.
      for (const [mode, layout] of [['clicked', 'desktop'], ['preanswered', 'desktop'], ['preanswered', 'mobile']]) {
        const view = oppId === '2' ? 'picked seat IN view' : 'picked seat OFF view';
        const tag = `${engineName}/opp${oppId} ${mode}/${layout} (${view})`;
        const ctx = await browser.newContext({ viewport: layout === 'mobile'
          ? { width: 390, height: 844 } : { width: 1600, height: 900 } });
        const page = await ctx.newPage();
        await login(page);
        let gameName;
        // 'clicked'     -> play the card server-side, ANSWER THE PICKER BY CLICKING (what a player does)
        // 'preanswered' -> both steps server-side; the page loads with the decision already pending
        //                  (the control — it passes even when the live game is broken)
        try { gameName = await buildGame(page, mode === 'clicked' ? 1 : 2); }
        catch (e) { ok(`${tag}: board built`, false, e.message); await ctx.close(); continue; }
        await page.goto(BASE + `NextTurn.php?folderPath=SWUSim&gameName=${gameName}&playerID=3&authKey=testschema`
          + `&viewerPerspective=1&opponentID=${oppId}${layout === 'mobile' ? '&swuLayout=mobile' : ''}`, { waitUntil: 'load' });
        await page.waitForTimeout(3000);

        if (mode === 'clicked') {
          const banner = page.locator('.optchoose-banner');
          const shown = await banner.isVisible().catch(() => false);
          ok(`${tag}: the opponent picker is shown`, shown);
          if (shown) {
            // Click the button for seat 2. Its TEXT is humanised (a username), so pick by position:
            // the options are emitted in OpponentsOf() order, so seat 2 is the first button.
            await banner.locator('button').first().click().catch(() => {});
            await page.waitForTimeout(3500);
          }
        }

        const s = await pickerState(page);
        ok(`${tag}: a selection is pending`, s.pending, JSON.stringify(s));
        // THE assertion: an opponent's hand is never rendered as cards, so the ONLY usable UI is the popup.
        ok(`${tag}: the card picker is usable`, s.popupShown && s.popupCards > 0,
           `popup=${s.popupShown} cards=${s.popupCards} inline=${s.inline} offView=${s.offView} zones=[${s.zones.join(',')}]`);
        if (layout === 'mobile') {
          // The three things the owner asked for on a phone, 2026-09-25.
          const sh = s.shape || {};
          ok(`${tag}: the picker claims the screen width`, sh.panelW >= sh.vw * 0.85,
             `panel ${Math.round(sh.panelW)}px of ${sh.vw}px`);
          ok(`${tag}: cards are a 2-column grid`, sh.display === 'grid' && sh.columns === 2,
             `${sh.display} cols=${sh.columns}`);
          ok(`${tag}: the card area scrolls vertically`, sh.overflowY === 'auto' || sh.overflowY === 'scroll', sh.overflowY);
          // Bigger cards are the POINT of the two columns — the board's own card size is ~52px tall.
          ok(`${tag}: cards are big enough to read`, sh.cardH >= 100, `${Math.round(sh.cardH)}px tall`);
        }
        const fat = (s.labels || []).filter((l) => l.ratio > 0.34);
        ok(`${tag}: the zone label does not cover the card`, fat.length === 0,
           (s.labels || []).map((l) => `"${l.text}" ${Math.round(l.lh)}/${Math.round(l.ch)}px`).join('; ') || 'no labels');
        await page.screenshot({ path: `${SHOTS}/swusim-lookathand-${engineName}-opp${oppId}-${mode}-${layout}.png` }).catch(() => {});
        await ctx.close();
      }
    }
  } finally {
    await browser.close();
  }
}

for (const [name, pass, extra] of results) console.log(`${pass ? 'PASS' : 'FAIL'}  ${name}${extra ? '  — ' + extra : ''}`);
console.log(allOk ? '\nALL PASS' : '\nFAILURES');
process.exit(allOk ? 0 : 1);
