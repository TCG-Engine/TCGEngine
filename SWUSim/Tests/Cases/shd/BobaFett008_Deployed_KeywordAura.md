# SHD_008 Boba Fett (deployed) — "Each OTHER friendly unit that has 1 or more keywords gets +1/+0."
# Deployed as a unit, Boba buffs the friendly Sentinel SOR_063 (2 power → 3) but not the vanilla SOR_210
# (4 power → 4), and not himself.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:SHD_008;myLeaderDeployed:true}
WithP1GroundArena: SOR_063:1:0
WithP1GroundArena: SOR_210:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:SOR_063
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:1:CARDID:SOR_210
P1GROUNDARENAUNIT:1:POWER:4
