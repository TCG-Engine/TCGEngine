# SHD_056 Follower of The Way (2-cost 1/3 ground) — "While this unit is upgraded, it gets +1/+1." The
# upgraded copy (base 1/3 + SOR_120 +2/+2 + self +1/+1) is 4/6; the un-upgraded copy stays 1/3.

## GIVEN
CommonSetup: bbk/bbk
P1OnlyActions: true
WithP1GroundArena: SHD_056:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP1GroundArena: SHD_056:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:6
P1GROUNDARENAUNIT:1:POWER:1
P1GROUNDARENAUNIT:1:HP:3
