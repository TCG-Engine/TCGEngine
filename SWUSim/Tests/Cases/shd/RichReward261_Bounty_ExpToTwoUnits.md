# SHD_261 Rich Reward — attached unit gains "Bounty — Give an Experience token to each of up to 2
# units." P2's marine wears it; LAW_124 defeats it; P1 collects and picks both surviving P1 units:
# LAW_124 4/7 → 5/8, Consular Security Force 3/7 → 4/8.

## GIVEN
CommonSetup: grw/grw
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SHD_261

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:POWER:4
