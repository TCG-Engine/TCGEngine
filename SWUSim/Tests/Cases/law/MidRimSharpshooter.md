# PayOpponentDiscards
#// LAW_193 Mid Rim Sharpshooter (Aggression, cost 3, Saboteur) — When Played: you may pay 1 resource. If
#// you do, an opponent discards a card from their hand. Pay 1 -> P2 (2 cards) discards one.

## GIVEN
CommonSetup: rrw/bgw/{myResources:4}
WithActivePlayer: 1
WithP2Hand: SOR_095
WithP2Hand: SOR_237
WithP1Hand: LAW_193

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P2>AnswerDecision:myHand-0

## EXPECT
P2HANDCOUNT:1
