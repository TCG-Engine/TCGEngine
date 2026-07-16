# WhenDefeatedCloneSearch
#// LOF_117 Sifo-Dyas — When Defeated: search the top 8 for any number of Clone units (combined cost ≤4),
#// discard them (marked free-playable this phase), rest to the bottom. Sifo-Dyas dies attacking SOR_039,
#// finds TWI_240 (Clone, cost 1), discards it with TPF, then P1 plays it from discard for free.

## GIVEN
CommonSetup: ggw/rrk/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: LOF_117:1:0
WithP2GroundArena: SOR_039:1:0
WithP1Deck: TWI_240

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:TWI_240
- P1>PlayFromDiscard:1

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_240
