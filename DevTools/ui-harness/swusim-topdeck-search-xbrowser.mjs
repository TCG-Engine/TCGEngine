// The top-deck SEARCH popup: centred on the board, and minimisable so the player can read the board
// before choosing — Chromium + Firefox + WebKit.
//
// Owner 2026-09-28, from a screenshot of ASH_110 Admiral Ackbar's "SEARCH THE TOP CARDS": the popup was
// centred on the VIEWPORT (so the chat/log sidebar pushed it off the board's centre) and covered the board
// completely, with no way to look at the game state before committing. Two changes:
//   centring  #topdecksearch-panel > .topdecksearch-box   (SWUSim/Custom/GameLayout.php)
//   minimise  ShowTopDeckSearchPanel                      (Core/UILibraries20260928.js)
//
// WHAT NEEDS A BROWSER:
//  1. Centred on the PLAY AREA, not the viewport — the sidebar width is read at runtime from
//     --swu-sidebar-w, so the assertion cannot drift from the layout.
//  2. Minimised, THE BOARD IS ACTUALLY USABLE. This is the whole point of the request, and it is the one
//     thing that cannot be checked by reading CSS: a full-screen overlay that merely LOOKS transparent
//     still eats every click. Asserted by hit-testing a real card through the overlay.
//  3. Minimised hides the card row and the confirm button, and restoring brings them back — the state
//     survives render(), which tears the panel down and rebuilds it on every selection change.
//
// ⚠ Measure the panel AFTER any transition settles, and never read a node captured before a re-render —
// both cost real debugging time on the sibling YES/NO probe. See swusim-yesno-prompt-length-xbrowser.mjs.
//
// Usage: node swusim-topdeck-search-xbrowser.mjs [BASE] [SHOTS_DIR]
//        ENGINES=chromium,firefox,webkit (default: all three)
import { chromium, firefox, webkit } from 'playwright';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

const BASE   = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const SHOTS  = process.argv[3] || '/tmp';
const here   = dirname(fileURLToPath(import.meta.url));
const SCHEMA = readFileSync(resolve(here, '../../SWUSim/Tests/Visual/DecisionBanners_MobileFit.md'), 'utf8');

const ALL = { chromium, firefox, webkit };
const ENGINES = Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n));

let allOk = true;
const results = [];
const ok = (name, cond, extra = '') => { if (!cond) allOk = false; results.push([name, !!cond, extra]); };

const LOGIN_USER = 'claudebot1';

// ASH_110 Admiral Ackbar's real shape: look at the top 2, take SPACE units with combined cost <= 5.
// allIDs | matchable | constraint | costMap | pickLabel | pickVerb   (underscores are transport)
const PARAM = 'SOR_225,SOR_237|SOR_225,SOR_237|cost:5|SOR_225:1,SOR_237:2|space_units|Play';

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

async function openPanel(page, param) {
  return page.evaluate(async (param) => {
    const gone = document.getElementById('topdecksearch-panel');
    if (gone) gone.remove();
    if (typeof window.ShowTopDeckSearchPanel !== 'function') return { missing: 'ShowTopDeckSearchPanel' };
    window.ShowTopDeckSearchPanel({ Param: param }, 0, function () {});
    await new Promise((r) => setTimeout(r, 250));
    return { ok: true };
  }, param);
}

