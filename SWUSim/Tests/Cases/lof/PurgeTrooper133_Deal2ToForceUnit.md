# LOF_133 Purge Trooper — When Played: may deal 2 damage to a Force unit. P1 deals 2 to the enemy Force
# unit (Plo Koon).

## GIVEN
CommonSetup: rrk/ggw/{myResources:3;handCardIds:LOF_133}
P1OnlyActions: true
WithP2GroundArena: LOF_050:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
