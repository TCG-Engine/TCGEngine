# WhenPlayed_SearchTop8ForAnUpgrade_DrawIt
#// HMW_085 Remote Scout (1/3, Vigilance, cost 2) — "When Played: Search the top 8 cards of your deck for an
#// upgrade, reveal it, and draw it. (Put the other cards on the bottom in a random order.)" One upgrade
#// (SOR_120 Academy Training) sits among unit fillers in the top 8; it is drawn (hand +1, deck -1).
#// COVERAGE: control=N/A — STRUCTURAL: the only ability is a WHEN PLAYED, resolved during the play by
#//           the player who played it, and "your deck" is that same player's. There is no later re-read
#//           for a control change to re-point; a stolen Remote Scout has no ability left to resolve.

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: HMW_085
WithP1Deck: [SOR_120 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_120

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_085
P1HANDCOUNT:1
P1DECKCOUNT:9

---

# WhenPlayed_NoUpgradeInTop8_DrawsNothingCardsReturn
#// No upgrade among the top 8 (all unit fillers). The search still presents (the player looks at the top 8),
#// but nothing is drawable — choosing none (empty AnswerDecision) draws nothing and returns all peeked cards
#// to the bottom, so the deck count is unchanged and no card is milled.

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: HMW_085
WithP1Deck: [SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_085
P1HANDCOUNT:0
P1DECKCOUNT:10

---

# RequestBoundary_TheSearchSurvivesTheChoice
#// The request-boundary cell, and the highest-risk one on this card: a top-8 search PEEKS a set of cards
#// and then asks which to take. Anything the peek holds in memory is empty in the next request — the
#// engine-wide version of that bug once DESTROYED every peeked card in production — so the peeked set
#// has to live in the gamestate, not in a global.
#// Same board as WhenPlayed_SearchTop8ForAnUpgrade_DrawIt, with a boundary between the search and the
#// pick: the upgrade still reaches hand and the rest of the deck is intact.
## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_085
WithP1Deck: [SOR_095 SOR_095 SOR_120 SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:SOR_120
## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:4
P1GROUNDARENAUNIT:0:CARDID:HMW_085

---

# ShortDeck_FewerThanEightCards_StillSearchesWhatIsThere
#// "Search the top 8" on a deck of fewer than 8. A search that indexed a fixed window rather than
#// clamping to the deck size fails here, and every other section on this card uses a deck big enough to
#// hide it.
#// Three cards in the deck, one of them the upgrade: it is still found and drawn, and the other two go
#// back.
## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_085
WithP1Deck: [SOR_095 SOR_120 SOR_095]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_120
## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:2
