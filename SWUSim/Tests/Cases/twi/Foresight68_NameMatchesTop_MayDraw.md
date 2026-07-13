# TWI_068 Foresight (Upgrade) — grants "When the regroup phase starts (before drawing cards): Name a
# card, then look at the top card of your deck. If it's the named card, you may reveal and draw it." The
# deck top is SOR_046 (Consular Security Force); at regroup the controller names it and draws it.
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
- P1>AnswerDecision:YES
## EXPECT
P1HANDCOUNT:3
P1DECKCOUNT:3
