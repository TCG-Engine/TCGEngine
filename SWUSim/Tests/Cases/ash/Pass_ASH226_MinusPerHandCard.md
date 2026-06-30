# ASH_226 Qi'ra (Ground, 9/7, cost 7) — "This unit gets -1/-0 for each card in your hand." With Qi'ra in
# play and 2 cards in P1's hand, her power is 9 - 2 = 7.
## GIVEN
CommonSetup: yyk/yyk/{handCardIds:SOR_095,SOR_046}
WithP1GroundArena: ASH_226:1:0
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:0:POWER:7
