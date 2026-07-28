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
