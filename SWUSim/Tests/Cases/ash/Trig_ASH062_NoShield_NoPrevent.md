# ASH_062 The Mandalorian with NO Shield token cannot prevent anything — no offer is made and the combat
# damage lands. P1's SOR_046 attacks P2's SOR_095 (3/3); SOR_095 is defeated normally and The Mandalorian
# (which has no Shield to spend) survives untouched.
## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: ASH_062:1:0
## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:ASH_062
P2GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:3
