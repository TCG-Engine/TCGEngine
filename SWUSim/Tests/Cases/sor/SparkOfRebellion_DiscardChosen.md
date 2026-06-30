# SOR_200 Spark of Rebellion (Event, cost 2, Cunning/Heroism) — "Look at an opponent's hand and
# discard a card from it." P1 plays Spark and sees P2's two-card hand; P1 chooses to discard the
# first card (SOR_171, an event). P2 hand 2→1, P2 discard 0→1 (From HAND). The Spark event itself
# goes to P1's discard.

## GIVEN
CommonSetup: yyw/yyw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_200
WithP2Hand: SOR_171
WithP2Hand: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirHand-0

## EXPECT
P2HANDCOUNT:1
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_171
P2DISCARDUNIT:0:FROM:HAND
P1DISCARDCOUNT:1
