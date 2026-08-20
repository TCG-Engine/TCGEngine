# VISUAL CHECK — Twin Suns: fortify / arrest pips on a preview tile's base
#
#   Setup as usual, drive the WHEN lines through TestSchemaStep.php, then open as playerID=2 so P1
#   (the fortified + arresting seat) is a preview TILE rather than your own board:
#   http://localhost:3400/TCGEngine/NextTurn.php?folderPath=SWUSim&gameName=N&playerID=2
#
# WHAT TO LOOK AT
#   • P1's tile: its BASE thumbnail wears a GREY pip on the top-LEFT (3 = Fortify upgrades) and a
#     GOLDENROD pip on the top-RIGHT (2 = arrests). Same two colours as the full board's tabs.
#   • ⚠ The pips are OVERLAID on the thumbnail's corners, never placed beside it. Row 1 of every tile
#     must stay exactly as wide as the others — assert the first leader's offset is EQUAL on all tiles
#     (29px at 1700x1100). The tiles are a comparison view; a fortified seat whose row 1 is wider than
#     the rest is the bug this avoids.
#   • The base's centre is left to its damage counter — check a damaged fortified base shows all three
#     (damage centre, grey top-left, goldenrod top-right) without overlap.
#   • Seats with neither show no pips at all.
#   • CLICK either chip for its panel: grey → "Attached Upgrades", goldenrod → "Captured Units".
#     ⚠ CLICK, not hover — consistent with the discard pile, and any subsequent click ANYWHERE
#     (inside the panel or outside it) dismisses it. Regression checks: hovering a chip must do
#     NOTHING; a click on the panel itself must close it, not just a click outside.
#     Both reuse showLineageOverflowPopup, so a tile and the zoomed board show the same panel.
#     ⚠ Captured identities are OPEN INFORMATION (CR 1077.1 / 207.1) — showing them is correct.
#
# ⚠ Both counts come from the Base zone's virtuals (UpgradeCount / CaptiveCount) which the mini-board
# already receives — no extra transport. Base captives are GlobalEffects flags drained at
# RegroupPhaseStart, so ARRESTED is per-round; do not write a fixture that passes regroup.
#
# VERIFIED 2026-08-19, Chromium + Firefox at 1700x1100.
# WebKit NOT covered: it does not launch on this machine.

## GIVEN
CommonSetup: yyk/rrk/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
WithSeatOrder: 1234
#// ⚠ The two arrests are SEEDED, not played. A seat gets ONE action per turn, and letting the other
#// three pass to come back round reaches the REGROUP phase — which rescues every base captive, so the
#// count reads 0. WithP{n}BaseCaptive writes the same SWU_BASECAPTIVE GlobalEffects flag the card
#// writes, which is the only way to build a MULTI-arrest board in Twin Suns. The played path is
#// covered in 2P by BaseTabs_FortifiedAndArrested_2P.md and Cases/sec/Arrest.md.
P1OnlyActions: true
WithP1BaseCaptive: SOR_095
WithP1BaseCaptive: SOR_046
WithP1BaseUpgrade: HMW_081
WithP1BaseUpgrade: HMW_171
WithP1BaseUpgrade: HMW_205
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP3Leader:  SHD_014
WithP3Leader2: SHD_015
WithP3Base:    SOR_026:5
WithP4Leader:  TWI_009
WithP4Leader2: TWI_010
WithP4Base:    SOR_026:8

## WHEN

## EXPECT
SEATCOUNT:4
P1BASEUPGRADECOUNT:3
P1BASECAPTIVECOUNT:2