// Measure the panel in its current state, plus whether a real board card can be clicked through.
async function measure(page) {
  return page.evaluate(async () => {
    await new Promise((r) => setTimeout(r, 450));   // let any transition settle before measuring
    const overlay = document.getElementById('topdecksearch-panel');
    if (!overlay) return { missing: '#topdecksearch-panel' };
    const box = overlay.querySelector('.topdecksearch-box');
    if (!box) return { missing: '.topdecksearch-box' };

    // Resolve the sidebar by LAYING IT OUT — a custom property read back with getPropertyValue is an
    // unresolved token stream ("clamp(160px, 14vw, 200px)"), and parseFloat on that silently gives 0.
    const ruler = document.createElement('div');
    ruler.style.cssText = 'position:absolute;visibility:hidden;height:0;width:var(--swu-sidebar-w, 0px)';
    document.body.appendChild(ruler);
    const sidebarW = ruler.getBoundingClientRect().width;
    ruler.remove();

    const vw = window.innerWidth, vh = window.innerHeight;
    const r = box.getBoundingClientRect();

    // Can the player actually use the board? Hit-test a real card's centre through the overlay.
    // ⚠ The question is "does the POPUP intercept the click", NOT "is the topmost element the card". The
    // card's centre legitimately resolves to a board container (#stuffParent, #swuMobileRoot) depending on
    // layout and what sits above the art, so asserting the card itself was wrong and failed on a feature
    // that works. What must hold is that nothing inside #topdecksearch-panel is in the way.
    const card = document.querySelector('#myGroundArena [data-uniqueid], [data-uniqueid]');
    let hitInPopup = null, hitTag = '';
    if (card) {
      const cb = card.getBoundingClientRect();
      const cx = cb.left + cb.width / 2, cy = cb.top + cb.height / 2;
      if (cx >= 0 && cy >= 0 && cx <= vw && cy <= vh) {
        const hit = document.elementFromPoint(cx, cy);
        hitTag = hit ? (hit.id || hit.className || hit.tagName) : 'null';
        hitInPopup = !!hit && !!hit.closest && !!hit.closest('#topdecksearch-panel');
      }
    }
    return {
      vw, vh, sidebarW,
      boxCentre: r.left + r.width / 2,
      boardCentre: (vw - sidebarW) / 2,
      boxInView: r.left >= -0.5 && r.right <= vw + 0.5 && r.top >= -0.5 && r.bottom <= vh + 0.5,
      overlayBg: getComputedStyle(overlay).backgroundColor,
      overlayPointer: getComputedStyle(overlay).pointerEvents,
      boxPointer: getComputedStyle(box).pointerEvents,
      minimized: overlay.classList.contains('is-minimized'),
      cardCount: overlay.querySelectorAll('img').length,
      hasConfirm: !!overlay.querySelector('button:not(.topdecksearch-minimize-btn)'),
      hasMinBtn: !!overlay.querySelector('.topdecksearch-minimize-btn'),
      hitInPopup, hitTag: String(hitTag).slice(0, 40),
      minBtnGlyph: (overlay.querySelector('.topdecksearch-minimize-btn') || {}).textContent || '',
    };
  });
}

const alphaOf = (css) => {
  const m = String(css).match(/rgba?\(([^)]+)\)/);
  if (!m) return null;
  const p = m[1].split(',').map((x) => parseFloat(x.trim()));
  return p.length >= 4 ? p[3] : 1;
};

