# SEC_133 Syril Karn — decline the On Attack disclose → no unit chosen, no damage (attack still lands).

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SEC_133:1:0
WithP1Hand: SEC_133
WithP1Hand: SEC_133
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:2
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION
