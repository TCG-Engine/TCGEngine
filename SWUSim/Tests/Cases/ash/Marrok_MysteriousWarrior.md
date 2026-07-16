# UpgradedLosesSentinelGainsSaboteur
#// ASH_030 Marrok (Ground, 2/6, Sentinel) — While upgraded, he loses Sentinel and gains Saboteur. An
#// unupgraded Marrok has Sentinel (no Saboteur); an upgraded one has Saboteur (no Sentinel).
## GIVEN
CommonSetup: brk/brk
WithP1GroundArena: ASH_030:1:0
WithP1GroundArena: ASH_030:1:0
WithP1GroundArenaUpgrade: 1:SOR_120
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:NOTKEYWORD:Saboteur
P1GROUNDARENAUNIT:1:NOTKEYWORD:Sentinel
P1GROUNDARENAUNIT:1:HASKEYWORD:Saboteur
