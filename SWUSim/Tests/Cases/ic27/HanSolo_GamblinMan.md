# SearchTopTen_DrawsTheChosenCard_ThenDiscardsAtRandom
#// IC27_155 Han Solo — Gamblin' Man. Unit (Ground), cost 3, [Cunning][Heroism], 5/3, Rebel, unique.
#// "Saboteur. When Played: Search the top 10 cards of your deck for a card and draw it, then discard a random
#// card from your hand."
#// The search is LAW_005 Jyn Erso's ("for a card" — CR 8.26.1: no attribute, so a card is always taken) with the
#// rest to the bottom; "then" is unconditional (TS26_39 Captain Vaughn), so the random discard happens even when
#// the deck is empty. The discard comes AFTER the draw: with nothing else in hand, the drawn card is the one
#// discarded. Saboteur is the generated keyword.
#//
#// COVERAGE: offer=Offer_OnlyTheTopTen (the 11th card is not offered) + ShortDeck_EveryCardOffered ·
#//           decline=Decline_StillTakesTheTopCard · no-target=EmptyDeck_NoSearch_StillDiscardsAtRandom ·
#//           quantity=DiscardsExactlyOne · order=this section (discard after draw) ·
#//           control=N/A ("your deck", "your hand" — the controller playing him; he is played, never stolen mid-play) ·
#//           reqboundary=RequestBoundary_BeforeThePick · modes=2P only (self only)

## GIVEN
CommonSetup: yyw/rrk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_155
WithP1Deck: [SOR_046 SOR_095 SOR_128]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_095

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:IC27_155
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_095
P1DECKCOUNT:2
P1NODECISION

---

# Offer_OnlyTheTopTen
#// Eleven cards: the top ten are SOR_046, the eleventh is SOR_095. Only the top ten are searched.

## GIVEN
CommonSetup: yyw/rrk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_155
WithP1Deck: [SOR_046 SOR_046 SOR_046 SOR_046 SOR_046 SOR_046 SOR_046 SOR_046 SOR_046 SOR_046 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1SEARCHPLAYABLEHAS:SOR_046
P1SEARCHPLAYABLENOT:SOR_095

---

# ShortDeck_EveryCardOffered
#// Fewer than ten cards: the whole deck is searched, the bottom card included.

## GIVEN
CommonSetup: yyw/rrk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_155
WithP1Deck: [SOR_046 SOR_128 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1SEARCHPLAYABLEHAS:SOR_046
P1SEARCHPLAYABLEHAS:SOR_128
P1SEARCHPLAYABLEHAS:SOR_095

---

# Decline_StillTakesTheTopCard
#// Answering the search with no pick still takes a card — the top one (CR 8.26.1) — and, alone in hand, it is the
#// random discard.

## GIVEN
CommonSetup: yyw/rrk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_155
WithP1Deck: [SOR_046 SOR_095 SOR_128]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1HANDCOUNT:0
P1DISCARDUNIT:0:CARDID:SOR_046
P1DECKCOUNT:2

---

# EmptyDeck_NoSearch_StillDiscardsAtRandom
#// "Then" is unconditional: with an empty deck there is nothing to search, but SEC_080 — the only card left in hand
#// — is still discarded. Searching is not drawing from an empty deck, so the base takes no damage.

## GIVEN
CommonSetup: yyw/rrk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_155
WithP1Hand: SEC_080

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SEC_080
P1BASEDMG:0
P1NODECISION

---

# DiscardsExactlyOne
#// "A random card" is one card. Hand after the draw: three SEC_080 (two held + the one drawn) — identical, so the
#// random pick is deterministic in effect. Exactly one goes to the discard.

## GIVEN
CommonSetup: yyw/rrk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_155
WithP1Hand: SEC_080
WithP1Hand: SEC_080
WithP1Deck: [SEC_080 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SEC_080

## EXPECT
P1HANDCOUNT:2
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SEC_080
P1DECKCOUNT:1
P1DECKTOPCARD:SOR_046

---

# RequestBoundary_BeforeThePick
#// The first section with a request boundary between the search offer and the pick.

## GIVEN
CommonSetup: yyw/rrk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_155
WithP1Deck: [SOR_046 SOR_095 SOR_128]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:SOR_095

## EXPECT
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_095
P1DECKCOUNT:2
