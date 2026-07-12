# TWI_222 Political Pressure — the opponent ACCEPTS (AnswerDecision:YES) and discards a random card
# from their (1-card) hand → no Battle Droids are created. With exactly 1 card in hand the "random"
# discard is deterministic.

## GIVEN
CommonSetup: yyk/grw/{myResources:1;handCardIds:TWI_222;theirhandCardIds:SOR_095}
WithActivePlayer: 1

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:0
P2HANDCOUNT:0
P2DISCARDCOUNT:1
