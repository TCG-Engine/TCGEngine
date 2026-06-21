# LAW_136 Syndicate Spice Runner (Command,Villainy, cost 2) — When Played: search the top 3 cards for an
# Underworld unit, reveal it, and draw it. LAW_124 (Underworld) is the only match; SOR_237 is left.

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
