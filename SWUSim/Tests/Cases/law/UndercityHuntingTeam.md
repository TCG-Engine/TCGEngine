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
