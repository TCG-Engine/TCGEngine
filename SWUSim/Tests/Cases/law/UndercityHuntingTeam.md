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
