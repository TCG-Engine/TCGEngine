# WhenPlayed_Decline_NoDiscard
#// SOR_147 Black One — the discard/draw is optional ("You may"). Declining leaves the hand
#// intact (the 2 non-Black-One cards remain), nothing is discarded, and no card is drawn.
#// COVERAGE: offer=N/A — the clause targets nothing: it is a YES/NO on "discard your hand", whose
#//           subject is the whole zone, so no candidate pool is ever built and SELECTABLEEXACT has
#//           nothing to read. What stands in for a pool assertion is WHOSE hand and deck the clause
#//           reads — NoGloryOnlyResults_NewControllerResolvesIt pins that to the RESOLVER's zones,
#//           not the owner's · decline=WhenPlayed_Decline_NoDiscard (When Played half) +
#//           WhenDefeated_Decline_HandIntact (When Defeated half) · control=
#//           NoGloryOnlyResults_NewControllerResolvesIt (both readings on one board: the taker
#//           RESOLVES the When Defeated out of the taker's own hand and deck, while the card itself
#//           still lands in its OWNER's discard) · boundary pair=WhenPlayed_ShortDeck_
#//           DrawsOnlyWhatRemains (2 in the deck, 2 drawn) vs WhenPlayed_DiscardHandDraw3 (3 and 3)
#//           on the draw, and WhenPlayed_EmptyHand_StillDraws3 (0 cards discarded, per the official
#//           07/14/2025 ruling) vs WhenPlayed_DiscardHandDraw3 (2 discarded) on the discard ·
#//           reqboundary=WhenDefeated_DiscardHandDraw3 (the attack declaration closes one request
#//           and the YES arrives in the next, after the defeat has already moved Black One out of
#//           the arena — the trigger has to be resolved from serialized state, not from a live
#//           arena object)

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

---

# WhenPlayed_EmptyHand_StillDraws3
#// SOR_147 Black One — Intended, per the official card ruling of 07/14/2025: "If you have no cards
#// in your hand, you may still choose to discard your hand in order to draw 3 cards." Black One is
#// P1's ONLY hand card, so by the time the When Played resolves the hand is empty. The offer is
#// still raised, YES discards nothing (the discard pile stays at 0) and the "if you do" is
#// nonetheless satisfied — 3 cards come off the deck.

## GIVEN
CommonSetup: ggw/ggw/{myResources:8}
P1OnlyActions: true
WithP1Hand: SOR_147
WithP1Deck: [SOR_128 SOR_128 SOR_128]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1HANDCOUNT:3
P1DISCARDCOUNT:0
P1DECKCOUNT:0
P1SPACEARENACOUNT:1

---

# WhenPlayed_ShortDeck_DrawsOnlyWhatRemains
#// SOR_147 Black One — Intended boundary on the draw: "draw 3 cards" with only 2 cards left draws
#// the 2 that exist and stops; it is not an all-or-nothing clause and it must not fault on the
#// empty deck. N-1 half of the pair whose N half is WhenPlayed_DiscardHandDraw3 (3 in the deck, 3
#// drawn). The 2-card hand is still discarded in full either way.

## GIVEN
CommonSetup: ggw/ggw/{myResources:8}
P1OnlyActions: true
WithP1Hand: SOR_147
WithP1Hand: SOR_128
WithP1Hand: SOR_128
WithP1Deck: [SOR_128 SOR_128]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1HANDCOUNT:2
P1DISCARDCOUNT:2
P1DECKCOUNT:0
P1SPACEARENACOUNT:1

---

# WhenDefeated_Decline_HandIntact
#// SOR_147 Black One — the decline branch on the WHEN DEFEATED half (the existing decline section
#// covers only the When Played half). Black One trades into JTL_069 Munificent Frigate and dies;
#// answering NO leaves P1's 2-card hand untouched, draws nothing, and the only thing in the
#// discard is Black One itself.

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
- P1>AnswerDecision:NO

## EXPECT
P1SPACEARENACOUNT:0
P1HANDCOUNT:2
P1DECKCOUNT:3
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_147
