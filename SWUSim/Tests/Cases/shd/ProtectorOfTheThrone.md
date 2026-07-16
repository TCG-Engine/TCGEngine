# SentinelWhileUpgraded
#// SHD_247 Protector of the Throne (3-cost, Heroism) — "While this unit is upgraded, it gains Sentinel."
#// Guard: with an upgrade it has Sentinel; without one it does not.

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SHD_247:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP1GroundArena: SHD_247:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:1:NOTKEYWORD:Sentinel
