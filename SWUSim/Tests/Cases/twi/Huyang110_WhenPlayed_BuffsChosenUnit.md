# TWI_110 Huyang (Unit 2/4, Ground, cost 3) — "When Played: Choose another friendly unit. While this
# unit is in play, the chosen unit gets +2/+2." Playing Huyang with a friendly SOR_095 (3/3) → choose
# it → 5/5.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3;handCardIds:TWI_110}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
