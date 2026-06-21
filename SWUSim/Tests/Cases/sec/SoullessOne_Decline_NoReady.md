# SEC_190 Soulless One — decline the On Attack disclose → no resources readied.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_190:1:0
WithP1Resources: 4:SOR_095:0
WithP1Hand: SEC_220
WithP1Hand: SEC_230
WithP1Hand: SEC_133

## WHEN
- P1>AttackGroundArena:0
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:3
P1RESAVAILABLE:0
P1NODECISION
