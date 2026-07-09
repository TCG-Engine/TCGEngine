# SHD_001 Gar Saxon (front passive) — "Each friendly upgraded unit gets +1/+0." An upgraded SOR_046
# (3 base + SHD_072 which is +0/+0) gets +1 → power 4; an identical non-upgraded SOR_046 stays at 3.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:SHD_001}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SHD_072
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:1:POWER:3
