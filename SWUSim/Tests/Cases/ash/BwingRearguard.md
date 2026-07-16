# SentinelWhileGroundUnit
#// ASH_078 B-Wing Rearguard (Space, 3/5) — While you control a ground unit, this unit gains Sentinel. With
#// a friendly ground unit (SOR_095) present, B-Wing has Sentinel.
## GIVEN
CommonSetup: bbw/bbk
WithP1SpaceArena: ASH_078:1:0
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1SPACEARENAUNIT:0:CARDID:ASH_078
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel
