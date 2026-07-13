# TWI_068 Foresight — the reveal/draw is optional ("you may"). The top IS the named card (SOR_046), but
# P1 DECLINES, so no extra card is drawn: P1 ends with only the two normal regroup draws (hand 2, deck 4),
# the same as if the ability had done nothing.
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
- P1>AnswerDecision:Consular Security Force
- P1>AnswerDecision:NO
## EXPECT
P1HANDCOUNT:2
P1DECKCOUNT:4
