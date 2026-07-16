# NameMatchesTop_MayDraw
#// TWI_068 Foresight (Upgrade) — grants "When the regroup phase starts (before drawing cards): Name a
#// card, then look at the top card of your deck. If it's the named card, you may reveal and draw it." The
#// deck top is SOR_046 (Consular Security Force); at regroup the controller names it and draws it.
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

---

# NameMatches_DeclineDraw
#// TWI_068 Foresight — the reveal/draw is optional ("you may"). The top IS the named card (SOR_046), but
#// P1 DECLINES, so no extra card is drawn: P1 ends with only the two normal regroup draws (hand 2, deck 4),
#// the same as if the ability had done nothing.
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

---

# NameWrong_NoDraw
#// TWI_068 Foresight — if the top card is NOT the named card, nothing is revealed or drawn. The top is
#// SOR_046 but P1 names a different card, so there is no reveal/draw prompt and the hand stays empty.
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
