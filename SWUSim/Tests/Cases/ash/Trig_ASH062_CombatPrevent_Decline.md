# ASH_062 The Mandalorian — the prevention is a "may": P2 declines (AnswerDecision:-), so the Shield is
# kept and the 3 combat damage lands normally on SOR_095 (3/3 → defeated). The Mandalorian survives with
# its Shield intact.
## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: ASH_062:1:0
WithP2GroundArenaUpgrade: 1:SOR_T02
## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P2>AnswerDecision:-
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:ASH_062
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
P2GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:3
