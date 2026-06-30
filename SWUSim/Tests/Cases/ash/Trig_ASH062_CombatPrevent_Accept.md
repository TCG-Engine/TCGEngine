# ASH_062 The Mandalorian (Ground, 5/4, Shielded) — "If damage would be dealt to another friendly unit,
# you may defeat a Shield token on this unit. If you do, prevent that damage." P1's SOR_046 attacks P2's
# SOR_095; P2 defeats The Mandalorian's Shield to prevent the 3 combat damage, so SOR_095 takes 0 and the
# Shield is gone (SOR_095 still counters 3 onto SOR_046).
## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: ASH_062:1:0
WithP2GroundArenaUpgrade: 1:SOR_T02
## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P2>AnswerDecision:YES
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:1:CARDID:ASH_062
P2GROUNDARENAUNIT:1:SHIELDCOUNT:0
P2GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:DAMAGE:3
