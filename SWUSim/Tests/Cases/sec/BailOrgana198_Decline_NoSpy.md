# SEC_198 Bail Organa — decline the optional discard → no Spy, hand intact.

## GIVEN
CommonSetup: yyw/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_198:1:0
WithP1Hand: SOR_095

## WHEN
- P1>AttackGroundArena:0
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:2
P1GROUNDARENACOUNT:1
P1HANDCOUNT:1
P1NODECISION
