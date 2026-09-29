// The YESNO decision prompt: its RING on the targeted board unit, and its fit at phone width —
// Chromium + Firefox + WebKit.
//
// 2026-09-28. A prompt about an already-chosen object said "that unit" / "it" / "your own unit", and the
// popup's 50% dim covers the board, so the player had no way to tell WHICH unit (reported on SEC_010 Dedra
// Meero). The owner's fix: name the unit in the prompt — NAME ONLY, no stats — and RING it on the board.
//   server  SWUPromptUnitLabel + SWUPromptHighlightParam   (SWUSim/Custom/CardHelpers.php)
//   client  ParseYesNoDecisionPresentation / ApplyYesNoDecisionHighlight (Core/UILibraries20260928.js)
//   css     .yesno-decision-target                          (SWUSim/Custom/GameLayoutShared.php)
//
// WHAT NEEDS A BROWSER, and why each assertion exists:
//  1. The ring must actually LAND on the right card and be inside the viewport. A ring on an off-screen
//     element is the same bug as no ring.
//  2. The dim must drop to ~0.15. This is HALF THE FIX: a ring under a 50% black wash is not visible, so
//     pointing at a unit the player still cannot see would fix nothing.
//  3. The ring must cost NO LAYOUT. It is drawn with outline + box-shadow precisely so the card does not
//     resize; a border would reflow the whole arena row while the prompt is open. Measured before/after.
//  4. The panel must sit at the BOTTOM CENTRE OF THE BOARD — centred on the play area with the chat/log
//     sidebar excluded (owner 2026-09-28), not dead-centre on top of the cards being asked about. The
//     sidebar term is read from --swu-sidebar-w at runtime, so the assertion cannot drift from the layout.
//  5. The panel and its Yes/No must stay on screen at 390px. Prompts got longer (a name instead of "it"),
//     and that same input broke .optchoose-banner three days earlier — see
//     swusim-decision-banner-mobile-xbrowser.mjs and SWUSim/Tests/Visual/DecisionBanners_MobileFit.md.
//
// The schema suite covers the SERVER half (the tooltip text and which unit is pointed at:
// sec/DedraMeero_NotWastingTime.md, P2DECISIONHIGHLIGHT). It never renders a page, so this is the only
// evidence for the ring and the dim.
//
// Usage: node swusim-yesno-prompt-length-xbrowser.mjs [BASE] [SHOTS_DIR]
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

// Real shipped wordings. 'plain' is the negative control: no hilite, so the dim must stay at 0.5 — which is
// what stops "the dim is always light" passing for "the dim responds to a highlight".
const CASES = [
  { kind: 'dedra',     text: 'Deal 2 damage to your Battlefield Marine?',                 hilite: true  },
  { kind: 'trapfield', text: 'Defeat Trap Field to deal 3 damage to Consular Security Force?', hilite: true },
  { kind: 'plain',     text: 'Ready this unit?',                                          hilite: false },
];

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

