# SOR_190 Lothal Insurgent (Unit 3/2, cost 2, Cunning/Heroism) — "When Played: If you played another
# card this phase, each opponent draws a card then discards a random card from their hand." P1 first
# plays a throwaway (SOR_210), then plays Lothal → the "another card this phase" condition is met.
# P2's hand is empty and their deck top is SOR_171, so P2 draws SOR_171 then discards it (the only
# card → the random discard is deterministic): P2 hand stays 0, P2 discard +1 (From HAND), deck -1.

## GIVEN
CommonSetup: yyw/yyw/{myResources:5}
P1OnlyActions: true
WithP1Hand: SOR_210
WithP1Hand: SOR_190
WithP2Deck: SOR_171

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P2HANDCOUNT:0
P2DECKCOUNT:0
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_171
P2DISCARDUNIT:0:FROM:HAND
