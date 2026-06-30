# ASH_198 Nowhere to Hide (Upgrade/Condition) — Attached unit gains Sentinel. SOR_095 carrying it has
# Sentinel.
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:ASH_198
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
