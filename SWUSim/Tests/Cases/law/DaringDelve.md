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
- P1>AnswerDecision:myDiscard-1

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
- P1>AnswerDecision:myDiscard-1

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

---

# PlayedFromTheOpponentsDiscard_ReturnPoolIsTheCastersDiscard
#// LAW_203 Daring Delve — "your deck" / "your hand" belong to whoever PLAYS the event, not to whoever owns
#// the card. An event is normally cast from its owner's hand, so the two seats coincide and the axis is
#// invisible; SEC_205 Obi-Wan is the vehicle that separates them. Obi-Wan's combat damage to P2's base
#// mills the top of P2's deck — LAW_203 itself — into P2's discard and flags it playable from there, so P1
#// casts a P2-OWNED Daring Delve.
#//
#// The decks are seeded differently on purpose: P1 holds exactly SOR_128 (Aggression) + SOR_237, P2 holds
#// two spare SOR_095 after the mill. "Discard 2 cards from your deck" must empty P1's library and leave
#// P2's at 2. The decision is left PENDING so the return offer can be read: the only candidate is
#// myDiscard-0 — P1's OWN discard, in P1's frame — i.e. the "discarded this way" pool follows the caster.
#// Note the event card itself is NOT in P1's discard (P1's discard holds exactly the 2 milled cards): it
#// belongs to P2 and returns to P2's pile.

## GIVEN
CommonSetup: yyk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SEC_205:1:0
WithP1Resources: 1
WithP1Deck: [SOR_128 SOR_237]
WithP2Deck: [LAW_203 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayFromOpponentDiscard:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myDiscard-0
P1DECKCOUNT:0
P2DECKCOUNT:2
P1DISCARDCOUNT:2

---

# PlayedFromTheOpponentsDiscard_ReturnsToTheCastersHand
#// LAW_203 Daring Delve — the same P2-owned cast, carried to the end state. P1 accepts the return, and the
#// Aggression card milled out of P1's library comes back to P1's HAND: P1 holds SOR_128 and P2's hand is
#// still empty. P1's deck is emptied and P2's is untouched at 2, so every owner-scoped word on the card —
#// "your deck", "your hand" — is pinned to the caster rather than to the card's owner. Reading the owner
#// would have milled P2 and handed P2 the card back.
#//
#// COVERAGE: control=PlayedFromTheOpponentsDiscard_ReturnPoolIsTheCastersDiscard + this section (a
#//           P2-OWNED Daring Delve cast by P1 via SEC_205's play-from-their-discard permission: "your
#//           deck" and "your hand" both resolve to the CASTER; both decks and both hands asserted) ·
#//           offer=PlayedFromTheOpponentsDiscard_ReturnPoolIsTheCastersDiscard (pending SELECTABLEEXACT on
#//           the return pool) + NoAggressionCard / EmptyDeck (P1NODECISION when the pool is empty) ·
#//           decline=not encoded (no section answers "-" on the "you may return") · boundary
#//           pair=MillReturnAggression (an Aggression card was milled -> a return is offered) vs
#//           NoAggressionCard (none -> P1NODECISION) · reqboundary=not encoded

## GIVEN
CommonSetup: yyk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SEC_205:1:0
WithP1Resources: 1
WithP1Deck: [SOR_128 SOR_237]
WithP2Deck: [LAW_203 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayFromOpponentDiscard:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_128
P1DECKCOUNT:0
P2DECKCOUNT:2
P2HANDCOUNT:0
