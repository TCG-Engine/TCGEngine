# OpponentsDeck_DifferenceOfThree_DealtToAnEnemyUnit
#// IC27_119 Sabine Wren (Ground 5/4, cost 4, [Aggression][Heroism]) — "Saboteur. When Played: Discard 2 cards from a
#// deck. You may deal damage to a unit equal to the difference in those cards' costs." Both decks have cards, so P1
#// picks one: P2's. Its top two — Consular Security Force (4) and Death Star Stormtrooper (1) — are discarded; the
#// difference is 3, dealt to P2's Battlefield Marine (3/3), which is defeated.
#// Fixture: CommonSetup rrw = Aggression base + Aggression/Heroism leader, so Sabine costs her printed 4.
#// COVERAGE: offer=Offer_DeckPicker_BothDecks + Offer_DamagePool_AnyUnit_SabineIncluded ·
#//           decline=Decline_NoDamageIsDealt · boundary=DifferenceOfZero_NoOffer (0 vs a positive difference) +
#//           ReversedOrder_StillThree (the ABSOLUTE difference) · control=N/A (no owner-scoped zone; "a deck" is any
#//           player's, and a When Played does not re-fire on a control change) ·
#//           reqboundary=RequestBoundary_BothPicksInFreshRequests
#//           modes=2P,TwinSuns ("a deck" is any player's — TwinSuns_AFarSeatsDeck) · TeamSuns=N/A (no friendly/enemy
#//                 wording; a teammate's deck is just another seat in the same picker)

## GIVEN
CommonSetup: rrw/rrk/{myResources:4;myhandCardIds:IC27_119}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
WithP1Deck: [SEC_080 SEC_080]
WithP2Deck: [SOR_046 SOR_128 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent's_deck
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2DISCARDCOUNT:3
P2DECKCOUNT:1
P2DECKTOPCARD:SEC_080
P2GROUNDARENACOUNT:0
P1DECKCOUNT:2
P1NODECISION

---

# Offer_DeckPicker_BothDecks
#// "a deck": with both decks non-empty, both are offered — the pick is left PENDING.

## GIVEN
CommonSetup: rrw/rrk/{myResources:4;myhandCardIds:IC27_119}
P1OnlyActions: true
WithP1Deck: [SEC_080 SEC_080]
WithP2Deck: [SOR_046 SOR_128]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1OPTIONHAS:Your_deck
P1OPTIONHAS:Opponent's_deck

---

# YourOwnDeck_IsALegalChoice
#// P1 picks its OWN deck: its top two (Consular Security Force 4, Battlefield Marine 2) go to P1's discard; difference
#// 2, dealt to P2's Security Force (3/7, survives on 2). P2's deck is untouched.

## GIVEN
CommonSetup: rrw/rrk/{myResources:4;myhandCardIds:IC27_119}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_046 SOR_095 SEC_080]
WithP2Deck: [SOR_128 SOR_128]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Your_deck
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1DISCARDCOUNT:2
P1DECKCOUNT:1
P2DECKCOUNT:2
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# ReversedOrder_StillThree
#// The difference is ABSOLUTE: top card 1, second card 4 → 3 (not -3, not 0). Only P2's deck has cards, so it is
#// picked without a prompt.

## GIVEN
CommonSetup: rrw/rrk/{myResources:4;myhandCardIds:IC27_119}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2Deck: [SOR_128 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2DISCARDCOUNT:2
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# DifferenceOfZero_NoOffer
#// Two cards of the same cost: a difference of 0 deals nothing, so no unit is offered.

## GIVEN
CommonSetup: rrw/rrk/{myResources:4;myhandCardIds:IC27_119}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2Deck: [SOR_095 SEC_080]

## WHEN
- P1>PlayHand:0

## EXPECT
P2DISCARDCOUNT:2
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# OnlyOneCardInTheDeck_NoDifference_NoOffer
#// The only non-empty deck holds one card: it is discarded, but there is no second card and so no difference — nothing
#// is offered. (Preview judgement call.)

## GIVEN
CommonSetup: rrw/rrk/{myResources:4;myhandCardIds:IC27_119}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2Deck: SOR_128

## WHEN
- P1>PlayHand:0

## EXPECT
P2DISCARDCOUNT:1
P2DECKCOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# BothDecksEmpty_NothingHappens
#// No deck has a card: nothing is discarded and nothing is offered; Sabine is simply played.

## GIVEN
CommonSetup: rrw/rrk/{myResources:4;myhandCardIds:IC27_119}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1DISCARDCOUNT:0
P2DISCARDCOUNT:0
P1NODECISION

---

# Decline_NoDamageIsDealt
#// "You may": the cards are still discarded, but declining deals nothing.

## GIVEN
CommonSetup: rrw/rrk/{myResources:4;myhandCardIds:IC27_119}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2Deck: [SOR_128 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2DISCARDCOUNT:2
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# Offer_DamagePool_AnyUnit_SabineIncluded
#// "a unit" is unqualified: P1's Marine, Sabine herself, P2's Security Force and P2's X-Wing (space) are all offered.
#// The pick is left PENDING.

## GIVEN
CommonSetup: rrw/rrk/{myResources:4;myhandCardIds:IC27_119}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_237:1:0
WithP2Deck: [SOR_128 SOR_046]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&theirGroundArena-0&theirSpaceArena-0
P1GROUNDARENAUNIT:1:CARDID:IC27_119

---

# EventsCostsCount_DamageCanBeLethal
#// Any card's printed cost counts — an event too: SOR_251 Confiscate (1) and Consular Security Force (4) → 3, lethal to
#// P2's 3/3 Battlefield Marine.

## GIVEN
CommonSetup: rrw/rrk/{myResources:4;myhandCardIds:IC27_119}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
WithP2Deck: [SOR_251 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:3

---

# RequestBoundary_BothPicksInFreshRequests
#// OpponentsDeck_DifferenceOfThree_… with a fresh request before each answer (deck pick, then unit pick).

## GIVEN
CommonSetup: rrw/rrk/{myResources:4;myhandCardIds:IC27_119}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
WithP1Deck: [SEC_080 SEC_080]
WithP2Deck: [SOR_046 SOR_128 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:Opponent's_deck
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2DISCARDCOUNT:3
P2GROUNDARENACOUNT:0
P1NODECISION

---

# TwinSuns_AFarSeatsDeck
#// Three seats, no teams: "a deck" offers every seat's non-empty deck. P1 picks seat 3's; its top two (4 and 1) go to
#// seat 3's discard, and the 3 damage defeats seat 3's Marine. Seat 2's deck is untouched.

## GIVEN
CommonSetup: rrw/rrk/{myResources:4;myhandCardIds:IC27_119}
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_024
WithP3GroundArena: SOR_095:1:0
WithP1Deck: [SEC_080 SEC_080]
WithP2Deck: [SEC_080 SEC_080]
WithP3Deck: [SOR_046 SOR_128 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P3_deck
- P1>AnswerDecision:p3GroundArena-0

## EXPECT
SEATCOUNT:3
P3DECKCOUNT:1
P3GROUNDARENACOUNT:0
P2DECKCOUNT:2
