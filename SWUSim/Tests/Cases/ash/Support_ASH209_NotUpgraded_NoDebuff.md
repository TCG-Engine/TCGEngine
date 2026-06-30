# ASH_209 Ezra Bridger — the -3/-0 On Attack fires ONLY while Ezra is upgraded. With no upgrade, Ezra
# attacks the enemy base and the enemy SEC_080 keeps its full 3 power (no decision offered).
## GIVEN
CommonSetup: bbw/bbk
WithP1GroundArena: ASH_209:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:POWER:3
