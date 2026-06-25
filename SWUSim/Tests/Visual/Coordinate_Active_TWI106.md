# VISUAL CHECK — Coordinate keyword ACTIVE (animated icon)
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
# Load it by hand in the Test Schema Editor to confirm the active Coordinate icon.
#
# TWI_106 Coruscant Guard has "Coordinate - Ambush". Coordinate is ACTIVE while its
# controller has 3 or more units in play (CR 15.a, counting this unit). Here P1 has
# TWI_106 plus two more units = 3 total, so Coordinate is active and TWI_106 shows
# the animated coordinate_active.webp counter (TopRight). The other two vanilla units
# have no Coordinate, so they show nothing.
#
# Note: a unit keeps Coordinate even after its granted Ambush has been used — the
# indicator tracks the 3-unit condition, not whether Ambush already fired (CR 15.c).
#
# What to look at:
#   • TWI_106 (myGroundArena-0) shows the animated active Coordinate icon, top-right.
#   • Drop to 2 units (see Coordinate_Inactive_TWI106.md) and it swaps to the static icon.
#   • No WHEN steps — the initial GIVEN state is the whole check.

## GIVEN
CommonSetup: bbk/grw/{myResources:15;handCardIds:SOR_092}
WithP1GroundArena: TWI_106:1:1
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1GroundArenaUpgrade: 0:SOR_T02

## WHEN

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:1:CARDID:TWI_106
