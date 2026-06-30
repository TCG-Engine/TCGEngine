# SEC_143 The Elite Squad — the deal-2 is optional ("you may"). P1 plays SEC_143 and declines → LOF_093
#   is untouched.

## GIVEN
CommonSetup: rrk/grk/{myResources:8;handCardIds:SEC_143}
P1OnlyActions: true
WithP2GroundArena: LOF_093:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
