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

---

# WhenDefeated_DiscardHandDraw3
#// SOR_147 Black One — the SAME clause on the When Defeated half (the card reads "When Played/When
#// Defeated"). Black One (4/4) attacks JTL_069 Munificent Frigate (4/7) and dies to the 4 counter-
#// damage; YES discards P1's 2-card hand and draws 3.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_147:1:0
WithP2SpaceArena: JTL_069:1:0
WithP1Hand: SOR_128
WithP1Hand: SOR_128
WithP1Deck: SOR_128
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENACOUNT:0
P1HANDCOUNT:3
P1DISCARDCOUNT:3

---

# NoGloryOnlyResults_NewControllerResolvesIt
#// SOR_147 Black One — a take-control-then-defeat (JTL_043) defeats the unit under the TAKER's
#// control, so the TAKER resolves the When Defeated: P1 discards P1's hand and draws 3 from P1's
#// deck. Black One still lands in its OWNER P2's discard.

## GIVEN
CommonSetup: bbk/bbk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_043
WithP1Hand: SOR_128
WithP1Hand: SOR_128
WithP2SpaceArena: SOR_147:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Deck: SOR_128
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:YES

## EXPECT
P2SPACEARENACOUNT:0
P1HANDCOUNT:3
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_147
