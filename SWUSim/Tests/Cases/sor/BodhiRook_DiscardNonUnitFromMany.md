# SOR_201 Bodhi Rook (Unit, cost 3, Cunning) — "When Played: Look at an opponent's hand and discard
# a NON-UNIT card from it." P2's hand is a unit (SOR_095) + an event (SOR_171). Only the event is a
# valid target, so the discard auto-resolves on it (single legal target). The unit stays in hand.

## GIVEN
CommonSetup: yyw/yyw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_201
WithP2Hand: SOR_095
WithP2Hand: SOR_171
WithP2Hand: SOR_171

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirHand-1

## EXPECT
P1GROUNDARENACOUNT:1
P2HANDCOUNT:2
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_171
P2DISCARDUNIT:0:FROM:HAND
