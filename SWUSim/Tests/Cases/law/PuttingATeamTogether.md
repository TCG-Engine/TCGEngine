# SearchDrawUnit
#// LAW_166 Putting a Team Together (Command event, cost 1) — "Search the top 8 cards of your deck for a
#// Vigilance, Aggression, or Cunning unit, reveal it, and draw it." SOR_046 (Vigilance unit) is the only
#// match among the top cards (SOR_237 is Heroism-only) -> drawn.

## GIVEN
CommonSetup: ggw/bgw/{myResources:1}
WithP1Deck: SOR_046
WithP1Deck: SOR_237
WithP1Deck: SOR_237
WithP1Hand: LAW_166

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_046

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:2

---

# ForeignOwnedEvent_SearchesItsControllersDeck
#// LAW_166 — control axis. An EVENT can have owner ≠ controller too: LAW_215 Vermillion reveals the top
#// card of a deck and lets a chosen player play it FOR FREE, so P1 plays a P2-OWNED Putting a Team
#// Together. "Search the top 8 cards of YOUR deck" must then run over P1's deck.
#// Both decks are seeded with a DIFFERENT legal match so the searched deck is readable:
#//   · P1's deck: SOR_046 Consular Security Force (Vigilance unit) + SOR_237 Alliance X-Wing (Heroism
#//     only — not a legal match)
#//   · P2's deck: LAW_166 itself (revealed and played away) + SOR_164 Wampa (an Aggression unit, i.e.
#//     a legal match had the OWNER's deck been searched)
#// Answering SOR_046 would THROW if the owner's deck had been searched, and the counts pin it from the
#// other side: P1 draws SOR_046 and keeps 1 card, P2's deck still holds its untouched Wampa.
#// The spent event still returns to its OWNER: P2's discard holds it (P2DISCARDCOUNT:1) while P1's
#// discard stays empty — the effect follows the controller, the card follows the owner.
#//
#// COVERAGE: offer=the search pool is asserted behaviorally — SearchDrawUnit takes the only legal match
#//           while the Heroism-only X-Wing is left behind, and this section proves the pool comes from
#//           the CONTROLLER's deck (an out-of-deck answer throws) · decline=N/A (the search is
#//           mandatory when a match exists) · control=this section (foreign-owned event searches its
#//           controller's deck; the card itself returns to its owner) · reqboundary=the search answer
#//           is served on a later request in both sections · boundary=SearchDrawUnit pins the aspect
#//           filter (a Heroism-only unit is not a match).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LAW_215:1:0
WithP1Deck: SOR_046
WithP1Deck: SOR_237
WithP2Deck: LAW_166
WithP2Deck: SOR_164

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Theirs
- P1>AnswerDecision:You
- P1>AnswerDecision:YES
- P1>AnswerDecision:SOR_046

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_046
P1DECKCOUNT:1
P2DECKCOUNT:1
P2DISCARDCOUNT:1
P1DISCARDCOUNT:0

---

# SearchOffer_OnlyMatchingAspectUnitsInTheTop8
#// COVERAGE (this file; kept here because the earlier sections are frozen): offer=this section
#//           (SEARCHPLAYABLE membership: the three matching-aspect UNITS are offered; a Command unit,
#//           a Heroism-only unit, a Vigilance EVENT, a Cunning UPGRADE and a matching unit sitting in
#//           the 9th slot are all excluded) · decline=TakeNothing_NothingIsDrawn — ⚠ this CORRECTS the
#//           "decline=N/A (the search is mandatory when a match exists)" claim in the ledger above:
#//           the search offers a take-nothing exit even when a legal match is revealed ·
#//           control=ForeignOwnedEvent_SearchesItsControllersDeck · boundary=this section (8th vs 9th
#//           card of the deck) + RestOfTheLookedAtCardsGoToTheBottom (the 7 unchosen cards' disposition)
#//           · reqboundary=the search answer is served on a later request in every section.
#//
#// LAW_166 Putting a Team Together — "Search the top 8 cards of your deck for a Vigilance, Aggression, or
#// Cunning unit, reveal it, and draw it." The offer is TWO independent filters ANDed (aspect AND card
#// type) over a WINDOW of exactly 8 cards, so the pool is asserted while the search decision is still
#// pending. Deck top-down:
#//   1 SOR_046 Consular Security Force (Vigilance,Heroism unit) — offered
#//   2 SOR_164 Wampa (Aggression unit)                          — offered
#//   3 LAW_231 Weequay Pirate (Cunning unit)                    — offered
#//   4 SOR_095 Battlefield Marine (Command,Heroism unit)        — wrong aspect
#//   5 SOR_237 Alliance X-Wing (Heroism-only unit)              — wrong aspect
#//   6 SHD_078 Fell the Dragon (Vigilance EVENT)                — right aspect, wrong card type
#//   7 SOR_214 Smuggling Compartment (Cunning UPGRADE)          — right aspect, wrong card type
#//   8 SOR_225 TIE/ln Fighter (Villainy unit)                   — wrong aspect
#//   9 SOR_128 Death Star Stormtrooper (Aggression unit)        — a legal match, but OUTSIDE the top 8
#// Card 9 is the sharp one: it would be offered if the window were mis-sized by even one card.

