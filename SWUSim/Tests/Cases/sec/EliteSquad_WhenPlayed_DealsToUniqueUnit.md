# SEC_143 The Elite Squad — When Played: you may deal 2 damage to another unique unit. P1 plays SEC_143
#   (unique); the only OTHER unique unit is P2's LOF_093 (2/5), which takes 2. (Non-unique units would not
#   be offered; SEC_143 itself is excluded as "another".)

## GIVEN
CommonSetup: rrk/grk/{myResources:8;handCardIds:SEC_143}
P1OnlyActions: true
WithP2GroundArena: LOF_093:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
