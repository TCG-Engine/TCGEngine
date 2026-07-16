# WhenPlayed_Decline_NoDiscard
#// SOR_147 Black One — the discard/draw is optional ("You may"). Declining leaves the hand
#// intact (the 2 non-Black-One cards remain), nothing is discarded, and no card is drawn.

## GIVEN
CommonSetup: ggw/ggw/{myResources:8}
P1OnlyActions: true
WithP1Hand: SOR_147
WithP1Hand: SOR_128
WithP1Hand: SOR_128
WithP1Deck: SOR_128
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1HANDCOUNT:2
P1DISCARDCOUNT:0
P1DECKCOUNT:3

---

# WhenPlayed_DiscardHandDraw3
#// SOR_147 Black One (4/4, Space) — When Played/When Defeated: You may discard your hand. If
#// you do, draw 3 cards. P1 plays Black One (hand then holds 2 cards); choosing YES discards
#// those 2 (discard pile = 2) and draws 3 (hand = 3). Black One itself is in the space arena.

## GIVEN
CommonSetup: ggw/ggw/{myResources:8}
P1OnlyActions: true
WithP1Hand: SOR_147
WithP1Hand: SOR_128
WithP1Hand: SOR_128
WithP1Deck: SOR_128
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1HANDCOUNT:3
P1DISCARDCOUNT:2
P1SPACEARENACOUNT:1
