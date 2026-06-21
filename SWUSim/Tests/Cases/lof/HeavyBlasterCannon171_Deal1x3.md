# LOF_171 Heavy Blaster Cannon — When Played: may deal 1 to a ground unit, then 1, then 1 (same unit).
# Played onto SOR_095, it deals 3 total to the enemy 3/7.

## GIVEN
CommonSetup: rrk/ggw/{myResources:4;handCardIds:LOF_171}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
