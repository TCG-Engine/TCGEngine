# VISUAL CHECK — the Smuggle slot refill now SLIDES from the deck to the resources (SSOT #3, 2026-09-11)
#
# Visual-only schema. Lives under Tests/Visual/, which the regression endpoint does NOT scan (and the
# harness stubs every animation to a no-op), so nothing here is asserted automatically. Load it by hand in
# the Test Schema Editor, as P1, then as P2. Automated 3-engine pass (performs the Smuggle through the page's
# own input path, since the editor's STEP endpoint stubs animations): `node DevTools/ui-harness/swusim-refill-slide-xbrowser.mjs`.
#
# WHAT CHANGED: "put the top card of your deck into play as a resource" is one funnel now
# (SWUResourceTopOfDeck → the same move the ramp helpers use). Eight paths used to do it with a raw
# Remove + AddResources and queued NO animation — the card simply appeared in the resource count:
#   the Smuggle refill (unit / event / upgrade), the Plot refill, SHD_009 Hunter, SHD_214 Frontier Trader,
#   SEC_008 Bail Organa, LAW_029 Citadel Research Center.
# They now queue the same deck → resource-zone slide every other ramp card (Resupply Carrier, Chewbacca's
# Bowcaster, Galactic Escalation, …) already had. This file checks the Smuggle refill; the other seven use
# the identical call.
#
# What to look at (as P1, then replay as P2):
#   1. Click Smuggle on SHD_252 Smuggler's Aid (resource index 0) and pay. P1's base heals 5 → 2.
#   2. AS P1: a face-down card slides from P1's DECK pile to the collapsed RESOURCE badge (myResources-0) —
#      the refill. Before this change the resource count just ticked; no card moved.
#   3. The slide lands on the resource badge, not at some fallback position on the board (resources
#      collapse to one DOM element; a per-card target would fly to the wrong place).
#   4. AS P2: NO slide into P1's resources — the zone is Visibility=Self and the anim is scoped to P1.
#   5. Resource count stays 6 (one smuggled out, one refilled in); deck count drops by 1; the game log
#      reads "P1 resourced the top card of their deck (Smuggle slot refill)".
#   Negative: with an EMPTY deck (remove the WithP1Deck line) there is no slide and no log line — no
#   replacement and no damage (CR 8.22.h).
#
# BOARD SHAPE: SHD_252 at resource index 0 so the Smuggle target is unambiguous; five plain resources so
# the Smuggle cost (3 + Heroism via the leader) is payable; one card in the deck so the refill has a card
# to move (and so the empty-deck negative is a one-line edit).

## GIVEN
CommonSetup: gyw/gyw/{myBaseDamage:5}
P1OnlyActions: true
WithP1Resources: 1:SHD_252:1,5:SOR_095:1
WithP1Deck: [SOR_128]

## WHEN

## EXPECT
P1RESCOUNT:6
P1DECKCOUNT:1
