# DrawTwoDiscardOne
#// IBH_074 I Want Proof, Not Leads (Event, cost 2, Aggression/Villainy) — Draw 2 cards, then discard a
#//   card from your hand. P1 draws 2 (deck empties), then discards 1 → ends with 1 card in hand.

## GIVEN
CommonSetup: rrk/bbw/{myResources:2}
P1OnlyActions: true
WithP1Hand: IBH_074
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-1

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:0
P1DISCARDCOUNT:2
P1NODECISION

---

# Reprint102
#// IBH_102 I Want Proof, Not Leads (reprint of IBH_074) — draw 2, discard 1. Confirms the duplicate.

## GIVEN
CommonSetup: rrk/bbw/{myResources:2}
P1OnlyActions: true
WithP1Hand: IBH_102
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-1

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:0
P1DISCARDCOUNT:2
P1NODECISION
