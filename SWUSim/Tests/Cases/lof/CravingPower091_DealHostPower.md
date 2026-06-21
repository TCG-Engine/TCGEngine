# LOF_091 Craving Power (+2/+2) — Attach to a friendly unit. When Played: deal damage to an enemy unit
# equal to attached unit's power. Played onto SOR_095 (3 + 2 = 5), it deals 5 to the enemy 4/7.

## GIVEN
CommonSetup: ggk/rrw/{myResources:5;handCardIds:LOF_091}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:5
