# ASH_046 Scion Shuttle (Space, 1/3, Support) — "While this unit is attacking, the defending unit gets
# -1/-1." Scion attacks SOR_237 (2/3); the defender becomes 1/2, so its counter is 1 (not 2): Scion takes
# only 1 damage (proves the -1 power). SOR_237 takes Scion's 1 and survives.
## GIVEN
CommonSetup: bbk/bbk
WithP1SpaceArena: ASH_046:1:0
WithP2SpaceArena: SOR_237:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:0
## EXPECT
P1SPACEARENAUNIT:0:CARDID:ASH_046
P1SPACEARENAUNIT:0:DAMAGE:1
P2SPACEARENAUNIT:0:CARDID:SOR_237
P2SPACEARENAUNIT:0:DAMAGE:1
