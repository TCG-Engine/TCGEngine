# OnAttackLookTopDiscardOne
#// LAW_237 Qui-Gon Jinn (3/5, Sentinel) — When Played/On Attack: look at the top 3, you may discard 1,
#// put the rest back on top. Attacks the base; discard the top SOR_237.

## GIVEN
CommonSetup: yyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_237:1:0
WithP1Deck: SOR_237
WithP1Deck: SOR_046
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1DECKCOUNT:2
P1DISCARDCOUNT:1

---

# OnAttackDiscardNothing
#// LAW_237 Qui-Gon Jinn — the discard is optional ("you may discard 1"). On Attack, P1 looks at the top
#// 3 and declines to discard: all 3 stay on the deck (put back on top), nothing is milled.

## GIVEN
CommonSetup: yyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_237:1:0
WithP1Deck: SOR_237
WithP1Deck: SOR_046
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P1DECKCOUNT:3
P1DISCARDCOUNT:0

---

# WhenPlayedLookTopDiscardOne
#// LAW_237 Qui-Gon Jinn — the same look-top-3/discard-1 fires When Played, not only On Attack. P1 plays
#// Qui-Gon (cost 4) from hand; the top card SOR_237 is discarded, the rest go back on top.

## GIVEN
CommonSetup: yyk/bgw/{myResources:4}
P1OnlyActions: true
WithP1Hand: LAW_237
WithP1Deck: SOR_237
WithP1Deck: SOR_046
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_237
P1DECKCOUNT:2
P1DISCARDCOUNT:1

---

# FewerThanThreeCards
#// LAW_237 Qui-Gon Jinn — with fewer than 3 cards in deck, only the available cards are looked at. Deck
#// has a single card; On Attack, P1 discards it → deck empty, one card in discard.

## GIVEN
CommonSetup: yyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_237:1:0
WithP1Deck: SOR_237

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1DECKCOUNT:0
P1DISCARDCOUNT:1

---

# EmptyDeckNoEffect
#// LAW_237 Qui-Gon Jinn — with an empty deck the look-at ability has nothing to reveal, so it resolves
#// with no effect and no decision. On Attack, deck stays empty and nothing is discarded.

## GIVEN
CommonSetup: yyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_237:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1DECKCOUNT:0
P1DISCARDCOUNT:0
P1NODECISION

---

# LookNotDoubledByDeckSearchDoubler
#// LAW_237 Qui-Gon Jinn — "look at the top 3" is a LOOK-AT, not a deck SEARCH, so ASH_084 Arcana Star Map
#// ("if you would search a number of cards from your deck, search twice that many instead") attached to
#// Qui-Gon must NOT double it. With a 6-card deck the discard offer is exactly the top 3 cards, never 6.
#// Decision left PENDING to assert the offer.
#// COVERAGE: offer=LookNotDoubledByDeckSearchDoubler (pending SELECTABLEEXACT over the top-3 pool) ·
#//           reqboundary=N/A (single-request resolution; the look prompt and put-back run inside one
#//           request, with no post-decision state read across a boundary) · control=N/A (the look targets
#//           the controller's own deck; no unit changes hands mid-effect) · boundary=FewerThanThreeCards +
#//           EmptyDeckNoEffect (short-deck and zero-card edges) · decline=OnAttackDiscardNothing ("you may
#//           discard 1" declined; all 3 stay on top)

## GIVEN
CommonSetup: yyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_237:1:0
WithP1GroundArenaUpgrade: 0:ASH_084
WithP1Deck: [SOR_237 SOR_046 SOR_095 SOR_128 SOR_164 SOR_225]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myTempZone-0&myTempZone-1&myTempZone-2

---

# LookPromptOffersTheCARDS_NotTheDeckPile
#// LAW_237 Qui-Gon Jinn — ⚠ THE PROMPT-RENDER CELL (live bug report #962: "prompt shows no cards, only
#// the number 42"). "Look at the top 3 cards" must offer the CARDS THEMSELVES. Offering the deck's own
#// mzIDs (myDeck-N) instead makes the client render the `Deck` zone, which is declared
#// `Display: Mode=Single(Stacked), BindTo=DeckSlot` — one stacked pile whose only visible content is its
#// COUNT, so the player saw their remaining deck size and no cards at all.
#// The peeked cards are therefore staged into TempZone (`Display: Mode=None`), which is exactly what that
#// zone exists for: it routes an MZCHOOSE spec to the card-image popup rather than a board slot.
#// Asserting the POOL is the only way to catch this — the harness has no client, so every myDeck-N answer
#// resolves happily and the whole file stayed green over a bug that made the card unplayable in the UI.

## GIVEN
CommonSetup: yyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_237:1:0
WithP1Deck: [SOR_237 SOR_046 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myTempZone-0&myTempZone-1&myTempZone-2
