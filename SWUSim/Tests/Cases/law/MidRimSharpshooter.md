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

---

# PayOpponentEmptyHandStillPays
#// LAW_193 — the "you may pay 1 resource" cost is paid regardless of the opponent's hand; with an empty
#// enemy hand there is nothing to discard, but the resource is still spent (4 -> 3 for play, -1 -> 0 left).

## GIVEN
CommonSetup: rrw/bgw/{myResources:4}
WithActivePlayer: 1
WithP1Hand: LAW_193

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1RESAVAILABLE:0

---

# NotEnoughResourcesNoDiscard
#// LAW_193 — playing it costs 3; with only 3 resources there is nothing left to pay the optional 1, so the
#// opponent discards nothing and keeps both cards.

## GIVEN
CommonSetup: rrw/bgw/{myResources:3}
WithActivePlayer: 1
WithP2Hand: SOR_095
WithP2Hand: SOR_237
WithP1Hand: LAW_193

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:0
P2HANDCOUNT:2
