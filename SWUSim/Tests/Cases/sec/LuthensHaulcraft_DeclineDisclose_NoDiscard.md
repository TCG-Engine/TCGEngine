# SEC_153 Luthen's Haulcraft — decline the When Defeated disclose → opponent discards nothing.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1SpaceArena: SEC_153:1:0
WithP2SpaceArena: JTL_069:1:0
WithP1Hand: SEC_148
WithP1Hand: SEC_133
WithP2Hand: SOR_095
WithP2Hand: SOR_095

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENACOUNT:0
P2HANDCOUNT:2
P2DISCARDCOUNT:0
P1NODECISION
