# Bounty_Ready2Resources
#// SHD_221 Wanted — attached unit gains "Bounty — Ready 2 friendly resources." P2's marine wears it;
#// LAW_124 defeats it; P1 collects: exactly 2 of P1's 3 exhausted resources ready.

## GIVEN
CommonSetup: grw/grw
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SHD_221
WithP1Resources: 3:SOR_046:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1RESCOUNT:3
P1RESAVAILABLE:2
