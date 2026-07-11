# SHD_193 Frozen in Carbonite — "Attached unit can't ready." An exhausted host wearing SHD_193 does NOT
# ready at the regroup ready step, while an identical exhausted unit without the upgrade does.

## GIVEN
CommonSetup: yyk/yyk/{}
P1OnlyActions: true
WithP1GroundArena: SEC_080:0:0
WithP1GroundArenaUpgrade: 0:SHD_193
WithP1GroundArena: SEC_080:0:0

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:READY
