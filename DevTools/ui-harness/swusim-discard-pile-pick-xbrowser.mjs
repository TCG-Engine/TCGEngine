// A card offered from a DISCARD PILE must be pickable — Chromium/Firefox/WebKit, desktop + mobile.
//
// Reported 2026-10-03 (Discord): "A Fine Addition doesn't work with other players' discard pile, only own
// discard pile works". The engine offers the right pool (twi/AFineAddition.md is green); the CLIENT had no
// way to click it. A discard pile is a `Mode=Single(Latest)` zone — the board draws ONE card, the latest —
// so a candidate anywhere else in the pile is not on the board at all. Two routes stranded it:
//   • MIXED POOL (any seat count): the popup is used only when EVERY candidate sits in one pile
//     (ShouldPopupVisibleSingleZoneChoice). Add a hand card, or a second pile, and the discard candidate was
//     classed as an inline board click on a card that is never drawn. Both piles failed alike.
//   • TWIN SUNS OFF-VIEW: a candidate in the discard of a seat that is not on the current view was held out
//     as "off-view" (arrow badge, Zoom In). A pile has nothing to badge — the decision had NO UI. P2's pile
//     (on view) worked, P3's did not: "other players' discard pile".
// Fixture: SWUSim/Tests/Visual/DiscardPilePick_AFineAddition.md (the scenarios are built inline here).
//
// FIX (two layers): the engine asks WHICH ZONE first whenever the pool spans several (TWI_040#Z), so the card
// pick is always single-zone; and the client routes any undrawn pile card to the popup regardless
// (IsMZChooseSpecUndrawnPileCard — the safety net for other cards' mixed pools).
//
// THE assertion is end-to-end: tap the zone (if asked), click the candidate through whatever picker the page
// shows, then read the board — SOR_120 must now be attached to the friendly unit. A "popup is shown" check
// alone would pass a popup whose click submits nothing.
//
// Usage: node swusim-discard-pile-pick-xbrowser.mjs [BASE] [SHOTS_DIR]  ENGINES=chromium,firefox,webkit
import { chromium, firefox, webkit } from 'playwright';

const BASE  = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const SHOTS = process.argv[3] || '/tmp';
const ALL = { chromium, firefox, webkit };
const ENGINES = Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n));

let allOk = true;
const results = [];
const ok = (name, cond, extra = '') => { if (!cond) allOk = false; results.push([name, !!cond, extra]); };

// P1 kills P2's unit (the A Fine Addition condition), then plays A Fine Addition from hand. SOR_120 is the
// upgrade being looked for; SOR_046 is P1's only unit, so the host pick auto-resolves and the click on the
// candidate is the ONLY decision the page has to support.
const twoP = (hand, lines) => `## GIVEN
CommonSetup: brk/bbw/{myResources:6}
P1OnlyActions: true
WithP1Hand: ${hand}
${lines}
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
## EXPECT
`;
const ts3 = (lines) => `## GIVEN
CommonSetup: brk/bbw/{myResources:6}
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1Hand: TWI_040
WithP3Base: SOR_021:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0
${lines}
## WHEN
- P1>AttackGroundArena:0:p2GroundArena-0
- P1>PlayHand:0
## EXPECT
`;

// [name, schema, the candidate's mzID as the server emits it, the zone-menu option to tap first (or null)]
// A pool spanning several zones now asks WHICH zone first (TWI_040#Z — owner, 2026-10-03), so every
// later pick is a single-zone pick. A single-zone pool goes straight to the card pick, as before.
const SCENARIOS = [
  // controls — one pile only; these always worked, and must NOT grow a zone menu
  ['2P own pile only',            twoP('TWI_040', 'WithP1Discard: SOR_120'), 'myDiscard-0', null],
  ['2P opponent pile only',       twoP('TWI_040', 'WithP2Discard: SOR_120'), 'theirDiscard-0', null],
  // the mixed pool — a second zone in the pool stranded the pile card (either pile)
  ['2P hand + opponent pile',     twoP('[TWI_040 SOR_120]', 'WithP2Discard: SOR_120'), 'theirDiscard-0', "Opponent's_Discard"],
  ['2P hand + own pile',          twoP('[TWI_040 SOR_120]', 'WithP1Discard: SOR_120'), 'myDiscard-0', 'Your_Discard'],
  ['2P hand + pile, HAND chosen', twoP('[TWI_040 SOR_120]', 'WithP2Discard: SOR_120'), 'myHand-0', 'Your_Hand'],
  // buried: the candidate is NOT the pile's latest card (the defeated unit lands on top of it)
  ['2P both piles (buried)',      twoP('TWI_040', 'WithP1Discard: SOR_120\nWithP2Discard: SOR_120'), 'theirDiscard-0', "Opponent's_Discard"],
  // Twin Suns — P2 is the default view's opponent, P3 is off view
  ['TS3 on-view opponent pile',   ts3('WithP2Discard: SOR_120'), 'p2Discard-0', null],
  ['TS3 off-view opponent pile',  ts3('WithP3Discard: SOR_120'), 'p3Discard-0', null],
  ['TS3 two opponent piles',      ts3('WithP2Discard: SOR_120\nWithP3Discard: SOR_120'), 'p3Discard-0', "P3's_Discard"],
];

