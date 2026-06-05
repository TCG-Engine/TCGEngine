# Wedge Antilles (SOR_100): only friendly VEHICLE units get +1/+1.
# ASH_259 (LEP Ratcatcher, base 1/1) is a non-Vehicle Ground unit.
# ASH_259 should NOT get a boost — it stays 1/1.
# Wedge is at index 0; ASH_259 is at index 1.

## GIVEN
CommonSetup: grw/grw
WithP1GroundArena: SOR_100:2:0
WithP1GroundArena: ASH_259:2:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:1:POWER:1
P1GROUNDARENAUNIT:1:HP:1