for (const [engineName, engine] of ENGINES) {
  const browser = await engine.launch();
  try {
    for (const layout of ['mobile', 'desktop']) {
      const tag = `${engineName}/${layout}`;
      const ctx = await browser.newContext(layout === 'mobile'
        ? { viewport: { width: 390, height: 844 } }
        : { viewport: { width: 1600, height: 900 } });
      const page = await ctx.newPage();
      await login(page);
      let gameName;
      try { gameName = await buildGame(page); }
      catch (e) { ok(`${tag}: board built`, false, e.message); await ctx.close(); continue; }
      await page.goto(BASE + `NextTurn.php?folderPath=SWUSim&gameName=${gameName}&playerID=1&authKey=testschema`
        + `&viewerPerspective=1&opponentID=2${layout === 'mobile' ? '&swuLayout=mobile' : ''}`, { waitUntil: 'load' });
      await page.waitForTimeout(2500);

      const opened = await openPanel(page, PARAM);
      if (opened.missing) { ok(`${tag}: panel opened`, false, 'missing ' + opened.missing); await ctx.close(); continue; }

      // ── expanded ────────────────────────────────────────────────────────────────────────────────
      const exp = await measure(page);
      if (exp.missing) { ok(`${tag}: expanded rendered`, false, 'missing ' + exp.missing); await ctx.close(); continue; }
      ok(`${tag}: expanded centred on the BOARD`, Math.abs(exp.boxCentre - exp.boardCentre) <= 2,
         `box centre ${Math.round(exp.boxCentre)} vs board centre ${Math.round(exp.boardCentre)} (vw=${exp.vw}, sidebar=${Math.round(exp.sidebarW)})`);
      ok(`${tag}: expanded within viewport`, exp.boxInView,
         `${Math.round(exp.boxCentre)} / ${exp.vw}`);
      ok(`${tag}: expanded shows both cards`, exp.cardCount === 2, `${exp.cardCount} images`);
      ok(`${tag}: expanded dims the board`, (alphaOf(exp.overlayBg) ?? 0) >= 0.4, `background=${exp.overlayBg}`);
      ok(`${tag}: minimise control present`, exp.hasMinBtn, '');
      ok(`${tag}: expanded shows the "\u2013" minimise glyph`, exp.minBtnGlyph.trim() === '\u2013',
         `glyph="${exp.minBtnGlyph.trim()}"`);
      ok(`${tag}: expanded popup DOES cover the board`, exp.hitInPopup === true, `hit=${exp.hitTag}`);
      await page.screenshot({ path: `${SHOTS}/swusim-topdeck-expanded-${engineName}-${layout}.png` }).catch(() => {});

      // ── minimised ───────────────────────────────────────────────────────────────────────────────
      await page.click('.topdecksearch-minimize-btn');
      const min = await measure(page);
      ok(`${tag}: minimised flag set`, min.minimized, '');
      ok(`${tag}: minimised hides the cards`, min.cardCount === 0, `${min.cardCount} images`);
      ok(`${tag}: minimised hides the confirm button`, !min.hasConfirm, '');
      ok(`${tag}: minimised drops the dim`, (alphaOf(min.overlayBg) ?? 1) === 0,
         `background=${min.overlayBg}`);
      ok(`${tag}: minimised lets clicks reach the board`, min.overlayPointer === 'none',
         `overlay pointer-events=${min.overlayPointer}`);
      ok(`${tag}: minimised pill stays clickable`, min.boxPointer === 'auto',
         `box pointer-events=${min.boxPointer}`);
      // The assertion that matters: the popup does NOT intercept a click aimed at the board.
      ok(`${tag}: popup does not block the board while minimised`, min.hitInPopup === false,
         `hit=${min.hitTag}`);
      ok(`${tag}: minimised shows the "+" restore glyph`, min.minBtnGlyph.trim() === '+',
         `glyph="${min.minBtnGlyph.trim()}"`);
      ok(`${tag}: minimised still centred on the BOARD`, Math.abs(min.boxCentre - min.boardCentre) <= 2,
         `box centre ${Math.round(min.boxCentre)} vs board centre ${Math.round(min.boardCentre)}`);
      await page.screenshot({ path: `${SHOTS}/swusim-topdeck-minimised-${engineName}-${layout}.png` }).catch(() => {});

      // ── restored ────────────────────────────────────────────────────────────────────────────────
      await page.click('.topdecksearch-minimize-btn');
      const back = await measure(page);
      ok(`${tag}: restore brings the cards back`, back.cardCount === 2 && !back.minimized,
         `${back.cardCount} images, minimized=${back.minimized}`);
      ok(`${tag}: restore brings the dim back`, (alphaOf(back.overlayBg) ?? 0) >= 0.4, `background=${back.overlayBg}`);

      await ctx.close();
    }
  } finally {
    await browser.close();
  }
}

for (const [name, pass, extra] of results) console.log(`${pass ? 'PASS' : 'FAIL'}  ${name}${extra ? '  [' + extra + ']' : ''}`);
console.log(`\n${results.filter((r) => r[1]).length}/${results.length} assertions passed`);
process.exit(allOk ? 0 : 1);
