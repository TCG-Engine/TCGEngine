# SearchBountyHunter
#// LAW_138 Undercity Hunting Team (Command,Villainy, cost 5) — When Played: search the top 5 cards for a
#// Bounty Hunter unit, reveal it, and draw it. LAW_124 (Bounty Hunter) is the match.

## GIVEN
CommonSetup: grk/bgw/{myResources:5}
WithP1Deck: LAW_124
WithP1Deck: SOR_237
WithP1Hand: LAW_138

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:LAW_124

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:1

---

# EmptyDeck_NoSearch
#// LAW_138 Undercity Hunting Team — with an empty deck the When Played search has nothing to look at and
#// auto-passes (no decision). The team still enters play; base takes no damage.

## GIVEN
CommonSetup: grk/bgw/{myResources:5}
WithP1Hand: LAW_138

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:0
P1GROUNDARENACOUNT:1
P1BASEDMG:0

---

# NoBountyHunterInDeck_TakeNothing
#// LAW_138 Undercity Hunting Team — when the top cards contain no Bounty Hunter unit, every card is invalid
#// and the player must take nothing. Deck is a single SOR_164 Wampa (not a Bounty Hunter); declining leaves
#// the hand empty and the Wampa bottomed back into the (still 1-card) deck.

## GIVEN
CommonSetup: grk/bgw/{myResources:5}
WithP1Deck: SOR_164
WithP1Hand: LAW_138

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:1

---

# ForeignOwnedTeam_SearchesItsControllersDeck
#// LAW_138 — control axis. "Search the top 5 cards of YOUR deck" resolves from the ability's
#// CONTROLLER, not the card's owner. LAW_138 is owned by P2 (top card of P2's deck) but P1 plays it
#// for free via LAW_215 Vermillion, so it enters play under P1 and its When Played must search P1's
#// deck.
#// Both decks hold a DIFFERENT Bounty Hunter, so the searched deck is readable from the end state:
#//   · P1's deck: LAW_124 Industrious Team (Bounty Hunter) + SOR_237 Alliance X-Wing (not one)
#//   · P2's deck: LAW_138 itself (revealed and played away) + SOR_179 Boba Fett (Bounty Hunter)
#// Answering LAW_124 would THROW if the owner's deck had been searched (LAW_124 is not in it), and
#// the counts confirm from the other side: P1 draws LAW_124 and keeps 1 card in deck, while P2's deck
#// still holds an untouched Boba Fett and P2's hand is empty. Owner-scoped resolution would instead
#// have drawn SOR_179 and left P2's deck empty.
#//
#// COVERAGE: offer=the search pool is asserted behaviorally — SearchBountyHunter takes the only
#//           Bounty Hunter and NoBountyHunterInDeck_TakeNothing shows a non-matching card is not
#//           takeable; this section proves the pool comes from the CONTROLLER's deck ·
#//           decline=NoBountyHunterInDeck_TakeNothing (the "-" take-nothing answer) · control=this
#//           section (foreign-owned unit searches its controller's deck) · reqboundary=the search
#//           answer is served on a later request in every section · boundary=EmptyDeck_NoSearch (no
#//           window) vs SearchBountyHunter (a match inside the window).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LAW_215:1:0
WithP1Deck: LAW_124
WithP1Deck: SOR_237
WithP2Deck: LAW_138
WithP2Deck: SOR_179

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Theirs
- P1>AnswerDecision:You
- P1>AnswerDecision:YES
- P1>AnswerDecision:LAW_124

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_138
P1HANDCOUNT:1
P1HANDCARD:0:LAW_124
P1DECKCOUNT:1
P2DECKCOUNT:1
P2HANDCOUNT:0

---

# BountyHunterSittingSIXTH_IsOutsideTheWindow
#// LAW_138 Undercity Hunting Team — "Search the TOP 5 cards" is a depth limit, and this is the section
#// that measures it. The deck's first five cards are all non-Bounty-Hunters and the only LAW_124 in the
#// deck sits SIXTH: the search must come back empty, nothing is drawn, and all 6 cards stay in the deck.
#// NoBountyHunterInDeck_TakeNothing only shows an empty result when there is no Bounty Hunter anywhere, so
#// it cannot tell a five-card window from a search of the whole deck — this board can.

## GIVEN
CommonSetup: grk/bgw/{myResources:5}
P1OnlyActions: true
WithP1Deck: [SOR_164 SOR_095 SOR_046 SOR_063 SOR_237 LAW_124]
WithP1Hand: LAW_138

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:6

---

# DeckShorterThanFive_SearchesWhatIsThere
#// LAW_138 Undercity Hunting Team — "the top 5 cards" is a maximum, not a requirement. With only 2 cards
#// in the deck the search still runs over both, finds the Bounty Hunter and draws it, leaving 1 card
#// behind. Boundary partner of EmptyDeck_NoSearch (0 cards, no search at all) and SearchBountyHunter.

## GIVEN
CommonSetup: grk/bgw/{myResources:5}
P1OnlyActions: true
WithP1Deck: [SOR_164 LAW_124]
WithP1Hand: LAW_138

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:LAW_124

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:LAW_124
P1DECKCOUNT:1

---

# TheUnchosenCardsLeaveTheTopOfTheDeck
#// LAW_138 Undercity Hunting Team — the reminder text sends the other looked-at cards to the BOTTOM, so
#// after the search the top of the deck must be the first card that was never looked at. Deck from the
#// top: LAW_124 plus four non-Bounty-Hunters (the window), then SOR_237 sixth. Drawing LAW_124 leaves 6
#// cards, and the one now on top is SOR_237 — the four searched-past cards have gone underneath it.

## GIVEN
CommonSetup: grk/bgw/{myResources:5}
P1OnlyActions: true
WithP1Deck: [LAW_124 SOR_164 SOR_095 SOR_046 SOR_063 SOR_237]
WithP1Hand: LAW_138

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:LAW_124

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:LAW_124
P1DECKCOUNT:5
P1DECKTOPCARD:SOR_237
