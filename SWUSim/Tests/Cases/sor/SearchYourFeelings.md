# ChooseNone
#// SOR_042 Search Your Feelings — the searcher may choose to draw nothing; the deck is reshuffled and
#// stays at 3, no card enters hand.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_042
WithP1Resources: 4
WithP1Deck: SOR_063
WithP1Deck: SOR_095
WithP1Deck: SOR_237

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:3
P1DISCARDCOUNT:1

---

# DrawsChosenCard
#// SOR_042 Search Your Feelings (event, cost 4) — "Search your deck for a card and draw it. (Then,
#// shuffle your deck.)" P1 searches its 3-card deck and draws SOR_063; the deck drops to 2 (the rest
#// shuffled back) and the event goes to discard.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_042
WithP1Resources: 4
WithP1Deck: SOR_063
WithP1Deck: SOR_095
WithP1Deck: SOR_237

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_063

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:2
P1DISCARDCOUNT:1

---

# EmptyDeck_NoOp
#// SOR_042 Search Your Feelings — with an empty deck there is nothing to search: no decision, the event
#// just resolves to the discard.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_042
WithP1Resources: 4

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1NODECISION

---

# SearchOffer_AnyCardSelectable
#// Intended: the search is over the WHOLE deck with NO filter — every card is a legal pick
#// regardless of type or aspect. The deck holds a unit, an upgrade, an event, and an
#// off-aspect unit; the search decision is left PENDING so the playable pool is asserted
#// directly: all four are selectable.
#// COVERAGE: offer=this section · reqboundary=DrawsChosenCard (play and search answer are
#//           separate serialized steps) · control=PlayedFromOpponentsResources_SearchesYourOwnDeck —
#//           there are no unit targets and an event leaves no persistent object to change hands, so
#//           the reachable owner-vs-controller reading is a card played by someone OTHER than its
#//           owner: played out of the opponent's resources, "your deck" still resolves to the
#//           PLAYER's deck and the opponent's deck is never searched ·
#//           boundary pair=OneCardDeck_StillSearchable + EmptyDeck_NoOp (deck 1/0) ·
#//           decline=ChooseNone. The trailing "(Then, shuffle your deck.)" is not directly
#//           assertable — the shuffled order is random by design.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_042
WithP1Resources: 4
WithP1Deck: SOR_063
WithP1Deck: SOR_069
WithP1Deck: SOR_220
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SEARCHPLAYABLEHAS:SOR_063
P1SEARCHPLAYABLEHAS:SOR_069
P1SEARCHPLAYABLEHAS:SOR_220
P1SEARCHPLAYABLEHAS:SOR_128

---

# OneCardDeck_StillSearchable
#// Intended: with a single card left in the deck the search still works — the lone card is
#// offered, drawn to hand, and the deck empties.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_042
WithP1Resources: 4
WithP1Deck: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_063

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_063
P1DECKCOUNT:0
P1DISCARDCOUNT:1

---

# HiddenDraw_NoCardNameInLog
#// Intended: the searched-and-drawn card stays HIDDEN — its name must not be written to the
#// game log. The log's LAST entry after the whole flow is still the generic played-event line
#// (log writes are ordered, so any reveal/draw line naming the card would have landed after
#// it and become the last entry instead).

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_042
WithP1Resources: 4
WithP1Deck: SOR_063
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_063

## EXPECT
P1HANDCOUNT:1
LASTLOGCONTAINS:Search Your Feelings

---

# PlayedFromOpponentsResources_SearchesYourOwnDeck
#// SOR_042 Search Your Feelings — the CONTROL axis. An event has no persistent object to change
#// hands, so the reachable owner-vs-controller reading is a card PLAYED by someone other than its
#// owner: "search YOUR deck" must resolve for the player who played it. P2 has this very event in its
#// resource row; P1 plays LAW_066 Tear This Ship Apart and plays it from there for free. The search
#// then has to run on P1's two-card deck — the answer names a card that exists ONLY in P1's deck, and
#// out-of-pool answers are rejected, so acceptance is the proof that P2's deck was never the one
#// searched. P1 draws it and P1's deck drops to 1; the spent event goes to its OWNER's (P2's) discard
#// and P2 refills the resource from its own deck.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 13
WithP1Hand: LAW_066
WithP2Resources: 1:SOR_042:1
WithP1Deck: [SOR_063 SOR_095]
WithP2Deck: [SOR_220 SOR_220 SOR_220]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirResources-0
- P1>AnswerDecision:SOR_063

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_063
P1DECKCOUNT:1
P2HANDCOUNT:0
P2DECKCOUNT:2
P2RESCOUNT:1
P2DISCARDCOUNT:1
