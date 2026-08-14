# WhenPlayed_SearchRebelDraw
#// SOR_096 Mon Mothma (1/3, Ground) — When Played: Search the top 5 of your deck for a REBEL
#// card, reveal it, and draw it (rest to the bottom). The top 5 contain one Rebel (Battlefield
#// Marine SOR_095) among non-Rebel fillers; the player picks it and draws it.

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_096
WithP1Deck: SOR_095
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_095

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:7
P1GROUNDARENACOUNT:1

---

# SearchOffer_OnlyRebelPickable
#// Intended: the top-5 search offers ONLY Rebel cards as picks — the four non-Rebel fillers are
#// shown but not selectable. The search decision is left PENDING (no answer consumes it) so the
#// playable pool is asserted directly: Battlefield Marine (Rebel) is pickable, Cloud City Wing
#// Guard (non-Rebel) is not. The 6th card (SOR_128) is beyond the peek window and must not appear.
#// COVERAGE: offer=this section · reqboundary=WhenPlayed_SearchRebelDraw (play and answer are
#//           separate serialized steps) · control=N/A (deck search, no unit targets, controller-
#//           agnostic) · boundary pair=DeckSmallerThanFive_StillSearches + EmptyDeck_NoSearch
#//           (5+/under-5/zero) and NoRebelInTopFive_AllToBottom (match/no-match) ·
#//           decline=TakeNothing_AllFiveGoToBottom

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_096
WithP1Deck: SOR_095
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SEARCHPLAYABLEHAS:SOR_095
P1SEARCHPLAYABLENOT:SOR_063
P1SEARCHPLAYABLENOT:SOR_128

---

# TakeNothing_AllFiveGoToBottom
#// Intended: the searcher may take nothing; all 5 peeked cards go to the BOTTOM of the deck.
#// The former 6th card (SOR_128, never peeked) becoming the new top card proves the five were
#// bottomed rather than left in place; the deck count is unchanged and nothing was drawn.

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_096
WithP1Deck: SOR_095
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:6
P1DECKTOPCARD:SOR_128
P1GROUNDARENACOUNT:1

---

# DeckSmallerThanFive_StillSearches
#// Intended: with fewer than 5 cards in the deck, the search still works over what is there.
#// A 3-card deck with one Rebel: the Rebel is drawn, the other two go to the bottom, deck ends
#// at 2.

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_096
WithP1Deck: SOR_063
WithP1Deck: SOR_095
WithP1Deck: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_095

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_095
P1DECKCOUNT:2
P1GROUNDARENACOUNT:1

---

# EmptyDeck_NoSearch
#// Intended: with an EMPTY deck the When Played has nothing to peek — Mon Mothma still enters
#// play, no search decision is raised, and the turn passes normally.

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_096

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_096
P1DECKCOUNT:0
P1HANDCOUNT:0
P1NODECISION

---

# NoRebelInTopFive_AllToBottom
#// Intended: when NO card in the top 5 matches the Rebel filter there is nothing to take; all
#// five go to the bottom of the deck and nothing is drawn. With zero legal picks the search
#// resolves without a meaningful choice — the former 6th card on top proves the bottoming.

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_096
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:6
P1DECKTOPCARD:SOR_128
P1GROUNDARENACOUNT:1
P1NODECISION
