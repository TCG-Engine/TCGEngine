# LAW_229 The Master Codebreaker (Cunning, cost 2) — When Played: search the top 8 cards for a Gambit
# card, reveal it, and draw it. SOR_223 (Gambit) is the match; SOR_237 is left.

## GIVEN
CommonSetup: yyk/bgw/{myResources:2}
WithP1Deck: SOR_223
WithP1Deck: SOR_237
WithP1Hand: LAW_229

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_223

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:1