## GIVEN
CommonSetup: ggw/bgw/{myResources:1}
WithP1Deck: SOR_046
WithP1Deck: SOR_164
WithP1Deck: LAW_231
WithP1Deck: SOR_095
WithP1Deck: SOR_237
WithP1Deck: SHD_078
WithP1Deck: SOR_214
WithP1Deck: SOR_225
WithP1Deck: SOR_128
WithP1Hand: LAW_166

## WHEN
- P1>PlayHand:0

## EXPECT
P1SEARCHPLAYABLEHAS:SOR_046
P1SEARCHPLAYABLEHAS:SOR_164
P1SEARCHPLAYABLEHAS:LAW_231
P1SEARCHPLAYABLENOT:SOR_095
P1SEARCHPLAYABLENOT:SOR_237
P1SEARCHPLAYABLENOT:SHD_078
P1SEARCHPLAYABLENOT:SOR_214
P1SEARCHPLAYABLENOT:SOR_225
P1SEARCHPLAYABLENOT:SOR_128

---

# TakeNothing_NothingIsDrawn
#// LAW_166 Putting a Team Together — the search is DECLINABLE even when a legal match is revealed:
#// "search … for a unit" is permission, not compulsion, so the player may take nothing. SOR_046
#// (Vigilance unit) is a legal match and is deliberately declined: nothing reaches hand, and the three
#// looked-at cards all return to the deck, so the deck count is unchanged.

## GIVEN
CommonSetup: ggw/bgw/{myResources:1}
WithP1Deck: SOR_046
WithP1Deck: SOR_095
WithP1Deck: SOR_237
WithP1Hand: LAW_166

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:3

---

# RestOfTheLookedAtCardsGoToTheBottom
#// LAW_166 Putting a Team Together — "(Put the rest of the cards on the bottom of your deck in a random
#// order.)" The seven unchosen cards of the top 8 must end up UNDER the untouched remainder of the deck,
#// not back on top: after the draw, the deck's new top card is the card that was in the 9th slot.
#// The top 8 hold exactly one legal match, SOR_046 (Vigilance unit) — the other seven are a Command unit,
#// a Heroism-only unit, two Villainy units, a Command/Villainy unit, a Vigilance event and a trait-only
#// event — so the draw is unambiguous. SOR_128 (Aggression unit) sits 9th: never looked at, and after the
#// seven are buried it is the only card left above them.
#// Their relative order underneath is random by the card's own text, so only the new TOP is asserted.

## GIVEN
CommonSetup: ggw/bgw/{myResources:1}
WithP1Deck: SOR_046
WithP1Deck: SOR_095
WithP1Deck: SOR_237
WithP1Deck: SOR_225
WithP1Deck: SOR_232
WithP1Deck: SEC_080
WithP1Deck: SHD_078
WithP1Deck: SOR_251
WithP1Deck: SOR_128
WithP1Hand: LAW_166

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_046

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_046
P1DECKCOUNT:8
P1DECKTOPCARD:SOR_128
