# Wedge Antilles (SOR_100): friendly Vehicle units get +1/+1 while Wedge is in play.
# JTL_221 (Stolen AT-Hauler, base 4/5) is a Vehicle in P1's space arena.
# With Wedge on the ground, JTL_221 should read 5/6.

## GIVEN
CommonSetup: grw/grw
WithP1GroundArena: SOR_100:2:0
WithP1SpaceArena: JTL_221:2:0

## WHEN

## EXPECT
P1SPACEARENAUNIT:0:POWER:5
P1SPACEARENAUNIT:0:HP:6
