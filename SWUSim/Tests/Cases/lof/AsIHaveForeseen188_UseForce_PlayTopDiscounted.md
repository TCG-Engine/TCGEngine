# LOF_188 As I Have Foreseen — "Look at the top card. You may use the Force. If you do, play that card.
# It costs 4 resources less." The top card is SEC_080 (cost 3 → 0 after −4), so P1 uses the Force and
# plays it for free.

## GIVEN
CommonSetup: yyk/rrk/{myResources:1;handCardIds:LOF_188}
P1OnlyActions: true
WithP1Force: true
WithP1Deck: SEC_080

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1NOFORCE
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
