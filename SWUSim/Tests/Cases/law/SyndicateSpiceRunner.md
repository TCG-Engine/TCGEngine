# SearchUnderworld
#// LAW_136 Syndicate Spice Runner (Command,Villainy, cost 2) — When Played: search the top 3 cards for an
#// Underworld unit, reveal it, and draw it. LAW_124 (Underworld) is the only match; SOR_237 is left.

## GIVEN
CommonSetup: grk/bgw/{myResources:2}
WithP1Deck: LAW_124
WithP1Deck: SOR_237
WithP1Hand: LAW_136

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:LAW_124

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:1

---

# SearchWithOnlyTwoCards
#// LAW_136 Syndicate Spice Runner — the search still works when the deck holds fewer than 3 cards. Deck is
#// just LAW_124 (Underworld) + SOR_237 (Alliance X-Wing, not Underworld); the Underworld unit is revealed
#// and drawn, the other card is bottomed.

## GIVEN
CommonSetup: grk/bgw/{myResources:2}
WithP1Deck: LAW_124
WithP1Deck: SOR_237
WithP1Hand: LAW_136

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:LAW_124

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:1

---

# EmptyDeck_NoTrigger
#// LAW_136 Syndicate Spice Runner — with an empty deck the When Played search has nothing to look at and
#// auto-passes (no decision). The Spice Runner still enters play.

## GIVEN
CommonSetup: grk/bgw/{myResources:2}
WithP1Hand: LAW_136

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:0
P1GROUNDARENACOUNT:1

---

# ForeignOwnedRunner_SearchesItsControllersDeck
#// LAW_136 — control axis. "Search the top 3 cards of YOUR deck" resolves from the ability's
#// CONTROLLER, never from the card's owner. LAW_136 is owned by P2 (it is the top card of P2's deck)
#// but P1 plays it via LAW_215 Vermillion's free play, so it enters play under P1 and its When Played
#// must look at P1's deck.
#// The two decks are deliberately DISTINGUISHABLE — each holds a different Underworld unit:
#//   · P1's deck: LAW_124 Industrious Team (Underworld) + SOR_237 Alliance X-Wing (not Underworld)
#//   · P2's deck: LAW_136 itself (revealed and played away) + SOR_181 Jabba the Hutt (Underworld)
#// so whichever deck was searched is readable from the drawn CARDID and from which deck shrank.
#// Answering the search with LAW_124 would THROW if the search had run over the owner's deck (LAW_124
#// is not in it), and the counts pin it from the other side: P1 draws LAW_124 and ends at 1 card in
#// deck, while P2's deck still holds its untouched Jabba (P2DECKCOUNT:1, P2HANDCOUNT:0). Searching
#// the owner's deck would instead have drawn SOR_181 and emptied P2's deck.
#//
#// COVERAGE: offer=the search pool is asserted behaviorally — SearchUnderworld takes the only
#//           Underworld card while SOR_237 stays behind, and this section proves the pool is drawn
#//           from the CONTROLLER's deck (an out-of-deck answer throws) · decline=N/A (the search is
#//           mandatory when a match exists; the take-nothing path is EmptyDeck_NoTrigger) ·
#//           control=this section (foreign-owned unit searches its controller's deck) ·
#//           reqboundary=the search answer is served on a later request in every section ·
#//           boundary=SearchWithOnlyTwoCards (deck shorter than the 3-card window) +
#//           EmptyDeck_NoTrigger (no window at all).

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
WithP2Deck: LAW_136
WithP2Deck: SOR_181

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Theirs
- P1>AnswerDecision:You
- P1>AnswerDecision:YES
- P1>AnswerDecision:LAW_124

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_136
P1HANDCOUNT:1
P1HANDCARD:0:LAW_124
P1DECKCOUNT:1
P2DECKCOUNT:1
P2HANDCOUNT:0
