# TWI_187 Cad Bane — the capture loop is optional at each step: capturing one SOR_128 then declining
# leaves Cad Bane with a single captive and the rest of the enemy board intact.

## GIVEN
CommonSetup: yyk/bbw/{myResources:7;handCardIds:TWI_187}
P1OnlyActions: true
WithP2GroundArena: [SOR_046:1:0 SOR_128:1:0 SOR_128:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENACOUNT:2
