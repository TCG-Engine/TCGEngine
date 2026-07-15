# TS26_046 Secret Marriage — shielding only a friendly unit (no enemy) does NOT draw a card.
## GIVEN
CommonSetup: bbw/rrk/{myResources:2;handCardIds:TS26_046}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Deck: [SOR_046 SOR_095]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1HANDCOUNT:0
