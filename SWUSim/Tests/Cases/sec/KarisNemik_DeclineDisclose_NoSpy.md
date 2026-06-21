# SEC_148 Karis Nemik — decline the When Defeated disclose → no Spy token created.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_148:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Hand: SEC_153

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1NODECISION
