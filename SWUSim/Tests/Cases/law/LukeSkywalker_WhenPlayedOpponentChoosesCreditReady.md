# LAW_080 Luke Skywalker — the opponent instead picks "create a Credit token; ready this unit". P2
# gains a Credit; Luke (entered exhausted) becomes ready.

## GIVEN
CommonSetup: ryw/bgw/{myResources:7;theirResources:0}
WithActivePlayer: 1
WithP1Hand: LAW_080

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:CreditAndReady

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_080
P1GROUNDARENAUNIT:0:READY
P2CREDITCOUNT:1