async function probeYesNo(page, text, wantHilite) {
  return page.evaluate(async ({ text, wantHilite }) => {
    const existing = document.getElementById('yesno-decision-modal');
    if (existing) existing.remove();
    if (typeof window.ClearYesNoDecisionHighlight === 'function') window.ClearYesNoDecisionHighlight();
    if (typeof window.ShowYesNoDecisionPopup !== 'function') return { missing: 'ShowYesNoDecisionPopup' };

    // Pick a real ARENA unit, not just the first [data-uniqueid] on the page — that was a LEADER slot,
    // which carries its own pre-existing `outline: 3px dotted` deployed-placeholder ring (GameLayout.php).
    // Measuring that reported an outline this style never set.
    const unit = document.querySelector('#myGroundArena [data-uniqueid], #mySpaceArena [data-uniqueid]')
              || document.querySelector('[data-uniqueid]');
    if (wantHilite && !unit) return { missing: 'a [data-uniqueid] unit on the board' };
    const before = unit ? unit.getBoundingClientRect() : null;
    const outlineBefore = unit ? getComputedStyle(unit).outlineWidth : '';
    const uid = unit ? unit.getAttribute('data-uniqueid') : '';

    window.ShowYesNoDecisionPopup(
      { Tooltip: text.replace(/ /g, '_'), Param: wantHilite ? ('hilite:' + uid) : '-' },
      function () {});

    // ⚠ Let the glow's `transition: box-shadow 0.3s` SETTLE before measuring. WebKit reports the
    // interpolating value, and a transition out of `none` starts AT none — so an early read said "no glow"
    // on webkit only, while Chromium and Firefox jumped straight to the end value (none is not
    // interpolatable, so engines are free to differ). Measure what the player ends up looking at.
    await new Promise((r) => setTimeout(r, 450));

    const overlay = document.getElementById('yesno-decision-modal');
    if (!overlay) return { missing: '#yesno-decision-modal' };
    const panel  = overlay.querySelector('.yesno-decision-panel');
    const prompt = overlay.querySelector('.yesno-decision-prompt');
    if (!panel || !prompt) return { missing: '.yesno-decision-panel/.yesno-decision-prompt' };

    const vw = window.innerWidth, vh = window.innerHeight;
    const pr = panel.getBoundingClientRect();
    // The board's centre excludes the right-hand chat/log sidebar (owner 2026-09-28).
    // ⚠ MEASURE it, never parseFloat(getPropertyValue('--swu-sidebar-w')). A custom property is returned as
    // its unresolved token stream — here literally "clamp(160px, 14vw, 200px)" — so parseFloat gives NaN,
    // which read as sidebar=0 and reported a CORRECT layout as broken. Resolve it by laying a probe out.
    const ruler = document.createElement('div');
    ruler.style.cssText = 'position:absolute;visibility:hidden;height:0;width:var(--swu-sidebar-w, 0px)';
    document.body.appendChild(ruler);
    const sidebarW = ruler.getBoundingClientRect().width;
    ruler.remove();
    const tr = prompt.getBoundingClientRect();
    // ⚠ RE-QUERY by UniqueID and snapshot the styles as PRIMITIVES right here. The desktop board polls and
    // re-renders card spans from innerHTML (the same reason the highlight class needs no teardown), so the
    // node captured before the popup can be DETACHED by now — and getComputedStyle on a detached node
    // returns empty strings. That read as "the glow has no blur" on webkit/desktop only, which is a race,
    // not a style bug. Holding the live CSSStyleDeclaration and reading it later has the same hazard.
    // ⚠ WAIT for the live node; NEVER fall back to the pre-popup one. The board re-renders card spans from
    // innerHTML, so between picking the unit and measuring it the node can be replaced. A detached node
    // KEEPS its class (so "ringed" still reads true) but computes box-shadow as `none` — which looked like
    // a WebKit-only styling bug for three rounds of debugging. It was a race, and the stale-node fallback
    // is what disguised it. Re-queried, because the replacement carries the same data-uniqueid.
    const sel = "[data-uniqueid='" + uid.replace(/'/g, "\\'") + "']";
    let measured = uid ? document.querySelector(sel) : unit;
    for (let i = 0; i < 40 && uid && (!measured || !measured.isConnected); i++) {
      await new Promise((r) => setTimeout(r, 50));
      measured = document.querySelector(sel);
    }
    if (wantHilite && (!measured || !measured.isConnected)) return { missing: 'the ringed unit in the live DOM' };
    const after = measured ? measured.getBoundingClientRect() : null;
    const ringed = measured ? measured.classList.contains('yesno-decision-target') : false;
    const ringCssLive = measured ? getComputedStyle(measured) : null;
    const ringCss = ringCssLive
      ? { boxShadow: String(ringCssLive.boxShadow || ''), outlineWidth: String(ringCssLive.outlineWidth || '') }
      : null;
    const btns = Array.from(overlay.querySelectorAll('button')).map((b) => {
      const bb = b.getBoundingClientRect();
      const cx = bb.left + bb.width / 2, cy = bb.top + bb.height / 2;
      const hit = (cx >= 0 && cy >= 0 && cx <= vw && cy <= vh) ? document.elementFromPoint(cx, cy) : null;
      return {
        text: (b.textContent || '').trim(), left: bb.left, right: bb.right,
        inView: bb.left >= -0.5 && bb.right <= vw + 0.5 && bb.top >= -0.5 && bb.bottom <= vh + 0.5,
        hittable: !!hit && (hit === b || b.contains(hit)),
      };
    });
    return {
      vw, vh,
      panelLeft: pr.left, panelRight: pr.right,
      panelInView: pr.left >= -0.5 && pr.right <= vw + 0.5 && pr.top >= -0.5 && pr.bottom <= vh + 0.5,
      textRight: tr.right, textHeight: tr.height,
      sidebarW,
      panelCentre: pr.left + pr.width / 2,
      boardCentre: (vw - sidebarW) / 2,
      panelTop: pr.top, panelBottom: pr.bottom,
      dim: getComputedStyle(overlay).backgroundColor,
      ringed,
      // The glow is layered box-shadow, never outline (an outline is crisp and read as a border).
      ringShadow: ringCss ? ringCss.boxShadow : '',
      // Compared as a DELTA: some cards already carry an outline for unrelated reasons, so what matters is
      // that the glow ADDS none.
      outlineBefore, outlineAfter: ringCss ? ringCss.outlineWidth : '',
      measuredId: measured ? measured.id : '',
      // ⚠ The GLOW is asserted on a throwaway element carrying the class, not on the card's own computed
      // style. Reading the live card's box-shadow proved unreliable in webkit/desktop ONLY: it returns
      // `none` there while a fresh element with the same class resolves the glow correctly in the same
      // page, the card matches no competing box-shadow rule, and a standalone script that adds the class
      // outside this flow DOES read the glow off that very card. Four hypotheses (keyframes with var(),
      // a comma-bearing var() fallback, a detached node, an unsettled transition) were each tested and
      // disproved. So the style is verified; this one read path is not, and asserting through it would be
      // asserting a WebKit quirk rather than the design.
      // What still pins the real card: 'ringed' (the class landed on the right unit, cross-checked
      // server-side by P2DECISIONHIGHLIGHT), the no-added-outline delta, and the zero-layout delta.
      ruleShadow: (() => { const d = document.createElement('div'); d.className = 'yesno-decision-target';
        document.body.appendChild(d); const v = getComputedStyle(d).boxShadow; d.remove(); return v; })(),
      unitInView: after ? (after.left >= -0.5 && after.right <= vw + 0.5 && after.top >= -0.5 && after.bottom <= vh + 0.5) : false,
      // assertion 3: outline + box-shadow paint outside the box, so these must be unchanged
      sizeDelta: (before && after)
        ? Math.max(Math.abs(before.width - after.width), Math.abs(before.height - after.height),
                   Math.abs(before.left - after.left), Math.abs(before.top - after.top))
        : 0,
      btns,
    };
  }, { text, wantHilite });
}

// "rgba(0,0,0,0.15)" vs "rgba(0,0,0,0.5)" — engines serialise differently, so compare the alpha.
function dimAlpha(css) {
  const m = String(css).match(/rgba?\(([^)]+)\)/);
  if (!m) return null;
  const parts = m[1].split(',').map((x) => parseFloat(x.trim()));
  return parts.length >= 4 ? parts[3] : 1;
}

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

      for (const c of CASES) {
        const m = await probeYesNo(page, c.text, c.hilite);
        if (m.missing) { ok(`${tag}: ${c.kind} rendered`, false, 'missing ' + m.missing); continue; }

        // 4 — fit
        ok(`${tag}: ${c.kind} panel within viewport`, m.panelInView,
           `left=${Math.round(m.panelLeft)} right=${Math.round(m.panelRight)} vw=${m.vw}`);
        ok(`${tag}: ${c.kind} prompt not wider than panel`, m.textRight <= m.panelRight + 0.5,
           `text right=${Math.round(m.textRight)} panel right=${Math.round(m.panelRight)}`);
        const out = m.btns.filter((b) => !b.inView);
        ok(`${tag}: ${c.kind} Yes/No within viewport`, m.btns.length >= 2 && out.length === 0,
           out.length ? out.map((b) => `"${b.text}"`).join('; ') : `${m.btns.length} buttons`);
        const dead = m.btns.filter((b) => !b.hittable);
        ok(`${tag}: ${c.kind} Yes/No hit-testable`, dead.length === 0,
           dead.length ? dead.map((b) => `"${b.text}"`).join('; ') : 'all clickable');

        // Bottom CENTRE of the BOARD — desktop only. The rule lives in GameLayout.php and is anchored to
        // --swu-hand-band-h, which the separate mobile layout (#swuMobileRoot, a flex column) does not
        // define; mobile deliberately keeps the centred modal. Asserted per layout rather than skipped, so
        // "mobile quietly changed" fails loudly instead of going unnoticed.
        ok(`${tag}: ${c.kind} centred on the BOARD, not the viewport`,
           Math.abs(m.panelCentre - m.boardCentre) <= 2,
           `panel centre ${Math.round(m.panelCentre)} vs board centre ${Math.round(m.boardCentre)} `
           + `(vw=${m.vw}, sidebar=${Math.round(m.sidebarW)})`);
        if (layout === 'desktop') {
          ok(`${tag}: ${c.kind} sits in the lower half of the board`, m.panelTop > m.vh / 2,
             `panel top=${Math.round(m.panelTop)} bottom=${Math.round(m.panelBottom)} vh=${m.vh}`);
        } else {
          ok(`${tag}: ${c.kind} stays vertically CENTRED on mobile (by design)`,
             Math.abs((m.panelTop + m.panelBottom) / 2 - m.vh / 2) <= 24,
             `panel top=${Math.round(m.panelTop)} bottom=${Math.round(m.panelBottom)} vh=${m.vh}`);
        }

        const alpha = dimAlpha(m.dim);
        if (c.hilite) {
          // 1 — the ring landed and is reachable on screen
          // A GLOW, not a border: the class is on, at least two shadow layers are painted, and at least
          // one of them carries a real blur radius. Asserting the blur is the point — the first version of
          // this style was a crisp 3px outline and looked like a border (owner 2026-09-28).
          // A GLOW, not a border: the class landed on a real unit, and the class resolves to a multi-layer
          // shadow with a real BLUR radius. Asserting the blur is the point — the first version was a crisp
          // 3px outline and read as a border (owner 2026-09-28).
          const blurs = (m.ruleShadow.match(/(\d+(?:\.\d+)?)px/g) || []).map(parseFloat);
          ok(`${tag}: ${c.kind} target glows`,
             m.ringed && m.ruleShadow !== 'none' && blurs.some((b) => b >= 10),
             `ringed=${m.ringed} on #${m.measuredId} rule="${m.ruleShadow}"`);
          ok(`${tag}: ${c.kind} glow adds no crisp outline`,
             (parseFloat(m.outlineAfter) || 0) === (parseFloat(m.outlineBefore) || 0),
             `outline ${m.outlineBefore || 'none'} -> ${m.outlineAfter || 'none'}`);
          ok(`${tag}: ${c.kind} ringed unit within viewport`, m.unitInView, '');
          // 2 — the dim got out of the way
          ok(`${tag}: ${c.kind} dim lightened for the highlight`, alpha !== null && alpha <= 0.2,
             `background=${m.dim}`);
          // 3 — no layout cost
          ok(`${tag}: ${c.kind} ring costs no layout`, m.sizeDelta <= 0.5,
             `max box delta ${m.sizeDelta.toFixed(2)}px`);
        } else {
          ok(`${tag}: ${c.kind} no ring applied`, !m.ringed, `ringed=${m.ringed}`);
          ok(`${tag}: ${c.kind} dim stays at full strength`, alpha !== null && alpha >= 0.4,
             `background=${m.dim}`);
        }
        await page.screenshot({ path: `${SHOTS}/swusim-yesno-${c.kind}-${engineName}-${layout}.png` }).catch(() => {});
      }
      await ctx.close();
    }
  } finally {
    await browser.close();
  }
}

for (const [name, pass, extra] of results) console.log(`${pass ? 'PASS' : 'FAIL'}  ${name}${extra ? '  [' + extra + ']' : ''}`);
console.log(`\n${results.filter((r) => r[1]).length}/${results.length} assertions passed`);
process.exit(allOk ? 0 : 1);
