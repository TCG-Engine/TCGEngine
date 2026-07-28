# MillReturnAggression
#// LAW_203 Daring Delve (Aggression event, cost 1) — "Discard 2 cards from your deck. You may return an
#// Aggression card discarded this way to your hand." Mill SOR_128 (Aggression) + SOR_237 (Heroism);
#// return SOR_128 to hand.

## GIVEN
CommonSetup: rrk/bgw/{myResources:1}
WithP1Deck: SOR_128
WithP1Deck: SOR_237
WithP1Hand: LAW_203

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:0
P1DISCARDCOUNT:2

---

# BothAggressionReturnOne
#// LAW_203 Daring Delve — when BOTH milled cards are Aggression (SOR_164 Wampa + SOR_128), either is a
#// legal return target. Return one; the other stays discarded. Hand=1, deck=0, discard=2 (LAW_203 + the
#// card left behind).

## GIVEN
CommonSetup: rrk/bgw/{myResources:1}
WithP1Deck: SOR_164
WithP1Deck: SOR_128
WithP1Hand: LAW_203

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:0
P1DISCARDCOUNT:2

---

# NoAggressionCard
#// LAW_203 Daring Delve — when NEITHER milled card is Aggression (SOR_095 Command/Heroism + SOR_237
#// Heroism), there is nothing to return. Both stay discarded; hand ends empty, discard=3 (LAW_203 + both).

## GIVEN
CommonSetup: rrk/bgw/{myResources:1}
WithP1Deck: SOR_095
WithP1Deck: SOR_237
WithP1Hand: LAW_203

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:0
P1DISCARDCOUNT:3
P1NODECISION

---

# EmptyDeck
#// LAW_203 Daring Delve — with an empty deck there is nothing to mill and no card to return; only the
#// event itself ends in the discard.

## GIVEN
CommonSetup: rrk/bgw/{myResources:1}
WithP1Hand: LAW_203

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:0
P1DISCARDCOUNT:1
P1NODECISION
