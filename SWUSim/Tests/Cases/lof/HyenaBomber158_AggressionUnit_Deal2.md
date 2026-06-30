# LOF_158 Hyena Bomber — When Played: if you control another Aggression unit, may deal 2 damage to a
# ground unit. P1 controls the Aggression Acolyte, so playing the Bomber lets P1 deal 2 to the enemy 3/7.

## GIVEN
CommonSetup: rrk/ggw/{myResources:3;handCardIds:LOF_158}
P1OnlyActions: true
WithP1GroundArena: LOF_129:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
