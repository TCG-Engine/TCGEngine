# EmptyOpponentHand
#// TS26_80 Reveal Intentions — edge: the opponent's hand is empty, so the caster (P1) discards nothing
#// from it (no decision), but P2 still discards a card from P1's hand, and BOTH players still draw a card.
## GIVEN
CommonSetup: yyk/rrk/{myResources:3}
WithActivePlayer: 1
WithP1Hand: TS26_80
WithP1Hand: SOR_095
WithP1Hand: SOR_046
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:theirHand-0
## EXPECT
P2DISCARDCOUNT:0
P1DISCARDCOUNT:2
P1DECKCOUNT:1
P2DECKCOUNT:1
P1HANDCOUNT:2
P2HANDCOUNT:1

---

# MutualDiscardThenDraw
#// TS26_80 Reveal Intentions (Event, cost 1, Cunning, Gambit) — "Each player reveals their hand. In
#// player order, each player discards a card from the hand of the player to their right. Then, each player
#// draws a card." In 2P: P1 discards a card from P2's hand (its choice), P2 discards a card from P1's hand,
#// then both draw. P1 plays the event, so its own discard pile also holds the spent event (→ count 2).
## GIVEN
CommonSetup: yyk/rrk/{myResources:3}
WithActivePlayer: 1
WithP1Hand: TS26_80
WithP1Hand: SOR_095
WithP1Hand: SOR_046
WithP2Hand: SOR_095
WithP2Hand: SOR_046
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirHand-0
- P2>AnswerDecision:theirHand-0
## EXPECT
P2DISCARDCOUNT:1
P1DISCARDCOUNT:2
P1DECKCOUNT:1
P2DECKCOUNT:1
P1HANDCOUNT:2
P2HANDCOUNT:2

---

# TheFinalDrawStillHappensWithEMPTYDecks
#// TS26_80 Reveal Intentions — "Then, each player draws a card" is unconditional. With both decks empty
#// the discards still resolve and both players still attempt the draw, each eating the empty-deck penalty
#// of 3 base damage. Both hands end empty and both bases sit on 3.

## GIVEN
CommonSetup: yyk/rrk/{myResources:3}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: TS26_80
WithP1Hand: SOR_095
WithP2Hand: SOR_046

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirHand-0
- P2>AnswerDecision:theirHand-0

## EXPECT
P1HANDCOUNT:0
P2HANDCOUNT:0
P1BASEDMG:3
P2BASEDMG:3

---

# TwinSuns_EachSeatDiscardsFromTheSeatToITSRight
#// ⚠ THE ADJACENCY CELL — added 2026-08-21 under the USER RULING that **RIGHT is the increment along
#// SeatOrder** (so seat 1's right neighbour is seat 2, and seat 4's wraps to seat 1). Before this the
#// card resolved OtherPlayer(): unambiguous at two seats, undefined at four, and it never even asked
#// seats 3 and 4.
#// Every seat holds a DIFFERENT card, so the discard pile of each seat identifies exactly WHO took from
#// it — the only assertion that can catch a wrong direction. If "right" were the decrement instead, all
#// four discards would land on the opposite neighbours and every line below would fail.
#//   P1 takes from P2 · P2 takes from P3 · P3 takes from P4 · P4 wraps and takes from P1
#// Then every seat draws one.
#// ⚠ Each seat holds 2 cards so the pick is a real choice, and the decks are stocked so the closing
#//   draw does not deck anyone out.

## GIVEN
CommonSetup: yyk/rrk/{myResources:4}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: [TS26_80 SOR_095 SOR_095]
WithP2Hand: [SOR_046 SOR_046]
WithP3Hand: [SEC_080 SEC_080]
WithP4Hand: [SOR_128 SOR_128]
WithP1Deck: [SOR_237 SOR_237 SOR_237]
WithP2Deck: [SOR_237 SOR_237 SOR_237]
WithP3Deck: [SOR_237 SOR_237 SOR_237]
WithP4Deck: [SOR_237 SOR_237 SOR_237]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p2Hand-0
- P2>AnswerDecision:p3Hand-0
- P3>AnswerDecision:p4Hand-0
- P4>AnswerDecision:p1Hand-0

## EXPECT
SEATCOUNT:4
P2DISCARDUNIT:0:CARDID:SOR_046
P3DISCARDUNIT:0:CARDID:SEC_080
P4DISCARDUNIT:0:CARDID:SOR_128
P1DISCARDCOUNT:2
