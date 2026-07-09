# The uniqueness rule applies ONLY to unique cards (CR 8.19.7 / 29): a player may control any number
# of copies of a NON-unique card. SOR_095 Battlefield Marine (2-cost, Command/Heroism, NOT unique).
# P1 controls one copy and plays a second — both stay in play, no prompt. Guards against the
# enforcement over-firing on non-unique duplicates.
#
# Leia (gw = Command+Heroism) covers both of SOR_095's aspects → no aspect penalty; cost 2 = 2 resources.

## GIVEN
CommonSetup: ggw/grw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1DISCARDCOUNT:0
P1NODECISION
