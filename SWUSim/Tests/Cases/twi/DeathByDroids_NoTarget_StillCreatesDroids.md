# TWI_076 Death by Droids — no unit costs 3 or less (P2's only unit is JTL_069, cost 4). The defeat
# clause fizzles cleanly (nothing to defeat), but the second sentence still creates 2 Battle Droid
# tokens. Guard for the empty-target branch.

## GIVEN
CommonSetup: brk/grw/{myResources:5;handCardIds:TWI_076}
P1OnlyActions: true
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2SPACEARENACOUNT:1
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
P1NODECISION
