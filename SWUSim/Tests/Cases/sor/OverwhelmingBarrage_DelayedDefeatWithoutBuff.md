## GIVEN
CommonSetup: ggk/ggk/{myResources:5;handCardIds:SOR_092}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0    # 3/3 dealer → buffed to 5/5
WithP2GroundArena: SOR_046:1:0    # 3/7 — takes 3
WithP2GroundArena: SOR_095:1:0    # 3/3 — takes 2, survives

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0:2,theirGroundArena-1:3
- P1>AttackGroundArena:0:0
- P1>Pass

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
