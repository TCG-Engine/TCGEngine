# LAW_041 Nothing Left to Fear — the defeat is a "may"; decline it. Buff still applies; nothing dies.

## GIVEN
CommonSetup: bgw/rrk/{myResources:5}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Hand: LAW_041

## WHEN
# Single friendly unit -> buff auto-applies; decline the optional defeat.
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P1DISCARDCOUNT:1
