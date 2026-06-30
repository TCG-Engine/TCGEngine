# SEC_051 Bo-Katan Kryze — "When an enemy unit is defeated: give an Experience token to a friendly unit."
#   SOR_095 kills SOR_128; Bo-Katan's reaction gives an Experience token to SOR_095.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_051:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_051
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION
