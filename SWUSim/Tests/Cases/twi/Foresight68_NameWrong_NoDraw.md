# TWI_068 Foresight — if the top card is NOT the named card, nothing is revealed or drawn. The top is
# SOR_046 but P1 names a different card, so there is no reveal/draw prompt and the hand stays empty.
## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:TWI_068
P1Deck: [SOR_046 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
P2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>AnswerDecision:Battlefield Marine
## EXPECT
P1HANDCOUNT:2
P1DECKCOUNT:4
