# SOR_206 Mining Guild TIE Fighter — the draw is gated on paying 2 resources. With only 1
# ready resource the option isn't offered: the attack resolves with no decision pending, no
# resources spent, and no card drawn. Unaffordable-cost guard.

## GIVEN
CommonSetup: yyk/yyk/{myResources:1}
P1OnlyActions: true
WithP1SpaceArena: SOR_206:1:0
WithP1Deck: SOR_128

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1NODECISION
P1RESAVAILABLE:1
P1HANDCOUNT:0
