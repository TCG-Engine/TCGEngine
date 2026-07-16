# SentinelWhileExhaustedAlly
#// ASH_120 Warrior of Clan Kryze (Ground, 2/3) — While you control another exhausted unit, this unit
#// gains Sentinel. With an exhausted friendly SOR_095 present, Kryze has Sentinel.
## GIVEN
CommonSetup: ggk/ggk
WithP1GroundArena: ASH_120:1:0
WithP1GroundArena: SOR_095:0:0
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_120
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
