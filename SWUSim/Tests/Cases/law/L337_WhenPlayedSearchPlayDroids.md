# LAW_063 L3-37 (3/2, Hidden) — When Played: search the top 10 cards for any number of Droid units with
# combined cost 5 or less and play each for free. Two SEC_080 (Droid, cost 2 each = 4) on top are both
# played; SOR_237 (non-Droid) is left.

## GIVEN
CommonSetup: grw/bgw/{myResources:6}
WithP1Deck: SEC_080
WithP1Deck: SEC_080
WithP1Deck: SOR_237
WithP1Hand: LAW_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SEC_080,SEC_080

## EXPECT
P1GROUNDARENACOUNT:3
P1DECKCOUNT:1
