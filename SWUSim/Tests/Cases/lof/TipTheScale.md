# DiscardNonUnit
#// LOF_226 Tip the Scale — Look at an opponent's hand and discard a non-unit card from it. P2's hand has a
#// unit (SOR_146) and an event (SOR_073); the event is discarded.

## GIVEN
CommonSetup: yyk/ggw/{myResources:2;handCardIds:LOF_226}
P1OnlyActions: true
WithP2Hand: SOR_146
WithP2Hand: SOR_073

## WHEN
- P1>PlayHand:0

## EXPECT
P2HANDCOUNT:1
P2DISCARDCOUNT:1

---

# EmptyOpponentHand_PlaysWithNoEffect
#// LOF_226 Tip the Scale — playable even when the opponent's hand is EMPTY: there is nothing to look at or
#// discard, so the event resolves with no effect. It still costs 2 (P1 goes to 0 available), the event lands
#// in P1's discard, and P2's discard stays empty. Ref: "Play anyway → exhaustedResourceCount 2".

## GIVEN
CommonSetup: yyk/ggw/{myResources:2;handCardIds:LOF_226}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P2DISCARDCOUNT:0
P1RESAVAILABLE:0
