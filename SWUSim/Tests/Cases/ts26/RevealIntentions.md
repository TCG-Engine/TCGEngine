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
