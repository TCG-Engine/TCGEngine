# VISUAL CHECK — Coordinate keyword INACTIVE (static icon)
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
# Load it by hand in the Test Schema Editor to confirm the inactive Coordinate icon.
#
# TWI_106 Coruscant Guard has "Coordinate - Ambush". Coordinate is INACTIVE while its
# controller has 2 or fewer units in play — the unit still HAS the keyword, the
# following ability is just dormant (CR 15.c). Here P1 has TWI_106 plus one more unit
# = 2 total, so TWI_106 shows the static coordinate_inactive.png counter (TopRight).
#
# Note: a unit keeps Coordinate even after its granted Ambush has been used; the
# indicator reflects the live 3-unit condition, not whether Ambush already fired.
#
# What to look at:
#   • TWI_106 (myGroundArena-0) shows the static inactive Coordinate icon, top-right.
#   • Add a third unit (see Coordinate_Active_TWI106.md) and it swaps to the animated icon.
#   • No WHEN steps — the initial GIVEN state is the whole check.

## GIVEN
CommonSetup: bbk/grw
WithP1GroundArena: TWI_106:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:TWI_106
