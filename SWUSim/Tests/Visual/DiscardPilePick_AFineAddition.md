# VISUAL CHECK — a card offered from a DISCARD PILE is pickable (popup), even off view / in a mixed pool
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression runner). Load it in the Test Schema
# Editor as P1, or run the automated probe (7 board shapes x desktop/mobile x 3 engines, end-to-end):
#   cd DevTools/ui-harness && node swusim-discard-pile-pick-xbrowser.mjs
#
# REPORTED 2026-10-03 (Discord): "A Fine Addition doesn't work with other players' discard pile, only own
# discard pile works." The engine offered the right pool (twi/AFineAddition.md is green); the client could
# not click it. A discard pile is `Mode=Single(Latest)` — the board draws ONE card of it — so:
#   • off-view seat (this board): P3's pile is not on the current view; the spec was held out as "off-view"
#     (an arrow badge for a UNIT), and a pile has nothing to badge → the decision had no UI at all.
#   • mixed pool (2-player too): with a hand card or a second pile in the pool, the popup was skipped and a
#     buried pile card was classed as an inline board click on a card that is never drawn.
# FIX (two layers):
#   • engine: a pool spanning several zones first asks "Play an upgrade from where?" (TWI_040#Z — Your Hand /
#     Your Discard / <seat>'s Discard), so the card pick is always one zone (owner, 2026-10-03).
#   • client safety net: IsMZChooseSpecUndrawnPileCard (Core/UILibraries*.js) routes an undrawn pile card to
#     the MZChoose popup; swuTwNormalizeSelection (GameLayoutShared.php) flags an off-view pile `forcePopup`.
# This board has ONE zone (P3's pile), so no zone menu — it pins the off-view popup. The probe also covers the
# zone menu (mixed pools, two opponent piles) end-to-end.
#
# WHAT TO LOOK AT
#   • The "Choose card" popup opens over the board showing Academy Training (SOR_120) from P3's discard.
#   • Clicking it attaches SOR_120 to P1's Consular Security Force (the only friendly unit).

## GIVEN
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
WithP3Discard: SOR_120

## WHEN
- P1>AttackGroundArena:0:p2GroundArena-0
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:p3Discard-0
