# VISUAL CHECK — "look at an opponent's hand" must open its picker for EITHER opponent
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression runner). Automated probe:
#   cd DevTools/ui-harness && node swusim-lookathand-twinsuns-xbrowser.mjs
#
# REPORTED 2026-09-25 (game 1310334, 3-seat Twin Suns): "play Remnant Lookouts, choose P2 — nothing
# happens." The card resolves, the opponent picker appears, you pick a seat, and then no discard prompt
# ever arrives. The decision is left pending and the turn cannot continue.
#
# THE SERVER IS NOT AT FAULT. ash/RemnantLookouts.md::TwinSuns_PickAnOpponent_ThenTheDiscardOfferAppears
# pins that the MZMAYCHOOSE is queued with exactly the right pool ("p2Hand-0"). The failure is entirely in
# how the CLIENT routes that pool to a UI.
#
# THE CLIENT PATH, and why it depends on WHICH opponent you pick
#   An opponent's hand is never rendered as cards — it is a Visibility:Self zone shown as a count badge —
#   so a pick from it has to go to the MZChoose POPUP. Two gates decide that, and both are two-seat shaped:
#     1. swuTwNormalizeSelection (GameLayoutShared.php) maps a "p{n}<Zone>" spec onto the CURRENTLY VIEWED
#        frame. A Twin Suns client renders one opponent at a time, so if seat n is not the viewed opponent
#        the spec is pushed to offViewSpecs — "badge only". That is right for an arena unit (switch view
#        and click it) and useless for a HAND, which no view ever renders as cards. Result: the spec is in
#        neither inlineSpecs nor popupCards, so nothing is shown at all.
#     2. ShouldUseMZChoosePopupForSpec (UILibraries) decides "hidden zone -> popup" via
#        `isOpponentZone = zone.indexOf('their') === 0`, which is FALSE for "p2Hand". Only the 'their'
#        spelling — i.e. only a 2-player game — takes the popup branch.
#   So at two seats this always worked (the zone IS "theirHand"), and at three it works only by luck,
#   when the seat you pick happens to be the one already on screen.
#
# WHAT THIS PINS — the probe drives the REAL flow (play the card, answer the picker) and then asserts a
# usable picker exists, for BOTH opponents, by loading the board with &opponentID=2 and again with
# &opponentID=3. The off-view case is the reported bug; the in-view case is the negative control that
# proves the probe can tell the two apart.
#
# ⚠ ASSERT THE PICKER, NOT THE ABSENCE OF AN ERROR. "Nothing happens" produces no console error and no
#   changed board — the only observable is that no selectable UI exists for a decision that is pending.
#
# THE FIX — three parts, each mutation-verified, each failing DIFFERENTLY when reverted:
#   1. Core/UILibraries*.js  ShouldUseMZChoosePopupForSpec: isOpponentZone also matches /^p\d+/.
#      Reverted -> popup=false, offView=1 (no UI at all).
#   2. GameLayoutShared.php  swuTwNormalizeSelection: a popup-eligible spec is never held out as
#      "off-view". Reverted -> popup=false, offView=1.
#   3. zzGameCodeGenerator.php: publish window.p{n}<Zone>Data for every seat (the popup resolves a spec
#      by `window[spec.zone + 'Data']`, and only my/their existed). Reverted+regenerated -> popup=true,
#      cards=0 — an EMPTY picker, which is why all three are needed and none is decorative.
#   ⚠ 3 is a GENERATOR change: regenerate (`php zzGameCodeGenerator.php rootName=SWUSim`) after deploy or
#     the picker is empty on the server. It leaks nothing — responseArr is already published whole as
#     window.swuLastResponseArr and each slot is redacted per viewer by the $canSee<Zone>Player{n} gates.
#
# MOBILE PRESENTATION (owner, 2026-09-25: "make that modal bigger, 2-column grid, overflow y scroll")
#   At <=640px the picker sizes its own cards (>=132px, vs the board's ~52px), claims the screen width,
#   and lays the cards out as a fixed 2-column grid that scrolls vertically. The scroll is on the CARD
#   AREA, not the panel — scrolling the panel would push the PASS button (the only way to decline)
#   off-screen.
#   ⚠ The zone label is an absolutely-positioned strip at the bottom of each card, so it grows UPWARD
#     over the art. Now that it carries a SEAT NAME it must be one line (nowrap + ellipsis, full text in
#     the title) and sized off the card. Unconstrained it wrapped to three lines and covered the whole
#     preview.
#
# AUTOMATED PROBE (2026-09-25): 72 assertions — 2 opponent views x {clicked/desktop, preanswered/desktop,
#   preanswered/mobile} x Chromium/Firefox/WebKit. Mobile rows additionally assert panel width >=85vw,
#   display:grid with exactly 2 columns, overflow-y auto, card height >=100px, and label/card ratio <0.34.

## GIVEN
#// ⚠ THE ACTOR AND VIEWER IS SEAT 3 — the reported configuration (game 1310334's log: "P3 played Remnant
#// Lookouts / P3 chose P2"). An earlier cut of this fixture acted from seat 1 and PASSED in the browser,
#// which is how a seat-1 fixture hides a seat-3 bug. Both opponents hold cards, so the opponent picker
#// really renders instead of auto-resolving.
#// ⚠ CommonSetup builds seats 1-2 ONLY: seat 3's base must be seeded, and because that base carries no
#// matching aspect ASH_220 costs 3+2 — 3 resources silently buys nothing and the card never enters play,
#// which looks exactly like the bug under test.
#// ⚠ P2 holds THREE cards, as in the reported game — a one-card pool cannot show whether the picker's
#// grid works, and the mobile layout (two columns, scrolling) is only meaningful with several cards.
CommonSetup: yyk/yyk/{handCardIds:SOR_095;theirHandCardIds:SOR_095}
WithP2Hand: SOR_095 SOR_046 SOR_123
WithSeatOrder: 1234
WithLiveSeats: 123
WithGamePhase: ActionPhase
WithInitiativePlayer: 3
WithInitiativeClaimed: true
WithActivePlayer: 3
WithP3Base: SOR_021:0
WithP3Resources: 9
WithP3Hand: ASH_220
WithP2Deck: [SOR_046]

## WHEN
- P3>PlayHand:0
- P3>AnswerDecision:P2

## EXPECT
P3DECISIONTOOLTIP:Choose_a_card
P3SELECTABLEEXACT:p2Hand-0&p2Hand-1&p2Hand-2
