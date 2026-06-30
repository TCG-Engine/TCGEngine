# SEC_090 Director Krennic — the return is "you may". P2 declines (AnswerDecision:NO), so the milled
#   unit (SOR_095) stays in P2's discard. P2 deck 3→2, discard 0→1, hand unchanged. Proves the optional
#   return decline path no-ops cleanly.

## GIVEN
CommonSetup: ggw/grk/{theirHandCardIds:SOR_225}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_090:1:0
WithP2Deck: SOR_095
WithP2Deck: SOR_046
WithP2Deck: SOR_046

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P2>AnswerDecision:NO

## EXPECT
P2DECKCOUNT:2
P2HANDCOUNT:1
P2DISCARDCOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:3