async function login(page) {
  await page.goto(BASE + 'SharedUI/LoginPage.php', { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="userID"]', 'claudebot1');
  await page.fill('input[name="password"]', 'pass');
  await Promise.all([page.waitForNavigation({ waitUntil: 'load' }).catch(() => {}), page.click('button[type="submit"]')]);
}

async function buildGame(page, schema) {
  const setup = await (await page.request.post(BASE + 'SWUSim/TestSchemaSetup.php', { multipart: { schema } })).json();
  if (setup.error) throw new Error('setup: ' + setup.error);
  for (const step of setup.whenSteps) {
    const r = await (await page.request.post(BASE + 'SWUSim/TestSchemaStep.php',
      { multipart: { gameName: String(setup.gameName), step: step.raw } })).json();
    if (r.error) throw new Error(`step "${step.raw}": ${r.error}`);
  }
  return setup.gameName;
}

for (const [engineName, engine] of ENGINES) {
  const browser = await engine.launch();
  try {
    for (const [name, schema, wantSpec, zoneOpt] of SCENARIOS) {
      for (const layout of ['desktop', 'mobile']) {
        const tag = `${engineName}/${layout} ${name}`;
        const ctx = await browser.newContext({ viewport: layout === 'mobile'
          ? { width: 390, height: 844 } : { width: 1600, height: 900 } });
        const page = await ctx.newPage();
        await login(page);
        let gameName;
        try { gameName = await buildGame(page, schema); }
        catch (e) { ok(`${tag}: board built`, false, e.message); await ctx.close(); continue; }
        await page.goto(BASE + `NextTurn.php?folderPath=SWUSim&gameName=${gameName}&playerID=1&authKey=testschema`
          + (layout === 'mobile' ? '&swuLayout=mobile' : ''), { waitUntil: 'load' });
        await page.waitForTimeout(3000);

        // Step 1 — the zone menu: shown exactly when the pool spans several zones.
        const banner = page.locator('.optchoose-banner');
        const menuShown = await banner.isVisible().catch(() => false);
        ok(`${tag}: zone menu ${zoneOpt ? 'shown' : 'NOT shown'}`, menuShown === !!zoneOpt);
        if (zoneOpt && menuShown) {
          const btn = banner.locator(`button[data-option="${zoneOpt}"]`);
          const label = (await btn.textContent().catch(() => '')) || '';
          // A seat-possessive option shows the seat's NAME, never the raw transport token.
          if (/^P\d+'s_/.test(zoneOpt)) ok(`${tag}: the seat option is named, not "${zoneOpt}"`, !/^P\d/.test(label.trim()), label);
          await btn.click().catch(() => {});
          await page.waitForTimeout(3500);
        }

        // Step 2 — the card pick, through whichever picker the page shows.
        const before = await page.evaluate(() => {
          const sm = window.SelectionMode || {};
          return {
            offered: (sm._twAllSpecs || sm.allowedZones || []).map((s) => s && s.originalSpec),
            popup:   (sm.popupCards || []).map((s) => s && s.originalSpec),
            inline:  (sm.inlineSpecs || []).map((s) => s && s.originalSpec),
            offView: (sm._twOffView || []).map((s) => s && s.originalSpec),
          };
        });
        ok(`${tag}: the card pick offers ${wantSpec}`, before.offered.includes(wantSpec), JSON.stringify(before.offered));
        if (zoneOpt) ok(`${tag}: the card pick offers ONLY the chosen zone`, before.offered.length === 1, JSON.stringify(before.offered));
        const isHand = /Hand-\d+$/.test(wantSpec);
        ok(`${tag}: ${wantSpec} is ${isHand ? 'clickable on the board' : 'in the picker popup'}`,
           isHand ? before.inline.includes(wantSpec) : before.popup.includes(wantSpec),
           `popup=[${before.popup}] inline=[${before.inline}] offView=[${before.offView}]`);

        if (isHand) {
          await page.locator('#' + wantSpec).click({ timeout: 5000 }).catch(() => {});
          await page.waitForTimeout(3500);
        } else {
          // The popup card carries no mzID attribute, so locate it by its position in popupCards.
          const idx = before.popup.indexOf(wantSpec);
          if (idx >= 0) {
            await page.locator('#mzchoose-popup .mzchoose-popup-card').nth(idx).click().catch(() => {});
            await page.waitForTimeout(3500);
          }
        }
        const attached = await page.evaluate(() => String(window.myGroundArenaData || '').includes('SOR_120'));
        ok(`${tag}: clicking it plays the upgrade (SOR_120 attached)`, attached);
        await page.screenshot({ path: `${SHOTS}/swusim-discard-pile-pick-${engineName}-${layout}-${name.replace(/[^a-z0-9]+/gi, '_')}.png` }).catch(() => {});
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
