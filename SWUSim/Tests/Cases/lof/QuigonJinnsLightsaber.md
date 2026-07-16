# CombinedCostExhaust
#// LOF_201 Qui-Gon Jinn's Lightsaber — When Played: if the attached unit is Qui-Gon Jinn, exhaust any number
#// of units with combined cost 6 or less. Attached to LOF_200 (Qui-Gon Jinn), it exhausts SOR_059 (cost 1)
#// and SOR_063 (cost 3), total 4 ≤ 6.

## GIVEN
CommonSetup: yyw/ggk/{myResources:8;handCardIds:LOF_201}
P1OnlyActions: true
WithP1GroundArena: LOF_200:1:0
WithP2GroundArena: SOR_059:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENAUNIT:0:CARDID:LOF_200
